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

    /**
     * Browser-ready URL for the product image, or null when there is none.
     *
     * Local files are streamed through the shop.product-image route rather than
     * /storage/... so previews work even when the public/storage symlink is
     * missing or stale. The version query busts the cache after an image change.
     */
    public function getImagePreviewUrlAttribute(): ?string
    {
        $path = trim((string) $this->image_url);

        if ($path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return route('shop.product-image', [
            'product' => $this->getKey(),
            'v' => $this->updated_at?->timestamp,
        ]);
    }

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
