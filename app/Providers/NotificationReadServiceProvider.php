<?php

namespace App\Providers;

use App\Http\Controllers\UserNotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class NotificationReadServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web','auth'])->group(function (): void {
            Route::post('/my-notifications/{notification}/read', [UserNotificationController::class,'read'])
                ->name('my-notifications.read');

            Route::post('/my-notifications/read-all', [UserNotificationController::class,'readAll'])
                ->name('my-notifications.read-all');
        });
    }
}
