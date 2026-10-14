<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUserstamps;

    protected $table = 'orders';

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
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'is_guest_order' => 'boolean',
        'paid_at' => 'datetime',
        'delivery_fee' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function delivery_zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function order_logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id');
    }

    public function order_payment_receipts()
    {
        return $this->hasMany(OrderPaymentReceipt::class, 'order_id');
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
