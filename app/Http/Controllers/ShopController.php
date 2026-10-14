<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Public storefront. Ported from shop.php — same filter set (branch, category,
 * price range, search, sort), same "fall back to newest in-stock products if no
 * slides configured" behaviour for the hero banner.
 */
class ShopController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $branchId = (int) $request->query('branch_id', 0);
        $branch = $branchId > 0 ? Branch::find($branchId) : $branches->first();

        if (! $branch) {
            abort(404, 'No branches available.');
        }

        $catFilter = (int) $request->query('cat', 0);
        $priceMin = (float) $request->query('pmin', 0);
        $priceMax = (float) $request->query('pmax', 0);
        $sort = (string) $request->query('sort', 'name_asc');
        $search = trim((string) $request->query('q', ''));

        $slides = Slide::where('is_active', true)->orderBy('sort_order')->orderBy('id')->limit(8)->get();

        if ($slides->isEmpty()) {
            $fallbackProducts = Product::query()
                ->join('stock', function ($join) use ($branch) {
                    $join->on('stock.product_id', '=', 'products.id')
                        ->where('stock.branch_id', $branch->id)
                        ->where('stock.quantity', '>', 0);
                })
                ->orderByDesc('products.id')
                ->limit(5)
                ->select('products.*', 'stock.selling_price')
                ->get();

            $slides = $fallbackProducts->map(fn ($p) => (object) [
                'title' => $p->name,
                'subtitle' => $p->description ?: "Available now at {$branch->name}",
                'badge_text' => 'New Arrival',
                'image_url' => $p->image_url ?: 'products/default.png',
                'cta_label' => 'Shop Now',
                'cta_link' => '#pc-'.$p->id,
                'bg_color' => '',
            ]);
        }

        $categories = Category::query()
            ->leftJoin('products', 'products.category_id', '=', 'categories.id')
            ->leftJoin('stock', function ($join) use ($branch) {
                $join->on('stock.product_id', '=', 'products.id')
                    ->where('stock.branch_id', $branch->id)
                    ->where('stock.quantity', '>', 0);
            })
            ->groupBy('categories.id', 'categories.category_name')
            ->select('categories.id', 'categories.category_name', DB::raw('COUNT(DISTINCT stock.product_id) as cnt'))
            ->orderBy('categories.category_name')
            ->get();

        $productsQuery = Product::query()
            ->join('stock', 'stock.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('stock.branch_id', $branch->id)
            ->where('stock.quantity', '>', 0)
            ->select(
                'products.id', 'products.name', 'products.image_url', 'products.sku',
                'products.description', 'products.category_id', 'categories.category_name',
                'stock.quantity as stock', 'stock.selling_price as price'
            );

        if ($catFilter > 0) {
            $productsQuery->where('products.category_id', $catFilter);
        }
        if ($priceMin > 0) {
            $productsQuery->where('stock.selling_price', '>=', $priceMin);
        }
        if ($priceMax > 0) {
            $productsQuery->where('stock.selling_price', '<=', $priceMax);
        }
        if ($search !== '') {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.description', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'price_asc' => $productsQuery->orderBy('stock.selling_price'),
            'price_desc' => $productsQuery->orderByDesc('stock.selling_price'),
            'newest' => $productsQuery->orderByDesc('products.id'),
            default => $productsQuery->orderBy('products.name'),
        };

        $products = $productsQuery->get();

        // New products slider: newest active, in-stock products for the selected branch.
        // This list is intentionally independent from the main product filters so
        // customers can always discover recent arrivals.
        $newProducts = Product::query()
            ->join('stock', 'stock.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('stock.branch_id', $branch->id)
            ->where('stock.quantity', '>', 0)
            ->where(function ($query) {
                $query->whereNull('products.is_active')
                    ->orWhere('products.is_active', true);
            })
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id')
            ->limit(12)
            ->select(
                'products.id',
                'products.name',
                'products.image_url',
                'products.sku',
                'products.description',
                'products.category_id',
                'categories.category_name',
                'stock.quantity as stock',
                'stock.selling_price as price',
                'products.created_at'
            )
            ->get();

        $priceBounds = DB::table('products')
            ->join('stock', function ($join) use ($branch) {
                $join->on('stock.product_id', '=', 'products.id')
                    ->where('stock.branch_id', $branch->id)
                    ->where('stock.quantity', '>', 0);
            })
            ->selectRaw('COALESCE(MIN(stock.selling_price), 0) as mn, COALESCE(MAX(stock.selling_price), 0) as mx')
            ->first();

        $deliveryZones = DeliveryZone::where('is_active', true)->orderBy('fee')->orderBy('zone_name')->get();

        $paymentGateways = PaymentGateway::where('is_active', true)->where('supports_collection', true)
            ->orderByDesc('is_default')->orderBy('display_name')->get();

        $mobileGateways = $paymentGateways->filter(fn ($g) => in_array($g->provider_type, ['mobile_money', 'mixed'])
            || $g->supports_mtn || $g->supports_airtel);

        return view('shop.index', [
            'branches' => $branches,
            'branch' => $branch,
            'slides' => $slides,
            'categories' => $categories,
            'products' => $products,
            'newProducts' => $newProducts,
            'priceBounds' => $priceBounds,
            'deliveryZones' => $deliveryZones,
            'mobileGateways' => $mobileGateways,
            'catFilter' => $catFilter,
            'search' => $search,
            'sort' => $sort,
        ]);
    }


    /**
     * Stream a product image through Laravel.
     *
     * This avoids broken /storage/... URLs when public/storage symlinks are
     * unavailable or restricted by shared hosting.
     */
    public function productImage(Product $product)
    {
        if (! $product->image_url) {
            abort(404);
        }

        // Existing installations may contain an absolute external image URL.
        if (filter_var($product->image_url, FILTER_VALIDATE_URL)) {
            return redirect()->away($product->image_url);
        }

        $path = ltrim($product->image_url, '/');

        // Normalize legacy values such as storage/products/file.jpg.
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $mime = $disk->mimeType($path) ?: 'image/jpeg';

        return response($disk->get($path), 200, [
            'Content-Type' => $mime,
            'Content-Length' => $disk->size($path),
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

}
