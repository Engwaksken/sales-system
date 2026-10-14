<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasUserstamps;

    protected $table = 'suppliers';

    protected $fillable = [
        'branch_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
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

    public function stock()
    {
        return $this->hasMany(Stock::class, 'supplier_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'supplier_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'supplier_id');
    }

}
