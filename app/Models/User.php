<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasUserstamps;

    protected $table = 'users';

    /** Every role a user can hold, with its display label. Keep in sync with the users.role column. */
    public const ROLES = [
        'admin' => 'Admin',
        'general_manager' => 'General Manager',
        'manager' => 'Manager',
        'marketing_manager' => 'Marketing Manager',
        'accountant' => 'Accountant',
        'cashier' => 'Cashier',
        'customer' => 'Customer',
    ];

    /** Back-office roles whose access is controlled by role_permissions (admin always passes). */
    public const STAFF_ROLES = ['general_manager', 'manager', 'marketing_manager', 'accountant', 'cashier'];

    public static function roleLabel(?string $role): string
    {
        try {
            return Role::query()->where('name', $role)->value('label')
                ?? self::ROLES[$role] ?? ucwords(str_replace('_', ' ', (string) $role));
        } catch (\Throwable) {
            // Let existing user pages remain usable until the roles migration is applied.
            return self::ROLES[$role] ?? ucwords(str_replace('_', ' ', (string) $role));
        }
    }

    public static function roleOptions(): array
    {
        try { return Role::options() ?: self::ROLES; } catch (\Throwable) { return self::ROLES; }
    }

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'branch_id',
        'profile_pic',
        'verification_code',
        'code_expires',
        'remember_token',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'code_expires' => 'datetime',
    ];

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function systemNotificationReads(): HasMany
    {
        return $this->hasMany(SystemNotificationRead::class);
    }

    public function orderNotificationReads(): HasMany
    {
        return $this->hasMany(OrderNotificationRead::class);
    }
}
