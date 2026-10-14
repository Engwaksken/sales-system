<?php

namespace App\Providers;

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CustomerAppController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MobileApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/mobile')
            ->middleware('api')
            ->group(function (): void {
                Route::get('/health', fn () => response()->json([
                    'ok' => true,
                    'service' => 'NSales Customer Mobile API',
                ]));

                Route::post('/login', [AuthController::class, 'login'])
                    ->middleware('throttle:10,1');
                Route::post('/register', [AuthController::class, 'register'])
                    ->middleware('throttle:5,1');
                Route::post('/register/verify-otp', [AuthController::class, 'verifyRegistration'])
                    ->middleware('throttle:10,1');
                Route::post('/register/resend-otp', [AuthController::class, 'resendRegistrationOtp'])
                    ->middleware('throttle:3,1');
                Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
                    ->middleware('throttle:5,1');
                Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])
                    ->middleware('throttle:10,1');
                Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendForgotPasswordOtp'])
                    ->middleware('throttle:3,1');
                Route::post('/reset-password', [AuthController::class, 'resetPassword'])
                    ->middleware('throttle:5,1');

                Route::middleware('auth:sanctum')->group(function (): void {
                    Route::get('/me', [AuthController::class, 'me']);
                    Route::post('/logout', [AuthController::class, 'logout']);

                    Route::prefix('customer')->group(function (): void {
                        Route::get('/home', [CustomerAppController::class, 'home']);
                        Route::get('/products', [CustomerAppController::class, 'products']);

                        Route::get('/orders', [CustomerAppController::class, 'orders']);
                        Route::get('/orders/{order}', [CustomerAppController::class, 'order']);

                        Route::get('/payments', [CustomerAppController::class, 'payments']);

                        Route::get('/notifications', [CustomerAppController::class, 'notifications']);
                        Route::get('/notifications/unread-count', [CustomerAppController::class, 'unreadCount']);
                        Route::post('/notifications/read-all', [CustomerAppController::class, 'readAllNotifications']);
                        Route::post('/notifications/{notification}/read', [CustomerAppController::class, 'readNotification']);

                        Route::get('/profile', [CustomerAppController::class, 'profile']);
                        Route::post('/profile', [CustomerAppController::class, 'updateProfile']);

                        Route::get('/chat', [CustomerAppController::class, 'chat']);
                        Route::post('/chat/messages', [CustomerAppController::class, 'sendChat']);

                        Route::get('/history', [CustomerAppController::class, 'history']);
                    });
                });
            });
    }
}
