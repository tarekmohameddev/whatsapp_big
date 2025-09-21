<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\PlanController;
use App\Http\Controllers\User\TemplateController;
use App\Http\Controllers\User\WithdrawController;
use App\Http\Controllers\User\Auth\LoginController;
use App\Http\Controllers\User\SupportTicketController;
use App\Http\Controllers\User\Auth\PasswordController;
use App\Http\Controllers\User\Auth\RegisterController;
use App\Http\Controllers\PaymentMethod\BkashController;
use App\Http\Controllers\PaymentMethod\CoinbaseCommerce;
use App\Http\Controllers\PaymentMethod\PaymentWithPaytm;
use App\Http\Controllers\User\Contact\ContactController;
use App\Http\Controllers\PaymentMethod\PaymentController;
use App\Http\Controllers\PaymentMethod\PaymentWithStripe;
use App\Http\Controllers\PaymentMethod\PaymentWithPaypal;
use App\Http\Controllers\Admin\Core\GlobalWorldController;
use App\Http\Controllers\PaymentMethod\PaymentWithRazorpay;
use App\Http\Controllers\PaymentMethod\PaymentWithPayStack;
use App\Http\Controllers\PaymentMethod\PaymentWithInstamojo;
use App\Http\Controllers\User\Contact\ContactGroupController;
use App\Http\Controllers\PaymentMethod\PaymentWithFlutterwave;
use App\Http\Controllers\User\Dispatch\CommunicationController;
use App\Http\Controllers\User\Contact\ContactSettingsController;
use App\Http\Controllers\User\Auth\GoogleAuthenticatedController;
use App\Http\Controllers\User\Auth\AuthenticatedSessionController;
use App\Http\Controllers\User\Auth\AuthorizationProcessController;
use App\Http\Controllers\User\Communication\SmsCampaignController;
use App\Http\Controllers\User\Communication\SmsDispatchController;
use App\Http\Controllers\PaymentMethod\SslCommerzPaymentController;
use App\Http\Controllers\User\Communication\EmailCampaignController;
use App\Http\Controllers\User\Communication\EmailDispatchController;
use App\Http\Controllers\User\Communication\WhatsappCampaignController;
use App\Http\Controllers\User\Communication\WhatsappDispatchController;
use App\Http\Controllers\User\Pipeline\IntegrationController as UserPipelineIntegrationController;
use App\Http\Controllers\User\Communication\Gateway\SmsGatewayController;
use App\Http\Controllers\User\Communication\Gateway\EmailGatewayController;
use App\Http\Controllers\User\Communication\Gateway\WhatsappDeviceController;
use App\Http\Controllers\User\Communication\Gateway\AndroidSessionController;
use App\Http\Controllers\User\Communication\Gateway\WhatsappCloudApiController;
use App\Http\Controllers\User\Communication\Gateway\AndroidSessionSimController;

## ------------------------------- ##
## Authentication Route Declartion ##
## ------------------------------- ##

Route::middleware([
            'guest',
            'maintenance',
            'check.domain',
            'domain.verified',
        ])->group(function () {

    Route::controller(RegisterController::class)
            ->middleware('registration')
            ->group(function() {

        Route::get('register/{uid?}', 'register')->name('register');
        Route::post('register/{uid?}', 'store')->name('register.store');
    });
 
    Route::middleware('login')
            ->group(function() {
 
        Route::controller(LoginController::class)
                ->group(function() {
            
            Route::get('/login', 'create')->name('login');
            Route::post('login', 'store')->name('login.store');
        });

        Route::controller(PasswordController::class)
                ->name('password.')
                ->group(function() {

            Route::post('forgot-password', 'store')->name('email');
            Route::get('forgot-password', 'create')->name('request');
            Route::get('password/resend/code', 'resendCode')->name('resend.code');
            Route::post('reset-password', 'updatePassword')->name('update');
            Route::get('reset-password/{token}', 'resetPassword')->name('reset');
            Route::get('password/code/verify', 'passwordResetCodeVerify')->name('verify.code');
            Route::post('password/code/verify', 'emailVerificationCode')->name('email.verify.code');
        });
    });
});
 
Route::middleware([
            'auth',
            'maintenance'
        ])->group(function () {
     
    Route::get('logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');
});
 
Route::controller(GoogleAuthenticatedController::class)
            ->group(function() {

    Route::get('auth/google', 'redirectToGoogle');
    Route::get('auth/google/callback', 'handleGoogleCallback');
});

## ------------------------------------- ##
## Authentication Route Declaration Ends ##
## ------------------------------------- ##

