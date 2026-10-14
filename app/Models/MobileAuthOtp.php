<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileAuthOtp extends Model
{
    protected $fillable = [
        'email',
        'purpose',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'reset_token_hash',
        'reset_token_expires_at',
    ];

    protected $hidden = [
        'code_hash',
        'reset_token_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
    ];
}
