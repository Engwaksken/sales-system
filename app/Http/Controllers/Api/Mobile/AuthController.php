<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Services\MobileOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        private readonly MobileOtpService $otp
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('email', strtolower($credentials['email']))
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The supplied login details are incorrect.'],
            ]);
        }

        if (Schema::hasColumn('users', 'email_verified_at') && ! $user->email_verified_at) {
            $this->otp->issue($user->email, $user->name ?: 'User', 'registration');

            return response()->json([
                'message' => 'Please verify your email before signing in. A new OTP has been sent.',
                'code' => 'EMAIL_VERIFICATION_REQUIRED',
                'email' => $user->email,
                'masked_email' => $this->maskEmail($user->email),
            ], 403);
        }

        if (isset($user->status) &&
            ! in_array(strtolower((string) $user->status), ['active', '1'], true)) {
            return response()->json([
                'message' => 'This user account is not active.',
            ], 403);
        }

        return $this->tokenResponse(
            $user,
            $credentials['device_name'] ?: 'NSales Mobile'
        );
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ]);

        $email = strtolower($data['email']);
        $existing = User::query()->where('email', $email)->first();

        if ($existing && $existing->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['An account already exists with this email address.'],
            ]);
        }

        $branchId = null;

        if (Schema::hasTable('branches')) {
            $branchId = Branch::query()->orderBy('id')->value('id');
        }

        $user = $existing ?: new User();

        $user->fill([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'branch_id' => $user->exists ? $user->branch_id : $branchId,
        ]);

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (Schema::hasTable('customers')) {
            $customer = Customer::query()
                ->when(
                    Schema::hasColumn('customers', 'user_id'),
                    fn ($q) => $q->where('user_id', $user->id),
                    fn ($q) => $q->where('email', $user->email)
                )
                ->first();

            $customer ??= new Customer();

            $customer->name = $user->name;
            $customer->email = $user->email;
            $customer->branch_id = $customer->branch_id ?: $user->branch_id;

            if (Schema::hasColumn('customers', 'user_id')) {
                $customer->user_id = $user->id;
            }

            $customer->save();
        }

        $this->otp->issue($user->email, $user->name ?: 'User', 'registration');

        return response()->json([
            'message' => 'Account created. Enter the 6-digit OTP sent to your email.',
            'email' => $user->email,
            'masked_email' => $this->maskEmail($user->email),
            'requires_verification' => true,
        ], 201);
    }

    public function verifyRegistration(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $this->otp->verify($data['email'], 'registration', $data['otp']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'otp' => [$e->getMessage()],
            ]);
        }

        $user = User::query()
            ->where('email', strtolower($data['email']))
            ->firstOrFail();

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return $this->tokenResponse(
            $user,
            $data['device_name'] ?: 'NSales Mobile'
        );
    }

    public function resendRegistrationOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', strtolower($data['email']))
            ->firstOrFail();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'This email address is already verified.',
            ]);
        }

        $this->otp->issue($user->email, $user->name ?: 'User', 'registration');

        return response()->json([
            'message' => 'A new verification code has been sent.',
            'masked_email' => $this->maskEmail($user->email),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', strtolower($data['email']))
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['We could not find an account with that email address.'],
            ]);
        }

        $this->otp->issue($user->email, $user->name ?: 'User', 'password_reset');

        return response()->json([
            'message' => 'A 6-digit password reset code has been sent.',
            'email' => $user->email,
            'masked_email' => $this->maskEmail($user->email),
        ]);
    }

    public function verifyForgotPasswordOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        try {
            $record = $this->otp->verify(
                $data['email'],
                'password_reset',
                $data['otp']
            );

            $resetToken = $this->otp->createResetToken($record);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'otp' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'message' => 'OTP verified. You can now create a new password.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resendForgotPasswordOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', strtolower($data['email']))
            ->firstOrFail();

        $this->otp->issue($user->email, $user->name ?: 'User', 'password_reset');

        return response()->json([
            'message' => 'A new password reset code has been sent.',
            'masked_email' => $this->maskEmail($user->email),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'reset_token' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ]);

        try {
            $record = $this->otp->verifyResetToken(
                $data['email'],
                $data['reset_token']
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'reset_token' => [$e->getMessage()],
            ]);
        }

        $user = User::query()
            ->where('email', strtolower($data['email']))
            ->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        // Log out old mobile sessions after a password reset.
        $user->tokens()->delete();
        $record->delete();

        return response()->json([
            'message' => 'Password changed successfully. Sign in with your new password.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function tokenResponse(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken(
            $deviceName,
            ['mobile']
        )->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    private function userPayload(User $user): array
    {
        $customer = null;

        if (Schema::hasTable('customers')) {
            $customer = Customer::query()
                ->when(
                    Schema::hasColumn('customers', 'user_id'),
                    fn ($q) => $q->where('user_id', $user->id),
                    fn ($q) => $q->where('email', $user->email)
                )
                ->first();
        }

        return [
            'id' => $user->id,
            'name' => $user->name ?? 'Customer',
            'email' => $user->email,
            'role' => 'customer',
            'branch_id' => $user->branch_id ?? null,
            'customer_id' => $customer?->id,
            'phone' => $customer?->phone,
            'address' => $customer?->address,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
        ];
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
