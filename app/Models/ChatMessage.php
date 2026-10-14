<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasUserstamps;

    protected $table = 'chat_messages';

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'user_id',
        'message',
        'attachment_path',
        'is_read',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
