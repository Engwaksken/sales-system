<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasUserstamps;

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'location',
        'contact',
        'branch_email',
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

    public function users()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'branch_id');
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'branch_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'branch_id');
    }

    public function product_branches()
    {
        return $this->hasMany(ProductBranch::class, 'branch_id');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'branch_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'branch_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'branch_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'branch_id');
    }

    public function efris_config()
    {
        return $this->hasMany(EfrisConfig::class, 'branch_id');
    }

    public function efris_devices()
    {
        return $this->hasMany(EfrisDevice::class, 'branch_id');
    }

    public function accounting_journals()
    {
        return $this->hasMany(AccountingJournal::class, 'branch_id');
    }

    public function chat_conversations()
    {
        return $this->hasMany(ChatConversation::class, 'branch_id');
    }

}
