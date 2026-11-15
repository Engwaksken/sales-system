<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** One audited action: who did what to which record, from where, and whether it looks suspicious. */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'changes' => 'array',
        'flagged' => 'boolean',
        'created_at' => 'datetime',
    ];

    /** Filter names shown in the UI => subject_type values stored. */
    public const MODULES = [
        'sale' => 'Sales', 'sale_item' => 'Sale items', 'payment' => 'Payments', 'stock' => 'Stock',
        'stock_issue' => 'Stock issues', 'product' => 'Products', 'order' => 'Orders', 'expense' => 'Expenses',
        'customer' => 'Customers', 'supplier' => 'Suppliers', 'user' => 'Users', 'role_permission' => 'Permissions',
        'setting' => 'Settings', 'auth' => 'Sign-ins',
    ];

    /**
     * Shared by the website page and the mobile API.
     *
     * @param  array{q?: ?string, user_id?: mixed, module?: ?string, action?: ?string, flagged?: mixed, from?: ?string, to?: ?string, branch_id?: mixed}  $f
     */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($f['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($f['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($f['module'] ?? null, fn ($q, $m) => $m === 'auth'
                ? $q->whereIn('action', ['login', 'logout', 'login_failed'])
                : $q->where('subject_type', $m))
            ->when(filter_var($f['flagged'] ?? false, FILTER_VALIDATE_BOOL), fn ($q) => $q->where('flagged', true))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', Carbon::parse($d)->endOfDay()))
            ->when(trim((string) ($f['q'] ?? '')), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('description', 'like', "%{$s}%")
                ->orWhere('user_name', 'like', "%{$s}%")
                ->orWhere('flag_reason', 'like', "%{$s}%")));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
