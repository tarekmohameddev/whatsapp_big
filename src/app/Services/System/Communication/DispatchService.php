<?php

namespace App\Services\System\Communication;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Campaign;
use App\Models\Template;
use App\Enums\StatusEnum;
use Illuminate\View\View;
use App\Enums\SettingKey;
use App\Enums\ServiceType;
use App\Models\AndroidSim;
use App\Models\DispatchLog;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\Common\Status;
use App\Models\ContactGroup;
use Illuminate\Http\Response;
use App\Models\AndroidSession;
use App\Managers\GatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Enums\System\PriorityEnum;
use App\Services\Core\MailService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\RedirectResponse;
use App\Enums\System\ChannelTypeEnum;
use App\Jobs\ProcessDispatchLogBatch;
use Illuminate\Support\LazyCollection;
use App\Managers\CommunicationManager;
use App\Exceptions\ApplicationException;
use App\Enums\System\CampaignStatusEnum;
use App\Services\System\TemplateService;
use App\Http\Requests\SmsCampaignRequest;
use App\Http\Utility\Api\ApiJsonResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Service\Admin\Core\CustomerService;
use App\Http\Requests\EmailCampaignRequest;
use App\Enums\System\CommunicationStatusEnum;
use App\Http\Requests\WhatsappCampaignRequest;
use App\Services\System\Contact\ContactService;
use App\Enums\System\EmailVerificationStatusEnum;
use App\Jobs\InsertDispatchLogs;
use App\Models\DispatchDelay;
use App\Enums\System\DispatchTypeEnum;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DispatchService
{ 
     protected $mailService;
     protected $gatewayService;
     protected $gatewayManager;
     protected $contactService;
     protected $dispatchManager;
     protected $templateService;
     protected $customerService;
     

     public function __construct()
     {
          $this->mailService       = new MailService();
          $this->gatewayService    = new GatewayService();
          $this->gatewayManager    = new GatewayManager();
          $this->contactService    = new ContactService();
          $this->templateService   = new TemplateService();
          $this->customerService   = new CustomerService();
          $this->dispatchManager   = new CommunicationManager();
     }

     ##------------------##
     ## Regular Dispatch ##
     ##------------------##

     /**
      * loadLogs
      *
      * @param ChannelTypeEnum $channel
      * @param int|string|null|null $campaign_id
      * @param User|null $user
      * 
      * @return View
      */
     public function loadLogs(ChannelTypeEnum $channel, int|string|null $campaign_id = null, ?User $user = null): View
     {
          $title              = translate("{$channel->value} Dispatch Logs");
          $logs               = $this->dispatchManager->getDispatchLogs(channel: $channel, campaign_id: $campaign_id, user: $user);
          $gateways           = $this->gatewayManager->getGateways(channel: $channel, user: $user);
          $androidSessions    = $channel == ChannelTypeEnum::SMS 
                                   ? $this->gatewayManager->getAndroidSessions(user: $user)
                                   : null;
          
          $panelType = $user ? "user" : "admin";
          return view("{$panelType}.communication.{$channel->value}.index", 
                    compact('title', 'logs', 'gateways', 'androidSessions'));
     }

     /**
      * showDispatchLog
      *
      * @param ChannelTypeEnum $channel
      * @param string|int $id
      * @param User|null $user
      * 
      * @return View
      */
     public function showDispatchLog(ChannelTypeEnum $channel, string|int $id, ?User $user = null): View {

          $title    = translate("Details View");
          $log      = $this->dispatchManager->getSpecificDispatchLog(id: $id, user: $user);
          return view('partials.email_view', compact('title', 'log'));
     }

     /**
      * createDispatchLog
      *
      * @param ChannelTypeEnum $channel
      * @param User|null $user
      * 
      * @return View
      */
     public function createDispatchLog(ChannelTypeEnum $channel, ?User $user = null): View {

          $credentials        = config('setting.gateway_credentials.email');
          $type               = $channel->value;
          $title              = ucfirst($type);
          $groups             = $this->contactService->getChannelSpecificGroup(channel: $channel, user: $user);
          $templates          = $this->templateService->getChannelSpecificTemplates(channel: $channel, user: $user);
          $gateways           = $this->gatewayManager->getGateways($channel, loadPaginated: false, user: $user);
          $gateways           = $gateways->groupBy('type')
                                             ->map(function ($group, $type) {
                                                  return $group->mapWithKeys(function ($gateway) {
                                                  return [$gateway->id => $gateway->name];
                                                  })->toArray();
                                             })->toArray();
                                             
          $androidSessions    = $channel == ChannelTypeEnum::SMS 
                                   ? $this->gatewayManager->getAndroidSessions(user: $user)
                                   : null;
          $planAccess = null;
          if($user) $planAccess = (object)planAccess($user);
          
          $panelType = $user ? "user" : "admin";
          
          return view("{$panelType}.communication.{$channel->value}.create", 
               compact('title', 'templates', 'gateways', 'groups', 'type', 'credentials', 'androidSessions', 'planAccess'));
     }

      /**
     * Store dispatch logs for a communication channel.
     *
     * @param ChannelTypeEnum $type
     * @param Request $request
     * @param bool $isCampaign
     * @param int|string|null $campaignId
     * @param User|null $user
     * @param bool $isApi
     * @return RedirectResponse|array
     */
    public function storeDispatchLogs(
     ChannelTypeEnum $type,
     Request $request,
     bool $isCampaign = false,
     int|string|null $campaignId = null,
     ?User $user = null,
     bool $isApi = false
     ): RedirectResponse|array {
     
          $metaData      = [];
          $messageData   = $request->input('message');
          $contactsInput = $request->input('contacts');
          $scheduleAt    = $request->input('schedule_at');
     
          if ($type == ChannelTypeEnum::EMAIL) {
               $metaData = Arr::set($metaData, "email_from_name", $request->input('email_from_name'));
               $metaData = Arr::set($metaData, "reply_to_address", $request->input('reply_to_address'));
          }
     
          $dispatchLogs = [];
          $insertedLogs = DB::transaction(function () use ($contactsInput, $messageData, $scheduleAt, $type, $user, $request, $isCampaign, $campaignId, &$dispatchLogs, $metaData) {
                              $campaign = null;
                              $groups   = $this->contactService->handleContacts(type: $type, contactsInput: $contactsInput, user: $user);
                              $message  = $this->createMessage(request: $request, messageData: $messageData, type: $type, isCampaign: $isCampaign, user: $user);
     
                              if ($isCampaign) {
                                   $campaign = $this->createCampaign(request: $request, type: $type, message: $message, campaignId: $campaignId, user: $user);
                              }

                              $dispatchLogs = $this->prepareDispatchLogs(request: $request, groups: $groups, message: $message, type: $type, scheduleAt: $scheduleAt, campaign: $campaign, metaData: $metaData, user: $user);
                              $dispatchLogs = $this->gatewayService->assignGateway(type: $type, dispatchLogs: $dispatchLogs, request: $request, user: $user);

                              $totalLogCount = count($dispatchLogs);
                              $contactCount = $this->checkUserCredits(type: $type, contactCount: $totalLogCount, user: $user);
                              $insertedLogs = [];

                              $insertedLogs = LazyCollection::make($dispatchLogs)
                                                                 ->groupBy('gatewayable_id')
                                                                 ->flatMap(function ($gatewayLogs, $gatewayId) use ($type, $message, $totalLogCount, $isCampaign, $user, $request) {
                                                                      
                                                                      $gateway = null;
                                                                      if($request->input("method") == "android") {

                                                                           $gateway = AndroidSim::active()
                                                                                               ->where("id", $gatewayId)
                                                                                               ->first();
                                                                      } else {

                                                                           $gateway = Gateway::active()
                                                                                                    ->where("id", $gatewayId)
                                                                                                    ->select(["bulk_contact_limit", "type"])
                                                                                                    ->first();
                                                                      }
                                                                      
                                                                      if (!$gateway) throw new ApplicationException(translate("Gateway not found"));
                                                                      
                                                                      $bulkLimit     = $gateway->bulk_contact_limit ?? 1;
                                                                      $logCount      = $gatewayLogs->count();
                         
                                                                      if ($bulkLimit > 1 && $logCount > 1) {
                                                                           
                                                                           return $gatewayLogs
                                                                                     ->chunk($bulkLimit)
                                                                                     ->flatMap(function ($chunk) use ($gatewayId, $type, $message, $totalLogCount, $isCampaign, $user) {
                                                                                          $unit = $this->dispatchManager->createDispatchUnit($gatewayId, $message, $type, $chunk->count());
                                                                                          return $chunk->chunk(1000)->flatMap(function ($subChunk) use ($unit, $totalLogCount, $type, $isCampaign, $user) {

                                                                                               $chunkArray = $subChunk->map(function ($log) use ($unit) {
                                                                                                    Arr::set($log, "dispatch_unit_id", $unit->id);
                                                                                                    return $log;
                                                                                               })->toArray();
                                                                 
                                                                                               if ($totalLogCount <= 1000) { 
                                                                                                    DB::table('dispatch_logs')->insert($chunkArray);
                                                                                                    return $this->retrieveInsertedLogs($chunkArray);
                                                                                               } else {
                                                                                                    InsertDispatchLogs::dispatch($chunkArray, $type, $isCampaign ? 'campaign' : 'regular', $user);
                                                                                                    return $chunkArray; 
                                                                                               }
                                                                                          });
                                                                                     });
                                                                      } else {
                                                                           return $gatewayLogs->chunk(1000)->flatMap(function ($chunk) use($totalLogCount, $type, $isCampaign, $user) {
                                                                                $chunkArray = $chunk->toArray();
                                                                                if ($totalLogCount <= 1000) { 

                                                                                DB::table('dispatch_logs')->insert($chunkArray);
                                                                                     return $this->retrieveInsertedLogs($chunkArray);
                                                                                } else {
                                                                                     InsertDispatchLogs::dispatch($chunkArray, $type, $isCampaign ? 'campaign' : 'regular', $user);
                                                                                     return $chunkArray;
                                                                                }
                                                                           });
                                                                      }
                                                                 })->all();

                              if ($totalLogCount <= 1000 && !$isCampaign && !$scheduleAt) {
                                   $this->queueGatewayLogs($insertedLogs, $type, $isCampaign ? 'campaign' : 'regular', $user);
                              }
     
                              if ($user) {

                                   if ($user && $contactCount > 0) {
                                        $serviceType = $this->getServiceType($type);
                                        $this->customerService->deductCreditLog(
                                             $user,
                                             $contactCount,
                                             $serviceType->value,
                                             false,
                                             translate("Deducted {$contactCount} credits for {$type->name} dispatch")
                                        );
                                   }
                              }
     
                              return $insertedLogs;
                         });
     
          if ($isApi) return $this->dispatchManager->getMappedDispatchLogs($insertedLogs, $type);

          $panelType = $user 
                         ? "user" 
                         : "admin";

          $notify[] = [ 'success', 
                         translate("{$type->name} " 
                              . ($isCampaign 
                                   ? 'campaign' 
                                   : 'dispatch') . " logs are being processed")];
          return redirect()
                    ->route("{$panelType}.communication.{$type->value}" 
                         . ($isCampaign 
                              ? '.campaign' 
                              : '') . ".index")
                    ->withNotify($notify);
     }

     
     /**
      * checkUserCredits
      *
      * @param ChannelTypeEnum $type
      * @param User|null $user
      * @param int $contactCount
      * 
      * @return int
      */
     private function checkUserCredits(ChannelTypeEnum $type, int $contactCount, ?User $user = null): int
     {
          if (!$user) {
               return 0;
          }

          $planAccess = (object) planAccess($user);
          $creditColumn = $type->value."_credit";
          if($user->$creditColumn == 0) 
               throw new ApplicationException(
                    translate("Insufficient credits")
               );
               
          $creditsPerDay = Arr::get($planAccess->{$type->value}, 'credits_per_day', 0);

          if ($creditsPerDay <= 0) {
               return 0;
          }

          $todayStart    = Carbon::today()->startOfDay();
          $todayEnd      = Carbon::today()->endOfDay();

          $usedCredits = DB::table('dispatch_logs')
                              ->where('user_id', $user->id)
                              ->where('type', $type->value)
                              ->whereBetween('created_at', [$todayStart, $todayEnd])
                              ->whereIn('status', [
                                   CommunicationStatusEnum::PENDING->value,
                                   CommunicationStatusEnum::SCHEDULE->value,
                                   CommunicationStatusEnum::PROCESSING->value,
                                   CommunicationStatusEnum::DELIVERED->value
                              ])
                              ->count();

          if ($usedCredits + $contactCount > $creditsPerDay) {
               throw new ApplicationException(
                    translate("You have exceeded your daily {$type->name} credit limit of {$creditsPerDay}.")
               );
          }

          return $contactCount;
     }

     public function getServiceType(ChannelTypeEnum $type): ServiceType
     {
          return match ($type) {
               ChannelTypeEnum::EMAIL => ServiceType::EMAIL,
               ChannelTypeEnum::SMS => ServiceType::SMS,
               ChannelTypeEnum::WHATSAPP => ServiceType::WHATSAPP,
          };
     }

     /**
      * retrieveInsertedLogs
      *
      * @param array $logs
      * 
      * @return array
      */
     protected function retrieveInsertedLogs(array $logs): array
     {
          return collect($logs)->map(function ($log) {
               $log['id'] = DB::table('dispatch_logs')
                    ->where('message_id', Arr::get($log, "message_id"))
                    ->where('contact_id', Arr::get($log, "contact_id"))
                    ->where('created_at', Arr::get($log, "created_at"))
                    ->value('id');
               return $log;
          })->all();
     }

     /**
      * queueGatewayLogs
      *
      * @param array $dispatchLogs
      * @param ChannelTypeEnum $channel
      * @param string $pipe
      * @param User|null $user
      * 
      * @return void
      */
     public function queueGatewayLogs(array $dispatchLogs, ChannelTypeEnum $channel, string $pipe, ?User $user = null): void
     {
          $gatewayLogs = array_filter($dispatchLogs, fn($log) => Arr::get($log, 'gatewayable_type') === Gateway::class);
          if (empty($gatewayLogs)) return;
          
          $batches       = [];
          $batchSizes    = config("queue.batch_sizes.{$pipe}.{$channel->value}");
          $queue         = config("queue.pipes.{$pipe}.{$channel->value}");
          $minBatchSize  = Arr::get($batchSizes, "min");
          $maxBatchSize  = Arr::get($batchSizes, "max");
          $gatewayConfig = config("setting.gateway_credentials.{$channel->value}");
          $logCounter    = 0;
          collect($gatewayLogs)
               ->groupBy(function ($log) use ($channel, $logCounter) {
                    
                    return implode('|', [
                         Arr::get($log, 'gatewayable_id'),
                         Arr::get($log, 'user_id'),
                         $channel->value,
                         Arr::get($log, 'campaign_id', 'none'),
                         Arr::get($log, 'id', 'none'),
                         Arr::get($log, 'scheduled_at', 'none'),
                    ]);
               })
               ->map(function ($logs, $groupKey) use ($channel, $pipe, &$batches, $maxBatchSize, $minBatchSize, $gatewayConfig, &$logCounter) {
                    
                    [$gatewayId, $userId, $channelValue, $campaignId, $dispatchId, $scheduledAt] = explode('|', $groupKey);
                    
                    
                    $gatewayId          = $gatewayId ?: null;
                    $userId             = $userId ?: null;
                    $dispatchId         = $dispatchId !== 'none' ? $dispatchId : null;
                    $messagesToSend     = $logs->count();
                    $dispatchType       = $scheduledAt ? DispatchTypeEnum::CAMPAIGN : DispatchTypeEnum::REGULAR;
                    $delay              = $this->gatewayService->calculateDispatchDelay($gatewayId, $channel, $messagesToSend, $campaignId ? "campaign" : "regular", $userId);

                    $gateway            = Gateway::where('id', $gatewayId)->select(['bulk_contact_limit', 'type'])->first();
                    $typeConfig         = $gateway ? Arr::get($gatewayConfig, $gateway->type, []) : [];
                    $nativeBulkSupport  = Arr::get($typeConfig, 'meta_data', false);
                    $bulkLimit          = $gateway ? ($gateway->bulk_contact_limit ?? 1) : 1;
                    $logCount           = $logs->count();
                    
                    if ($nativeBulkSupport && $bulkLimit > 1 && $logCount > 1) {

                         collect($logs)
                              ->groupBy('dispatch_unit_id')
                              ->map(function ($unitLogs) use ($gatewayId, $dispatchId, $dispatchType, $userId, $channel, $pipe, &$batches, $maxBatchSize, $delay, &$logCounter) {

                                   return $unitLogs->chunk($maxBatchSize)
                                                       ->filter(fn($chunk) => count($chunk) >= 1)
                                                       ->map(function ($chunk) use ($gatewayId, $dispatchId, $dispatchType, $userId, $channel, $pipe, &$batches, $delay, &$logCounter) {
                                                            
                                                            $logCounter++;
                                                            $unitIds = $chunk->keys()->all();
                                                            $delay = $delay * $logCounter;
                                                            $this->storeDispatchDelay($gatewayId, $channel->value, $dispatchId, $dispatchType, $delay, $userId);
                                                            $job = ProcessDispatchLogBatch::dispatch($unitIds, $channel, $pipe, true)
                                                                                               ->delay(now()->addSeconds($delay));
                                                            $batches[] = $job;
                                                       });
                              })->all();
                    } else {
                         
                         $logs->chunk($maxBatchSize)
                                   ->filter(fn($chunk) => count($chunk) >= $minBatchSize)
                                   ->map(function ($chunk) use ($gatewayId, $dispatchId, $dispatchType, $userId, $channel, $pipe, &$batches, $delay, &$logCounter) {

                                        $logCounter++; 
                                        $ids      = collect($chunk)->pluck('id')->toArray();
                                        $delay    = $delay * $logCounter;
                                        
                                        $this->storeDispatchDelay($gatewayId, $channel->value, $dispatchId, $dispatchType, $delay, $userId);
                                        $job = ProcessDispatchLogBatch::dispatch($ids, $channel, $pipe, false)
                                                                           ->delay(now()->addSeconds($delay));
                                        $batches[] = $job;
                                   })->all();
                    }
               });

          // if (!empty($batches)) {
          //      Bus::batch($batches)
          //           ->allowFailures()
          //           ->onQueue($queue)
          //           ->dispatch();
          // }
     }

     /**
      * createMessage
      *
      * @param Request $request
      * @param array $messageData
      * @param ChannelTypeEnum $type
      * @param bool $isCampaign
      * @param User|null $user
      * 
      * @return Message
      */
     protected function createMessage(Request $request, array $messageData, ChannelTypeEnum $type, bool $isCampaign = false, ?User $user = null): Message
     {
          $template = Template::find(request()->input("whatsapp_template_id"));
          
          return Message::create([
               'user_id'      => $user?->id,
               'type'         => $type->value,
               'message'      => $type == ChannelTypeEnum::WHATSAPP && $template
                                   ? json_encode($this->getTemplateData(request(), $template))
                                   : textSpinner(Arr::get($messageData, 'message_body')),
               'subject'      => Arr::get($messageData, 'subject'),
               'main_body'    => Arr::has($messageData, 'main_body')
                                   ? buildDomDocument(textSpinner(Arr::get($messageData, 'main_body'))) 
                                   : null,
               'file_info'    => $this->findAndUploadFile($request),
               'template_id'  => request()->input("whatsapp_template_id"),
               'is_campaign'  => $isCampaign,
          ]);
     }

     /**
      * createCampaign
      *
      * @param Request $request
      * @param ChannelTypeEnum $type
      * @param Message $message
      * @param int|string|null $campaignId
      * @param User|null $user
      *
      * @return Campaign
      */
     protected function createCampaign(Request $request, ChannelTypeEnum $type, Message $message, int|string|null $campaignId = null, ?User $user = null): Campaign
     {
          $scheduleAt = $request->has('schedule_at') 
                              ? Carbon::parse($request->input('schedule_at'))->setTimezone(config('app.timezone'))
                              : Carbon::now();
          
          return Campaign::updateOrCreate([
               "id" => $campaignId
          ],[
               'user_id'           => $user?->id,
               'message_id'        => $message->id,
               'group_id'          => null,
               'type'              => $type->value,
               'name'              => $request->input('name'),
               'priority'          => PriorityEnum::LOW->value, 
               'repeat_format'     => $request->input('repeat_format', 'none'),
               'repeat_time'       => $request->has('repeat_time') 
                                        ? (int)$request->input('repeat_time') 
                                        : 0,
               'status'            => CampaignStatusEnum::ACTIVE->value,
               'schedule_at'       => $scheduleAt,
               'meta_data'         => [
                    'repeat_format'     => $request->input('repeat_format'),
                    'sms_type'          => $request->input('sms_type'),
                    'email_from_name'   => $request->input('email_from_name'),
                    'reply_to_address'   => $request->input('reply_to_address'),
               ],
          ]);
     }

     /**
      * prepareDispatchLogs
      *
      * @param Request $request
      * @param mixed $groups
      * @param Message $message
      * @param ChannelTypeEnum $type
      * @param string|null $scheduleAt
      * @param Campaign|null $campaign
      * @param array|null $metaData
      * @param User|null $user
      * 
      * @return array
      */
     protected function prepareDispatchLogs(Request $request, $groups, Message $message, ChannelTypeEnum $type, ?string $scheduleAt, ?Campaign $campaign = null, ?array $metaData = null, ?User $user = null): array
     {
          $dispatchLogs = [];
          $now = Carbon::now();
          $contactColumn = $type->value . "_contact";
          
          LazyCollection::make($groups)
               ->map(function ($group) use ($contactColumn, $request) {
                    $group = $group->load(["contacts"]);
                    if ($group->name == SettingKey::SINGLE_CONTACT_GROUP_NAME->value) {
                         
                         return $group->contacts
                                             ->whereNotNull($contactColumn)
                                             ->when(!is_array($request->input("contacts")), fn($q) => 
                                                  $q->where($contactColumn, $request->input("contacts"))
                                                       ->first());
                    } else {
                         return $group->contacts->whereNotNull($contactColumn);
                    }
               })
               ->flatten()
               ->chunk(1000)
               ->each(function ($chunk) use ($message, $type, $scheduleAt, $campaign, $user, $now, &$dispatchLogs, $metaData) {
                    $chunk->each(function ($contact) use ($message, $type, $scheduleAt, $campaign, $user, $now, &$dispatchLogs, $metaData) {
                         
                         if ($type == ChannelTypeEnum::EMAIL 
                         && (site_settings('email_contact_verification') == StatusEnum::TRUE->status() 
                              || site_settings('email_contact_verification') == Status::ACTIVE->value)
                         && $contact->email_verification != EmailVerificationStatusEnum::VERIFIED) {
                              return;
                         }
                         if($campaign && $type == ChannelTypeEnum::EMAIL) {
                              $metaData = Arr::set($metaData, "unsubscribe_link", $this->generateUnsubscribeLink($campaign->id, $contact->uid, $type));
                         }

                         $log = [];
                         Arr::set($log, 'user_id', $user?->id);
                         Arr::set($log, 'message_id', $message->id);
                         Arr::set($log, 'contact_id', $contact->id);
                         Arr::set($log, 'campaign_id', $campaign?->id);
                         Arr::set($log, 'type', $type->value);
                         Arr::set($log, 'gatewayable_id', null); // Set by assignGateway later
                         Arr::set($log, 'gatewayable_type', null); // Set by assignGateway later
                         Arr::set($log, 'priority', PriorityEnum::LOW->value);
                         Arr::set($log, 'status', $scheduleAt 
                                                                           ? CommunicationStatusEnum::SCHEDULE->value
                                                                           : CommunicationStatusEnum::PENDING->value);
                         
                         Arr::set($log, 'scheduled_at', $scheduleAt 
                              ? Carbon::parse($scheduleAt)->setTimezone(config('app.timezone')) 
                              : null);
                         Arr::set($log, 'meta_data', json_encode($metaData));
                         Arr::set($log, 'created_at', $now);
                         Arr::set($log, 'updated_at', $now);
                         $dispatchLogs[] = $log;
                    });
               });
          return $dispatchLogs;
     }

     /**
      * generateUnsubscribeLink
      *
      * @param int $campaign_id
      * @param string $contact_uid
      * @param ChannelTypeEnum $channel
      * 
      * @return string
      */
     public function generateUnsubscribeLink(int $campaign_id, string $contact_uid, ChannelTypeEnum $channel): string
     {
         $encrypted_campaign_id = encrypt($campaign_id);
         $encrypted_contact_uid = encrypt($contact_uid);
 
         $unsubscribeLink = route('unsubscribe', [
             'campaign_id' => $encrypted_campaign_id,
             'contact_id' => $encrypted_contact_uid,
             'channel' => $channel->value,
         ]);
 
         return $unsubscribeLink;
     }
 

     /**
      * updateStatusesForAndroid
      *
      * @param AndroidSession $androidSession
      * @param array $logs
      * 
      * @return JsonResponse
      */
     public function updateStatusesForAndroid(AndroidSession $androidSession, array $logs): JsonResponse
     {
          $logIds = array_column($logs, 'id');
          
          $invalidLogs = DispatchLog::select('id', 'status')
                                        ->whereIn('id', $logIds)
                                        ->where('gatewayable_type', AndroidSim::class)
                                        ->where(function ($query) use ($androidSession) {
                                             $query->where('status', '!=', CommunicationStatusEnum::PROCESSING->value)
                                                  ->when("gatewayable_type" instanceof AndroidSim, fn(Builder $q): Builder =>
                                                  $q->orWhereHas('gatewayable', function ($subQuery) use ($androidSession) {
                                                       $subQuery->where('android_session_id', '!=', $androidSession->id);
                                                  }));
                                        })->get()
                                             ->mapWithKeys(function ($log) {
                                                  return [$log->id => $log->status->value];
                                             })->toArray();
          
          if (!empty($invalidLogs)) {

               $invalidId          = array_key_first($invalidLogs);
               $invalidStatus      = Arr::get($invalidLogs, $invalidId);
               $hasMatchingSession = DispatchLog::where('id', $invalidId)
                                                       ->where('gatewayable_type', AndroidSim::class)
                                                       ->when("gatewayable_type" instanceof AndroidSim, fn(Builder $q): Builder =>
                                                            $q->whereHas('gatewayable', function ($query) use ($androidSession) {
                                                                 $query->where('android_session_id', $androidSession->id);
                                                            }))->exists();
     
               if ($invalidStatus != CommunicationStatusEnum::PROCESSING->value) 
                    return ApiJsonResponse::error(
                         translate("DispatchLog with ID "). $invalidId. translate(" cannot be updated because its status is "). $invalidStatus. translate(", only logs with status 'processing' can be updated."),
                         null,
                         403
                    );
               
     
               if (!$hasMatchingSession) 
                    return ApiJsonResponse::error(
                         translate("DispatchLog with ID"). $invalidId .translate("does not belong to this Android session."),
                         null,
                         403
                    );
          }
     
          $updatedCount = $this->dispatchManager->updateDispatchLogStatuses($androidSession, $logs);
     
          if ($updatedCount === 0) 
               return ApiJsonResponse::success(
                    translate('No dispatch log statuses were updated'),
                    null,
                    200
               );
          
     
          return ApiJsonResponse::success(
               translate("Successfully updated"). $updatedCount. translate("dispatch log statuses"),
               ['updated_count' => $updatedCount]
          );
     }

     /**
      * destroyDispatchLog
      *
      * @param string|int|null $id
      * @param User|null $user
      * 
      * @return RedirectResponse
      */
     public function destroyDispatchLog(string|int|null $id, ?User $user = null): RedirectResponse {
          
          $dispatchLog = $this->dispatchManager->getSpecificDispatchLog($id);
          if(!$dispatchLog) throw new ApplicationException('Invalid dispatch log', 401);
          $message = $dispatchLog->message;
          
          $dispatchLog->delete();
          if($message) {

               $messageId = $message->id;
               $remainingDispatchLogs = $this->dispatchManager->dispatchLogsExists(column: "message_id", value: $messageId);
               if (!$remainingDispatchLogs) $message->delete();
          }

          return returnBackWithResponse(status: "succ:ess", message:"Successfully deleted dispatch log");  
     }

     /**
      * updateDispatchLogStatus
      *
      * @param ChannelTypeEnum $channel
      * @param Request $request
      * @param User|null $user
      * 
      * @return RedirectResponse
      */
     public function updateDispatchLogStatus(ChannelTypeEnum $channel, Request $request, ?User $user = null): RedirectResponse{
          
          if(!$request->input("status")) throw new ApplicationException('Invalid Status', 401);
          $dispatchLog = $this->dispatchManager->getSpecificDispatchLog($request->input("id"));
          if(!$dispatchLog) throw new ApplicationException('Invalid dispatch log', 401);

          $status   = CommunicationStatusEnum::from($request->input("status"));
          if($user && $status == CommunicationStatusEnum::CANCEL && $dispatchLog->status != CommunicationStatusEnum::PENDING) throw new ApplicationException('Only pending logs status can be updated', Response::HTTP_FORBIDDEN);
          $response = false;
          $response = DB::transaction(function() use($channel, $request, $dispatchLog, $user, $status): bool {

               $dispatchLog->status = $status;
               $dispatchLog->response_message = $request->input("response_message");
               if(@$dispatchLog?->user) {

                    $column = $channel->value."_credit";
                    $currentCredit = $dispatchLog->user->$column;
                    if($currentCredit >= 0 ) {

                         $dispatchLog->user->$column = 
                              $status == CommunicationStatusEnum::DELIVERED
                                             ? --$dispatchLog->user->$column
                                             : (CommunicationStatusEnum::FAIL
                                                  ? ++$dispatchLog->user->$column
                                                  : $dispatchLog->user->$column);
                         $dispatchLog->user->save();
                    }
               }
               $dispatchLog->save();
               return true;
          });

          return returnBackWithResponse(
               status: $response 
                         ? "success"
                         : "error", 
               message: $response 
                    ? "Successfully deleted dispatch log"
                    : "Could not update log status");  
     }

     ##-------------------##
     ## Campaign Dispatch ##
     ##-------------------##

     /**
      * loadCampaignLogs
      *
      * @param ChannelTypeEnum $channel
      * @param User|null $user
      * 
      * @return View
      */
     public function loadCampaignLogs(ChannelTypeEnum $channel, ?User $user = null): View
     {
          $title              = translate("{$channel->value} Campaign Logs");
          $logs               = $this->dispatchManager->getCampaignLogs(channel: $channel, user: $user);

          $panelType = $user ? "user" : "admin";
          return view("{$panelType}.communication.campaigns", 
                    compact('title', 'logs'));
     }

     /**
      * createCampaignLog
      *
      * @param ChannelTypeEnum $channel
      * @param User|null $user
      * 
      * @return View
      */
     public function createCampaignLog(ChannelTypeEnum $channel, ?User $user = null): View
     {
          $type               = $channel->value;
          $title              = translate("Create an {$channel->value} camapign");
          $groups             = $this->contactService->getChannelSpecificGroup(channel: $channel, user: $user);
          $templates          = $this->templateService->getChannelSpecificTemplates(channel: $channel, user: $user);
          $gateways           = $this->gatewayManager->getGateways(channel: $channel, loadPaginated: false, user: $user);
          $gateways           = $gateways->groupBy('type')
                                             ->map(function ($group, $type) {
                                                  return $group->mapWithKeys(function ($gateway) {
                                                       return [$gateway->id => $gateway->name];
                                                  })->toArray();
                                             })->toArray();
          $androidSessions    = $channel == ChannelTypeEnum::SMS 
                                   ? $this->gatewayManager->getAndroidSessions(loadPaginated: $loadPaginated = false, user: $user)
                                   : null;   

          $planAccess = null;
          if($user) $planAccess = (object)planAccess($user);

          $panelType = $user ? "user" : "admin";
          
          return view("{$panelType}.communication.{$channel->value}.campaign.create", compact('title', 'groups', 'type', 'templates', 'gateways', 'androidSessions', 'planAccess'));
     }

     /**
      * showCampaignLog
      *
      * @param ChannelTypeEnum $channel
      * @param int|string $id
      * @param User|null $user
      * 
      * @return View
      */
     public function showCampaignLog(ChannelTypeEnum $channel, int|string $id, ?User $user = null): View {

          $type               = $channel->value;
          $title              = translate("Create an {$channel->value} camapign");
          $campaign           = $this->dispatchManager->getSpecificCampaignLog(id: $id, user: $user);
          $groups             = $this->contactService->getChannelSpecificGroup(channel: $channel, user: $user);
          $templates          = $this->templateService->getChannelSpecificTemplates(channel: $channel, user: $user);
          $gateways           = $this->gatewayManager->getGateways(channel: $channel, user: $user); 
          $gateways           = $gateways->groupBy('type')
                                             ->map(function ($group, $type) {
                                                  return $group->mapWithKeys(function ($gateway) {
                                                  return [$gateway->id => $gateway->name];
                                                  })->toArray();
                                             })->toArray();    
          $androidSessions    = $channel == ChannelTypeEnum::SMS 
                                   ? $this->gatewayManager->getAndroidSessions(loadPaginated: $loadPaginated = false, user: $user)
                                   : null;   

          $planAccess = null;
          if($user) $planAccess = (object)planAccess($user);

          $panelType = $user ? "user" : "admin";
          return view("{$panelType}.communication.{$channel->value}.campaign.edit", 
               compact('title', 'groups', 'type', 'templates', 'gateways', 'androidSessions', 'campaign', 'planAccess'));
     }

     /**
      * destroyCampaignLog
      *
      * @param string|int|null $id
      * @param User|null $user
      * 
      * @return RedirectResponse
      */
     public function destroyCampaignLog(string|int|null $id, ?User $user = null): RedirectResponse {
          
          $campaignLog = $this->dispatchManager->getSpecificCampaignLog(id: $id, user: $user);
          if(!$campaignLog) throw new ApplicationException('Invalid dispatch log', 401);
          
          $campaignLog?->dispatchLogs();
          $campaignLog?->message();
          $campaignLog->delete();


          return returnBackWithResponse(status: "success", message:"Successfully deleted campaign");  
     }


     ## Old Functions

     public function getTemplateData($request, $template) {

        
          $template_message = $template["template_data"]["components"];
          $request_data     = $request->all();
          $matches = []; $i = 0; $message = []; $data = [];
          
          foreach ($request_data as $request_key => $request_value) {
              
              if (str_contains($request_key, "_placeholder_")) {
  
                  preg_match('/([a-z]+)_placeholder_(\d+)/', $request_key, $match);
                  $matches[]          = $match;
                  $data[$request_key] = $request_value;
              }
              if (str_contains($request_key, "_header_media")) {
  
                  $fileType = explode('_', $request_key)[0];
                  $fileLink = "";
                  
                  if ($fileType == "image") { $fileLink = storeCloudMediaAndGetLink('image_header_media', $request->file('image_header_media')); } 
                  elseif ($fileType == "video") { $fileLink = storeCloudMediaAndGetLink('video_header_media', $request->file('video_header_media')); } 
                  elseif ($fileType == "document") { $fileLink = storeCloudMediaAndGetLink('document_header_media', $request->file('document_header_media')); }
  
                  preg_match('/([a-z]+)_header_media/', $request_key, $match);
                  $match[]            = "header_media"; 
                  $match[]            = $fileLink; 
                  $matches[]          = $match;
                  $data[$request_key] = $request_value;
              }
              if (str_contains($request_key, "_button_")) {
  
                  preg_match('/([a-z]+)_button_(\d+)/', $request_key, $match);
              
                  $match[]   = $request_value; 
                  $matches[] = $match;
                  $data[]    = $match; 
              }
              if (str_contains($request_key, "flow_")) {
                  preg_match('/(flow)_([a-z]+)/', $request_key, $match); 
                  $match[]   = $request_value; 
                  $match[1] = "flow"; 
                  $matches[] = $match;
                  $data[]    = $match; 
              }
          }
          array_column($matches, 1);
          $k = 0;
          $t = 0;
          foreach ($matches as $value) {
              
              $type                 = strtoupper($value[1]); 
              $number               = $value[2];
              
              $template_message_key = array_search($type, array_column($template_message, 'type'));
              
              if ($template_message_key !== false || preg_match('/button/', $value[0]) || preg_match('/_header_media/', $value[0]) ||  preg_match('/flow_cloud/', $value[0])) {
                  
                  if ($value[1] == "header") {
                      
                      foreach($template_message[$template_message_key]['example']["$value[1]_text"] as $template_key => $template_value) {
                          
                          $message[$template_message_key]["type"]         = strtolower($template_message[$template_message_key]["type"]);
                          $message[$template_message_key]["parameters"][] = [
                              "type" => strtolower($template_message[$template_message_key]["format"]),
                              strtolower($template_message[$template_message_key]["format"]) => $request_data["$value[1]_placeholder_$template_key"]
                          ];
                      }
                  } elseif ($value[1] == "reply") {
  
                      $message[] = [
                          "type"       => "button",
                          "sub_type"   => "QUICK_REPLY",
                          "index"      => $value[2],
                          "parameters" => [
                              [
                                  "type" => "text",
                                  "text" => $value[3],
                              ]
                          ],
                      ];
  
                  } elseif ($value[1] == "code") {
                      
                      $message[3] = [
                          "type"       => "button",
                          "sub_type"   => "COPY_CODE",
                          "index"      => $value[2],
                          "parameters" => [
                              [
                                  "type" => "coupon_code",
                                  "coupon_code" => $value[3],
                              ]
                          ],
                      ];
                      
                  } elseif ($value[1] == "url") {
                      
                      $message[4] = [
                          "type"       => "button",
                          "sub_type"   => "URL",
                          "index"      => $value[2],
                          "parameters" => [
                              [
                                  "type" => "text",
                                  "text" => substr($value[3], strlen($template_message[$t]['buttons'][0]['example'][0] ?? '')),
                              ]
                          ],
                      ];
                  } elseif ($value[1] == "flow") {
                      
                      $flow_key = null;
                      foreach ($template_message as $index => $item) {
                          if (isset($item['buttons'])) {
                              foreach ($item['buttons'] as $button) {
                                  if ($button['type'] === 'FLOW') {
                                      $flow_key = $index; 
                                      break 2; 
                                  }
                              }
                          }
                      }
                      $message[3] = [
                          "type"       => "button",
                          "sub_type"   => "FLOW",
                          "index"      => 0,
                          "parameters" => [
                              [
                                  "type" => "action",
                                  "action" => [
                                      "flow_token" => "unused",
                                  ]
                              ]
                          ],
                      ];
                      
                  } elseif ($value[2] === 'header_media') {
  
                      $message[] = [
                          "type"       => "header",
                          "parameters" => [
                              [
                                  "type"  => strtolower($value[1]),
                                  strtolower($value[1]) => [
                                      "link" => $value[3],
                                  ],
                              ]
                          ],
                      ];
                  } else {
                     
                      foreach($template_message[$template_message_key]['example']["$value[1]_text"] as $template_key => $template_value) {
                          
                          $message[$template_message_key]["type"]         = strtolower($template_message[$template_message_key]["type"]);
                          $message[$template_message_key]["parameters"][] = [
                              "type" => "text",
                              "text" => $data["body_placeholder_$k"]
                          ];
                          $k++;
                      }
                    
                  } 
              }
              $t++;
          }
          return $message;
     }

     public function findAndUploadFile($request): ?array {
        
          $fileTypes = ['image', 'document', 'audio', 'video', 'others'];
          
          foreach ($fileTypes as $fileType) {
              
               if ($request->hasFile($fileType)) {
                         
                    $file     = $request->file($fileType);
                    $fileName = uniqid().time().'.'.$file->getClientOriginalExtension();
                    $path     = filePath()['whatsapp']['path_'.$fileType];
                    
                    if(!file_exists($path)) {
     
                         mkdir($path, 0777, true);
                    }
                    try {
                         $file->move($path, $fileName);
                         
                         return [
     
                              'type'     => $fileType,
                              'url_file' => $path . '/' . $fileName,
                              'name'     => $fileName
                         ];
                    } catch (\Exception $e) {
     
                         return null;
                    }
               }
          }
  
          return null;
     }
     
     /**
      * storeDispatchDelay
      *
      * @param string|int $gatewayId
      * @param string|ChannelTypeEnum $channel
      * @param string|int $dispatchId
      * @param string|DispatchTypeEnum $dispatchType
      * @param int|float|null|null $delayValue
      * @param int|null $userId
      * 
      * @return DispatchDelay
      */
     public function storeDispatchDelay(string|int $gatewayId, string|ChannelTypeEnum $channel, string|int $dispatchId, string|DispatchTypeEnum $dispatchType, int|float|null $delayValue = null, ?int $userId = null): DispatchDelay
     {
          return DispatchDelay::updateOrCreate([
               'user_id'           => $userId,
               'gateway_id'        => $gatewayId,
               'channel'           => $channel,
               'dispatch_id'       => $dispatchId,
               'dispatch_type'     => $dispatchType,
          ], [
               'delay_value'  => $delayValue,
               'applies_from' => Carbon::now(),
          ]);
     }
}