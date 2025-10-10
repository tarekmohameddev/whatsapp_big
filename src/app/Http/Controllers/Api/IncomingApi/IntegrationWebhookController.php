<?php

namespace App\Http\Controllers\Api\IncomingApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\PipelineIntegration;
use App\Models\PipelineIntegrationRule;
use App\Models\User;
use App\Enums\System\ChannelTypeEnum;
use App\Services\System\Communication\DispatchService;

class IntegrationWebhookController extends Controller
{
    public function __construct(private DispatchService $dispatchService) {}

    public function handle(Request $request, string $uid): JsonResponse
    {
        $integration = PipelineIntegration::where('uid', $uid)->first();
        if (!$integration) {
            return response()->json(['status' => 'error', 'message' => 'Integration not found'], 404);
        }

        $provided = $request->header('X-Webhook-Secret');
        if (!$provided || !hash_equals($integration->webhook_secret, $provided)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $phonePath = $integration->phone_path;
        $recipient = $this->getValueByPath($payload, $phonePath);
        if (!$recipient) {
            return response()->json(['status' => 'ignored', 'reason' => 'phone_not_found'], 200);
        }

        $resolved = $this->resolveAction($integration, $payload);
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'no_rules_matched'], 422);
        }
        $targets = (array) Arr::get($resolved, 'targets', []);
        $variableMappings = (array) Arr::get($resolved, 'variables', []);

        // Build variables from mappings against payload
        $variables = [];
        foreach ($variableMappings as $map) {
            $name = Arr::get($map, 'name');
            $path = Arr::get($map, 'path');
            $static = Arr::get($map, 'value');
            if (!$name) continue;
            if ($static !== null && $static !== '') {
                $variables[$name] = (string) $static;
                continue;
            }
            if ($path) {
                $value = $this->getValueByPath($payload, $path);
                $variables[$name] = is_scalar($value) || is_null($value) ? (string) ($value ?? '') : json_encode($value);
            }
        }

        // Fallback: if no mappings configured, auto-map top-level scalar keys
        if (empty($variables)) {
            foreach ($payload as $k => $v) {
                if (is_scalar($v) || is_null($v)) {
                    $variables[$k] = (string) ($v ?? '');
                }
            }
        }

        $user = User::where('id', $integration->user_id)->first();

        // Choose target via round-robin when targets are present
        $selectedTarget = null;
        $selectedRuleId = Arr::get($resolved, 'selected_rule_id');
        if (!empty($targets)) {
            $countTargets = count($targets);
            // Load and increment rr_index atomically
            $rrIndex = DB::transaction(function () use ($selectedRuleId) {
                $index = 0;
                if ($selectedRuleId) {
                    $rule = PipelineIntegrationRule::where('id', $selectedRuleId)->lockForUpdate()->first();
                    if ($rule) {
                        $index = (int) ($rule->rr_index ?? 0);
                        $rule->rr_index = $index + 1;
                        $rule->save();
                    }
                }
                return $index;
            });
            $pick = $countTargets > 0 ? ($rrIndex % $countTargets) : 0;
            $selectedTarget = $targets[$pick] ?? $targets[0] ?? null;
        }

        // Use selected target only; rules-only design
        $method = $selectedTarget['method'] ?? null;
        $gatewayId = $selectedTarget['gateway_id'] ?? null;
        $templateId = $selectedTarget['template_id'] ?? null;

        $req = new \Illuminate\Http\Request();
        $req->setMethod('POST');
        $req->merge([
            'contacts' => $recipient,
            'method' => $method === 'cloud_api' ? 'cloud_api' : 'evolution_api',
            'gateway_id' => (string) ($gatewayId ?? '-1'),
            'whatsapp_template_id' => $method === 'cloud_api' ? $templateId : null,
            'evolution_template_id' => $method === 'evolution_api' ? $templateId : null,
            'message' => [
                'message_body' => Arr::get($payload, 'message') ?? '',
            ],
            // carry full webhook payload as dispatch meta
            'dispatch_meta' => $payload,
            // carry resolved variables for template substitution
            'variables' => $variables,
        ]);
        if (in_array($method, ['cloud_api','evolution_api'], true)) {
            $req->merge(['cloud_api' => 'true']);
        }

        // For Cloud API templates, map variables to body placeholders in order
        if ($method === 'cloud_api' && !empty($variables)) {
            $i = 1;
            foreach ($variables as $val) {
                $req->merge(["body_placeholder_{$i}" => $val]);
                $i++;
            }
        }

        // Remove any {{var}} from free-text message body using resolved variables
        if (!empty($variables)) {
            $body = (string) Arr::get($req->input('message'), 'message_body', '');
            if ($body) {
                foreach ($variables as $key => $val) {
                    $body = str_replace('{{'.$key.'}}', $val, $body);
                }
                // Remove any leftover placeholders
                $body = preg_replace('/{{\s*[^}]+\s*}}/', '', $body);
                $req->merge(['message' => ['message_body' => $body]]);
            }
        }

        // Bind the synthetic request so internal request() helper uses it
        $originalRequest = app('request');
        app()->instance('request', $req);
        try {
            $logs = $this->dispatchService->storeDispatchLogs(
                type: ChannelTypeEnum::WHATSAPP,
                request: $req,
                isCampaign: false,
                campaignId: null,
                user: $user,
                isApi: true
            );
        } finally {
            app()->instance('request', $originalRequest);
        }

        return response()->json([
            'status' => 'ok',
            'dispatched' => $logs,
        ]);
    }

    private function resolveAction(PipelineIntegration $integration, array $payload): ?array
    {
        // Rules-only: start empty
        $resolved = [
            'targets' => [],
            'variables' => [],
        ];

        // Merge actions of all matching rules (ascending priority). Later matches can set different fields.
        $rules = $integration->rules()->where('status', 'active')->orderBy('priority')->get();
        $selectedRuleId = null;
        foreach ($rules as $rule) {
            $value = $this->getValueByPath($payload, $rule->match_path);
            if (!$this->matches($rule->operator, $value, $rule->value)) {
                continue;
            }

            $action = (array) ($rule->action ?: []);

            // New: multi-targets support overrides legacy fields when present
            $targets = [];
            if (isset($action['targets']) && is_array($action['targets'])) {
                foreach ($action['targets'] as $t) {
                    $tMethod = Arr::get($t, 'method');
                    $tGateway = Arr::get($t, 'gateway_id');
                    $tTemplate = Arr::get($t, 'template_id');
                    if (in_array($tMethod, ['cloud_api','evolution_api'], true) && $tGateway) {
                        $targets[] = [
                            'method' => $tMethod,
                            'gateway_id' => $tGateway,
                            'template_id' => $tTemplate,
                        ];
                    }
                }
            }

            // If multi-targets provided, they become the active rotation set
            if (!empty($targets)) {
                $resolved['targets'] = $targets;
                $selectedRuleId = $rule->id;
            }
            // Merge variables mapping: later rules override by name
            $ruleVars = collect((array) Arr::get($action, 'variables', []))
                ->filter(fn($v) => (string) Arr::get($v, 'name') !== '' && (string) Arr::get($v, 'path') !== '')
                ->keyBy(fn($v) => Arr::get($v, 'name'));
            if ($ruleVars->isNotEmpty()) {
                $existing = collect($resolved['variables'] ?? [])->keyBy(fn($v) => Arr::get($v, 'name'));
                $merged = $existing->merge($ruleVars)->values()->all();
                $resolved['variables'] = $merged;
            }
        }

        // If no targets were resolved by rules, return null
        if (empty($resolved['targets'])) {
            return null;
        }

        if (!empty($resolved['targets'])) {
            $resolved['selected_rule_id'] = $selectedRuleId;
        }
        return $resolved;
    }

    private function matches(string $op = 'equals', $value, $expectation): bool
    {
        return match ($op) {
            'exists' => !is_null($value),
            'in' => in_array((string) $value, array_map('trim', explode(',', (string) $expectation)) ?? [], true),
            'not_equals' => (string) $value !== (string) $expectation,
            default => (string) $value === (string) $expectation,
        };
    }

    private function getValueByPath(array $data, string $path)
    {
        $segments = explode('.', $path);
        $cursor = $data;
        foreach ($segments as $segment) {
            if (is_array($cursor) && array_key_exists($segment, $cursor)) {
                $cursor = $cursor[$segment];
            } else {
                return null;
            }
        }
        return $cursor;
    }
}


