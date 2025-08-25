<?php

namespace App\Http\Controllers\Api\IncomingApi;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Managers\GatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Enums\System\ChannelTypeEnum;
use App\Managers\CommunicationManager;
use App\Http\Utility\Api\ApiJsonResponse;
use App\Http\Resources\GetWhatsAppLogResource;
use App\Services\System\Contact\ContactService;
use App\Http\Requests\ApiWhatsappDispatchRequest;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;
use App\Services\System\Communication\DispatchService;

class WhatsAppController extends Controller
{
    protected ContactService $contactService;
    protected GatewayManager $gatewayManager;
    protected DispatchService $dispatchService;
    protected CommunicationManager $communicationManager;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->contactService       = new ContactService();
        $this->gatewayManager       = new GatewayManager();
        $this->dispatchService      = new DispatchService();
        $this->communicationManager = new CommunicationManager();
    }

    /**
     * getWhatsAppLog
     *
     * @param int|string|null $id
     * @return JsonResponse
     */
    public function getWhatsAppLog(int|string|null $id = null): JsonResponse
    {
        $user = $this->authenticateUser();
        $whatsappLog = $this->communicationManager->getSpecificDispatchLog($id, $user);

        if (!$whatsappLog) {
            return ApiJsonResponse::notFound(translate("Invalid WHATSAPP Log ID"));
        }

        return ApiJsonResponse::success(
            translate('Successfully fetched WhatsApp from Logs'),
            new GetWhatsAppLogResource($whatsappLog)
        );
    }

    /**
     * store
     *
     * @param ApiWhatsappDispatchRequest $request
     * @return JsonResponse
     */
    public function store(ApiWhatsappDispatchRequest $request): JsonResponse
    {
        $tempFiles = []; // Track temporary files for cleanup

        try {
            DB::beginTransaction();

            $user = $this->authenticateUser();
            $method = WhatsAppGatewayTypeEnum::NODE->value;
            $gatewayId = "-1";
            $contacts = $request->input('contact');

            // Create a new request to hold files
            $modifiedRequest = new Request($request->all());

            // MIME type mapping for common extensions
            $mimeTypes = [
                'webp' => 'image/webp',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'mp3' => 'audio/mpeg',
                'mp4' => 'video/mp4',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'pdf' => 'application/pdf',
            ];

            // Prepare files for the request
            foreach ($contacts as $index => $contact) {
                if (Arr::has($contact, 'media') && Arr::has($contact, 'url')) {
                    $mediaType = Arr::get($contact, 'media');
                    $url = Arr::get($contact, 'url');

                    // Download the file from the URL
                    $response = Http::get($url);
                    if ($response->successful()) {
                        $fileContent = $response->body();
                        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'bin';
                        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $extension;
                        file_put_contents($tempPath, $fileContent);
                        $tempFiles[] = $tempPath; // Track for cleanup

                        // Determine MIME type
                        $mimeType = mime_content_type($tempPath) ?: 'application/octet-stream';
                        if (isset($mimeTypes[$extension]) && $mimeType === 'text/html') {
                            $mimeType = $mimeTypes[$extension]; // Fallback to extension-based MIME
                        }

                        // Create UploadedFile instance
                        $uploadedFile = new UploadedFile(
                            $tempPath,
                            basename($url),
                            $mimeType,
                            null,
                            true
                        );

                        $modifiedRequest->files->set("contact.$index.$mediaType", $uploadedFile);
                        
                    } else {
                        throw new \App\Exceptions\ApplicationException(
                            translate("Failed to download file from URL: {$url}"),
                            400
                        );
                    }
                }
            }
            
            $logs = collect($contacts)
                        ->map(function ($contact, $index) use ($user, $method, $gatewayId, $modifiedRequest) {
                            $messageData = [
                                'message_body' => Arr::get($contact, "message"),
                            ];
                            $metaData = [
                                'sms_type' => Arr::get($contact, 'sms_type'),
                            ];
                    
                            if (Arr::get($contact, 'gateway_identifier')) {
                                $gateway = $this->gatewayManager
                                    ->getSpecificGateway(
                                        channel: ChannelTypeEnum::WHATSAPP,
                                        type: $method,
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
                                'method' => $method,
                                'gateway_id' => Arr::get($contact, 'gateway_identifier', $gatewayId),
                            ]);
                    
                            // Attach file to requestData if present
                            $mediaType = Arr::get($contact, 'media');
                            if ($mediaType) {
                                $fileKey    = "contact.$index.$mediaType";
                                $allFiles   = $modifiedRequest->allFiles();
                                $file       = Arr::get($allFiles, $fileKey);
                                
                                if ($file instanceof UploadedFile) {
                                    
                                    $requestData->files->set($mediaType, $file);
                                }
                            }
                            
                            return $this->dispatchService->storeDispatchLogs(
                                type: ChannelTypeEnum::WHATSAPP,
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
                message: translate('WhatsApp dispatch request created successfully'),
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
        } finally {

            foreach ($tempFiles as $tempPath) {
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        }
    }

    /**
     * sendWithQuery
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            $method = WhatsAppGatewayTypeEnum::NODE->value;
            $gatewayId = "-1";

            $group = $this->contactService->createGroupFromApiContacts(
                type: ChannelTypeEnum::WHATSAPP,
                contacts: array_map(fn($whatsapp) => ['whatsapp' => $whatsapp], $contacts),
                user: $user
            );

            if ($gatewayIdentifier) {
                $gateway = $this->gatewayManager->getSpecificGateway(
                    channel: ChannelTypeEnum::WHATSAPP,
                    type: $method,
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
                type: ChannelTypeEnum::WHATSAPP,
                request: $apiRequest,
                isCampaign: false,
                campaignId: null,
                user: $user,
                isApi: true
            );

            return ApiJsonResponse::success(
                message: translate('WhatsApp dispatch request created successfully'),
                data: $logs
            );
        } catch (\Exception $e) {
            return ApiJsonResponse::validationError(
                $e->getMessage()
            );
        }
    }

    /**
     * authenticateUser
     *
     * @return User|null
     */
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