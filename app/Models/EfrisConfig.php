<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class EfrisConfig extends Model
{
    use HasUserstamps;

    protected $table = 'efris_config';

    protected $fillable = [
        'branch_id',
        'tin',
        'taxpayer_name',
        'taxpayer_address',
        'taxpayer_email',
        'taxpayer_phone',
        'business_type',
        'vat_registered',
        'environment',
        'private_key_pem',
        'public_key_pem',
        'ura_public_key_pem',
        'portal_username',
        'portal_password_enc',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vat_registered' => 'boolean',
        'is_active' => 'boolean',
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

    public function efris_devices()
    {
        return $this->hasMany(EfrisDevice::class, 'config_id');
    }

    public function efris_invoices()
    {
        return $this->hasMany(EfrisInvoice::class, 'config_id');
    }

    public function efris_api_logs()
    {
        return $this->hasMany(EfrisApiLog::class, 'config_id');
    }

}
