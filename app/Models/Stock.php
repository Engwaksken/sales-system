<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasUserstamps;

    protected $table = 'stock';

    protected $fillable = [
        'supplier_id',
        'product_id',
        'branch_id',
        'total_stock',
        'quantity',
        'unit_type',
        'cost_price',
        'expiry_date',
        'selling_price',
        'stock_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'expiry_date' => 'date',
        'selling_price' => 'decimal:2',
        'stock_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

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

}
