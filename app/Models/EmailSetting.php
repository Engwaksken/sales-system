<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    use HasUserstamps;

    protected $table = 'email_settings';

    protected $fillable = [
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_password',
        'smtp_secure',
        'from_email',
        'from_name',
        'created_by',
        'updated_by',
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
