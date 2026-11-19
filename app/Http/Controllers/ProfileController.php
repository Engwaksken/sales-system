<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** The signed-in user's own profile on the website: details, photo and password. */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $isCustomer = UserProfile::isCustomer($user);

        return view('profile.edit', [
            'user' => $user,
            'isCustomer' => $isCustomer,
            'customer' => $isCustomer ? UserProfile::customer($user) : null,
            'photoUrl' => UserProfile::photoUrl($user),
            'roleLabel' => User::roleLabel($user->role ?: 'customer'),
            'branchName' => $user->branch_id && Schema::hasTable('branches')
                ? DB::table('branches')->where('id', $user->branch_id)->value('name') : null,
            'passkeyCount' => $user->webAuthnCredentials()->whereEnabled()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isCustomer = UserProfile::isCustomer($user);

        $data = $request->validateWithBag('details', [
            'name' => ['required', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],
            'phone' => [$isCustomer ? 'required' : 'nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'photo.image' => 'The photo must be a picture (JPG, PNG or WebP).',
            'photo.max' => 'The photo must be 4 MB or smaller.',
        ]);

        UserProfile::save(
            $user,
            trim($data['name']),
            photo: $request->file('photo'),
            removePhoto: $request->boolean('remove_photo'),
            phone: $isCustomer ? trim((string) ($data['phone'] ?? '')) : null,
            address: $isCustomer ? trim((string) ($data['address'] ?? '')) : null,
        );

        return redirect()->route('profile.edit')->with('success', 'Your profile has been saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.different' => 'Choose a password different from the current one.',
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            // Remembered sign-ins on other browsers stop working.
            'remember_token' => Str::random(60),
        ])->save();

        // Sign out everywhere else: other browser sessions and the mobile app
        // (including its fingerprint sign-in). This browser stays signed in.
        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }
        $user->tokens()->delete();

        ActivityLogger::record('password_changed', 'Changed their password on the website', user: $user);

        return redirect()->route('profile.edit')
            ->with('success', 'Password changed. Other browsers and the mobile app have been signed out.');
    }
}
