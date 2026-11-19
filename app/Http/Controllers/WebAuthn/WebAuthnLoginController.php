<?php

namespace App\Http\Controllers\WebAuthn;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;

/**
 * Passkey sign-in (fingerprint, face or device PIN through the browser).
 *
 * A passkey proves both "has this device" and "is this person", so it skips
 * the password and the emailed code that a password sign-in needs.
 */
class WebAuthnLoginController extends Controller
{
    /** A challenge for any passkey saved for this site; the device picks the account. */
    public function options(AssertionRequest $request): Responsable
    {
        return $request->secureLogin()->toVerify();
    }

    public function login(AssertedRequest $request): JsonResponse
    {
        $user = $request->login();

        if (! $user) {
            return response()->json([
                'message' => "That passkey wasn't recognised. Sign in with your password instead.",
            ], 422);
        }

        // The Login event already records the sign-in in the activity log.
        $request->session()->forget('login_verification');

        return response()->json(['redirect' => route('dashboard')]);
    }
}
