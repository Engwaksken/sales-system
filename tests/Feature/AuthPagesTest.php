<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_has_labelled_fields_and_links(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to your account')
            ->assertSee('action="'.route('login').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('<label for="password">', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee(route('password.request'), false)
            ->assertSee(route('customer.signup'), false);
    }

    public function test_create_account_page_has_all_fields_and_strength_hint(): void
    {
        $this->get('/customer/signup')
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('action="'.route('customer.register').'"', false)
            ->assertSee('autocomplete="name"', false)
            ->assertSee('autocomplete="tel"', false)
            ->assertSee('name="address"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('data-password-strength="customer_password"', false)
            ->assertSee(route('login'), false);
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('action="'.route('password.email').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('Back to sign in');
    }

    public function test_password_reset_code_page_uses_numeric_one_time_code_input(): void
    {
        $this->withSession(['password_reset' => ['user_id' => 1, 'email' => 'jane@example.com']])
            ->get('/verify-otp')
            ->assertOk()
            ->assertSee('ja**@example.com')
            ->assertSee('name="otp"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('autocomplete="one-time-code"', false)
            ->assertSee('action="'.route('password.otp.resend').'"', false);
    }

    public function test_sign_in_code_page_renders(): void
    {
        $this->withSession(['login_verification' => ['user_id' => 1, 'email' => 'jane@example.com']])
            ->get('/login/verify-otp')
            ->assertOk()
            ->assertSee('action="'.route('login.otp.verify').'"', false)
            ->assertSee('autocomplete="one-time-code"', false)
            ->assertSee('action="'.route('login.otp.resend').'"', false);
    }

    public function test_reset_password_page_has_strength_hint(): void
    {
        $this->withSession(['password_reset' => ['user_id' => 1, 'verified_at' => now()->timestamp]])
            ->get('/reset-password')
            ->assertOk()
            ->assertSee('action="'.route('password.update').'"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('data-password-strength="password"', false);
    }

    public function test_validation_errors_show_inline_under_the_field(): void
    {
        $this->from('/login')->post('/login', ['email' => 'not-an-email', 'password' => ''])
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('id="email-error"', false)
            ->assertSee('id="password-error"', false);
    }

    public function test_branding_comes_from_settings(): void
    {
        \App\Models\Setting::query()->insert([
            ['setting_key' => 'site_name', 'setting_value' => 'Acme Traders'],
            ['setting_key' => 'theme_primary', 'setting_value' => '#123abc'],
        ]);
        \Illuminate\Support\Facades\Cache::forget('app.site_settings');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Acme Traders')
            ->assertSee('--auth-primary:#123abc', false);
    }
}
