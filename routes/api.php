<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CustomerAppController;
use App\Http\Controllers\Api\Mobile\DashboardController;
use App\Http\Controllers\Api\Mobile\ExpenseController;
use App\Http\Controllers\Api\Mobile\ResourceController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/register/verify-otp', [AuthController::class, 'verifyRegistration'])->middleware('throttle:10,1');
    Route::post('/register/resend-otp', [AuthController::class, 'resendRegistrationOtp'])->middleware('throttle:3,1');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:10,1');
    Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendForgotPasswordOtp'])->middleware('throttle:3,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', DashboardController::class);

        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
        Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'receipt']);
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

        Route::get('/resources/{resource}', [ResourceController::class, 'index']);
        Route::get('/resources/{resource}/{id}', [ResourceController::class, 'show']);

        // Notification inbox: shared by customers and staff (the staff bell uses it).
        Route::prefix('customer/notifications')->group(function (): void {
            Route::get('/', [CustomerAppController::class, 'notifications']);
            Route::get('/unread-count', [CustomerAppController::class, 'unreadCount']);
            Route::post('/read-all', [CustomerAppController::class, 'readAllNotifications']);
            Route::post('/{notification}/read', [CustomerAppController::class, 'readNotification']);
        });

        Route::prefix('customer')->middleware('customer')->group(function (): void {
            Route::get('/home', [CustomerAppController::class, 'home']);
            Route::get('/products', [CustomerAppController::class, 'products']);
            Route::get('/orders', [CustomerAppController::class, 'orders']);
            Route::get('/orders/{order}', [CustomerAppController::class, 'order'])->whereNumber('order');
            Route::get('/payments', [CustomerAppController::class, 'payments']);
            Route::get('/profile', [CustomerAppController::class, 'profile']);
            Route::post('/profile', [CustomerAppController::class, 'updateProfile']);
            Route::get('/chat', [CustomerAppController::class, 'chat']);
            Route::post('/chat/messages', [CustomerAppController::class, 'sendChat'])->middleware('throttle:20,1');
            Route::get('/history', [CustomerAppController::class, 'history']);
        });
    });
});
