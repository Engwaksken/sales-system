<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasUserstamps;

    protected $table = 'journal_entries';

    protected $fillable = [
        'journal_id',
        'account_code',
        'description',
        'debit',
        'credit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journal()
    {
        return $this->belongsTo(AccountingJournal::class, 'journal_id');
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
