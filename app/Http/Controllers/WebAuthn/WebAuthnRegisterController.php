<?php

namespace App\Http\Controllers\WebAuthn;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

/** The signed-in user's own passkeys: list, add and remove. */
class WebAuthnRegisterController extends Controller
{
    public function index(Request $request): View
    {
        return view('passkeys.index', [
            'passkeys' => $request->user()->webAuthnCredentials()->whereEnabled()->latest()->get(),
        ]);
    }

    /**
     * A challenge for the device to make a new passkey. It must check the person
     * (fingerprint, face or PIN) and keep the account on the device, so sign-in
     * needs no email.
     */
    public function options(AttestationRequest $request): Responsable
    {
        return $request->secureRegistration()->userless()->toCreate();
    }

    public function register(AttestedRequest $request): JsonResponse
    {
        $alias = trim((string) $request->input('alias', ''));
        $alias = mb_substr($alias !== '' ? $alias : $this->deviceName((string) $request->userAgent()), 0, 60);

        $request->save(['alias' => $alias]);
        ActivityLogger::record('passkey_added', 'Added a passkey: '.$alias, user: $request->user());

        return response()->json(['message' => 'Passkey added. You can now sign in with it.'], 201);
    }

    public function destroy(Request $request, string $passkey): RedirectResponse
    {
        $credential = $request->user()->webAuthnCredentials()->whereKey($passkey)->firstOrFail();
        $credential->delete();
        ActivityLogger::record('passkey_removed', 'Removed a passkey: '.($credential->alias ?: 'unnamed'), user: $request->user());

        return redirect()->route('passkeys.index')->with('success', 'Passkey removed. It can no longer sign in to your account.');
    }

    /** A readable default name such as "Chrome on Android". */
    private function deviceName(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') => 'Mac',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'this device',
        };

        return "$browser on $system";
    }
}
