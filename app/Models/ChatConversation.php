<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    use HasUserstamps;

    protected $table = 'chat_conversations';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'branch_id',
        'status',
        'created_by',
        'updated_by',
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

    public function chat_messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    /**
     * Backward-compatible alias. Some older/newer chat code calls messages().
     */
    public function messages()
    {
        return $this->chat_messages();
    }
}
