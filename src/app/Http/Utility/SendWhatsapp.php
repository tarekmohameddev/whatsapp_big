<?php
namespace App\Http\Utility;

use App\Enums\CommunicationStatusEnum;
use App\Enums\ServiceType;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;
use App\Models\CampaignContact;
use App\Models\CommunicationLog;
use App\Models\DispatchLog;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Template;
use App\Models\User;
use App\Models\WhatsappCreditLog;
use App\Models\WhatsappDevice;
use App\Service\Admin\Core\CustomerService;
use App\Traits\Dispatchable;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\EvolutionWhatsappTemplate;
use App\Enums\System\EvolutionWhatsappTemplateTypeEnum;

class SendWhatsapp
{
    use Dispatchable;

    /**
     * send
     *
     * @param Gateway $gateway
     * @param array|string $to
     * @param array|Collection|DispatchLog $dispatchLog
     * @param Message $message
     * @param string $body
     * 
     * @return bool
     */
    public function send(Gateway $gateway, array|string $to, array|Collection|DispatchLog $dispatchLog, Message $message, string $body): bool
    {
        $body = textSpinner($body);
        return $this->sendWithHandler($gateway, $to,$dispatchLog, $message, $body);
    }

    /**
     * sendWithHandler
     *
     * @param Gateway $gateway
     * @param array|string $to
     * @param array|Collection|DispatchLog $dispatchLog
     * @param Message $message
     * @param string $body
     * 
     * @return bool
     */
    public function sendWithHandler(Gateway $gateway, array|string $to, array|Collection|DispatchLog $dispatchLog, Message $message, string $body): bool
    {
        try {
            $success = false;
            if($gateway->type == WhatsAppGatewayTypeEnum::NODE->value) {
                
                $success = $this->sendNodeMessages($dispatchLog, $gateway, $message, $body, $to);
            } elseif ($gateway->type == WhatsAppGatewayTypeEnum::CLOUD->value) {
    
                $success = $this->sendCloudApiMessages($dispatchLog, $gateway, $message, $body, $to);
            } elseif ($gateway->type == WhatsAppGatewayTypeEnum::EVOLUTION->value) {

                $success = $this->sendEvolutionMessages($dispatchLog, $gateway, $message, $body, $to);
            }
            if ($success && $dispatchLog) {
                $this->markAsDelivered($dispatchLog);
            }
            return $success;
        } catch (Exception $e) {
            $this->fail($dispatchLog, $e->getMessage());
            return false;
        }
    }

    /**
     * sendNodeMessages
     *
     * @param DispatchLog $log
     * @param Gateway $gateway
     * @param Message $message
     * @param string|array $to
     * 
     * @return bool
     */
    public function sendNodeMessages(DispatchLog $log, Gateway $gateway, Message $message, string $messageData, string|array $to): bool {

        $body = [];
        if(!is_null($message->file_info)) {
            
            $url  = Arr::get($message->file_info, 'url_file', null);
            $type = Arr::get($message->file_info, 'type', null);
            $name = Arr::get($message->file_info, 'name', null);

            if(!filter_var($url, FILTER_VALIDATE_URL)) {

                $url = url($url);
            }
            
            if($type == "image" ) {

                $body = [
                    'image'    => [
                        'url'=>$url
                    ],
                    'mimetype' => 'image/jpeg',
                    'caption'  => $messageData,
                ];
            }

            elseif($type == "audio" ) {

                $body = [
                    'audio' => [
                        'url' => $url
                    ],
                    'caption' => $messageData,
                ];
            }

            elseif($type == "video" ) {

                $body = [
                    'video' => [

                        'url' => $url
                    ],
                    'caption' => $messageData,
                ];
            }

            elseif($type == "document" ) {

                $body = [
                    'document' => [
                        'url' => $url
                    ],
                    'mimetype' => 'application/pdf',
                    'fileName' => $name,
                    'caption'  => $messageData,
                ];
            }
        } else {

            $body['text'] = $messageData;
        }
        
        //send api
        $response = null;
        $apiURL    = env('WP_SERVER_URL').'/message/send?id='.$gateway->name;
        
        $postInput = [
            'receiver' => trim($to),
            'message'  => $body
        ];
        
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $response = Http::withoutVerifying()->withHeaders($headers)->post($apiURL, $postInput);
     
        if ($response && $response->status() === 200) {

            $res = json_decode($response->getBody(), true);
            if (!Arr::has($res, "success")) {
                throw new Exception("Failed To Connect Gateway");
            }
        } else {
            throw new Exception("Failed To Connect Gateway");
        }
        return true;
    }

    /**
     * sendCloudApiMessages
     *
     * @param DispatchLog $log
     * @param Gateway $gateway
     * @param Message $message
     * @param string $body
     * @param string|array $to
     * 
     * @return bool
     */
    public static function sendCloudApiMessages(DispatchLog $log, Gateway $gateway, Message $message, string $body, string|array $to): bool {

        $message                = $message->load(['template']);
        $template               = $message->template;
        $default_crendetials    = (object) config("setting.whatsapp_business_credentials.default");
        $gateway_credentials    = (object) $gateway->meta_data;
        $messageBody            = $message->message ? json_decode($message->message, true) : [];
        $url                    = "https://graph.facebook.com/$default_crendetials->version/$gateway_credentials->phone_number_id/messages";
        
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => "Bearer $gateway_credentials->user_access_token",
            'Cookie'        => 'ps_l=0; ps_n=0',
        ];
        
