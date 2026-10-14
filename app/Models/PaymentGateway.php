<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasUserstamps;

    protected $table = 'payment_gateways';

    protected $fillable = [
        'gateway_name',
        'gateway_code',
        'display_name',
        'provider_type',
        'description',
        'is_active',
        'is_default',
        'sandbox_mode',
        'token_url',
        'collect_url',
        'base_url',
        'status_url',
        'client_id',
        'client_secret',
        'wallet_guid',
        'callback_url',
        'return_url',
        'supports_collection',
        'supports_disbursement',
        'supports_mtn',
        'supports_airtel',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sandbox_mode' => 'boolean',
        'supports_collection' => 'boolean',
        'supports_disbursement' => 'boolean',
        'supports_mtn' => 'boolean',
        'supports_airtel' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'gateway_id');
    }

}
