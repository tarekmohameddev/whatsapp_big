<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\CoreController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\Admin\Core\GlobalWorldController;



Route::get('queue-work', [QueueController::class, 'processAllQueues'])
    ->name('queue.work');

Route::get('queue-work/default', [QueueController::class, 'processDefault'])
    ->name('queue.work.default');
Route::get('queue-work/dispatch-logs', [QueueController::class, 'processDispatchlogs'])
    ->name('queue.work.dispatch-logs');
Route::get('queue-work/regular-sms', [QueueController::class, 'processRegularSms'])
    ->name('queue.work.regular-sms');
Route::get('queue-work/regular-email', [QueueController::class, 'processRegularEmail'])
    ->name('queue.work.regular-email');
Route::get('queue-work/regular-whatsapp', [QueueController::class, 'processRegularWhatsapp'])
    ->name('queue.work.regular-whatsapp');
Route::get('queue-work/campaign-sms', [QueueController::class, 'processCampaignSms'])
    ->name('queue.work.campaign-sms');
Route::get('queue-work/campaign-email', [QueueController::class, 'processCampaignEmail'])
    ->name('queue.work.campaign-email');
Route::get('queue-work/campaign-whatsapp', [QueueController::class, 'processCampaignWhatsapp'])
    ->name('queue.work.campaign-whatsapp');
Route::get('queue-work/import-contacts', [QueueController::class, 'processContactImport'])
    ->name('queue.work.import-contacts');
Route::get('queue-work/verify-email', [QueueController::class, 'processEmailVerify'])
    ->name('queue.work.verify-email');

Route::get('cron/run', [CronController::class, 'run'])->name('cron.run');

Route::middleware([
    'check.domain',
    'domain.verified',
])->group(function () { 

    Route::controller(WebController::class)->middleware(['redirect.to.login'])->group(function () {
        Route::get('/', 'index')->name('home');
        Route::get('service/{type?}', 'service')->name('service');
        Route::get('blog/search', 'blogSearch')->name('blog.search');
        Route::get('blog/{uid?}', 'blog')->name('blog');
        Route::get('about/', 'about')->name('about');
        Route::get('pricing/', 'pricing')->name('pricing');
        Route::get('contact/', 'contact')->name('contact');
        Route::post('contact/', 'getInTouch')->name('contact.get_in_touch');
        Route::get('/pages/{key}/{id}', 'pages')->name('page');
    });
    
    
    Route::controller(FrontendController::class)->group(function() {
    
        Route::get('/default/image/{size}', 'defaultImageCreate')->name('default.image');
        Route::get('email/contact/demo/file', 'demoImportFile')->name('email.contact.demo.import');
        Route::get('sms/demo/import/file', 'demoImportFilesms')->name('phone.book.demo.import.file');
        Route::get('demo/file/download/{extension}/{type}', 'demoFileDownloader')->name('demo.file.download');  
        Route::get('api/document', 'apiDocumentation')->name('api.document');
    });
    
    Route::get('/default-captcha/{randCode}', [HomeController::class, 'defaultCaptcha'])->name('captcha.genarate');
    Route::any('/webhook', [WebhookController::class, 'postWebhook'])->name('webhook');
    Route::any('/facebook/login', [MetaController::class, 'facebookLogin'])->name('facebook.login');

      Route::get('/api/translations/{lang_code}', [GlobalWorldController::class, 'getTranslations'])->name('api.translations');
    Route::get('/language/change/{lang?}', [GlobalWorldController::class, 'languageChange'])->name('language.change');
    
    Route::get('/unsubscribe', [HomeController::class, 'unsubscribe'])->name('unsubscribe');
    Route::get('/unsubscribe/success', [HomeController::class, 'unsubscribeSuccess'])->name('unsubscribe.success');
    
    Route::get('/domain-unverified', [CoreController::class, 'domainNotVerified'])->name('domain.unverified')->withoutMiddleware(['domain.verified' , 'check.domain']);
    
    Route::post('/check-license', [CoreController::class, 'checkLicense'])->name('check.license.key')->withoutMiddleware(['domain.verified' , 'check.domain']);
});




