<?php

namespace App\Services;

use App\Models\MobileAuthOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class MobileOtpService
{
    public function __construct(
        private readonly MobileAuthMailService $mail
    ) {
    }

    public function issue(string $email, string $name, string $purpose): MobileAuthOtp
    {
        MobileAuthOtp::query()
            ->where('email', strtolower($email))
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $otp = (string) random_int(100000, 999999);

        $record = MobileAuthOtp::create([
            'email' => strtolower($email),
            'purpose' => $purpose,
            'code_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            $this->mail->sendOtp($email, $name, $otp, $purpose);
        } catch (\Throwable $e) {
            $record->delete();
            throw $e;
        }

        return $record;
    }

    public function verify(string $email, string $purpose, string $code): MobileAuthOtp
    {
        $record = MobileAuthOtp::query()
            ->where('email', strtolower($email))
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if (! $record) {
            throw new RuntimeException('No active verification code was found. Request a new code.');
        }

        if ($record->verified_at) {
            return $record;
        }

        if ($record->expires_at->isPast()) {
            throw new RuntimeException('The verification code has expired. Request a new code.');
        }

        if ($record->attempts >= 5) {
            throw new RuntimeException('Too many incorrect attempts. Request a new verification code.');
        }

        $record->increment('attempts');

        if (! Hash::check($code, $record->code_hash)) {
            throw new RuntimeException('The verification code is incorrect.');
        }

        $record->forceFill([
            'verified_at' => now(),
        ])->save();

        return $record->fresh();
    }

    public function createResetToken(MobileAuthOtp $record): string
    {
        $token = Str::random(64);

        $record->forceFill([
            'reset_token_hash' => Hash::make($token),
            'reset_token_expires_at' => now()->addMinutes(15),
        ])->save();

        return $token;
    }

    public function verifyResetToken(string $email, string $token): MobileAuthOtp
    {
        $record = MobileAuthOtp::query()
            ->where('email', strtolower($email))
            ->where('purpose', 'password_reset')
            ->whereNotNull('verified_at')
            ->whereNotNull('reset_token_hash')
            ->latest('id')
            ->first();

        if (! $record ||
            ! $record->reset_token_expires_at ||
            $record->reset_token_expires_at->isPast() ||
            ! Hash::check($token, $record->reset_token_hash)) {
            throw new RuntimeException('The password reset session is invalid or has expired.');
        }

        return $record;
    }
}