        if($messageBody == []) {

            $data = [
             
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                "template" => [
                    "name" => $template->name,
                    "language" => [
                        "code" => $template->template_data['language']
                    ],
                    "components" => $messageBody
                ]
            ];

        } else {
            $data = [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                "template" => [
                    "name" => $template->name,
                    "language" => [
                        "code" => $template->template_data['language']
                    ],
                    "components" => $messageBody
                ]
            ];
        }
        
        $response           = Http::withHeaders($headers)->post($url, $data);
        $responseBody       = $response->body();
        $responseData       = json_decode($responseBody, true);
		
        if (!$response->successful()) { 
          	
           if(isset($responseData['error']['message'])) {

             	throw new Exception($responseData['error']['message']);
           }
           else { 
                throw new Exception("Failed To Dispatch via cloud API"); 
           }
            
        } else {
            if(isset($responseData['error']['message'])) {

                throw new Exception($responseData['error']['message']);
            } else {
                throw new Exception($response->body());
            }
        }
        
        // $customerService    = new CustomerService;
        // if ($response->successful()) {

        //     $log->response_message = $responseBody;
        //     $log->status           = CommunicationStatusEnum::PROCESSING->value;
        //     $log->update();

        //     if($log->user_id) {
                        
        //         $user        = User::find($log->user_id);
        //         $customerService->deductCreditLog($user, 1, ServiceType::WHATSAPP->value);
        //     }
        // } else {
        //     if(isset($responseData['error']['message'])) {

        //         self::processFailed($log, $responseData['error']['message']);
        //         \Log::error("WhatsApp dispatch fail: " . $responseData['error']['message']);
        //     } else {
        //         self::processFailed($log, $response->body());
        //         \Log::error("WhatsApp dispatch fail: " . $response->body());
        //     }
        // }
    }

    /**
     * sendEvolutionMessages
     *
     * @param DispatchLog $log
     * @param Gateway $gateway
     * @param Message $message
     * @param string $messageData
     * @param string|array $to
     *
     * @return bool
     */
    public function sendEvolutionMessages(DispatchLog $log, Gateway $gateway, Message $message, string $messageData, string|array $to): bool {

        $server      = Arr::get($gateway->meta_data, 'server');
        $instance    = Arr::get($gateway->meta_data, 'instance');
        $token       = Arr::get($gateway->meta_data, 'token');

        if(!$server || !$instance || !$token) throw new Exception('Missing Evolution API credentials');

        $number = is_array($to) ? (string) Arr::first($to) : (string) $to;

        // Try to read selected Evolution template id from message meta_data
        $templateId = Arr::get($message->meta_data ?? [], 'evolution_template_id');

        // Default behavior: simple text
        $endpointPath = '/message/sendText/';
        $payload = [
            'number' => $number,
            'text'   => $messageData,
        ];

        if ($templateId) {
            $tpl = EvolutionWhatsappTemplate::where('id', $templateId)->first();
            if (!$tpl) {
                throw new Exception('Selected Evolution template not found');
            }

            // Build payload from template and merge the recipient number
            $payload = array_merge((array) $tpl->payload, ['number' => $number]);

            // Choose endpoint path based on template type
            switch ($tpl->type) {
                case EvolutionWhatsappTemplateTypeEnum::SIMPLE_TXT:
                    $endpointPath = '/message/sendText/';
                    break;
                case EvolutionWhatsappTemplateTypeEnum::IMAGE:
                    $endpointPath = '/message/sendMedia/';
                    break;
                case EvolutionWhatsappTemplateTypeEnum::POLL:
                    $endpointPath = '/message/sendPoll/';
                    break;
                case EvolutionWhatsappTemplateTypeEnum::LIST_BUTTONS:
                    $endpointPath = '/message/sendList/';
                    break;
                default:
                    $endpointPath = '/message/sendText/';
                    break;
            }
        }

        $url = rtrim($server, '/').$endpointPath.rawurlencode($instance);

        $headers = [
            'Content-Type' => 'application/json',
            'apikey'       => $token,
        ];

        $response = Http::withoutVerifying()->withHeaders($headers)->post($url, $payload);

        if (!$response) throw new Exception('No response from Evolution API');

        if ($response->successful()) {
            return true;
        }

        $responseData = json_decode($response->body(), true);
        $errorMessage = is_array($responseData) && Arr::get($responseData, 'message')
                        ? Arr::get($responseData, 'message')
                        : $response->body();
        throw new Exception($errorMessage ?: 'Failed To Dispatch via Evolution API');
    }

    /**
     * @param CommunicationLog $log
     * @param $status
     * @param $errorMessage
     * @return void
     */
    public static function addedCreditLog(CommunicationLog $log, $status, $errorMessage = null): void {
        
        $log->status           = $status;
        $log->response_message = !is_null($errorMessage) ? $errorMessage : null;
        $log->save();
        $user = User::find($log->user_id);

        if ($user && $status == CommunicationStatusEnum::FAIL->value) {

            if($log->whatsapp_template_id) {

                CustomerService::addedCreditLog($user, 1, ServiceType::WHATSAPP->value);
            } else {

                $messages    = str_split($log->message["message_body"], site_settings('whatsapp_word_count'));
                $totalcredit = count($messages);
                CustomerService::addedCreditLog($user, $totalcredit, ServiceType::WHATSAPP->value);
            }
        }
    }

    private static function processFailed($whatsapp_log, $message = "Failed To Connect Gateway") {

        $status = (string) CommunicationStatusEnum::FAIL->value;
        if($whatsapp_log->user_id) {

            SendWhatsapp::addedCreditLog($whatsapp_log, $status, $message);
        } else {

            $whatsapp_log->response_message = $message;
            $whatsapp_log->status = $status;
            $whatsapp_log->save();
        }
    }
}
