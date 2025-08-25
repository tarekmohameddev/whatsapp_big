<?php

namespace App\Http\Controllers\Api\IncomingApi;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Enums\System\ChannelTypeEnum;
use App\Managers\CommunicationManager;
use App\Http\Resources\GetSmsLogResource;
use App\Http\Utility\Api\ApiJsonResponse;
use App\Http\Requests\ApiSmsDispatchRequest;
use App\Managers\GatewayManager;
use App\Services\System\Contact\ContactService;
use App\Enums\System\Gateway\SmsGatewayTypeEnum;
use App\Services\System\Communication\DispatchService;
use Illuminate\Support\Arr;

class SmsController extends Controller
{
    protected DispatchService $dispatchService;
    protected ContactService $contactService;
    protected GatewayManager $gatewayManager;
    protected CommunicationManager $communicationManager;

    public function __construct()
    {
        $this->dispatchService = new DispatchService();
        $this->contactService = new ContactService();
        $this->gatewayManager = new GatewayManager();
        $this->communicationManager = new CommunicationManager();
    }

    public function getSmsLog(int|string|null $id = null): JsonResponse
    {
        $user = $this->authenticateUser();
        $smsLog = $this->communicationManager->getSpecificDispatchLog($id, $user);

        if (!$smsLog) {
            return ApiJsonResponse::notFound(translate("Invalid SMS Log ID"));
        }

        return ApiJsonResponse::success(
            translate('Successfully fetched Sms from Logs'),
            new GetSmsLogResource($smsLog)
        );
    }

    /**
     * store
     *
     * @param ApiSmsDispatchRequest $request
     * 
     * @return JsonResponse
     */
    public function store(ApiSmsDispatchRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $this->authenticateUser();
            $method = null;
            $gatewayId = null;
            if ($user instanceof Admin) {
                $method = site_settings("api_sms_method", "1") == "1"
                    ? SmsGatewayTypeEnum::ANDROID->value
                    : SmsGatewayTypeEnum::API->value;
                $gatewayId = $method == SmsGatewayTypeEnum::ANDROID->value
                    ? "0"
                    : "-1";
            } else {
                $method = @$user?->api_sms_method == "1"
                    ? SmsGatewayTypeEnum::ANDROID->value
                    : SmsGatewayTypeEnum::API->value;
                $gatewayId = $method == SmsGatewayTypeEnum::ANDROID->value
                    ? "0"
                    : "-1";
            }
            $contacts = $request->input('contact');

            $logs = collect($contacts)
                ->map(function ($contact) use ($user, $method, $gatewayId) {
                    $messageData = [
                        'message_body' => Arr::get($contact, "message"),
                    ];
                    $metaData = [
                        'sms_type' => Arr::get($contact, 'sms_type'),
                    ];
                    
                    if (Arr::get($contact, 'gateway_identifier')) {
                        
                        $gateway = $this->gatewayManager
                            ->getSpecificGateway(
                                channel: ChannelTypeEnum::SMS,
                                type: null,
                                column: "uid",
                                value: Arr::get($contact, 'gateway_identifier'),
                                user: $user
                            );
                        $contact = Arr::set($contact, "gateway_identifier", @$gateway?->id);
                    }
                    
                    
                    $requestData = new Request([
                        'contacts' => $contact['number'],
                        'message' => $messageData,
                        'schedule_at' => Arr::get($contact, 'schedule_at'),
                        'sms_type' => Arr::get($metaData, "sms_type"),
                        'method' => $method,
                        'gateway_id' => Arr::get($contact, 'gateway_identifier', $gatewayId),
                    ]);
                    return $this->dispatchService->storeDispatchLogs(
                        type: ChannelTypeEnum::SMS,
                        request: $requestData,
                        isCampaign: false,
                        campaignId: null,
                        user: $user,
                        isApi: true
                    );
                })->flatten(1)
                ->toArray();

            DB::commit();

            return ApiJsonResponse::success(
                message: translate('Sms dispatch request created successfully'),
                data: $logs
            );
        } catch (\App\Exceptions\ApplicationException $e) {
            DB::rollBack();
            return ApiJsonResponse::error(
                translate($e->getMessage()),
                [],
                $e->getStatusCode()
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiJsonResponse::validationError(
                $e->getMessage()
            );
        }
    }

    public function sendWithQuery(Request $request): JsonResponse
    {
        try {
            $contacts = explode(',', $request->query('contacts', ''));
            $message = $request->query('message', '');
            $scheduleAt = $request->query('schedule_at', '');
            $smsType = $request->query('sms_type', '');
            $gatewayIdentifier = $request->query('gateway_identifier', '');

            if (empty($contacts) || !$message) {
                return ApiJsonResponse::validationError(
                    ['contacts' => 'Contacts, message, and subject are required']
                );
            }

            $user = $this->authenticateUser();
            $method = null;
            $gatewayId = null;
            if ($user instanceof Admin) {
                $method = site_settings("api_sms_method", "1") == "1"
                    ? SmsGatewayTypeEnum::ANDROID->value
                    : SmsGatewayTypeEnum::API->value;
                $gatewayId = $method == SmsGatewayTypeEnum::ANDROID->value
                    ? "0"
                    : "-1";
            } else {
                $method = @$user?->api_sms_method == "1"
                    ? SmsGatewayTypeEnum::ANDROID->value
                    : SmsGatewayTypeEnum::API->value;
                $gatewayId = $method == SmsGatewayTypeEnum::ANDROID->value
                    ? "0"
                    : "-1";
            }

            $group = $this->contactService->createGroupFromApiContacts(
                type: ChannelTypeEnum::SMS,
                contacts: array_map(fn($sms) => ['sms' => $sms], $contacts),
                user: $user
            );

            if ($gatewayIdentifier) {
                $gateway = $this->gatewayManager->getSpecificGateway(
                    channel: ChannelTypeEnum::SMS,
                    type: null,
                    column: "uid",
                    value: $gatewayIdentifier,
                    user: $user
                );
                $gatewayIdentifier = @$gateway?->id;
            }

            $messageData = [
                'message_body' => $message,
            ];
            $metaData = [
                'sms_type' => $smsType,
            ];

            $apiRequest = new Request([
                'contacts' => [$group->id],
                'message' => $messageData,
                'schedule_at' => $scheduleAt,
                'sms_type' => $metaData['sms_type'],
                'method' => $method,
                'gateway_id' => $gatewayIdentifier ?: $gatewayId,
            ]);

            $logs = $this->dispatchService->storeDispatchLogs(
                type: ChannelTypeEnum::SMS,
                request: $apiRequest,
                isCampaign: false,
                campaignId: null,
                user: $user,
                isApi: true
            );

            return ApiJsonResponse::success(
                message: translate('Sms dispatch request created successfully'),
                data: $logs
            );
        } catch (\Exception $e) {
            
            return ApiJsonResponse::validationError(
                $e->getMessage()
            );
        }
    }

    protected function authenticateUser(): ?User
    {
        $apiKey = request()->header('Api-key');

        if (!$apiKey) {
            throw new \App\Exceptions\ApplicationException(
                translate('Invalid API key'),
                401
            );
        }
        $user = User::where('api_key', $apiKey)->first();
        $admin = Admin::where('api_key', $apiKey)->first();

        if (!$user && !$admin) {
            throw new \App\Exceptions\ApplicationException(
                translate('Invalid API key'),
                401
            );
        }

        return $user;
    }
}