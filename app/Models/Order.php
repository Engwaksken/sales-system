<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUserstamps;

    protected $table = 'orders';

    // Match the schema default on newly created instances without a refresh.
    // This counter is deliberately not mass assignable.
    protected $attributes = [
        'notification_version' => 1,
    ];

    protected $fillable = [
        'sale_id',
        'order_id',
        'customer_id',
        'customer_name',
        'phone',
        'email',
        'delivery_location',
        'delivery_zone_id',
        'branch_id',
        'payment_method',
        'gateway_id',
        'gateway_code',
        'gateway_reference',
        'gateway_status',
        'gateway_message',
        'mm_phone',
        'mm_network',
        'payment_status',
        'total_amount',
        'discount',
        'amount_paid',
        'balance_amount',
        'status',
        'orders_count',
        'notes',
        'is_guest_order',
        'created_by_user_id',
        'paid_at',
        'delivery_zone',
        'delivery_fee',
        'subtotal',
        'order_number',
        'address',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'notification_version' => 'integer',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'is_guest_order' => 'boolean',
        'paid_at' => 'datetime',
        'delivery_fee' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function delivery_zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }

    public function created_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function order_items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function order_logs(): HasMany
    {
        return $this->hasMany(OrderLog::class, 'order_id');
    }

    public function order_payment_receipts(): HasMany
    {
        return $this->hasMany(OrderPaymentReceipt::class, 'order_id');
    }


    public function notificationReads(): HasMany
    {
        return $this->hasMany(OrderNotificationRead::class);
    }

    /**
     * Preserve Eloquent's update lifecycle, but serialize status comparisons against
     * the stored row. Bulk/query-builder updates bypass this model hook.
     */
    protected function performUpdate(Builder $query)
    {
        return $this->getConnection()->transaction(function () use ($query) {
            if ($this->fireModelEvent('updating') === false) {
                return false;
            }

            if ($this->usesTimestamps()) {
                $this->updateTimestamps();
            }

            $dirty = $this->getDirtyForUpdate();
            // The version is owned by this hook, never by a caller's update payload.
            unset($dirty['notification_version']);

            if (count($dirty) > 0) {
                $stored = $this->setKeysForSaveQuery($this->newModelQuery())
                    ->lockForUpdate()->first(['status', 'notification_version']);

                if ($stored) {
                    // A stale instance must use the locked counter, not its original
                    // version. Merely catching up is not a version change by this save.
                    $this->setAttribute('notification_version', $stored->notification_version);
                    $this->syncOriginalAttribute('notification_version');
                }

                if ($stored && array_key_exists('status', $dirty)
                    && $stored->status !== $this->status) {
                    $column = $this->getConnection()->getQueryGrammar()->wrap('notification_version');
                    $dirty['notification_version'] = $this->getConnection()->raw($column.' + 1');
                    $this->setAttribute('notification_version', $stored->notification_version + 1);
                }

                $this->setKeysForSaveQuery($query)->update($dirty);

                $this->syncChanges();
                $this->fireModelEvent('updated', false);
            } else {
                $this->setAttribute('notification_version', $this->getRawOriginal('notification_version'));
            }

            return true;
        });
    }

    public const STAGES = [
        'pending' => 'Received',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'ready_for_pickup' => 'Ready for Pickup',
        'ready_for_delivery' => 'Ready for Delivery',
        'out_for_delivery' => 'Out for Delivery',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function stageLabel(): string
    {
        return self::STAGES[$this->status] ?? str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function isReadyForPos(): bool
    {
        return in_array($this->status, ['ready_for_pickup', 'ready_for_delivery'], true);
    }

    public function hasPosSale(): bool
    {
        return ! is_null($this->sale_id);
    }

}
