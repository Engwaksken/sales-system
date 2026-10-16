<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNotificationRead extends Model
{
    protected $fillable = ['user_id', 'order_id', 'notification_version', 'read_at'];

    protected $casts = [
        'user_id' => 'integer',
        'order_id' => 'integer',
        'notification_version' => 'integer',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
