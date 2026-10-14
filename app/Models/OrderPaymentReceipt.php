<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class OrderPaymentReceipt extends Model
{
    use HasUserstamps;

    protected $table = 'order_payment_receipts';

    protected $fillable = [
        'order_id',
        'order_number',
        'receipt_number',
        'customer_id',
        'customer_name',
        'phone',
        'email',
        'payment_method',
        'gateway_code',
        'gateway_reference',
        'gateway_status',
        'amount_paid',
        'currency',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
