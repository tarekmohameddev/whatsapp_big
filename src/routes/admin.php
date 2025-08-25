<?php

use App\Http\Controllers\Admin\AiContentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\Core\BlogController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Core\AdminController;
use App\Http\Controllers\Admin\Core\ReportController;
use App\Http\Controllers\Admin\Core\SettingController;
use App\Http\Controllers\Admin\Core\LanguageController;
use App\Http\Controllers\Admin\Core\CurrencyController;
use App\Http\Controllers\Admin\Core\CustomerController;
use App\Http\Controllers\Admin\Contact\ContactController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Core\GlobalWorldController;
use App\Http\Controllers\Admin\Core\PricingPlanController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Contact\ContactGroupController;
use App\Http\Controllers\Admin\Core\FrontendSectionController;
use App\Http\Controllers\Admin\Ticket\SupportTicketController;
use App\Http\Controllers\Admin\Payment\PaymentGatewayController;
use App\Http\Controllers\Admin\Payment\WithdrawMethodController;
use App\Http\Controllers\Admin\Dispatch\CommunicationController;
use App\Http\Controllers\Admin\Contact\ContactSettingsController;

use App\Http\Controllers\Admin\Communication\SmsCampaignController;
use App\Http\Controllers\Admin\Communication\SmsDispatchController;
use App\Http\Controllers\Admin\Communication\EmailCampaignController;
use App\Http\Controllers\Admin\Communication\EmailDispatchController;
use App\Http\Controllers\Admin\Communication\WhatsappCampaignController;
use App\Http\Controllers\Admin\Communication\WhatsappDispatchController;

use App\Http\Controllers\Admin\Communication\Gateway\SmsGatewayController;
use App\Http\Controllers\Admin\Communication\Gateway\EmailGatewayController;
use App\Http\Controllers\Admin\Communication\Gateway\WhatsappDeviceController;
use App\Http\Controllers\Admin\Communication\Gateway\AndroidSessionController;
use App\Http\Controllers\Admin\Communication\Gateway\WhatsappCloudApiController;
use App\Http\Controllers\Admin\Communication\Gateway\AndroidSessionSimController;

