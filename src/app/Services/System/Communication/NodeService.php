<?php

namespace App\Services\System\Communication;

use App\Models\Gateway;
use Illuminate\Support\Arr;
use App\Enums\Common\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Enums\System\ChannelTypeEnum;
use App\Enums\System\Gateway\WhatsAppGatewayTypeEnum;
use App\Exceptions\ApplicationException;
use App\Http\Requests\WhatsappServerRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;

class NodeService
{ 
     public function sessionStatusUpdate(Gateway $whatsapp, string $value) {

          $status  = false;
          $message = translate("Something went wrong");
          
          switch ($value) {
  
              case 'connected':
  
                  $session = $this->sessionStatus($whatsapp->name);
                  if ($session->status() == 200) {
  
                      $status = true;
                      $message = translate("Successfully whatsapp sessions reconnect");
                  } else {
                      
                      $this->sessionDelete($whatsapp->name);
                      $message = translate("Successfully whatsapp sessions disconnected");
                  }
                  break;
  
              case 'disconnected':
  
                  $session = $this->sessionDelete($whatsapp->name);
                  
                  if ($session->status() == 200) {
  
                      $message = translate('Whatsapp Device successfully Deleted');
                  } else {
  
                      $message = translate('Opps! Something went wrong, try again');
                  }
                  break;
  
              default:
  
                  $session = $this->sessionDelete($whatsapp->name);
                  if ($session->status() == 200) {
  
                      $message = translate('Whatsapp Device successfully Deleted');
                  } else {
  
                      $message = translate('Opps! Something went wrong, try again');
                  }
                  break;
          }
  
          $whatsapp->status = $status ? Status::ACTIVE : Status::INACTIVE;
  
          return [
              $whatsapp,
              $message
          ];
      }

     /**
      * generateQr
      *
      * @param Request $request
      * 
      * @return JsonResponse
      */
     public function generateQr(Request $request, ?User $user = null): JsonResponse {

          $gateway = Gateway::when($user, fn(Builder $q): Builder =>
                                        $q->where("user_id", $user->id), 
                                             fn(Builder $q): Builder =>
                                                  $q->whereNull("user_id"))
                                    ->select(["id", "name", "meta_data"])
                                    ->where("channel", ChannelTypeEnum::WHATSAPP)
                                    ->where("type", WhatsAppGatewayTypeEnum::NODE)
                                    ->where('id', $request->input('id'))
                                    ->first();
          if(!$gateway) throw new ApplicationException("Invalid whatsapp device", HttpResponse::HTTP_NOT_FOUND);

          list($response, $responseBody) = $this->sessionCreate($gateway);
          
          $data = [];
          if ($response->status() === 200) {

               $data['status']  = $response->status();
               $data['qr']      = $responseBody->data->qr;
               $data['message'] = $responseBody->message;
   
          } else {
               
               $msg = $response->status() === 500 ? "Invalid Software License" : $responseBody->message;
               $data['status']  = $response->status();
               $data['qr']      = '';
               $data['message'] = $msg;
          }

          $response = [
               'response' => $gateway,
               'data' => $data
          ];
          return response()->json($response);
     }

     /**
      * confirmDeviceConnection
      *
      * @param Request $request
      * @param User|null $user
      * 
      * @return JsonResponse
      */
     public function confirmDeviceConnection(Request $request, ?User $user = null): JsonResponse {

          $gateway = Gateway::when($user, fn(Builder $q): Builder =>
                                   $q->where("user_id", $user->id), 
                                        fn(Builder $q): Builder =>
                                             $q->whereNull("user_id"))
                              ->select(["id", "name", "meta_data", "status"])
                              ->where("channel", ChannelTypeEnum::WHATSAPP)
                              ->where("type", WhatsAppGatewayTypeEnum::NODE)
                              ->where('id', $request->input('id'))
                              ->first();
          if(!$gateway) throw new ApplicationException("Invalid whatsapp device", HttpResponse::HTTP_NOT_FOUND);

          $metaData = $gateway->meta_data;
          $data = [];

          $checkConnection = $this->sessionStatus($gateway->name);

          if ($gateway->status == Status::ACTIVE || $checkConnection->status() === 200) { 

               $gateway->status = Status::ACTIVE;
               $response = json_decode($checkConnection->body());

               if (isset($response->data->wpInfo)) {

                    $wpNumber = str_replace('@s.whatsapp.net', '', $response->data->wpInfo->id);
                    $wpNumber = explode(':', $wpNumber);
                    $wpNumber = Arr::get($wpNumber, 0, Arr::get($metaData, "number", ""));
                    $metaData = Arr::set($metaData, "number", $wpNumber);
                    $gateway->meta_data = $metaData;

               }
                
               $gateway->save();
               $data['status']  = 301;
               $data['qr']      = asset('assets/file/dashboard/image/done.gif');
               $data['message'] = 'Successfully connected WhatsApp device';
          }

          $response = [
               'response' => $gateway,
               'data' => $data
          ];

          return response()->json($response);
     }

