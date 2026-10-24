<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUserstamps;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'branch_id',
        'category_id',
        'description',
        'image_url',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function product_images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function product_branches()
    {
        return $this->hasMany(ProductBranch::class, 'product_id');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'product_id');
    }

    public function sale_items()
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

}