Route::middleware([
            'check.domain',
            'domain.verified',
        ])->prefix('admin')
            ->name('admin.')
            ->group(function () {
    
    ## ---------------- ##
    ## Authentification ##
    ## ---------------- ##

    Route::controller(SystemUpdateController::class)
            ->name('system.')
            ->prefix('system/')
            ->group(function () {

        Route::any('/update/init', 'init')->name('update.init');
        Route::post('/update', 'update')->name('update');
        Route::get('check/update', 'checkUpdate')->name('check.update');
        Route::post('install/update', 'installUpdate')->name('install.update');
    });
            
    Route::controller(LoginController::class)
            ->group(function () {

        Route::get('/', 'showLogin')->name('login');
        Route::post('authenticate', 'authenticate')->name('authenticate');
        Route::get('logout', 'logout')->name('logout');
    });

    Route::controller(NewPasswordController::class)
            ->group(function () {

        Route::get('forgot-password', 'create')->name('password.request');
        Route::post('password/email', 'store')->name('password.email');
        Route::get('password/verify/code', 'passwordResetCodeVerify')->name('password.verify.code');
        Route::post('password/code/verify', 'emailVerificationCode')->name('email.password.verify.code');
    });

    Route::controller(ResetPasswordController::class)
            ->group(function () {

        Route::get('reset-password/{token}', 'create')->name('password.reset');
        Route::post('reset/password', 'store')->name('password.reset.update');
    });

    
    Route::middleware([
            'admin',
            'demo.mode',
            'sanitizer'
            ])->group(function () {

        Route::post('/verify-email', [GlobalWorldController::class, 'verifyEmail'])
                ->name('verify.email');

        ## ------------------ ##
        ## Contact Management ##
        ## ------------------ ##
        Route::prefix('contacts')
                ->name('contact.')
                ->group(function () {

            # Contact Settings Routes
            Route::prefix('settings')
                    ->name('settings.')
                    ->group(function () {

                Route::resource('/', ContactSettingsController::class, [
                    'parameters' => [
                        '' => 'attribute_name?'
                    ], 
                ])->only([
                    'index', 
                    'create', 
                    'store', 
                    'destroy'
                ])->names([
                    'index'     => 'index',
                    'create'    => 'create',
                    'store'     => 'save',  
                    'destroy'   => 'delete',
                ]);
                    
                Route::post('status/update', [ContactSettingsController::class, 'statusUpdate'])
                        ->name('status.update');
            });

            # Contact Groups 
            Route::prefix('groups')
                    ->name('group.')
                    ->group(function () {

                Route::resource('/', ContactGroupController::class, [
                    'parameters' => [
                        '' => 'uid?'
                    ],
                ])->only([
                    'store', 
                    'update', 
                    'destroy'
                ]);
        
                Route::controller(ContactGroupController::class)->group(function () {

                    Route::get('index/{uid?}', 'index')->name('index');
                    Route::post('status/update', 'updateStatus')->name('status.update');
                    Route::post('bulk/action', 'bulk')->name('bulk');
                    Route::post('fetch/{type?}', 'fetch')->name('fetch');
                    Route::get('import-progress', 'getImportProgress')->name('import.progress');
                });
            });
            
            # Contacts
            Route::resource('/', ContactController::class, [
                'parameters' => ['' => 'uid?'], 
            ])->only([
                'index', 
                'create', 
                'store', 
                'update', 
                'destroy'
            ]);
        
            Route::controller(ContactController::class)->group(function () {

                Route::get('index/{group_id?}', 'index')->name('index'); 
                Route::get('create/{group_id?}', 'create')->name('create.with_group'); 
                Route::post('status/update', 'updateStatus')->name('status.update');
                Route::post('bulk/action', 'bulk')->name('bulk');
                Route::post('upload/file', 'uploadFile')->name('upload.file');
                Route::post('delete/file', 'deleteFile')->name('delete.file');
                Route::post('parse/file', 'parseFile')->name('parse.file');
                Route::get('demo/file/{type?}', 'demoFile')->name('demo.file');
                Route::post('update/email/verification', 'singleEmailVerification')->name('update.email.verification');
                Route::post('export/{group_id?}', 'exportContacts')->name('export');
            });
        });

        ## ------------------ ##
        ## Gateway Management ##
        ## ------------------ ##
        Route::prefix('gateway')
                ->name('gateway.')
                ->group(function () {

            // SMS Gateways
            Route::prefix('sms')
                    ->name('sms.')
                    ->group(function () {

                // Android Gateways
                Route::prefix('android')
                        ->name('android.')
                        ->group(function () {

                    Route::resource('/', AndroidSessionController::class, [
                                'parameters' => ['' => 'id?'],
                            ])->only([
                                'index',
                                'store',
                                'update',
                                'destroy',
                            ])->names([
                                'index' => 'index',
                                'store' => 'store',
                                'update' => 'update',
                                'destroy' => 'delete',
                            ]);

                    Route::controller(AndroidSessionController::class)
                            ->group(function () {

                        Route::post('status/update', 'statusUpdate')->name('status.update');
                        Route::post('bulk/action', 'bulk')->name('bulk');
                    });
                    Route::prefix('sim')
                                ->name('sim.')
                                ->group(function () {


                            Route::resource('/', AndroidSessionSimController::class, [
                                        'parameters' => ['' => 'id?'],
                                    ])->only([
                                        'update',
                                        'destroy',
                                    ])->names([
                                        'update' => 'update',
                                        'destroy' => 'delete',
                                    ])->except([
                                            'index',
                                            'store'
                                        ]) ;
                            Route::controller(AndroidSessionSimController::class)
                                    ->group(function () { 
                                Route::get('index/{token?}', [AndroidSessionSimController::class, 'index'])->name('index');
                                Route::post('status/update', 'statusUpdate')->name('status.update');
                                Route::post('bulk/action', 'bulk')->name('bulk');
                            });
                        });
                });

                // API Gateways
                    Route::prefix('api')
                            ->name('api.')
                            ->group(function () {
                        Route::resource('/', SmsGatewayController::class, [
                            'parameters' => ['' => 'id?'],
                        ])->only([
                            'index',
                            'store',
                            'update',
                            'destroy',
                        ])->names([
                            'index' => 'index',
                            'store' => 'store',
                            'update' => 'update',
                            'destroy' => 'delete',
                        ]);

                        Route::controller(SmsGatewayController::class)
                                ->group(function () {

                            Route::post('status/update', 'updateStatus')->name('status.update');
                            Route::post('bulk/action', 'bulk')->name('bulk');
                        });
                    });
            });

            // WhatsApp Gateways
            Route::prefix('whatsapp')
                    ->name('whatsapp.')
                    ->group(function () {

                // Device Gateways
                Route::prefix('device')
                        ->name('device.')
                        ->group(function () {

                    Route::resource('/', WhatsappDeviceController::class, [
                        'parameters' => ['' => 'id?'],
                    ])->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);

                    Route::controller(WhatsappDeviceController::class)
                            ->group(function () {

                        Route::post('status/update', 'statusUpdate')->name('status.update');

                        Route::prefix('server')
                                ->name('server.')
                                ->group(function () {

                                Route::post('update', 'updateServer')->name('update');
                                Route::post('qr-code', 'whatsappQRGenerate')->name('qrcode');
                                Route::post('status', 'getDeviceStatus')->name('status');
                        });
                    });
                });

                // Cloud API Gateways
                Route::prefix('cloud/api')
                        ->name('cloud.api.')
                        ->group(function () {
                    Route::resource('/', WhatsappCloudApiController::class, [
                        'parameters' => ['' => 'id?'],
                    ])->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);

                    Route::controller(WhatsappCloudApiController::class)
                            ->group(function () {
                                
                        Route::post('status/update', 'statusUpdate')->name('status.update');
                    });
                });

                // Evolution API Gateways
                Route::prefix('evolution')
                        ->name('evolution.')
                        ->group(function () {
                    Route::resource('/', \App\Http\Controllers\Admin\Communication\Gateway\WhatsappEvolutionApiController::class, [
                        'parameters' => ['' => 'id?'],
                    ])->only([
                        'index',
                        'store',
                        'update',
                        'destroy',
                    ]);
                });
            });

            // Email Gateways
            Route::prefix('email')
                    ->name('email.')
                    ->group(function () {
                Route::resource('/', EmailGatewayController::class, [
                    'parameters' => ['' => 'id?'],
                ])->only([
                    'index',
                    'store',
                    'update',
                    'destroy',
                ])->names([
                    'index' => 'index',
                    'store' => 'store',
                    'update' => 'update',
                    'destroy' => 'delete',
                ]);

                Route::controller(EmailGatewayController::class)
                        ->group(function () {

                    Route::post('test', 'testGateway')->name('test');
                    Route::post('status/update', 'updateStatus')->name('status.update');
                });
            });
        });

        ## ------------------- ##
        ## Dispatch Management ##
        ## ------------------- ##
        Route::prefix('communication')
                ->name('communication.')
                ->group(function () {

            // SMS Dispatches
            Route::prefix('sms')
                    ->name('sms.')
                    ->group(function () {
                        Route::resource('/', SmsDispatchController::class, [
                            'parameters' => ['' => 'id?'],
                        ])->only([
                            'index',
                            'create',
                            'store',
                            'destroy',
                        ])->names([
                            'index'     => 'index',
                            'create'    => 'create',
                            'store'     => 'store',
                            'destroy'   => 'delete',
                        ]);

                        Route::controller(SmsDispatchController::class)->group(function () {
                            Route::get('index/{campaign_id?}', 'index')->name('index');
                            Route::post('bulk/action', 'bulk')->name('bulk');
                            Route::post('status/update', 'updateStatus')->name('status.update');
                        });

                        // SMS Campaigns
                        Route::prefix('campaign')
                                ->name('campaign.')
                                ->group(function () {
                                    Route::resource('/', SmsCampaignController::class, [
                                        'parameters' => ['' => 'id?'],
                                    ])->only([
                                        'index',
                                        'create',
                                        'store',
                                        'edit',
                                        'update',
                                        'destroy',
                                    ]);

                                    Route::controller(SmsCampaignController::class)->group(function () {
                                        Route::post('bulk/action', 'bulk')->name('bulk');
                                    });
                        });
            });

            // WhatsApp Dispatches
            Route::prefix('whatsapp')
                    ->name('whatsapp.')
                    ->group(function () {
                Route::resource('/', WhatsappDispatchController::class, [
                    'parameters' => ['' => 'id?'],
                ])->only([
                    'index',
                    'create',
                    'store',
                    'destroy',
                ])->names([
                    'index'     => 'index',
                    'create'    => 'create',
                    'store'     => 'store',
                    'destroy'   => 'delete',
                ]);

                Route::controller(WhatsappDispatchController::class)->group(function () {
                    Route::get('index/{campaign_id?}', 'index')->name('index');
                    Route::post('bulk/action', 'bulk')->name('bulk');
                    Route::post('status/update', 'updateStatus')->name('status.update');
                });

                // WhatsApp Campaigns
                Route::prefix('campaign')
                        ->name('campaign.')
                        ->group(function () {
                            Route::resource('/', WhatsappCampaignController::class, [
                                'parameters' => ['' => 'id?'],
                            ])->only([
                                'index',
                                'create',
                                'store',
                                'edit',
                                'update',
                                'destroy',
                            ]);

                            Route::controller(WhatsappCampaignController::class)->group(function () {
                                Route::post('bulk/action', 'bulk')->name('bulk');
                            });
                });
            });

            // Email Dispatches
            Route::prefix('email')
                    ->name('email.')
                    ->group(function () {
                Route::resource('/', EmailDispatchController::class, [
                    'parameters' => ['' => 'id?'],
                ])->only([
                    'index',
                    'create',
                    'store',
                    'destroy',
                ])->names([
                    'destroy'   => 'delete',
                ]);

                Route::controller(EmailDispatchController::class)->group(function () {
                    Route::get('index/{campaign_id?}', 'index')->name('index');
                    Route::get('show/{id}', 'show')->name('show');
                    Route::post('bulk/action', 'bulk')->name('bulk');
                    Route::post('status/update', 'updateStatus')->name('status.update');
                });

                // Email Campaigns
                Route::prefix('campaign')
                                ->name('campaign.')
                                ->group(function () {
                                    Route::resource('/', EmailCampaignController::class, [
                                        'parameters' => ['' => 'id?'],
                                    ])->only([
                                        'index',
                                        'create',
                                        'store',
                                        'edit',
                                        'update',
                                        'destroy',
                                    ]);

                                    Route::controller(EmailCampaignController::class)->group(function () {
                                        Route::post('bulk/action', 'bulk')->name('bulk');
                                    });
                        });
            });

            // API Route for Android App (to be implemented later)
            Route::controller(CommunicationController::class)->group(function () {
                Route::get('api', 'api')->name('api');
            });
        });

        ## ------------------- ##
        ## Template Management ##
        ## ------------------- ##
        Route::prefix("template")
                ->name("template.")
                ->group(function() {

            Route::resource("/", TemplateController::class, [
                "parameters" => ["" => "uid?"],
            ])->only([
                "edit",
                "store",
                "update",
                "destroy"
            ]);

            Route::controller(TemplateController::class)
                        ->group(function () {

                Route::get('refresh', 'refresh')->name('refresh');
                Route::get('email/templates', 'emailTemplates')->name('email.templates');
                Route::get('fetch/{channel?}', 'fetch')->name('fetch');
                Route::get('create/{channel}', 'create')->name('create');
                Route::get('index/{channel}/{cloud_id?}', 'index')->name('index');
                Route::get('get/{uid}', 'templateJson')->name('get');
                Route::get('edit/json/{uid?}', 'editTemplateJson')->name('.edit.json');
                Route::post('status/update', 'updateStatus')->name('status.update');
                Route::post('approve', 'approve')->name('approve');
            });
        });

        ## --------------- ##
        ## Withdraw Method ##
        ## --------------- ##
        Route::prefix('payment/')
                ->name('payment.')
                ->group(function() {


            Route::resource("withdraw", WithdrawMethodController::class, [
                "parameters" => ["" => "uid?"],
            ])->except(["show"]);

            Route::controller(WithdrawMethodController::class)
                        ->prefix("withdraw/")
                        ->name("withdraw.")
                        ->group(function () {
                Route::post('status/update', 'updateStatus')->name('status.update');
            });
            
            Route::controller(PaymentGatewayController::class)
                    ->group(function () { 

                Route::get('automatic/index', 'index')->name('automatic.index');
                Route::get('manual/index', 'index')->name('manual.index');
                Route::get('create', 'create')->name('create');
                Route::post('store', 'store')->name('store');
                Route::get('edit/{id}/{slug?}', 'edit')->name('edit');
                Route::post('/status/update', 'statusUpdate')->name('status.update');
                Route::post('automatic/update/{id}', 'automaticUpdate')->name('automatic.update');
                Route::post('manual/update/{id}', 'manualUpdate')->name('manual.update');
                Route::post('delete', 'delete')->name('delete');
            });
        });

        ## --------------------- ##
        ## AI Content Management ##
        ## --------------------- ##
        Route::prefix('ai')
                ->name('ai.')
                ->group(function () {

            Route::controller(AiContentController::class)
                    ->prefix('content/')
                    ->name('content.')
                    ->group(function () {

                Route::post('generate/text', 'generateText')->name('generate.text');
                Route::post('generate/image', 'generateImage')->name('generate.image');
            });
        });
        



                
        
        // Route::controller(TemplateController::class)
        //         ->prefix('template/')
        //         ->name('template.')
        //         ->group(function() {

        //     Route::prefix('sms/')->name('sms')->group(function() {

        //         Route::get('', 'index');
        //         Route::get('user', 'index')->name('.user');
        //     });
        //     Route::prefix('email/')->name('email')->group(function() {

        //         Route::get('', 'index');
        //         Route::get('create', 'createEmailTemplate')->name('.create');
        //         Route::get('edit/{id?}', 'editEmailTemplate')->name('.edit');
        //         Route::get('edit/json/{id?}', 'editTemplateJson')->name('.edit.json');
        //         Route::get('get/{id?}', 'templateJson')->name('.get');
        //         Route::get('user', 'index')->name('.user');
        //         Route::get('fetch', 'emailTemplates')->name('.fetch');
        //     });
        //     Route::get('whatsapp/{id?}', 'index')->name('whatsapp.index');
            
        //     Route::post('save', 'save')->name('save');
            
        //     Route::post('delete', 'delete')->name('delete');
            
        // });




        ## Old Functions
        
        //Admin Panel 
        Route::controller(AdminController::class)->group(function () {

            //Dashboard
            Route::get('dashboard', 'dashboard')->name('dashboard');

            //Admin Account
            Route::get('profile', 'profile')->name('profile');
            Route::post('profile/update', 'profileUpdate')->name('profile.update');
            Route::post('password/update', 'passwordUpdate')->name('password.update');
        });

        //Manage Customer
        Route::controller(CustomerController::class)->prefix('user/')->name('user.')->group(function () {

            Route::get('', 'index')->name('index');
            Route::get('trashed', 'trashed')->name('trashed');
            Route::get('active/', 'index')->name('active');
            Route::get('banned/', 'index')->name('banned');
            Route::get('detail/{uid}', 'details')->name('details');
            Route::get('login/{uid}', 'login')->name('login');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('store', 'store')->name('store');
            Route::post('modify/credit', 'modifyCredit')->name('modify.credit');

            Route::post('delete/{uid?}', 'softDelete')->name('soft.delete');
            Route::post('restore/{uid?}', 'restore')->name('restore');

            Route::delete('/{uid?}', 'destroy')->name('destroy');
            Route::get('delete-progress', 'deleteProgress')->name('delete.progress');
        });

        //Manage Membership Plans
        Route::controller(PricingPlanController::class)->prefix('membership/plan/')->name('membership.plan.')->group(function() {

            Route::get('index', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('edit/{id}', 'edit')->name('edit');
            Route::post('update', 'update')->name('update');
            Route::post('delete', 'delete')->name('delete');
            Route::post('status/update/', 'statusUpdate')->name('status.update');
            Route::post('bulk/action/', 'bulk')->name('bulk');
        });

        // Manage Frontend Section 
        Route::controller(FrontendSectionController::class)
                ->prefix('frontend/section/')
                ->name('frontend.sections.')
                ->group(function () {
        
            Route::get('{section_key}/{type?}', 'index')->name('index');
            Route::post('/save/content/{section_key}/{type?}', 'saveFrontendSectionContent')->name('save.content');
            Route::get('/element/content/{section_key}/{type?}/{id?}', 'getFrontendSectionElement')->name('element.content');
            Route::post('/element/delete/', 'delete')->name('element.delete');
        });

        //Settings
        Route::prefix('system/')
                ->name('system.')
                ->group(function () {

            Route::prefix('language/')
                    ->controller(LanguageController::class)
                    ->name('language.')
                    ->group(function () {
    
                Route::get('', 'index')->name('index');
                Route::get('translate/{code?}', 'translate')->name('translate');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::delete('delete/{id?}', 'delete')->name('delete');
                Route::post('/status/update', 'updateStatus')->name('status.update');

                Route::prefix('data/')->name('data.')->group(function() {

                    Route::post('update', 'languageDataUpdate')->name('update');
                    Route::post('delete', 'languageDataDelete')->name('delete');
                });
            });

            Route::controller(SystemController::class)->group(function() {

                Route::get('/cache/clear', 'cacheClear')->name('cache.clear');
                Route::get("info/", 'systemInfo')->name('info');
            });
            
            Route::controller(SettingController::class)->group(function() {

                Route::get('setting/{type?}', 'index')->name('setting');
                Route::post('setting/store', 'store')->name('setting.store');
            });

            Route::controller(CurrencyController::class)->prefix('currency/')->name('currency.')->group(function () {

                Route::get('/', 'index')->name('index');
                Route::get('active', 'index')->name('active');
                Route::get('inactive', 'index')->name('inactive');
                Route::post('/store', 'save')->name('store');
                Route::post('/update', 'save')->name('update');
                Route::post('/status/update', 'statusUpdate')->name('status.update');
                Route::post('/delete', 'delete')->name('delete');
            });
    
            

            Route::controller(GlobalWorldController::class)->prefix('spam/word/')->name('spam.word.')->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('delete', 'delete')->name('delete');
            });
        });
        
        //Support Ticket
        Route::prefix('support/')->name('support.')->group(function () {

            Route::controller(SupportTicketController::class)->prefix('ticket/')->name('ticket.')->group(function() {

                Route::get('/', 'index')->name('index');
                Route::get('closed', 'index')->name('closed');
                Route::get('running', 'index')->name('running');
                Route::get('replied', 'index')->name('replied');
                Route::get('answered', 'index')->name('answered');

                Route::prefix('priority/')->name('priority.')->group(function () {
                    
                    Route::get('high', 'index')->name('high');
                    Route::get('medium', 'index')->name('medium');
                    Route::get('low', 'index')->name('low');
                });
                
                Route::post('reply/{id}', 'ticketReply')->name('reply');
                Route::post('closed/{id}', 'closedTicket')->name('closeds');
                Route::get('details/{id}', 'ticketDetails')->name('details');
                Route::get('download/{id}', 'supportTicketDownload')->name('download');
            });
        });

        //Report and logs
        Route::controller(ReportController::class)->prefix('report')->name('report.')->group(function() {

            Route::prefix('record/')->name("record.")->group(function() {

                Route::get('transaction', 'transaction')->name('transaction');
                Route::get('subscription', 'subscription')->name('subscription');
                Route::get('payment', 'paymentLog')->name('payment');
                Route::get('withdraw', 'withdrawLogs')->name('withdraw');
                Route::get('affiliate', 'affiliateLogs')->name('affiliate');
            });

            Route::prefix('credit/')->name("credit.")->group(function() {

                Route::get('sms/', 'credit')->name('sms');
                Route::get('whatsapp/', 'credit')->name('whatsapp');
                Route::get('email/', 'credit')->name('email');
            });
            
            Route::get('payment/detail/{id}', 'paymentDetail')->name('payment.detail');
            Route::post('payment/approve', 'approve')->name('payment.approve');
            Route::post('payment/reject', 'reject')->name('payment.reject');

            Route::get('withdraw/detail/{trx_code?}', 'withdrawDetail')->name('withdraw.detail');
            Route::post('withdraw/status/update/{trx_code?}', 'updateWithdrawStatus')->name('withdraw.status.update');
        });
        
        Route::controller(BlogController::class)->prefix('blog/')->name('blog.')->group(function() {

            Route::get("index/", "index")->name("index");
            Route::get("create", "create")->name("create");
            Route::get("edit/{uid}", "edit")->name("edit");
            Route::post("save", "save")->name("save");
            Route::post("delete", "delete")->name("delete");
            Route::post('status/update', 'statusUpdate')->name('status.update');
            Route::post('/bulk/action','bulk')->name('bulk');
        });
    });
});