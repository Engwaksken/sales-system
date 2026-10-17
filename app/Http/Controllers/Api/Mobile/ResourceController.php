<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResourceController extends Controller
{
    private const RESOURCES = [
        'sales' => 'sales',
        'orders' => 'orders',
        'products' => 'products',
        'stock' => 'stock',
        'customers' => 'customers',
        'payments' => 'payments',
        'order-payment-receipts' => 'order_payment_receipts',
        'notifications' => 'notifications_app',
        'expenses' => 'expenses',
    ];

    public function index(Request $request, string $resource): JsonResponse
    {
        $table = $this->table($resource);

        if (! Schema::hasTable($table)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ],
            ]);
        }

        $columns = Schema::getColumnListing($table);
        $query = DB::table($table);

        $this->authorizeResource($request, $resource, $query, $columns);

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $searchColumns = array_values(array_intersect($columns, [
                'name',
                'title',
                'invoice_no',
                'receipt_no',
                'receipt_number',
                'order_number',
                'customer_name',
                'email',
                'phone',
                'sku',
                'description',
                'reference_no',
                'gateway_reference',
            ]));

            $saleIds = collect();
            if ($resource === 'sales' &&
                Schema::hasTable('sale_items') &&
                Schema::hasTable('products')) {
                $saleIds = DB::table('sale_items')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->where('products.name', 'like', '%'.$search.'%')
                    ->whereIn('sale_items.sale_id', (clone $query)->select('id'))
                    ->pluck('sale_items.sale_id')
                    ->unique()
                    ->values();
            }

            if ($searchColumns || $saleIds->isNotEmpty()) {
                $query->where(function ($q) use ($searchColumns, $search, $saleIds): void {
                    foreach ($searchColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $q->{$method}($column, 'like', '%'.$search.'%');
                    }

                    if ($saleIds->isNotEmpty()) {
                        $method = $searchColumns ? 'orWhereIn' : 'whereIn';
                        $q->{$method}('id', $saleIds);
                    }
                });
            }
        }

        $sort = in_array('created_at', $columns, true)
            ? 'created_at'
            : (in_array('id', $columns, true) ? 'id' : $columns[0]);

        $paginator = $query
            ->orderByDesc($sort)
            ->paginate(min(max((int) $request->query('per_page', 20), 5), 100));

        $rows = collect($paginator->items())
            ->map(fn ($row) => (array) $row);

        $rows = $this->safeRows($rows, $resource, $request->user()->role, $columns);

        if ($resource === 'sales') {
            $rows = $this->attachSaleProducts($rows);
        }

        return response()->json([
            'data' => $rows->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, string $resource, int $id): JsonResponse
    {
        $table = $this->table($resource);

        abort_unless(Schema::hasTable($table), 404);

        $query = DB::table($table);
        $this->authorizeResource($request, $resource, $query, Schema::getColumnListing($table));
        $row = $query->where('id', $id)->first();

        abort_unless($row, 404);

        $record = (array) $row;
        $record = $this->safeRows(collect([$record]), $resource, $request->user()->role, Schema::getColumnListing($table))->first();
        $data = collect([$record]);

        if ($resource === 'sales') {
            $data = $this->attachSaleProducts($data);
        }

        return response()->json([
            'data' => $data->first(),
        ]);
    }

    private function attachSaleProducts(Collection $rows): Collection
    {
        if ($rows->isEmpty() ||
            ! Schema::hasTable('sale_items') ||
            ! Schema::hasTable('products')) {
            return $rows;
        }

        $saleIds = $rows
            ->pluck('id')
            ->filter()
            ->values();

        $products = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereIn('sale_items.sale_id', $saleIds)
            ->select([
                'sale_items.sale_id',
                'sale_items.product_id',
                'sale_items.quantity',
                'sale_items.price',
                'sale_items.total',
                'products.name as product_name',
                'products.sku as product_sku',
            ])
            ->orderBy('sale_items.id')
            ->get()
            ->groupBy('sale_id');

        return $rows->map(function (array $row) use ($products): array {
            $items = collect($products->get($row['id'], []))
                ->map(fn ($item) => (array) $item)
                ->values();

            $row['items'] = $items->all();
            $row['product_names'] = $items
                ->pluck('product_name')
                ->filter()
                ->unique()
                ->implode(', ');

            return $row;
        });
    }

    private function table(string $resource): string
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    private function authorizeResource(Request $request, string $resource, $query, array $columns): void
    {
        $user = $request->user();
        abort_unless($user, 401);
        if ($user->role === 'admin') return;

        if ($user->role === 'customer') {
            abort_unless($resource === 'products' || in_array($resource, ['sales', 'orders', 'customers', 'payments', 'order-payment-receipts'], true), 403);
        } else {
            $permissions = [
                'sales' => 'manage_sales', 'payments' => 'manage_sales', 'order-payment-receipts' => 'manage_sales',
                'expenses' => 'manage_expenses', 'orders' => 'manage_orders', 'products' => 'manage_products',
                'stock' => 'manage_stock', 'customers' => 'manage_customers', 'notifications' => 'manage_notifications',
            ];
            abort_unless(isset($permissions[$resource]), 403);
            $this->requirePermission($user, $permissions[$resource]);
        }

        if ($user->role === 'customer' && $resource !== 'products') {
            abort_unless(Schema::hasTable('customers') && Schema::hasColumn('customers', 'user_id'), 403);
            $customer = DB::table('customers')->where('user_id', $user->id)->first();
            abort_unless($customer, 403);
            $id = $customer->id;
            if ($resource === 'sales') $query->where('customer_id', $id);
            elseif ($resource === 'customers') $query->where('id', $id);
            elseif ($resource === 'orders') {
                abort_unless(Schema::hasColumn('orders', 'customer_id'), 403);
                $query->whereNotNull('customer_id')->where('customer_id', $id);
            }
            elseif ($resource === 'payments') {
                abort_unless(Schema::hasColumn('payments', 'sale_id') && Schema::hasTable('sales'), 403);
                $query->whereIn('sale_id', DB::table('sales')->select('id')->where('customer_id', $id));
            } elseif ($resource === 'order-payment-receipts') {
                abort_unless(Schema::hasColumn('order_payment_receipts', 'order_id') && Schema::hasTable('orders'), 403);
                $query->whereIn('order_id', DB::table('orders')->select('id')->where('customer_id', $id));
            } else abort(403);
        }

        if ($user->role !== 'customer' && in_array($resource, ['sales','orders','stock','payments','expenses','order-payment-receipts','customers','notifications'], true)) {
            abort_unless(Schema::hasTable('branches') && Schema::hasColumn('branches', 'id'), 403);
            abort_unless($user->branch_id, 403);
            if ($resource === 'customers') {
                abort_unless(Schema::hasTable('customers') && Schema::hasColumn('customers', 'branch_id'), 403);
                $query->where('branch_id', $user->branch_id);
            }
            elseif (in_array('branch_id', $columns, true)) $query->where('branch_id', $user->branch_id);
            elseif ($resource === 'payments' && Schema::hasColumn('payments', 'sale_id') && Schema::hasTable('sales') && Schema::hasColumn('sales', 'branch_id')) $query->whereIn('sale_id', DB::table('sales')->select('id')->where('branch_id', $user->branch_id));
            elseif ($resource === 'order-payment-receipts' && Schema::hasColumn('order_payment_receipts', 'order_id')) $query->whereIn('order_id', DB::table('orders')->select('id')->where('branch_id', $user->branch_id));
            elseif ($resource === 'notifications') {
                abort_unless(Schema::hasTable('notifications_app') && Schema::hasColumn('notifications_app', 'user_id'), 403);
                $query->whereNotNull('user_id')->where('user_id', $user->id);
            }
            else abort(403);
        }
    }

    private function safeRows(Collection $rows, string $resource, string $role, array $columns): Collection
    {
        $allow = [
            'sales' => ['id','customer_id','invoice_no','total_amount','discount','vat','payment_method','sale_date','payment_status','partial_amount','balance_amount','branch_id','created_at','items','product_names'],
            'orders' => ['id','sale_id','order_id','customer_id','customer_name','phone','email','payment_method','payment_status','total_amount','discount','amount_paid','balance_amount','status','orders_count','paid_at','delivery_zone','delivery_fee','subtotal','order_number','created_at'],
            'products' => ['id','name','sku','description','price','selling_price','image','category_id'],
            'stock' => ['id','product_id','branch_id','quantity','stock_date','created_at'],
            'customers' => ['id','name','email','phone','address'],
            'payments' => ['id','invoice_no','receipt_no','sale_id','amount','method','status','created_at'],
            'order-payment-receipts' => ['id','order_id','order_number','receipt_number','payment_method','amount_paid','currency','created_at'],
            'notifications' => ['id','title','message','type','is_read','created_at'],
            'expenses' => ['id','title','amount','expense_date','category','description','branch_id','created_at'],
        ];
        abort_unless(isset($allow[$resource]), 403);
        $safe = array_intersect($allow[$resource], $columns);
        return $rows->map(fn (array $row) => array_intersect_key($row, array_flip($safe)));
    }

    private function requirePermission($user, string $permission): void
    {
        abort_unless(\App\Models\RolePermission::where('role', $user->role)->where('permission_key', $permission)->exists(), 403);
    }
}
