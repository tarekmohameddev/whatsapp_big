<?php

namespace App\Http\Controllers\User\Communication\Gateway;

use Exception;
use Illuminate\View\View;
use App\Traits\ModelAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Enums\System\ChannelTypeEnum;
use Illuminate\Support\Facades\Session;
use App\Exceptions\ApplicationException;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;
use App\Http\Requests\GatewayRequest;
use App\Services\System\Communication\GatewayService;

class WhatsappEvolutionApiController extends Controller
{
    use ModelAction;
    protected $gatewayService;

    public function __construct()
    {
        $this->gatewayService = new GatewayService();
    }

    public function index(): View
    {
        $user = auth()->user();
        Session::put("menu_active", false);
        return $this->gatewayService->loadLogs(channel: ChannelTypeEnum::WHATSAPP, type: WhatsAppGatewayTypeEnum::EVOLUTION, user: $user);
    }

    public function store(GatewayRequest $request): RedirectResponse
    {
        try {
            $data = $request->all();
            unset($data["_token"]);
            $user = auth()->user();
            return $this->gatewayService->saveGateway(channel: ChannelTypeEnum::WHATSAPP, data: $data, user: $user);
        } catch (ApplicationException $e) {
            $notify[] = ["error", translate($e->getMessage())];
            return back()->withNotify($notify);
        } catch (Exception $e) {
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }

    public function update(GatewayRequest $request, string|int $id): RedirectResponse
    {
        try {
            $data = $request->all();
            unset($data["_token"]);
            $user = auth()->user();
            return $this->gatewayService->saveGateway(channel: ChannelTypeEnum::WHATSAPP, data: $data, id: $id, user: $user);
        } catch (ApplicationException $e) {
            $notify[] = ["error", translate($e->getMessage())];
            return back()->withNotify($notify);
        } catch (Exception $e) {
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }

    public function destroy(string|int|null $id = null): RedirectResponse
    {
        try {
            $user = auth()->user();
            return $this->gatewayService->destroyGateway(channel: ChannelTypeEnum::WHATSAPP, type: null, id: $id, user: $user);
        } catch (ApplicationException $e) {
            $notify[] = ["error", translate($e->getMessage())];
            return back()->withNotify($notify);
        } catch (Exception $e) {
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }
}


