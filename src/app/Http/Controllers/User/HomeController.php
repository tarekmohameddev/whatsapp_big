<?php

namespace App\Http\Controllers\User;

use App\Enums\ServiceType;
use App\Enums\SettingKey;
use App\Enums\StatusEnum;
use App\Enums\System\ChannelTypeEnum;
use App\Enums\System\CommunicationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\AffiliateLog;
use App\Models\AndroidApi;
use App\Models\AndroidApiSimInfo;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\CampaignUnsubscribe;
use App\Models\DispatchLog;
use App\Models\GeneralSetting;
use App\Models\SmsGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Transaction;
use App\Models\Group;
use App\Models\Contact;
use App\Models\Template;
use App\Models\CreditLog;
use App\Models\EmailLog;
use App\Models\EmailCreditLog;
use App\Models\PaymentMethod;
use App\Models\WhatsappLog;
use App\Models\WhatsappCreditLog;
use App\Models\PaymentLog;
use App\Models\Gateway;
use App\Models\PostWebhookLog;
use App\Models\PricingPlan;
use Gregwar\Captcha\PhraseBuilder;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\Session;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Rules\FileExtentionCheckRule;
use App\Service\Admin\Core\FileService;
use App\Service\Admin\Dispatch\WhatsAppService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public $fileService;
    public function __construct() {

        $this->fileService = new FileService();
    }

    public function dashboard()
    {
        Session::put("menu_active", false);
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();
        $title          = translate("Welcome Back").", ".auth()->user()->name;
        $user = Auth::user();
        
        $planAccess = planAccess($user);
        $creditUsage = [];
        if(!empty($planAccess)){

            $planAccess = (object) $planAccess;
            $types = ['sms', 'email', 'whatsapp'];
            $creditUsage = [];
    
            foreach ($types as $type) {
                $creditColumn = $type . '_credit';
                $creditsPerDay = Arr::get($planAccess->{$type}, 'credits_per_day', 0);
    
                // Unlimited if credits_per_day is 0
                $isUnlimited = ($creditsPerDay == 0);
    
                $todayStart = Carbon::today()->startOfDay();
                $todayEnd = Carbon::today()->endOfDay();
    
                $usedCredits = 0;
                if (!$isUnlimited) {
                    $usedCredits = DB::table('dispatch_logs')
                        ->where('user_id', $user->id)
                        ->where('type', $type)
                        ->whereBetween('created_at', [$todayStart, $todayEnd])
                        ->whereIn('status', [
                            CommunicationStatusEnum::PENDING->value,
                            CommunicationStatusEnum::SCHEDULE->value,
                            CommunicationStatusEnum::PROCESSING->value,
                            CommunicationStatusEnum::DELIVERED->value
                        ])
                        ->count();
                }
    
                $creditUsage[$type] = [
                    'used' => $usedCredits,
                    'total' => $isUnlimited ? -1 : $creditsPerDay,
                    'available' => $isUnlimited ? -1 : max(0, $creditsPerDay - $usedCredits),
                    'is_unlimited' => $isUnlimited,
                ];
            }
        }


        $currentMonthCommission = DB::table('affiliate_logs')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('commission_amount');

        $previousMonthCommission = DB::table('affiliate_logs')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('commission_amount');

        $currentMonthWithdraw = DB::table('withdraw_logs')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('final_amount');

        $previousMonthWithdraw = DB::table('withdraw_logs')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('final_amount');

        // Net change for each month
        $currentMonthNet = $currentMonthCommission - $currentMonthWithdraw;
        $previousMonthNet = $previousMonthCommission - $previousMonthWithdraw;

        // Calculate growth rate
        if ($previousMonthNet != 0) {
            $growthRate = (($currentMonthNet - $previousMonthNet) / abs($previousMonthNet)) * 100;
        } else {
            $growthRate = $currentMonthNet > 0 ? 100 : 0;
        }

        $growthRateFormatted = ($growthRate >= 0 ? '+' : '') . number_format($growthRate, 1) . '%';
        
        $transactions   = Transaction::where('user_id', $user->id)
                                        ->orderBy('id', 'DESC')
                                        ->take(5)
                                        ->get();
        $credits        = CreditLog::where('user_id', $user->id)
                                        ->where('post_credit', '>', '0')
                                        ->orderBy('id', 'DESC')
                                        // ->with('user')
                                        ->take(5)
                                        ->get();

        
        $logs = [
            "sms" => [
                'all' => DispatchLog::where('type', ChannelTypeEnum::SMS->value)->where('user_id', $user->id)->count(),
                'success' => DispatchLog::where('type', ChannelTypeEnum::SMS->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::DELIVERED->value)->count(),
                'pending' => DispatchLog::where('type', ChannelTypeEnum::SMS->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::PENDING->value)->count(),
                'failed' => DispatchLog::where('type', ChannelTypeEnum::SMS->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::FAIL->value)->count(),
                // 'all' => 10,
                // 'success' => 10,
                // 'pending' => 10,
                // 'failed' => 10,
            ],
            "email" => [
                // 'all' => 10,
                // 'success' => 10,
                // 'pending' => 10,
                // 'failed' => 10,
                'all' => DispatchLog::where('type', ChannelTypeEnum::EMAIL->value)->where('user_id', $user->id)->count(),
                'success' => DispatchLog::where('type', ChannelTypeEnum::EMAIL->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::DELIVERED->value)->count(),
                'pending' => DispatchLog::where('type', ChannelTypeEnum::EMAIL->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::PENDING->value)->count(),
                'failed' => DispatchLog::where('type', ChannelTypeEnum::EMAIL->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::FAIL->value)->count(),
            ],
            'whats_app' => [
                // 'all' => 10,
                // 'success' => 10,
                // 'pending' => 10,
                // 'failed' => 10,
                'all' => DispatchLog::where('type', ChannelTypeEnum::WHATSAPP->value)->where('user_id', $user->id)->count(),
                'success' => DispatchLog::where('type', ChannelTypeEnum::WHATSAPP->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::DELIVERED->value)->count(),
                'pending' => DispatchLog::where('type', ChannelTypeEnum::WHATSAPP->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::PENDING->value)->count(),
                'failed' => DispatchLog::where('type', ChannelTypeEnum::WHATSAPP->value)->where('user_id', $user->id)->where('status', CommunicationStatusEnum::FAIL->value)->count(),
            ],
        ];
        
        return view('user.dashboard', compact('title', 'user', 'creditUsage', 'transactions', 'credits', 'logs', 'growthRateFormatted'));
    }

    public function profile()
    {
        Session::put("menu_active", false);
        $title = translate("User Profile");
        $user = auth()->user();
        return view('user.profile', compact('title', 'user'));
    }

    public function profileUpdate(Request $request)
    {
        $user = Auth::user();
        $this->validate($request, [
            'name' => 'nullable',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'image' => ['nullable', 'image', new FileExtentionCheckRule(json_decode(site_settings('mime_types'),true))],
            'address' => 'nullable|max:250',
            'city' => 'nullable|max:250',
            'state' => 'nullable|max:250',
            'zip' => 'nullable|max:250',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $address = [
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip
        ];
        $user->address = $address;
        if($request->hasFile('image')) {
            try {
                $removefile = $user->image ?: null;
                $user->image = $this->fileService->uploadFile($request->image, null, filePath()['profile']['user']['path'], filePath()['profile']['user']['size'], false);
            }catch (\Exception $exp){
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
        }
        $user->save();
        $notify[] = ['success', 'Your profile has been updated.'];
        return redirect()->route('user.profile')->withNotify($notify);
    }

    public function password()
    {
        $title = translate("Password Update");
        return view('user.password', compact('title'));
    }

    public function passwordUpdate(Request $request)
    {
        $this->validate($request, [
            'current_password' => 'nullable',
            'password' => 'required|confirmed',
        ]);

        $user = auth()->user();

        if ($user->password && !Hash::check($request->input('current_password'), $user->password)) {
            $notify[] = ['error', 'The password doesn\'t match!'];
            return back()->withNotify($notify);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        $notify[] = ['success', 'Password has been updated'];
        return back()->withNotify($notify);
    }
    
    public function transaction() {

        Session::put("menu_active", true);
        $title          = translate("Transaction log");
        $paymentMethods = PaymentMethod::where('status', 1)->get();
        $transactions   = Transaction::where('user_id', auth()->user()->id)
                            ->search(['transaction_number', 'amount'])
                            ->latest()
                            ->date()
                            ->paginate(paginateNumber(site_settings("paginate_number")))->onEachSide(1)
                            ->appends(request()->all());
        return view('user.report.record.transaction', compact('title', 'transactions', 'paymentMethods'));
    }

    public function paymentLog()
    {
        Session::put("menu_active", true);
        $title = translate("Payment Logs");
        $paymentLogs = PaymentLog::where('status', '!=', 0)
                                    ->where("user_id", auth()->user()->id)
                                    ->with('paymentGateway')
                                    ->latest()
                                    ->search(['trx_number', 'amount', 'paymentGateway:name'])
                                    ->with('user', 'plan')
                                    ->date()
                                    ->paginate(paginateNumber(site_settings("paginate_number")))->onEachSide(1)
                                    ->appends(request()->all());
        $paymentMethods = PaymentMethod::where('status', 1)->get();
        return view('user.report.record.payment', compact('title', 'paymentLogs', 'paymentMethods'));
    }

    public function affiliateLog()
    {
        Session::put("menu_active", true);
        $title = translate("Affiliate Logs");
        $logs = AffiliateLog::where("user_id", auth()->user()->id)
                                    ->with(['user', 'affiliate', 'subscription', 'subscription.plan'])
                                    ->latest()
                                    ->search(['trx_code', 'user:name', 'user:email', 'affiliate:name', 'affiliate:email'])
                                    ->date()
                                    ->paginate(paginateNumber(site_settings("paginate_number")))->onEachSide(1)
                                    ->appends(request()->all());
        return view('user.report.record.affiliate', compact('title', 'logs'));
    }

    public function credit() {
        
        Session::put("menu_active", true);
        $title      = translate("Credit logs");
        $creditLogs = CreditLog::where("user_id", auth()->user()->id)
                        ->routefilter()
                        ->latest()
                        ->date()
                        ->paginate(paginateNumber(site_settings("paginate_number")))->onEachSide(1)
                        ->appends(request()->all());

        return view('user.report.credit', compact('title', 'creditLogs'));
    }


    public function generateApiKey()
    {
        $title = translate("Generate Api Key");
        $user = Auth::user();
        return view('user.generate_api_key', compact('title', 'user'));
    }

    public function saveGenerateApiKey(Request $request)
    {
        $user = Auth::user();
        $user->api_key  = $request->has('api_key') ? $request->input('api_key') : $user->api_key ;
        $user->save();

        return response()->json([
            'message' => 'New Api Key Has been Generate'
        ]);
    }


    public function defaultSmsMethod() {
        
        $title          = "SMS Send Method";
        $user           = Auth::user();
        $setting        = GeneralSetting::first();
        $allowed_access = planAccess($user);
        $general 		= GeneralSetting::first();
        
        if($allowed_access) {

            $allowed_access = (object)planAccess($user);
           
        } else {

            $notify[] = ['error','Please Purchase A Plan'];
            return redirect()->route('user.dashboard')->withNotify($notify);
        }
        
        if($allowed_access->type == StatusEnum::FALSE->status()) {

            $smsGateways      = Gateway::where('user_id', $user->id)->sms()->orderBy('is_default', 'DESC')->paginate(paginateNumber(site_settings("paginate_number")));
            $gatewaysForCount = Gateway::where('user_id', $user->id)->sms()->where('status',1)->get();
            $androids         = AndroidApi::where('user_id', auth()->user()->id)->orderBy('id', 'DESC')->paginate(paginateNumber(site_settings("paginate_number")));
        } else {

            $smsGateways      = Gateway::whereNull('user_id')->sms()->orderBy('is_default', 'DESC')->paginate(paginateNumber(site_settings("paginate_number")));
            $gatewaysForCount = Gateway::whereNull('user_id')->sms()->where('status',1)->get();
            $androids         = AndroidApi::whereNull('user_id')->orderBy('id', 'DESC')->paginate(paginateNumber(site_settings("paginate_number")));
        }
        
        $defaultGateway = Arr::get($user->gateway_credentials, 'sms.default_gateway_id',  $setting->sms_gateway_id);
        $credentials    = SmsGateway::orderBy('id','asc')->get();
        
        if(request()->routeIs('user.sms.gateway.sendmethod.gateway')) {

            return view("user.gateway.settings.gateway", compact('title', 'smsGateways', 'defaultGateway', 'androids', 'general'));
        }
        elseif(request()->routeIs('user.sms.gateway.sendmethod.api')) {

            if($allowed_access->sms["is_allowed"]) {

                $gatewayCount = $gatewaysForCount->groupBy('type')->map->count(); 
                return view("user.gateway.index", compact('title', 'smsGateways', 'defaultGateway', 'androids', 'credentials', 'user', 'gatewayCount', 'allowed_access', 'general'));
            } else {
                $notify[] = ['error', "You Do Not Have The Permission To Create SMS Gateway!"];
                return back()->withNotify($notify);
            }
           
        }
        elseif(request()->routeIs('user.gateway.sendmethod.android')) {

            return view("user.android.gateways", compact('title', 'smsGateways', 'defaultGateway', 'androids', 'allowed_access', 'general'));
        }
    }


    public function defaultSmsGateway(Request $request) {

        $request->validate([
            'sms_gateway'=>"required"
        ]);

        $user              = Auth::user();
        $user->sms_gateway = $request->input('sms_gateway');
        $user->save();

        $notify[] = ['success', 'Default Gateway Updated!!!'];
        return back()->withNotify($notify);
    }

    public function defaultCaptcha(int | string $randCode) :void {
        
        $phrase  = new PhraseBuilder;
        $code    = $phrase->build(4);
        $builder = new CaptchaBuilder($code, $phrase);
        $builder->setBackgroundColor(220, 210, 230);
        $builder->setMaxAngle(25);
        $builder->setMaxBehindLines(0);
        $builder->setMaxFrontLines(0);
        $builder->build($width = 100, $height = 40, $font = null);
        $phrase = $builder->getPhrase();
 
        if(Session::has('gcaptcha_code')) {
            Session::forget('gcaptcha_code');
        }
        Session::put('gcaptcha_code', $phrase);
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        $builder->output();
    }

    /**
     * Handle the unsubscribe request.
     */
    public function unsubscribe(Request $request) {

        $campaign_id = decrypt($request->get('campaign_id'));
        $contact_uid = decrypt($request->get('contact_id'));
        $channel     = $request->get('channel', ChannelTypeEnum::EMAIL->value);
        $user_id     = Campaign::find($campaign_id)->user_id;
        $already_unsubscribed = CampaignUnsubscribe::where('campaign_id', $campaign_id)
            ->where('contact_uid', $contact_uid)
            ->where('channel', $channel)
            ->exists();

        if (!$already_unsubscribed) {
            CampaignUnsubscribe::create([
                'user_id'     => $user_id,
                'contact_uid' => $contact_uid,
                'campaign_id' => $campaign_id,
                'channel'     => $channel,
                'meta_data'   => json_encode([
                    'unsubscribed_at' => now()
                ]),
            ]);
        }
        return redirect()->route('unsubscribe.success');
    }

    public function unsubscribeSuccess() {
        
        return view('frontend.sections.unsubscribe-success')->with([
            'title'   => translate('Campagin Unsubscription'),
            'message' => 'You have successfully unsubscribed from this campaign.',
            'logo'    => asset('images/unsubscribe-logo.png'),
            'size'    => "300x600", 
        ]);
    }
}
