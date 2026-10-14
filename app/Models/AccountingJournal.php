<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class AccountingJournal extends Model
{
    use HasUserstamps;

    protected $table = 'accounting_journals';

    protected $fillable = [
        'journal_no',
        'journal_type',
        'reference_no',
        'reference_type',
        'reference_id',
        'journal_date',
        'description',
        'branch_id',
        'created_by',
        'is_posted',
        'updated_by',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'is_posted' => 'boolean',
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

    public function journal_entries()
    {
        return $this->hasMany(JournalEntry::class, 'journal_id');
    }

}
