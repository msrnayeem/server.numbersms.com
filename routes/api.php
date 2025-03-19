<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SmsController;

use App\Http\Controllers\Api\User\CoinShareController;
use App\Http\Controllers\Api\User\SmsHistoryController;
use App\Http\Controllers\Api\User\SupportController;
use App\Http\Controllers\Api\User\TransictionController;

use App\Http\Controllers\Api\Admin\AlluserController as admin_AlluserController;
use App\Http\Controllers\Api\Admin\CreaditController as admin_CreaditConnntroller;
use App\Http\Controllers\Api\Admin\SmsHistoryController as admin_SmsHistoryController;
use App\Http\Controllers\Api\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Api\Admin\TrannsictionController as admin_TransictionController;
use App\Http\Controllers\SmsApiController;
use App\Models\SmsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// guest
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verifyemail', [AuthController::class, 'verifyEmail']);
Route::post('/sendotp', [AuthController::class, 'sendotp']);

// payment
Route::controller(PaymentController::class)->group(function () {
    Route::get('/payment-success', 'success')->name('pay_success');
    Route::get('/payment-failed', 'faild')->name('pay_faild');
});

// authenticated 
Route::group([
    'middleware' => ['auth:api']
], function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/upadte-profile', [AuthController::class, 'updateProfile']);
    Route::post('/chanage-password', [AuthController::class, 'chanagePassword']);

    // for user
    Route::prefix('/app')->group(function () {
        Route::get('/transaction', [TransictionController::class, 'index']);

        Route::post('/share-token', [CoinShareController::class, 'shareToken']);

        Route::post('/create-support', [SupportController::class, 'index']);
        Route::get('/allsupport', [SupportController::class, 'getSupport']);

        Route::get('/get-smshistory', [SmsHistoryController::class, 'getSmsHistory']);
    });

    // for admin
    Route::prefix('/admin')->group(function () {
        Route::controller(admin_AlluserController::class)->group(function () {
            Route::post('/deleteuser', 'deluser');
            Route::post('/userolechnage', 'userRoleChnage');
            Route::get('/getalluser', 'allUsers');
        });
        Route::controller(admin_SmsHistoryController::class)->group(function () {
            Route::get('/get-smshistory', 'getSmsHistory');
            Route::post('/services-image', 'servicesImage');
        });
        Route::controller(AdminSupportController::class)->group(function () {
            Route::get('/get-support', 'index');
            Route::post('/del-support', 'delete');
            Route::post('/single-support', 'single');
        });
        Route::post('/addblance', [admin_CreaditConnntroller::class, 'index']);
        Route::post('/discount-manegar', [admin_CreaditConnntroller::class, 'discountManegar']);
        Route::get('/discount-manegar/get', [admin_CreaditConnntroller::class, 'discountManegarGet']);
        Route::get('/transaction', [admin_TransictionController::class, 'index']);
    });

    // payment
    Route::controller(PaymentController::class)->group(function () {
        Route::post('/payment', 'createPayment');
    });

    // sms
    Route::controller(SmsController::class)->group(function () {
        Route::get('/get-services', 'getServices');
        Route::post('/create-verify', 'createVerify');
        Route::post('/getaccountdetails', 'accountDetails');
        Route::post('/getotp', 'getOtp');
        Route::post('/cancel-services', 'cancelServices');
        Route::post('/reactiveservices', 'reactiveSMS');
    });
});


// sms
Route::prefix('/sms')->controller(SmsApiController::class)->group(function () {
    Route::post('/service', 'service')->name('sms_service');
    Route::post('/verification_pricing', 'verificationPricing')->name('sms_varificstion_price');
    Route::post('/create_verification', 'createVerification')->name('sms_createvarification');
    Route::post('/startpolling', 'startPolling')->name('sms_startPolling');
    Route::post('/getotp', 'getOtp')->name('sms_getotp');
    Route::post('/me', 'me')->name('sms_me');
});