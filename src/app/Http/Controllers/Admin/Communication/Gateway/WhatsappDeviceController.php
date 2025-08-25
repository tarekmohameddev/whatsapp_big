<?php

namespace App\Http\Controllers\Admin\Communication\Gateway;

use Exception;
use Illuminate\View\View;
use App\Traits\ModelAction;
use Illuminate\Support\Arr;
use App\Enums\Common\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Enums\System\ChannelTypeEnum;
use App\Http\Requests\GatewayRequest;
use Illuminate\Support\Facades\Session;
use App\Exceptions\ApplicationException;
use App\Http\Requests\WhatsappServerRequest;
use App\Services\System\Communication\NodeService;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;
use App\Services\System\Communication\GatewayService;

class WhatsappDeviceController extends Controller
{
    use ModelAction;
    
    protected $nodeService;
    protected $gatewayService;

    public function __construct()
    {
        $this->nodeService = new NodeService();
        $this->gatewayService = new GatewayService();
    }

    /**
     * index
     *
     * @return View
     */
    public function index(): View
    {
        Session::put("menu_active", true);
        return $this->gatewayService->loadLogs(channel: ChannelTypeEnum::WHATSAPP, type: WhatsAppGatewayTypeEnum::NODE);
    }

    /**
     *
     * @param GatewayRequest $request
     * 
     * @return \Illuminate\Http\RedirectResponse
     * 
     */
    public function store(GatewayRequest $request): RedirectResponse {
        
        try {

            $data = $request->all();
            unset($data["_token"]);
            $data = Arr::set($data, "type", WhatsAppGatewayTypeEnum::NODE);
            $data = Arr::set($data, "status", Status::INACTIVE->value);
            return $this->gatewayService->saveGateway(ChannelTypeEnum::WHATSAPP, $data);

        } catch (ApplicationException $e) {
            
            $notify[] = ["error", translate($e->getMessage())];
            return back()->withNotify($notify);

        } catch (Exception $e) {
            
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }

    /**
     * update
     *
     * @param GatewayRequest $request
     * @param string|int $id
     * 
     * @return RedirectResponse
     */
    public function update(GatewayRequest $request, string|int $id): RedirectResponse {
        
        try {

            $data = $request->all();
            unset($data["_token"]);
            $data = Arr::set($data, "type", WhatsAppGatewayTypeEnum::NODE);
            return $this->gatewayService->saveGateway(ChannelTypeEnum::WHATSAPP, $data, $id);

        } catch (ApplicationException $e) {
            
            $notify[] = ["error", translate($e->getMessage())];
            return back()->withNotify($notify);

        } catch (Exception $e) {
            
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }

    /**
     * statusUpdate
     *
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function statusUpdate(Request $request): JsonResponse {

        try {
            $message = $this->gatewayService->whatsappDeviceStatusUpdate($request);
            return response()->json([
               'success' => $message
            ]); 

        } catch (ApplicationException $e) {
            
            return response()->json([
                'success' =>  translate($e->getMessage()),
            ], $e->getStatusCode()); 

        } catch (Exception $e) {
            
            return response()->json([
                'success' => getEnvironmentMessage($e->getMessage()),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }
    }

    /**
     * whatsappQRGenerate
     *
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function whatsappQRGenerate(Request $request): JsonResponse {
        
        try {

            return $this->nodeService->generateQr($request);

        } catch (ApplicationException $e) {

            $data['status']  = $e->getStatusCode();
            $data['message'] = $e->getMessage();

            $response = [
                'response'  => $e->getMessage(),
                'data'      => $data
            ];
            return response()->json($response);
            // $notify[] = ["error", translate($e->getMessage())];
            // return back()->withNotify($notify);

        } catch (Exception $e) {

            $data['status']  = $e->getCode();
            $data['message'] = $e->getMessage();

            $response = [
                'response'  => $e->getMessage(),
                'data'      => $data
            ];
            return response()->json($response);
            
            // $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            // return back()->withNotify($notify);
        }
    }

    /**
     * getDeviceStatus
     *
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function getDeviceStatus(Request $request): JsonResponse {

        try {

            return $this->nodeService->confirmDeviceConnection($request);

        } catch (ApplicationException $e) {
            
            $data = [
                'status'    => $e->getCode(),
                'qr'        => "",
                'message'   => $e->getMessage(),
            ];
            return response()->json($data); 

        } catch (Exception $e) {

            $data = [
                'status'    => $e->getCode(),
                'qr'        => "",
                'message'   => $e->getMessage(),
            ];
            return response()->json($data); 
        }
    }

    /**
     * updateServer
     *
     * @param WhatsappServerRequest $request
     * 
     * @return RedirectResponse
     */
    public function updateServer(WhatsappServerRequest $request): RedirectResponse {
        
        try {

            $data = $request->all();
            unset($data["_token"]);
            return $this->nodeService->updateNodeServer($data);

        } catch (Exception $e) {
            
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }

    /**
     * destroy
     *
     * @param string|int|null|null $id
     * 
     * @return RedirectResponse
     */
    public function destroy(string|int|null $id = null): RedirectResponse
    {
        try {
            return $this->gatewayService->destroyGateway(channel: ChannelTypeEnum::WHATSAPP, type: null, id: $id);

        } catch (ApplicationException $e) {
            
            $notify[] = ["error", translate($e->getMessage())];
            return back()->withNotify($notify);

        } catch (Exception $e) {
            
            $notify[] = ["error", getEnvironmentMessage($e->getMessage())];
            return back()->withNotify($notify);
        }
    }
}
