<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class EfrisInvoice extends Model
{
    use HasUserstamps;

    protected $table = 'efris_invoices';

    protected $fillable = [
        'sale_id',
        'config_id',
        'device_id',
        'invoice_no',
        'tin',
        'issued_at',
        'gross_amount',
        'status',
        'qr_code',
        'verification_code',
        'request_json',
        'response_json',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'gross_amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function config()
    {
        return $this->belongsTo(EfrisConfig::class, 'config_id');
    }

    public function device()
    {
        return $this->belongsTo(EfrisDevice::class, 'device_id');
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
