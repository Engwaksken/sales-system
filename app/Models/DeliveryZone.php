<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasUserstamps;

    protected $table = 'delivery_zones';

    protected $fillable = [
        'zone_name',
        'fee',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
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
        return $this->hasMany(Order::class, 'delivery_zone_id');
    }

}
