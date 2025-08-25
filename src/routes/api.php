<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncomingApi\SmsController;
use App\Http\Controllers\Api\IncomingApi\EmailController;
use App\Http\Controllers\Api\IncomingApi\WhatsAppController;
use App\Http\Controllers\Api\Communication\SmsDispatchController;
use App\Http\Controllers\Api\Communication\Gateway\Android\SimController;
use App\Http\Controllers\Api\Communication\Gateway\Android\SessionController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

## ---------------------------- ##
##   Android App API Endpoints  ##
## ---------------------------- ##

Route::middleware(['android.gateway.token'])->group(function () {
    Route::prefix('gateway')
            ->name('gateway.')
            ->group(function () {
        Route::prefix('sms')
                ->name('sms.')
                ->group(function () {
            Route::prefix('android')
                    ->name('android.')
                    ->group(function () {
                Route::post('register-session', [SessionController::class, 'registerSession'])
                        ->name('register-session')
                        ->withoutMiddleware(['android.gateway.token']);
                Route::post('logout', [SessionController::class, 'logout'])
                        ->name('logout');

                Route::prefix('sim')
                        ->name('sim.')
                        ->group(function () {

                    Route::post('update-status', [SimController::class, 'updateStatus'])->name('update-status');
                });

                Route::apiResource('sim', SimController::class)
                        ->except(['show']) 
                        ->names([
                    'store'     => 'sim.store',
                    'update'    => 'sim.update',
                    'destroy'   => 'sim.delete',
                ]);
            });
        });
    });
    
    Route::prefix('logs')
            ->name('logs.')
            ->group(function () {
        Route::prefix('sms')
                ->name('sms.')
                ->group(function () {
            Route::get('pending', [SmsDispatchController::class, 'fetchPending'])
                ->name('pending');
            Route::post('update-statuses', [SmsDispatchController::class, 'updateStatuses'])
                ->name('update-statuses');
        });
    });
});

## ------------------- ##
##   Old Xsender API   ##
## ------------------- ##

Route::middleware(['incoming.api', 'sanitizer'])->name('incoming.')->group(function () {

    Route::post('email/send', [EmailController::class, 'store'])->name('email.send');
    Route::get('email/send', [EmailController::class, 'sendWithQuery'])->name('email.send.query');
    Route::get('get/email/{id?}', [EmailController::class, 'getEmailLog']);

    Route::post('sms/send', [SmsController::class, 'store'])->name('sms.send');
    Route::get('sms/send', [SmsController::class, 'sendWithQuery'])->name('sms.send.query');
    Route::get('get/sms/{id?}', [SmsController::class, 'getSmsLog']);

    Route::post('whatsapp/send', [WhatsAppController::class, 'store'])->name('whatsapp.send');
    Route::get('whatsapp/send', [WhatsAppController::class, 'sendWithQuery'])->name('whatsapp.send.query');
    Route::get('get/whatsapp/{id?}', [WhatsAppController::class, 'getWhatsAppLog']);
});