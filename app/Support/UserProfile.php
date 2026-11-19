<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Saving a user's own profile, shared by the website and the mobile app. */
class UserProfile
{
    public static function isCustomer(User $user): bool
    {
        return ($user->role ?: 'customer') === 'customer';
    }

    /** The customer record linked to a customer account, if any. */
    public static function customer(User $user): ?Customer
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

    /**
     * Saves the name and photo; for customers also phone and address on their
     * customer record (created if missing). A null phone or address keeps the old one.
     */
    public static function save(
        User $user,
        string $name,
        ?UploadedFile $photo = null,
        bool $removePhoto = false,
        ?string $phone = null,
        ?string $address = null,
    ): void {
        DB::transaction(function () use ($user, $name, $photo, $removePhoto, $phone, $address) {
            $user->name = $name;

            if ($photo || $removePhoto) {
                self::deletePhoto($user->profile_pic);
                $user->profile_pic = $photo ? $photo->store('avatars', 'public') : null;
            }
            $user->save();

            if (self::isCustomer($user) && Schema::hasTable('customers')) {
                $customer = self::customer($user) ?? new Customer([
                    'email' => $user->email,
                    'branch_id' => $user->branch_id,
                ]);
                if (Schema::hasColumn('customers', 'user_id')) {
                    $customer->user_id = $user->id;
                }
                $customer->name = $name;
                $customer->phone = $phone ?? $customer->phone;
                $customer->address = $address ?? $customer->address;
                $customer->save();
            }
        });
    }

    public static function photoUrl(User $user): ?string
    {
        return PublicMedia::url($user->profile_pic);
    }

    private static function deletePhoto(?string $path): void
    {
        $path = trim((string) $path);
        if ($path !== '' && str_starts_with($path, 'avatars/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
