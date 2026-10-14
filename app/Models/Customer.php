<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUserstamps;

    protected $table = 'customers';

    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'phone',
        'email',
        'address',
        'created_by',
        'updated_by',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function order_payment_receipts()
    {
        return $this->hasMany(OrderPaymentReceipt::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customer_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
