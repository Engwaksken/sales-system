<?php

namespace App\Providers;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthOtpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(function (): void {

            Route::get('/customer/signup', function () {
                return view('auth.customer-signup');
            })->middleware('guest')->name('customer.signup');

            Route::post('/customer/register', [LoginController::class, 'customerRegister'])
                ->name('customer.register')
                ->middleware('throttle:5,1');

            Route::get('/login/verify-otp', [LoginController::class, 'loginOtpForm'])
                ->name('login.otp');

            Route::post('/login/verify-otp', [LoginController::class, 'verifyLoginOtp'])
                ->name('login.otp.verify')
                ->middleware('throttle:10,1');

            Route::post('/login/verify-otp/resend', [LoginController::class, 'resendLoginOtp'])
                ->name('login.otp.resend')
                ->middleware('throttle:3,1');
        });
    }
}
