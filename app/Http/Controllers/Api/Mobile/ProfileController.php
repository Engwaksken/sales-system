<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Support\PublicMedia;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $isCustomer = ($user->role ?: 'customer') === 'customer';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $user, $data, $isCustomer) {
            $user->name = $data['name'];

            if ($request->hasFile('photo') || $request->boolean('remove_photo')) {
                $this->deletePhoto($user->profile_pic);
                $user->profile_pic = $request->hasFile('photo')
                    ? $request->file('photo')->store('avatars', 'public')
                    : null;
            }
            $user->save();

            if ($isCustomer && Schema::hasTable('customers')) {
                $customer = $this->customer($user) ?? new Customer([
                    'email' => $user->email,
                    'branch_id' => $user->branch_id,
                ]);
                if (Schema::hasColumn('customers', 'user_id')) {
                    $customer->user_id = $user->id;
                }
                $customer->name = $data['name'];
                $customer->phone = $data['phone'] ?? $customer->phone;
                $customer->address = $data['address'] ?? $customer->address;
                $customer->save();
            }
        });

        return response()->json([
            'message' => 'Profile saved.',
            'data' => $this->payload($user->fresh()),
        ]);
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
        if (! Schema::hasTable('customers')) {
            return null;
        }

        return Customer::query()
            ->when(Schema::hasColumn('customers', 'user_id'),
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('email', $user->email))
            ->first();
    }

    private function deletePhoto(?string $path): void
    {
        $path = trim((string) $path);
        if ($path !== '' && str_starts_with($path, 'avatars/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
