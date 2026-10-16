<?php

namespace App\Services;

use App\Models\EmailSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class MobileAuthMailService
{
    public function sendOtp(string $email, string $name, string $otp, string $purpose): void
    {
        $this->configureMailerFromDatabase();

        [$subject, $headline, $intro, $notice] = match ($purpose) {
            'registration' => [
                'account verification code',
                'Verify your account',
                'Use this one-time code to complete your account registration.',
                'If you did not create this account, you can ignore this email.',
            ],
            'password_reset' => [
                'password reset code',
                'Reset your password',
                'Use this one-time code to verify your identity and reset your password.',
                'If you did not request a password reset, you can safely ignore this email.',
            ],
            default => [
                'verification code',
                'Verify your request',
                'Use this one-time code to continue securely.',
                'If you did not request this code, you can ignore this email.',
            ],
        };

        try {
            $brand = $this->mailBranding();
            Mail::send('emails.auth-code', $brand + [
                'recipientName' => $name,
                'headline' => $headline,
                'intro' => $intro,
                'otp' => $otp,
                'expiresMinutes' => 10,
                'notice' => $notice,
            ], function ($message) use ($email, $name, $subject, $brand): void {
                $message->to($email, $name)->subject($brand['brandName'].' '.$subject);
            });
        } catch (Throwable $e) {
            report($e);
            throw new RuntimeException(
                'We could not send the verification code right now. Please try again in a moment.'
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

    private function mailBranding(): array
    {
        $settings = [];
        try {
            $settings = Setting::query()->whereIn('setting_key', [
                'site_name', 'system_logo', 'theme_primary',
            ])->pluck('setting_value', 'setting_key')->all();
        } catch (Throwable) {
            // Use application/mail defaults when database branding is unavailable.
        }

        $brandName = trim((string) ($settings['site_name'] ?? ''))
            ?: (config('mail.from.name') ?: config('app.name', 'Sales System'));
        $logoPath = trim((string) ($settings['system_logo'] ?? ''));
        $logoUrl = $logoPath === '' ? null : (str_starts_with($logoPath, 'http')
            ? $logoPath
            : asset(ltrim($logoPath, '/')));
        $brandColor = (string) ($settings['theme_primary'] ?? '#0f766e');
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)) {
            $brandColor = '#0f766e';
        }

        return compact('brandName', 'logoUrl', 'brandColor');
    }
}
