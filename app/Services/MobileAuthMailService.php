<?php

namespace App\Services;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class MobileAuthMailService
{
    public function sendOtp(string $email, string $name, string $otp, string $purpose): void
    {
        $this->configureMailerFromDatabase();

        [$subject, $intro] = match ($purpose) {
            'registration' => [
                'Verify Your NSales Account',
                'Use the verification code below to complete your NSales account registration.',
            ],
            'password_reset' => [
                'NSales Password Reset Code',
                'Use the verification code below to reset your NSales password.',
            ],
            default => [
                'NSales Verification Code',
                'Use the verification code below to continue.',
            ],
        };

        try {
            Mail::raw(
                "{$intro}\n\nVerification code: {$otp}\n\nThis code expires in 10 minutes.\n\nIf you did not request this code, you can ignore this email.",
                function ($message) use ($email, $name, $subject): void {
                    $message->to($email, $name)->subject($subject);
                }
            );
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
}
