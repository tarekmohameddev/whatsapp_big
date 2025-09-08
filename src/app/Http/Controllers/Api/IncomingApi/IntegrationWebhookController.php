<?php

namespace App\Http\Controllers\Api\IncomingApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
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
            return response()->json(['status' => 'ignored', 'reason' => 'no_rule_and_no_defaults'], 200);
        }
        $method = Arr::get($resolved, 'method');
        $gatewayId = Arr::get($resolved, 'gateway_id');
        $templateId = Arr::get($resolved, 'template_id');

        $user = User::where('id', $integration->user_id)->first();


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
        ]);
        if (in_array($method, ['cloud_api','evolution_api'], true)) {
            $req->merge(['cloud_api' => 'true']);
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
        // Start from defaults
        $defaults = $integration->defaults ?: [];
        $resolved = [
            'method' => Arr::get($defaults, 'method'),
            'gateway_id' => Arr::get($defaults, 'gateway_id'),
            'template_id' => Arr::get($defaults, 'template_id'),
        ];

        $allowedMethods = $integration->allowed_methods ?: null;
        $allowedGateways = $integration->allowed_gateways ?: null;

        // Merge actions of all matching rules (ascending priority). Later matches can set different fields.
        $rules = $integration->rules()->where('status', 'active')->orderBy('priority')->get();
        foreach ($rules as $rule) {
            $value = $this->getValueByPath($payload, $rule->match_path);
            if (!$this->matches($rule->operator, $value, $rule->value)) {
                continue;
            }

            $action = (array) ($rule->action ?: []);
            $method = Arr::get($action, 'method');

            // Accept gateway ids from either array or CSV string
            $gatewayIds = Arr::get($action, 'gateway_ids');
            if (!$gatewayIds && isset($action['gateway_ids_str'])) {
                $gatewayIds = array_filter(array_map('trim', explode(',', (string) $action['gateway_ids_str'])));
            }

            // Merge: last matching rule wins per field
            if ($method) {
                $resolved['method'] = $method;
            }
            if ($gatewayIds && is_array($gatewayIds) && count($gatewayIds) > 0) {
                $resolved['gateway_id'] = Arr::first($gatewayIds);
            }
            if (array_key_exists('template_id', $action) && Arr::get($action, 'template_id')) {
                $resolved['template_id'] = Arr::get($action, 'template_id');
            }
        }

        // Require that a method is finally decided
        if (empty($resolved['method'])) {
            return null;
        }

        // Enforce allowed methods (when configured)
        if (is_array($allowedMethods) && !empty($allowedMethods)) {
            if (!Arr::get($allowedMethods, $resolved['method'])) {
                return null;
            }
        }

        // Enforce allowed gateways (when configured)
        if (is_array($allowedGateways) && !empty($allowedGateways) && !empty($resolved['gateway_id'])) {
            $allowed = array_map('strval', $allowedGateways);
            if (!in_array((string) $resolved['gateway_id'], $allowed, true)) {
                // Fall back to defaults (or leave null to be resolved later by gateway manager)
                $resolved['gateway_id'] = Arr::get($defaults, 'gateway_id');
            }
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


