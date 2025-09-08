<?php

namespace App\Http\Controllers\User\Pipeline;

use App\Http\Controllers\Controller;
use App\Models\PipelineIntegration;
use App\Models\PipelineIntegrationRule;
use App\Models\Gateway;
use App\Enums\System\ChannelTypeEnum;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\EvolutionWhatsappTemplate;

class IntegrationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        Session::put('menu_active', true);
        $title = translate('Pipelines & Integrations');
        $integrations = PipelineIntegration::where('user_id', $user->id)->latest()->paginate(15);
        return view('user.pipelines.integrations.index', compact('title', 'integrations'));
    }

    public function create(): View
    {
        $user = auth()->user();
        Session::put('menu_active', true);
        $title = translate('Create Integration');
        $cloudGateways = Gateway::where('channel', ChannelTypeEnum::WHATSAPP->value)
            ->whereIn('type', [WhatsAppGatewayTypeEnum::CLOUD->value, WhatsAppGatewayTypeEnum::EVOLUTION->value])
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->orderBy('name')
            ->get(['id','name','type']);
        $evolutionTemplates = EvolutionWhatsappTemplate::where('user_id', $user->id)
            ->where('status', \App\Enums\Common\Status::ACTIVE->value)
            ->orderBy('name')
            ->get(['id','name']);
        return view('user.pipelines.integrations.create', compact('title', 'cloudGateways', 'evolutionTemplates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'webhook_secret' => 'required|string|max:255',
            'phone_path' => 'required|string|max:255',
            'allowed_methods' => 'nullable|array',
            'allowed_gateways' => 'nullable|array',
            'defaults' => 'nullable|array',
            'rules' => 'nullable|array',
            'rules.*.name' => 'required_with:rules|string|max:255',
            'rules.*.match_path' => 'required_with:rules|string|max:255',
            'rules.*.operator' => 'nullable|string|in:equals,in,not_equals,exists',
            'rules.*.value' => 'nullable|string|max:255',
            'rules.*.action' => 'nullable|array',
            'rules.*.priority' => 'nullable|integer|min:0',
            'rules.*.status' => 'nullable|string|in:active,inactive',
        ]);
        // Normalize defaults gateway/template based on selected method and per-method selects
        $defaults = (array) Arr::get($data, 'defaults', []);
        $defaultMethod = Arr::get($defaults, 'method');
        if ($defaultMethod === 'cloud_api') {
            $defaults['gateway_id'] = Arr::get($defaults, 'cloud_gateway_id');
            $defaults['template_id'] = Arr::get($defaults, 'cloud_template_id');
        } elseif ($defaultMethod === 'evolution_api') {
            $defaults['gateway_id'] = Arr::get($defaults, 'evolution_gateway_id');
            $defaults['template_id'] = Arr::get($defaults, 'evolution_template_id');
        }
        $data['defaults'] = $defaults;

        $integration = PipelineIntegration::create([
            'user_id' => $user->id,
            'name' => Arr::get($data, 'name'),
            'webhook_secret' => Arr::get($data, 'webhook_secret'),
            'phone_path' => Arr::get($data, 'phone_path'),
            'allowed_methods' => Arr::get($data, 'allowed_methods'),
            'allowed_gateways' => Arr::get($data, 'allowed_gateways'),
            'defaults' => Arr::get($data, 'defaults'),
        ]);

        foreach ((array) Arr::get($data, 'rules', []) as $rule) {
            // Normalize action gateway_ids/template_id from per-method selects
            $action = (array) Arr::get($rule, 'action', []);
            $method = Arr::get($action, 'method');
            if ($method === 'cloud_api') {
                $action['gateway_ids'] = array_values(array_filter((array) Arr::get($action, 'cloud_gateway_ids', [])));
                $action['template_id'] = Arr::get($action, 'cloud_template_id');
            } elseif ($method === 'evolution_api') {
                $action['gateway_ids'] = array_values(array_filter((array) Arr::get($action, 'evolution_gateway_ids', [])));
                $action['template_id'] = Arr::get($action, 'evolution_template_id');
            } else {
                // Method not set: still accept generic gateway_ids / CSV
                if (!isset($action['gateway_ids']) && isset($action['gateway_ids_str'])) {
                    $action['gateway_ids'] = array_filter(array_map('trim', explode(',', (string) $action['gateway_ids_str'])));
                }
            }
            PipelineIntegrationRule::create([
                'integration_id' => $integration->id,
                'name' => Arr::get($rule, 'name'),
                'match_path' => Arr::get($rule, 'match_path'),
                'operator' => Arr::get($rule, 'operator', 'equals'),
                'value' => Arr::get($rule, 'value'),
                'action' => $action,
                'priority' => Arr::get($rule, 'priority', 100),
                'status' => Arr::get($rule, 'status', 'active'),
            ]);
        }

        $notify[] = ['success', translate('Integration created successfully')];
        return redirect()->route('user.pipelines.integrations.index')->withNotify($notify);
    }

    public function edit(string $uid): View
    {
        $user = auth()->user();
        $integration = PipelineIntegration::where('uid', $uid)->where('user_id', $user->id)->firstOrFail();
        $title = translate('Edit Integration');
        $cloudGateways = Gateway::where('channel', ChannelTypeEnum::WHATSAPP->value)
            ->whereIn('type', [WhatsAppGatewayTypeEnum::CLOUD->value, WhatsAppGatewayTypeEnum::EVOLUTION->value])
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->orderBy('name')
            ->get(['id','name','type']);
        $rules = $integration->rules()->orderBy('priority')->get();
        $evolutionTemplates = EvolutionWhatsappTemplate::where('user_id', $user->id)
            ->where('status', \App\Enums\Common\Status::ACTIVE->value)
            ->orderBy('name')
            ->get(['id','name']);
        return view('user.pipelines.integrations.edit', compact('title', 'integration', 'cloudGateways', 'rules', 'evolutionTemplates'));
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $user = auth()->user();
        $integration = PipelineIntegration::where('uid', $uid)->where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'webhook_secret' => 'required|string|max:255',
            'phone_path' => 'required|string|max:255',
            'allowed_methods' => 'nullable|array',
            'allowed_gateways' => 'nullable|array',
            'defaults' => 'nullable|array',
            'rules' => 'nullable|array',
            'rules.*.id' => 'nullable|integer',
            'rules.*.name' => 'required_with:rules|string|max:255',
            'rules.*.match_path' => 'required_with:rules|string|max:255',
            'rules.*.operator' => 'nullable|string|in:equals,in,not_equals,exists',
            'rules.*.value' => 'nullable|string|max:255',
            'rules.*.action' => 'nullable|array',
            'rules.*.priority' => 'nullable|integer|min:0',
            'rules.*.status' => 'nullable|string|in:active,inactive',
        ]);
        // Normalize defaults like in store
        $defaults = (array) Arr::get($data, 'defaults', []);
        $defaultMethod = Arr::get($defaults, 'method');
        if ($defaultMethod === 'cloud_api') {
            $defaults['gateway_id'] = Arr::get($defaults, 'cloud_gateway_id');
            $defaults['template_id'] = Arr::get($defaults, 'cloud_template_id');
        } elseif ($defaultMethod === 'evolution_api') {
            $defaults['gateway_id'] = Arr::get($defaults, 'evolution_gateway_id');
            $defaults['template_id'] = Arr::get($defaults, 'evolution_template_id');
        }

        $integration->update([
            'name' => Arr::get($data, 'name'),
            'webhook_secret' => Arr::get($data, 'webhook_secret'),
            'phone_path' => Arr::get($data, 'phone_path'),
            'allowed_methods' => Arr::get($data, 'allowed_methods'),
            'allowed_gateways' => Arr::get($data, 'allowed_gateways'),
            'defaults' => $defaults,
        ]);

        $incomingRules = collect((array) Arr::get($data, 'rules', []));
        $keepIds = $incomingRules->pluck('id')->filter()->values()->all();
        $integration->rules()->whereNotIn('id', $keepIds ?: [0])->delete();
        foreach ($incomingRules as $rule) {
            $action = (array) Arr::get($rule, 'action', []);
            $method = Arr::get($action, 'method');
            if ($method === 'cloud_api') {
                $action['gateway_ids'] = array_values(array_filter((array) Arr::get($action, 'cloud_gateway_ids', [])));
                $action['template_id'] = Arr::get($action, 'cloud_template_id');
            } elseif ($method === 'evolution_api') {
                $action['gateway_ids'] = array_values(array_filter((array) Arr::get($action, 'evolution_gateway_ids', [])));
                $action['template_id'] = Arr::get($action, 'evolution_template_id');
            } else {
                // Method not set: still accept generic gateway_ids / CSV
                if (!isset($action['gateway_ids']) && isset($action['gateway_ids_str'])) {
                    $action['gateway_ids'] = array_filter(array_map('trim', explode(',', (string) $action['gateway_ids_str'])));
                }
            }
            $integration->rules()->updateOrCreate(
                ['id' => Arr::get($rule, 'id')],
                [
                    'name' => Arr::get($rule, 'name'),
                    'match_path' => Arr::get($rule, 'match_path'),
                    'operator' => Arr::get($rule, 'operator', 'equals'),
                    'value' => Arr::get($rule, 'value'),
                    'action' => $action,
                    'priority' => Arr::get($rule, 'priority', 100),
                    'status' => Arr::get($rule, 'status', 'active'),
                ]
            );
        }

        $notify[] = ['success', translate('Integration updated successfully')];
        return redirect()->route('user.pipelines.integrations.index')->withNotify($notify);
    }

    public function destroy(string $uid): RedirectResponse
    {
        $user = auth()->user();
        $integration = PipelineIntegration::where('uid', $uid)->where('user_id', $user->id)->firstOrFail();
        $integration->delete();
        $notify[] = ['success', translate('Integration deleted successfully')];
        return back()->withNotify($notify);
    }
}


