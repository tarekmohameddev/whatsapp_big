<?php

namespace App\Http\Controllers\Api\IncomingApi;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\EvolutionWhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Enums\System\ChannelTypeEnum;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $apiKey     = Arr::get($payload, 'apikey') ?? $request->header('apikey') ?? $request->header('ApiKey');
        $serverUrl  = Arr::get($payload, 'server_url');
        $selectedId = Arr::get($payload, 'data.message.listResponseMessage.singleSelectReply.selectedRowId');
        $senderJid  = Arr::get($payload, 'sender')
                    ?? Arr::get($payload, 'data.key.remoteJid')
                    ?? Arr::get($payload, 'data.message.contextInfo.participant');

        if (!$apiKey || !$selectedId || !$senderJid) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'missing_required_fields',
            ], 200);
        }

        $sender = $this->normalizeJidToPhone($senderJid);

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
            'instance'      => Arr::get($payload, 'instance') ?? Arr::get($payload, 'data.instanceId'),
            'gateway_id'    => $gateway->id,
            'user_id'       => $gateway->user_id,
            'raw'           => $payload,
        ];

        try {
            $requestBuilder = Http::withHeaders((array) $headers)->timeout(10);

            $response = match ($method) {
                'POST', 'PUT', 'PATCH' => $requestBuilder->{$method === 'POST' ? 'post' : ($method === 'PUT' ? 'put' : 'patch')}($url, $this->mergeBody($body, $context)),
                'DELETE' => $requestBuilder->delete($url, $this->mergeBody($body, $context)),
                default   => $requestBuilder->get($url, $this->mergeBody($body, $context)),
            };

            return response()->json([
                'status'        => 'processed',
                'http_status'   => $response->status(),
                'response_body' => $this->truncate($response->body()),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    protected function mergeBody($body, array $context): array
    {
        $base = is_array($body) ? $body : [];
        // Add under meta to avoid clobbering user-provided keys
        return array_merge($base, ['meta' => $context]);
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


