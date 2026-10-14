<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class EfrisApiLog extends Model
{
    use HasUserstamps;

    protected $table = 'efris_api_logs';

    protected $fillable = [
        'config_id',
        'operation_code',
        'operation_name',
        'http_method',
        'endpoint_url',
        'reference_type',
        'reference_id',
        'request_body',
        'response_body',
        'http_status_code',
        'efris_return_code',
        'efris_return_msg',
        'is_success',
        'latency_ms',
        'requested_at',
        'responded_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_success' => 'boolean',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function config()
    {
        return $this->belongsTo(EfrisConfig::class, 'config_id');
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