Route::middleware([
            'web',
            'auth',
            'sanitizer',
            'maintenance',
            'checkUserStatus',
        ])->prefix('user')
            ->name('user.')
            ->group(function () {
    
    ## Verfify Email Address Authenticity

    Route::post('/verify-email', [
                GlobalWorldController::class, 'verifyEmail'
            ])->name('verify.email');
    
    ## Email Verification Process

    Route::controller(AuthorizationProcessController::class)
                ->group(function() {

        Route::get('authorization', 'process')->name('authorization.process');
        Route::get('email/verification', 'sendNotification')->name('email.verification');
        Route::post('email/verification', 'processEmailVerification')->name('store.email.verification');
    });

    Route::middleware(['authorization', 'upgrade'])->group(function() {
        
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
                    
                Route::post('status/update', [ContactSettingsController::class, 'statusUpdate'])->name('status.update');
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
                    'index', 
                    'store', 
                    'update', 
                    'destroy'
                ]);
        
                Route::controller(ContactGroupController::class)->group(function () {
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
        Route::middleware(['allow.access'])
                ->prefix('gateway')
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
                        Route::post('webhook', 'webhook')->name('webhook');
                    });
                });

                // Evolution API Gateways
                Route::prefix('evolution')
                        ->name('evolution.')
                        ->group(function () {
                    Route::resource('/', \App\Http\Controllers\User\Communication\Gateway\WhatsappEvolutionApiController::class, [
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
        Route::middleware(['allow.access'])
                ->prefix('communication')
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
                            ])->names([
                                'index'     => 'index',
                                'create'    => 'create',
                                'store'     => 'store',
                            ]);

                            Route::controller(SmsDispatchController::class)->group(function () {
                                Route::get('index/{campaign_id?}', 'index')->name('index');
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
                    ])->names([
                        'index'     => 'index',
                        'create'    => 'create',
                        'store'     => 'store',
                    ]);

                    Route::controller(WhatsappDispatchController::class)->group(function () {
                        Route::get('index/{campaign_id?}', 'index')->name('index');
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
                        'store'
                    ]);

                    Route::controller(EmailDispatchController::class)->group(function () {
                        Route::get('index/{campaign_id?}', 'index')->name('index');
                        Route::get('show/{id}', 'show')->name('show');
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
                Route::controller(CommunicationController::class)->group(function () {
                    Route::get('api', 'api')->name('api');
                    Route::post('api/method/save/{type?}', 'apiSave')->name('api.method.save');
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
                Route::get('fetch/{type?}', 'fetch')->name('fetch');
                Route::get('create/{channel}', 'create')->name('create');
                Route::get('index/{channel}/{cloud_id?}', 'index')->name('index');
                Route::get('get/{uid}', 'templateJson')->name('get');
                Route::get('edit/json/{uid?}', 'editTemplateJson')->name('.edit.json');
                Route::post('status/update', 'updateStatus')->name('status.update');
            });

            // Evolution WhatsApp Templates (User)
            Route::prefix('whatsapp/evolution')
                ->name('whatsapp.evolution.')
                ->group(function () {
                    Route::get('/', [\App\Http\Controllers\User\Template\WhatsappEvolutionTemplateController::class, 'index'])->name('index');
                    Route::get('/create', [\App\Http\Controllers\User\Template\WhatsappEvolutionTemplateController::class, 'create'])->name('create');
                    Route::post('/', [\App\Http\Controllers\User\Template\WhatsappEvolutionTemplateController::class, 'store'])->name('store');
                    Route::get('/{uid}/edit', [\App\Http\Controllers\User\Template\WhatsappEvolutionTemplateController::class, 'edit'])->name('edit');
                    Route::patch('/{uid}', [\App\Http\Controllers\User\Template\WhatsappEvolutionTemplateController::class, 'update'])->name('update');
                    Route::delete('/{uid}', [\App\Http\Controllers\User\Template\WhatsappEvolutionTemplateController::class, 'destroy'])->name('destroy');

                        // Analytics and logs
                        Route::get('/analytics/clicks', [\App\Http\Controllers\User\Template\WhatsappEvolutionAnalyticsController::class, 'clicks'])->name('analytics.clicks');
                        Route::get('/analytics/clicks-summary', [\App\Http\Controllers\User\Template\WhatsappEvolutionAnalyticsController::class, 'clicksSummary'])->name('analytics.clicks_summary');
                        Route::get('/analytics/http-logs', [\App\Http\Controllers\User\Template\WhatsappEvolutionAnalyticsController::class, 'httpLogs'])->name('analytics.http_logs');
                });
        });

        ## ------------------- ##
        ## Withdraw Management ##
        ## ------------------- ##
        Route::prefix("withdraw")
                ->name("withdraw.")
                ->middleware(["check.affiliate.availability"])
                ->group(function() {

            Route::resource("/", WithdrawController::class, [
                "parameters" => ["" => "uid?"],
            ])->only([
                "index",
                "show",
                "create",
                "store",
            ]);
        });

        
        ## Old Routes

        //Templates
        // Route::controller(TemplateController::class)->prefix('template/')->name('template.')->group(function() {

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
        //     Route::get('refresh', 'refresh')->name('refresh');
        //     Route::post('save', 'save')->name('save');
        //     Route::post('status/update', 'statusUpdate')->name('status.update');
        //     Route::post('delete', 'delete')->name('delete');
        //     Route::get('fetch/{type?}', 'fetch')->name('fetch');
        // });


         //Report and logs
         Route::controller(HomeController::class)->prefix('report')->name('report.')->group(function() {

            Route::prefix('record/')->name("record.")->group(function() {

                Route::get('transaction', 'transaction')->name('transaction');
                Route::get('payment', 'paymentLog')->name('payment');
                Route::get('affiliate', 'affiliateLog')->middleware(["check.affiliate.availability"])->name('affiliate');
            });

            Route::prefix('credit/')->name("credit.")->group(function() {

                Route::get('sms/', 'credit')->name('sms');
                Route::get('whatsapp/', 'credit')->name('whatsapp');
                Route::get('email/', 'credit')->name('email');
            });
        });

        Route::controller(HomeController::class)->group(function() {

            Route::get('dashboard', 'dashboard')->name('dashboard');
            Route::get('profile', 'profile')->name('profile');
            Route::post('profile/update', 'profileUpdate')->name('profile.update');
            Route::get('password', 'password')->name('password');
            Route::post('password/update', 'passwordUpdate')->name('password.update');
            Route::get('generate/api-key', 'generateApiKey')->name('generate.api.key');
            Route::post('save/generate/api-key', 'saveGenerateApiKey')->name('save.generate.api.key');
        });

        // Pipelines & Integrations (User)
        Route::prefix('pipelines')
                ->name('pipelines.')
                ->group(function () {
            Route::resource('integrations', UserPipelineIntegrationController::class, [
                'parameters' => ['integrations' => 'uid?'],
            ])->only([
                'index', 'create', 'store', 'edit', 'update', 'destroy'
            ]);
        });

        //Messaging Gateways
        // Route::middleware(['allow.access'])->prefix('gateway/')->name('gateway.')->group(function() {

        //     //SMS Gateways
        //     Route::prefix('sms/')->name('sms.')->group(function() {

        //         //Android Gateways
        //         Route::controller(AndroidApiController::class)->prefix('android/')->name('android.')->group(function() {
                    
        //             Route::get('index', 'index')->name('index');
        //             Route::post('store', 'store')->name('store');
        //             Route::post('update', 'update')->name('update');
        //             Route::post('/status/update', 'statusUpdate')->name('status.update');
        //             Route::post('delete/', 'delete')->name('delete');
        //             Route::post('/bulk/action','bulk')->name('bulk');
        //             Route::prefix('link/')->name('link.')->group(function() {

        //                 Route::post('store', 'linkStore')->name('store');
        //             });
        //             Route::prefix('sim/')->name('sim.')->group(function() {

        //                 Route::get('list/{id?}', 'simList')->name('index');
        //                 Route::post('/bulk/action','simBulk')->name('bulk');
        //                 Route::post('delete/', 'simNumberDelete')->name('delete');
        //             });
        //         });

        //         //API Gateways
        //         Route::controller(SmsGatewayController::class)->prefix('api/')->name('api.')->group(function () {

        //             Route::get('index', 'index')->name('index');
        //             Route::post('/status/update', 'statusUpdate')->name('status.update');
        //             Route::post('delete', 'delete')->name('delete');
        //             Route::post('store', 'store')->name('store');
        //             Route::post('update', 'update')->name('update');
        //             Route::post('/bulk/action','bulk')->name('bulk');
        //         });
        //     });

        //     //WhatsApp Gateways
        //     Route::prefix('whatsapp/')->name('whatsapp.')->group(function() {
                
        //         Route::controller(WhatsappDeviceController::class)->prefix('device/')->name('device')->group(function() {

        //             Route::get('', 'index');
        //             Route::post('save', 'save')->name('.save');
        //             Route::post('status/update', 'statusUpdate')->name('.status.update');
        //             Route::post('delete', 'delete')->name('.delete');
                    
        //             Route::prefix('server/')->name('.server.')->group(function() {

        //                 Route::post('update', 'updateServer')->name('update');
        //                 Route::post('qr-code', 'whatsappQRGenerate')->name('qrcode');
        //                 Route::post('status', 'getDeviceStatus')->name('status');
        //             });
        //         });
                
        //         Route::controller(WhatsappCloudApiController::class)->prefix('cloud/api')->name('cloud.api')->group(function() {

        //             Route::get('{id?}', 'index');
        //             Route::post('webhook', 'webhook')->name('.webhook');
        //             Route::post('save', 'save')->name('.save');
        //             Route::post('status/update', 'statusUpdate')->name('.status.update');
        //             Route::post('delete', 'delete')->name('.delete');
        //         });
        //     });

        //     //Email Gateways
        //     Route::controller(EmailGatewayController::class)->prefix('email/')->name('email.')->group(function() {
                
        //         Route::get('index', 'index')->name('index');
        //         Route::post('test', 'testGateway')->name('test');
        //         Route::post('store', 'store')->name('store');
        //         Route::post('update', 'update')->name('update');
        //         Route::post('delete', 'delete')->name('delete');
        //         Route::post('status/update', 'statusUpdate')->name('status.update');
        //     });
        // });

        Route::controller(PlanController::class)->prefix('plans/')->name('plan.')->group(function () {

            Route::get('', 'create')->name('create');
            Route::get('make/payment/{id?}', 'makePayment')->name('make.payment');
            Route::post('store', 'store')->name('store');
            Route::get('subscriptions', 'subscription')->name('subscription');
            Route::post('renew', 'subscriptionRenew')->name('renew');
        });

        Route::controller(PaymentController::class)->group(function() {

            Route::get('payment/preview', 'preview')->name('payment.preview');
            Route::get('payment/confirm/{id?}', 'paymentConfirm')->name('payment.confirm');
            Route::get('manual/payment/confirm', 'manualPayment')->name('manual.payment.confirm');
            Route::post('manual/payment/update', 'manualPaymentUpdate')->name('manual.payment.update');
        });

        Route::controller(PaymentWithStripe::class)->group(function() {

            Route::post('ipn/strip', 'stripePost')->name('payment.with.strip');
            Route::get('/strip/success', 'success')->name('payment.with.strip.success');
        });

        Route::controller(PaymentWithPaypal::class)->group(function() {

            Route::post('ipn/paypal', 'postPaymentWithpaypal')->name('payment.with.paypal');
            Route::get('ipn/paypal/status/{trx_code?}/{id?}/{status?}', 'getPaymentStatus')->name('payment.paypal.status');
        });

        Route::get('ipn/paystack', [PaymentWithPayStack::class, 'store'])->name('payment.with.paystack');

        Route::controller(SslCommerzPaymentController::class)->group(function() {

            Route::post('ipn/pay/with/sslcommerz', 'index')->name('payment.with.ssl');
            Route::post('success', 'success');
            Route::post('fail', 'fail');
            Route::post('cancel', 'cancel');
            Route::post('/ipn', 'ipn');
        });

        Route::controller(PaymentWithPaytm::class)->group(function() {
            
            Route::post('ipn/paytm/process', 'getTransactionToken')->name('paytm.process');
            Route::post('ipn/paytm/callback', 'ipn')->name('paytm.ipn');
        });

        Route::controller(PaymentWithFlutterwave::class)->group(function() {

            Route::get('flutter-wave/{trx}/{type}', 'callback')->name('flutterwave.callback');
            
        });

        Route::controller(PaymentWithRazorpay::class)->group(function() {

            Route::post('ipn/razorpay', 'ipn')->name('razorpay');
            
        });

        Route::controller(PaymentWithInstamojo::class)->group(function() {

            Route::get('instamojo', 'process')->name('instamojo');
            Route::post('ipn/instamojo', 'ipn')->name('ipn.instamojo');
        });

        Route::controller(CoinbaseCommerce::class)->group(function() {

            Route::get('ipn/coinbase', 'store')->name('coinbase');
            Route::any('ipn/callback/coinbase', 'confirmPayment')->name('callback.coinbase');
        });

        Route::controller(BkashController::class)->group(function() {

            Route::get('ipn/bkash', 'confirmPayment')->name('bkash');
            Route::any('payment/callback/{trx_code?}/{type?}','callBack')->name('bkash.callback');
        });

        Route::prefix('support/')->name('support.')->group(function () {

            Route::controller(SupportTicketController::class)->prefix('ticket/')->name('ticket.')->group(function() {

                Route::get('create', 'create')->name('create');
                Route::post('store',  'store')->name('store');
                
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
    });
});
