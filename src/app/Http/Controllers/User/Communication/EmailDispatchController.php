<?php

namespace App\Http\Controllers\User\Communication;

use Exception;
use Illuminate\View\View;
use App\Traits\ModelAction;
use App\Managers\GatewayManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Enums\System\ChannelTypeEnum;
use Illuminate\Support\Facades\Session;
use App\Exceptions\ApplicationException;
use App\Http\Requests\EmailDispatchRequest;
use App\Services\System\Communication\DispatchService;

class EmailDispatchController extends Controller
{
    use ModelAction;

    public $gatewayManager;
    public $dispatchService;

    /**
     * __construct
     *
     */
    public function __construct() {
        $this->gatewayManager   = new GatewayManager();
        $this->dispatchService  = new DispatchService();
    }

    /**
     * index
     *
     * @return View
     */
    public function index(): View
    {
        $user = auth()->user();
        Session::put("menu_active", true);
        return $this->dispatchService->loadLogs(channel: ChannelTypeEnum::EMAIL, user: $user);
    }

    /**
     * create
     *
     * @return View
     */
    public function create(): View
    {
        $user = auth()->user();
        Session::put("menu_active", true);
        return $this->dispatchService->createDispatchLog(channel: ChannelTypeEnum::EMAIL, user: $user);
    }

    /**
     * show
     *
     * @param mixed $id
     * 
     * @return RedirectResponse
     */
    public function show($id): View
    {
        $user = auth()->user();
        return $this->dispatchService->showDispatchLog(channel: ChannelTypeEnum::EMAIL, id: $id, user: $user);
    }

    /**
     * store
     *
     * @param EmailDispatchRequest $request
     * 
     * @return RedirectResponse
     */
    public function store(EmailDispatchRequest $request): RedirectResponse
    {
        try {
            $user = auth()->user();
            Session::put("menu_active", true);
            return $this->dispatchService->storeDispatchLogs(type: ChannelTypeEnum::EMAIL, request: $request, user: $user);

        } catch (ApplicationException $e) {
            
            $notify[] = ["error", translate($e->getMessage())];
            return back()->withNotify($notify);

        } catch (Exception $e) {
            
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }
}