     /**
      * updateNodeServer
      *
      * @param array $data
      * 
      * @return RedirectResponse
      */
     public function updateNodeServer(array $data): RedirectResponse{

          $updated_env   = $this->updateEnvParam($data);
          $path          = app()->environmentFilePath();
          foreach ($updated_env as $key => $value) {
    
               $escaped = preg_quote('='.env($key), '/');
               
               file_put_contents($path, preg_replace(
                   "/^{$key}{$escaped}/m",
                   "{$key}={$value}",
                   file_get_contents($path)
               ));
          }
          $notify[] = ["success", translate("Server configuration updated successfully")];
          return back()->withNotify($notify);
     }
     /**
      * updateEnvParam
      *
      * @param array $request
      * 
      * @return array
      */
     public function updateEnvParam(array $data): array {

          $serverHost         = Arr::get($data, "server_host", "127.0.0.1");
          $serverPort         = Arr::get($data, "server_port", "3008");
          $maxRetries         = Arr::get($data, "max_retries", "5");
          $reconnectInterval  = Arr::get($data, "reconnect_interval", "5000");

          $data = [
              'WP_SERVER_URL'      => "http://$serverHost:$serverPort",
              'NODE_SERVER_HOST'   => $serverHost,
              'NODE_SERVER_PORT'   => $serverPort,
              'MAX_RETRIES'        => $maxRetries,
              'RECONNECT_INTERVAL' => $reconnectInterval,
          ];
          return $data;
     }

     /**
      * domain
      *
      * @return string
      */
     public function domain(): string {

          $currentUrl = request()->root();
          $parsedUrl  = parse_url($currentUrl);
          $domain     = Arr::get($parsedUrl, 'host', "");
          return $domain;
     }

     /**
      * sessionInit
      *
      * @return array
      */
     public function sessionInit(): array {

          $response   = Http::post(env('WP_SERVER_URL').'/sessions/init', [ 
              'domain' => $this->domain()
          ]);
          $responseBody = json_decode($response->body());
          return [$response, $responseBody];
     }

     /**
      * sessionCreate
      *
      * @param Gateway $gateway
      * 
      * @return array
      */
     public function sessionCreate(Gateway $gateway): array {

          $response = Http::post(env('WP_SERVER_URL').'/sessions/create', [
              
               'id'       => $gateway->name,
               'isLegacy' => Arr::get($gateway->meta_data, 'multidevice', false),
               'domain'   => $this->domain()
          ]);
          
          $responseBody = json_decode($response->body());
          return [
              $response, 
              $responseBody
          ];
     }

     /**
      * sessionStatus
      *
      * @param string $name
      * 
      * @return Response
      */
     public function sessionStatus(string $name): Response {

          $checkConnection = Http::get(env('WP_SERVER_URL').'/sessions/status/'.$name);
          return $checkConnection;
     }

     /**
      * checkServerStatus
      *
      * @return bool
      */
     public function checkServerStatus(): bool {

          $checkWhatsappServer = true;
          try {
  
              $wpUrl = env('WP_SERVER_URL');
              Http::get($wpUrl);

              Gateway::where("channel", ChannelTypeEnum::WHATSAPP)
                    ->where("type", WhatsAppGatewayTypeEnum::NODE)
                    ->select(["id", "status", "name"])
                    ->lazyById()
                    ->each(function ($gateway) use (&$checkWhatsappServer) {
                         
                         $sessions = $this->sessionStatus($gateway->name);
                         $gateway->status = Status::INACTIVE->value;

                         if ($sessions->status() === 200) {
                              $gateway->status = Status::ACTIVE->value;
                         }

                         $gateway->save();
                    });
  
          } catch (\Exception $e) {
               Log::error("Whatsapp Node Failed: ".$e->getMessage());
               $checkWhatsappServer = false;
          }
          return $checkWhatsappServer;
     }

     /**
      * sessionDelete
      *
      * @param mixed $name
      * 
      * @return Response
      */
     public function sessionDelete($name): Response {

          return Http::delete(env('WP_SERVER_URL').'/sessions/delete/'.$name);
     }

}