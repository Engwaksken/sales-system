<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class IotecCollection extends Model
{
    use HasUserstamps;

    protected $table = 'iotec_collections';

    protected $fillable = [
        'order_id',
        'phone',
        'network',
        'amount',
        'iotec_ref',
        'status',
        'raw_response',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
