<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BrandingController;
use App\Http\Controllers\Api\Mobile\CustomerAppController;
use App\Http\Controllers\Api\Mobile\DashboardController;
use App\Http\Controllers\Api\Mobile\ExpenseController;
use App\Http\Controllers\Api\Mobile\ResourceController;
use App\Http\Controllers\Api\Mobile\Staff;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    // Public: the sign-in screen shows the admin's logo and name before anyone logs in.
    Route::get('/branding', BrandingController::class)->middleware('throttle:60,1');

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

        // Staff workspace. Each controller checks the same permission keys as the website.
        Route::prefix('staff')->group(function (): void {
            Route::get('/lookups', Staff\LookupController::class);

            Route::get('/sales', [Staff\SaleController::class, 'index']);
            Route::post('/sales', [Staff\SaleController::class, 'store'])->middleware('throttle:60,1');
            Route::get('/sales/{sale}', [Staff\SaleController::class, 'show'])->whereNumber('sale');
            Route::post('/sales/{sale}/payments', [Staff\SaleController::class, 'recordPayment'])->whereNumber('sale');
            Route::get('/pos/products', [Staff\SaleController::class, 'posProducts']);
            Route::get('/pos/customers', [Staff\SaleController::class, 'posCustomers']);
            Route::get('/payments', [Staff\SaleController::class, 'payments']);

            Route::get('/orders', [Staff\OrderController::class, 'index']);
            Route::get('/orders/{order}', [Staff\OrderController::class, 'show'])->whereNumber('order');
            Route::patch('/orders/{order}/status', [Staff\OrderController::class, 'updateStatus'])->whereNumber('order');

            Route::get('/stock', [Staff\StockController::class, 'index']);
            Route::post('/stock', [Staff\StockController::class, 'store']);
            Route::get('/stock/products', [Staff\StockController::class, 'products']);
            Route::get('/stock/lookup-barcode', [Staff\StockController::class, 'lookupBarcode']);
            Route::put('/stock/{stock}', [Staff\StockController::class, 'update'])->whereNumber('stock');
            Route::get('/stock-issues', [Staff\StockController::class, 'issues']);
            Route::post('/stock-issues', [Staff\StockController::class, 'storeIssue']);

            Route::get('/chats', [Staff\ChatController::class, 'index']);
            Route::get('/chats/{conversation}', [Staff\ChatController::class, 'show'])->whereNumber('conversation');
            Route::post('/chats/{conversation}/reply', [Staff\ChatController::class, 'reply'])->whereNumber('conversation')->middleware('throttle:30,1');
            Route::post('/chats/{conversation}/close', [Staff\ChatController::class, 'close'])->whereNumber('conversation');

            Route::get('/activity-logs', [Staff\ActivityLogController::class, 'index']);
            Route::get('/activity-logs/summary', [Staff\ActivityLogController::class, 'summary']);

            Route::get('/reports', [Staff\ReportController::class, 'index']);
            Route::get('/reports/{type}', [Staff\ReportController::class, 'show']);

            // Products, categories, customers, suppliers and expense categories.
            $catalog = 'products|categories|customers|suppliers|expense-categories';
            Route::get('/catalog/{resource}', [Staff\CatalogController::class, 'index'])->where('resource', $catalog);
            Route::post('/catalog/{resource}', [Staff\CatalogController::class, 'store'])->where('resource', $catalog);
            Route::get('/catalog/{resource}/{id}', [Staff\CatalogController::class, 'show'])->where('resource', $catalog)->whereNumber('id');
            // POST as well as PUT: Android multipart uploads (product photos) cannot use PUT.
            Route::match(['put', 'post'], '/catalog/{resource}/{id}', [Staff\CatalogController::class, 'update'])->where('resource', $catalog)->whereNumber('id');
            Route::delete('/catalog/{resource}/{id}', [Staff\CatalogController::class, 'destroy'])->where('resource', $catalog)->whereNumber('id');
        });

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
