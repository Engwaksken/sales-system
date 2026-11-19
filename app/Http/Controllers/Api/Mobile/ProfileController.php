<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Support\DocumentSignature;
use App\Support\PublicMedia;
use App\Support\UserProfile;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** The signed-in user's own profile: name, photo, password (and phone/address for customers). */
class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($request->user())]);
    }

    /** Multipart: name, photo (image), remove_photo; customers also phone and address. */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $isCustomer = UserProfile::isCustomer($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        UserProfile::save(
            $user,
            $data['name'],
            photo: $request->file('photo'),
            removePhoto: $request->boolean('remove_photo'),
            phone: $isCustomer ? ($data['phone'] ?? null) : null,
            address: $isCustomer ? ($data['address'] ?? null) : null,
        );

        return response()->json([
            'message' => 'Profile saved.',
            'data' => $this->payload($user->fresh()),
        ]);
    }

    /** Saves the signature printed on this user's invoices and receipts: a drawn PNG (data URL) or an uploaded image. */
    public function saveSignature(Request $request): JsonResponse
    {
        $request->validate([
            'signature' => ['required_without:signature_data', 'nullable', 'image', 'mimes:png,jpg,jpeg', 'max:512'],
            'signature_data' => ['required_without:signature', 'nullable', 'string', 'max:700000'],
        ]);

        $user = $request->user();
        DocumentSignature::store($user, $request->file('signature') ?? $request->input('signature_data'));

        return response()->json([
            'message' => 'Signature saved. It will appear on your invoices and receipts.',
            'data' => $this->payload($user->fresh()),
        ]);
    }

    public function removeSignature(Request $request): JsonResponse
    {
        $user = $request->user();
        DocumentSignature::remove($user);

        return response()->json(['message' => 'Signature removed.', 'data' => $this->payload($user->fresh())]);
    }

    public function password(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }
        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Choose a password different from the current one.']);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        // Sign out other phones and browsers; keep this app signed in.
        $current = $user->currentAccessToken();
        $user->tokens()->when($current && method_exists($current, 'getKey'), fn ($q) => $q->where('id', '!=', $current->getKey()))->delete();

        return response()->json(['message' => 'Password changed. Other devices have been signed out.']);
    }

    private function payload(User $user): array
    {
        $customer = ($user->role ?: 'customer') === 'customer' ? $this->customer($user) : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?: 'customer',
            'role_label' => User::roleLabel($user->role ?: 'customer'),
            'branch_name' => $user->branch_id && Schema::hasTable('branches')
                ? DB::table('branches')->where('id', $user->branch_id)->value('name') : null,
            'photo_url' => self::photoUrl($user->profile_pic),
            'signature' => DocumentSignature::dataUri($user),
            'phone' => $customer?->phone,
            'address' => $customer?->address,
            'is_customer' => ($user->role ?: 'customer') === 'customer',
            'member_since' => optional($user->created_at)?->toDateString(),
        ];
    }

    public static function photoUrl(?string $path): ?string
    {
        return PublicMedia::url($path);
    }

    private function customer(User $user): ?Customer
    {
        return UserProfile::customer($user);
    }
}
