<?php

namespace App\Http\Controllers\Api\IncomingApi;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\EvolutionWhatsappTemplate;
use App\Models\DispatchLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Enums\System\ChannelTypeEnum;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;
use App\Models\EvolutionButtonClick;
use App\Models\EvolutionHttpActionLog;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $apiKey     = Arr::get($payload, 'apikey') ?? $request->header('apikey') ?? $request->header('ApiKey');
        $serverUrl  = Arr::get($payload, 'server_url');
        $selectedId = Arr::get($payload, 'data.message.listResponseMessage.singleSelectReply.selectedRowId');
        $senderJid  = Arr::get($payload, 'sender')
                    ?? Arr::get($payload, 'data.message.contextInfo.participant')
                    ?? Arr::get($payload, 'data.key.remoteJid');
        // Prefer remoteJid as the actual end-customer contact
        $contactJid = Arr::get($payload, 'data.key.remoteJid')
                    ?? Arr::get($payload, 'data.message.contextInfo.participant')
                    ?? Arr::get($payload, 'sender');

        if (!$apiKey || !$selectedId || !$contactJid) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'missing_required_fields',
            ], 200);
        }

        $sender   = $this->normalizeJidToPhone($senderJid);
        $customer = $this->normalizeJidToPhone($contactJid);

        $gateway = Gateway::query()
            ->where('channel', ChannelTypeEnum::WHATSAPP->value)
            ->where('type', WhatsAppGatewayTypeEnum::EVOLUTION->value)
            ->where('status', 'active')
            ->when($serverUrl, fn($q) => $q->where('meta_data->server', $serverUrl))
            ->where('meta_data->token', $apiKey)
            ->first();

        if (!$gateway || !$gateway->user_id) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'gateway_not_found_or_unassigned',
            ], 200);
        }

        $template = $this->findTemplateByRowId(userId: $gateway->user_id, rowId: $selectedId);

        if (!$template) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'template_or_row_not_found',
            ], 200);
        }

        $actions = $template->row_actions ?? [];
        $action  = Arr::get($actions, $selectedId);

        if (!$action || !Arr::get($action, 'enabled') || !Arr::get($action, 'url')) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'no_action_configured',
            ], 200);
        }

        $method  = strtoupper(Arr::get($action, 'method', 'GET'));
        $url     = Arr::get($action, 'url');
        $headers = Arr::get($action, 'headers') ?: [];
        $body    = Arr::get($action, 'body');

        // Enrich body with context (non-breaking)
        $context = [
            'selectedRowId' => $selectedId,
            'sender'        => $sender,
            'customer'      => $customer,
            'contact'       => $customer,
            'instance'      => Arr::get($payload, 'instance') ?? Arr::get($payload, 'data.instanceId'),
            'gateway_id'    => $gateway->id,
            'user_id'       => $gateway->user_id,
            'raw'           => $payload,
        ];

        // Try to attach original webhook payload saved during dispatch (if any)
        $context['webhook_payload'] = $this->resolveSourceWebhookPayload(
            userId: $gateway->user_id,
            sender: $customer,
            evolutionTemplateId: $template->id
        );

        // Persist button click log regardless of action execution result
        try {
            $rowTitle = null; $rowDescription = null;
            $sections = Arr::get($template->payload ?? [], 'sections', []);
            foreach ($sections as $section) {
                foreach (Arr::get($section, 'rows', []) as $row) {
                    if (Arr::get($row, 'rowId') === $selectedId) {
                        $rowTitle = Arr::get($row, 'title');
                        $rowDescription = Arr::get($row, 'description');
                        break 2;
                    }
                }
            }

            EvolutionButtonClick::create([
                'user_id'        => $gateway->user_id,
                'template_id'    => $template->id,
                'gateway_id'     => $gateway->id,
                'selected_row_id'=> $selectedId,
                'row_title'      => $rowTitle,
                'row_description'=> $rowDescription,
                'sender'         => $sender,
                'customer'       => $customer,
                'raw_payload'    => $payload,
            ]);
        } catch (\Throwable $e) {
            // Swallow logging errors
        }

        try {
            $requestBuilder = Http::withHeaders((array) $headers)->timeout(10);

            $resolvedBody = $this->mergeBody($body, $context);
            $response = match ($method) {
                'POST', 'PUT', 'PATCH' => $requestBuilder->{$method === 'POST' ? 'post' : ($method === 'PUT' ? 'put' : 'patch')}($url, $resolvedBody),
                'DELETE' => $requestBuilder->delete($url, $resolvedBody),
                default   => $requestBuilder->get($url, $resolvedBody),
            };

            // Log HTTP action result
            try {
                EvolutionHttpActionLog::create([
                    'user_id'         => $gateway->user_id,
                    'template_id'     => $template->id,
                    'gateway_id'      => $gateway->id,
                    'selected_row_id' => $selectedId,
                    'method'          => $method,
                    'url'             => $url,
                    'request_headers' => (array) $headers,
                    'request_body'    => is_array($resolvedBody) ? $resolvedBody : null,
                    'response_status' => $response->status(),
                    'response_body'   => Str::limit($response->body(), 10000, '...'),
                    'sender'          => $sender,
                    'customer'        => $customer,
                    'context_meta'    => $context,
                ]);
            } catch (\Throwable $e) {}

            return response()->json([
                'status'        => 'processed',
                'http_status'   => $response->status(),
                'response_body' => $this->truncate($response->body()),
            ], 200);
        } catch (\Throwable $e) {
            // Log HTTP action error
            try {
                EvolutionHttpActionLog::create([
                    'user_id'         => $gateway->user_id,
                    'template_id'     => $template->id,
                    'gateway_id'      => $gateway->id,
                    'selected_row_id' => $selectedId,
                    'method'          => $method,
                    'url'             => $url,
                    'request_headers' => (array) $headers,
                    'request_body'    => is_array($body) ? $body : null,
                    'response_status' => null,
                    'response_body'   => null,
                    'error_message'   => $e->getMessage(),
                    'sender'          => $sender,
                    'customer'        => $customer,
                    'context_meta'    => $context,
                ]);
            } catch (\Throwable $ie) {}
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    protected function mergeBody($body, array $context): array
    {
        $base = is_array($body) ? $body : [];
        // Resolve dynamic placeholders against context; do NOT auto-attach meta
        // so the outbound body is exactly what the user defined (backward safe).
        $resolved = $this->interpolateBody($base, $context);
        return $resolved;
    }

    /**
     * Interpolate placeholders in the action body using context values.
     * Supports:
     *  - String placeholders: "{{ path.to.value }}" (with optional default: "{{ path || default }}")
     *  - Object directive: { "$path": "path.to.value" }
     */
    protected function interpolateBody($node, array $context)
    {
        if (is_array($node)) {
            // $path directive: replace entire node with resolved value
            if (array_key_exists('$path', $node) && is_string($node['$path'])) {
                return $this->getValueByPath($context, $node['$path']);
            }

            $result = [];
            foreach ($node as $key => $value) {
                $result[$key] = $this->interpolateBody($value, $context);
            }
            return $result;
        }

        if (is_string($node)) {
            // Match {{ path }} or {{ path || default }}
            if (preg_match('/^\{\{\s*([^}|]+?)\s*(?:\|\|\s*(.*?)\s*)?\}\}$/', $node, $m)) {
                $path = trim($m[1]);
                $default = array_key_exists(2, $m) ? $m[2] : null;
                $value = $this->getValueByPath($context, $path);
                if ($value === null && $default !== null) {
                    return $default;
                }
                return $value;
            }
        }

        return $node;
    }

    protected function getValueByPath($data, string $path)
    {
        if ($path === '' || $path === '.') {
            return $data;
        }
        $segments = explode('.', $path);
        $cursor = $data;
        foreach ($segments as $seg) {
            $seg = trim($seg);
            if ($seg === '') continue;
            if (is_array($cursor)) {
                // numeric index support
                if (array_key_exists($seg, $cursor)) {
                    $cursor = $cursor[$seg];
                } elseif (ctype_digit($seg)) {
                    $idx = (int) $seg;
                    $cursor = $cursor[$idx] ?? null;
                } else {
                    $cursor = Arr::get($cursor, $seg);
                }
            } elseif (is_object($cursor)) {
                $cursor = $cursor->{$seg} ?? null;
            } else {
                return null;
            }
            if ($cursor === null) {
                return null;
            }
        }
        return $cursor;
    }

    protected function resolveSourceWebhookPayload(int $userId, string $sender, int $evolutionTemplateId): ?array
    {
        try {
            // Attempt 1: match user + sender contact + evolution template id
            $query = DispatchLog::query()
                ->where('user_id', $userId)
                ->where('type', ChannelTypeEnum::WHATSAPP->value);

            $candidate = (clone $query)
                ->whereHas('contact', function ($q) use ($sender) {
                    $q->where('whatsapp_contact', $sender)
                      ->orWhere('whatsapp_contact', 'like', "%$sender%")
                      ->orWhere('whatsapp_contact', 'like', "%+$sender%");
                })
                ->whereHas('message', function ($q) use ($evolutionTemplateId) {
                    $q->where('meta_data->evolution_template_id', $evolutionTemplateId);
                })
                ->latest('id')
                ->first();

            // Attempt 2: any log for user with this template id and non-null webhook_payload
            if (!$candidate) {
                $candidate = (clone $query)
                    ->whereHas('message', function ($q) use ($evolutionTemplateId) {
                        $q->where('meta_data->evolution_template_id', $evolutionTemplateId);
                    })
                    ->whereNotNull('meta_data')
                    ->latest('id')
                    ->get()
                    ->first(function ($log) {
                        $meta = $log->meta_data;
                        if (is_string($meta)) {
                            $meta = json_decode($meta, true);
                        }
                        return Arr::get((array) $meta, 'webhook_payload') !== null;
                    });
            }

            // Attempt 3: fallback latest for user with webhook_payload present
            if (!$candidate) {
                $candidate = (clone $query)
                    ->whereNotNull('meta_data')
                    ->latest('id')
                    ->get()
                    ->first(function ($log) {
                        $meta = $log->meta_data;
                        if (is_string($meta)) {
                            $meta = json_decode($meta, true);
                        }
                        return Arr::get((array) $meta, 'webhook_payload') !== null;
                    });
            }

            $log = $candidate;

            if (!$log) return null;

            $meta = $log->meta_data;
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = is_array($decoded) ? $decoded : [];
            }
            $saved = Arr::get($meta, 'webhook_payload');
            return is_array($saved) ? $saved : (is_string($saved) ? (json_decode($saved, true) ?: null) : null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeJidToPhone(string $jid): string
    {
        $atPos = strpos($jid, '@');
        return $atPos !== false ? substr($jid, 0, $atPos) : $jid;
    }

    protected function findTemplateByRowId(int $userId, string $rowId): ?EvolutionWhatsappTemplate
    {
        $templates = EvolutionWhatsappTemplate::query()
            ->where('user_id', $userId)
            ->where('type', 'list_buttons')
            ->where('status', 'active')
            ->get();

        foreach ($templates as $tpl) {
            $payload = $tpl->payload ?? [];
            $sections = Arr::get($payload, 'sections', []);
            foreach ($sections as $section) {
                $rows = Arr::get($section, 'rows', []);
                foreach ($rows as $row) {
                    if (Arr::get($row, 'rowId') === $rowId) {
                        return $tpl;
                    }
                }
            }
        }
        return null;
    }

    protected function truncate(string $text, int $limit = 2000): string
    {
        return Str::limit($text, $limit, '...');
    }
}


