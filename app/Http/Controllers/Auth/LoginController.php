<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use App\Models\User;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Step 1 of login:
     * validate email/password, email a 6-digit OTP, but DO NOT authenticate yet.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($credentials['email']));
        $key = 'login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'email' => 'Too many login attempts. Please wait '.RateLimiter::availableIn($key).' seconds and try again.',
            ])->onlyInput('email');
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors([
                'email' => 'Those credentials do not match our records.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($key);

        $this->issueLoginOtp(
            request: $request,
            user: $user,
            remember: $request->boolean('remember')
        );

        return redirect()
            ->route('login.otp')
            ->with('status', 'We sent a 6-digit login verification code to '.$this->maskEmail($user->email).'.');
    }

    /**
     * Step 2: show login OTP screen.
     */
    public function loginOtpForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $pending = $request->session()->get('login_verification');

        if (! $pending || empty($pending['user_id'])) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your login verification session has expired. Please sign in again.']);
        }

        return view('auth.login-otp', [
            'maskedEmail' => $this->maskEmail((string) ($pending['email'] ?? '')),
            'expiresMinutes' => 10,
        ]);
    }

    /**
     * Verify login OTP, then finally create the authenticated session.
     */
    public function verifyLoginOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pending = $request->session()->get('login_verification');

        if (! $pending || empty($pending['user_id']) || empty($pending['otp_hash'])) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your login verification session has expired. Please sign in again.']);
        }

        if (now()->timestamp > (int) ($pending['expires_at'] ?? 0)) {
            $request->session()->forget('login_verification');

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'The login verification code has expired. Please sign in again.']);
        }

        $attempts = (int) ($pending['attempts'] ?? 0) + 1;
        $pending['attempts'] = $attempts;
        $request->session()->put('login_verification', $pending);

        if ($attempts > 5) {
            $request->session()->forget('login_verification');

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Too many incorrect verification attempts. Please sign in again.']);
        }

        if (! Hash::check($request->string('otp')->toString(), $pending['otp_hash'])) {
            return back()->withErrors([
                'otp' => 'The verification code is incorrect.',
            ]);
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            $request->session()->forget('login_verification');

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'The user account could not be found.']);
        }

        $remember = (bool) ($pending['remember'] ?? false);

        $request->session()->forget('login_verification');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Always enter through Dashboard. Existing role/permission middleware and
        // navigation rules continue controlling which modules this user can access.
        return redirect()->route('dashboard');
    }

    public function resendLoginOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('login_verification');

        if (! $pending || empty($pending['user_id'])) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your login verification session has expired. Please sign in again.']);
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            $request->session()->forget('login_verification');

            return redirect()->route('login');
        }

        $this->issueLoginOtp(
            request: $request,
            user: $user,
            remember: (bool) ($pending['remember'] ?? false)
        );

        return back()->with(
            'status',
            'A new login verification code has been sent to '.$this->maskEmail($user->email).'.'
        );
    }


    /**
     * Public customer registration. Customers use the same login page and
     * the same email OTP verification step as staff/other users.
     */
    public function customerRegister(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ]);

        [$user, $customer] = DB::transaction(function () use ($validated): array {
            $branchId = Branch::query()->orderBy('id')->value('id');

            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
                'role' => 'customer',
                'branch_id' => $branchId,
            ]);

            $customer = Customer::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => strtolower($validated['email']),
                'address' => $validated['address'] ?? null,
            ]);

            return [$user, $customer];
        });

        $this->issueLoginOtp(
            request: $request,
            user: $user,
            remember: $request->boolean('remember')
        );

        return redirect()
            ->route('login.otp')
            ->with(
                'status',
                'Customer account created. We sent a 6-digit verification code to '.$this->maskEmail($user->email).'.'
            );
    }

    public function forgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'We could not find an account with that email address.',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $this->issueResetOtp($request, $user);

        return redirect()->route('password.otp')
            ->with('status', 'We sent a 6-digit verification code to '.$this->maskEmail($user->email).'.');
    }

    public function otpForm(Request $request): View|RedirectResponse
    {
        $reset = $request->session()->get('password_reset');

        if (! $reset || empty($reset['user_id'])) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-otp', [
            'maskedEmail' => $this->maskEmail((string) ($reset['email'] ?? '')),
            'expiresMinutes' => 10,
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $reset = $request->session()->get('password_reset');

        if (! $reset || empty($reset['user_id']) || empty($reset['otp_hash'])) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Your verification session has expired. Please request another code.']);
        }

        if (now()->timestamp > (int) ($reset['expires_at'] ?? 0)) {
            $request->session()->forget('password_reset');

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'The verification code has expired. Please request a new one.']);
        }

        $attempts = (int) ($reset['attempts'] ?? 0) + 1;
        $reset['attempts'] = $attempts;
        $request->session()->put('password_reset', $reset);

        if ($attempts > 5) {
            $request->session()->forget('password_reset');

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Too many incorrect code attempts. Please request a new verification code.']);
        }

        if (! Hash::check($request->string('otp')->toString(), $reset['otp_hash'])) {
            return back()->withErrors([
                'otp' => 'The verification code is incorrect.',
            ]);
        }

        $reset['verified_at'] = now()->timestamp;
        unset($reset['otp_hash']);
        $request->session()->put('password_reset', $reset);

        return redirect()->route('password.reset');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $reset = $request->session()->get('password_reset');

        if (! $reset || empty($reset['user_id'])) {
            return redirect()->route('password.request');
        }

        $user = User::find($reset['user_id']);

        if (! $user) {
            $request->session()->forget('password_reset');

            return redirect()->route('password.request');
        }

        $this->issueResetOtp($request, $user);

        return back()->with(
            'status',
            'A new verification code has been sent to '.$this->maskEmail($user->email).'.'
        );
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        $reset = $request->session()->get('password_reset');

        if (! $reset ||
            empty($reset['verified_at']) ||
            now()->timestamp - (int) $reset['verified_at'] > 900) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Your password reset session has expired. Please start again.']);
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $reset = $request->session()->get('password_reset');

        if (! $reset ||
            empty($reset['user_id']) ||
            empty($reset['verified_at']) ||
            now()->timestamp - (int) $reset['verified_at'] > 900) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Your password reset session has expired.']);
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ]);

        $user = User::findOrFail($reset['user_id']);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'verification_code' => null,
            'code_expires' => null,
        ])->save();

        $request->session()->forget('password_reset');
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been changed successfully. You can now sign in.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget('login_verification');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function issueLoginOtp(Request $request, User $user, bool $remember): void
    {
        $otp = (string) random_int(100000, 999999);

        $request->session()->put('login_verification', [
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
            'remember' => $remember,
        ]);

        $this->configureMailerFromDatabase();

        try {
            $brand = $this->mailBranding();
            Mail::send('emails.auth-code', $brand + [
                'recipientName' => $user->name,
                'headline' => 'Your sign-in verification code',
                'intro' => 'Use this one-time code to securely sign in to your account.',
                'otp' => $otp,
                'expiresMinutes' => 10,
                'notice' => 'If you did not try to sign in, change your password and contact your administrator.',
            ], function ($message) use ($user, $brand): void {
                $message->to($user->email, $user->name)
                    ->subject($brand['brandName'].' sign-in verification code');
            });
        } catch (Throwable $e) {
            report($e);
            $request->session()->forget('login_verification');

            abort(
                500,
                'We could not send your login verification code right now. Please try again in a moment.'
            );
        }
    }

    private function issueResetOtp(Request $request, User $user): void
    {
        $otp = (string) random_int(100000, 999999);

        $request->session()->put('password_reset', [
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);

        $this->configureMailerFromDatabase();

        try {
            $brand = $this->mailBranding();
            Mail::send('emails.auth-code', $brand + [
                'recipientName' => $user->name,
                'headline' => 'Reset your password',
                'intro' => 'Use this one-time code to verify your identity and reset your password.',
                'otp' => $otp,
                'expiresMinutes' => 10,
                'notice' => 'If you did not request a password reset, you can safely ignore this email.',
            ], function ($message) use ($user, $brand): void {
                $message->to($user->email, $user->name)
                    ->subject($brand['brandName'].' password reset code');
            });
        } catch (Throwable $e) {
            report($e);
            $request->session()->forget('password_reset');

            abort(
                500,
                'We could not send your verification code right now. Please try again in a moment.'
            );
        }
    }

    private function configureMailerFromDatabase(): void
    {
        $mail = EmailSetting::query()->latest('id')->first();

        if (! $mail) {
            return;
        }

        $encryption = strtolower((string) $mail->smtp_secure);

        if (! in_array($encryption, ['tls', 'ssl', 'starttls'], true)) {
            $encryption = null;
        }

        if ($encryption === 'starttls') {
            $encryption = 'tls';
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $mail->smtp_host,
            'mail.mailers.smtp.port' => (int) $mail->smtp_port,
            'mail.mailers.smtp.username' => $mail->smtp_user,
            'mail.mailers.smtp.password' => $mail->smtp_password,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address' => $mail->from_email ?: config('mail.from.address'),
            'mail.from.name' => $mail->from_name ?: config('app.name', 'NSales'),
        ]);
    }

    /** Branding is read defensively so auth mail still works before settings exist. */
    private function mailBranding(): array
    {
        $settings = [];
        try {
            $settings = Setting::query()->whereIn('setting_key', [
                'site_name', 'system_logo', 'theme_primary',
            ])->pluck('setting_value', 'setting_key')->all();
        } catch (Throwable) {
            // The configured mail sender and app name remain valid fallbacks.
        }

        $brandName = trim((string) ($settings['site_name'] ?? ''))
            ?: (config('mail.from.name') ?: config('app.name', 'Sales System'));
        $logoPath = trim((string) ($settings['system_logo'] ?? ''));
        $logoUrl = $logoPath === '' ? null : (str_starts_with($logoPath, 'http')
            ? $logoPath
            : asset(ltrim($logoPath, '/')));
        $color = (string) ($settings['theme_primary'] ?? '#0f766e');
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#0f766e';
        }

        return ['brandName' => $brandName, 'logoUrl' => $logoUrl, 'brandColor' => $color];
    }

    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return 'your email';
        }

        [$name, $domain] = explode('@', $email, 2);
        $visible = substr($name, 0, min(2, strlen($name)));

        return $visible
            .str_repeat('*', max(2, strlen($name) - strlen($visible)))
            .'@'.$domain;
    }
}
