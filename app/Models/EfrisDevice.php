<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class EfrisDevice extends Model
{
    use HasUserstamps;

    protected $table = 'efris_devices';

    protected $fillable = [
        'config_id',
        'branch_id',
        'device_serial_no',
        'device_type',
        'device_name',
        'activation_code',
        'activation_status',
        'activated_at',
        'invoice_counter',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    public function config()
    {
        return $this->belongsTo(EfrisConfig::class, 'config_id');
    }

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

    public function efris_invoices()
    {
        return $this->hasMany(EfrisInvoice::class, 'device_id');
    }

}
