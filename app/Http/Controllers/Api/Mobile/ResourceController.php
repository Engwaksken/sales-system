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

            if ($searchColumns) {
                $query->where(function ($q) use ($searchColumns, $search): void {
                    foreach ($searchColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $q->{$method}($column, 'like', '%'.$search.'%');
                    }
                });
            }

            if ($resource === 'sales' &&
                Schema::hasTable('sale_items') &&
                Schema::hasTable('products')) {
                $saleIds = DB::table('sale_items')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->where('products.name', 'like', '%'.$search.'%')
                    ->pluck('sale_items.sale_id')
                    ->unique()
                    ->values();

                if ($saleIds->isNotEmpty()) {
                    $query->orWhereIn('id', $saleIds);
                }
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

    public function show(string $resource, int $id): JsonResponse
    {
        $table = $this->table($resource);

        abort_unless(Schema::hasTable($table), 404);

        $row = DB::table($table)->where('id', $id)->first();

        abort_unless($row, 404);

        $data = collect([(array) $row]);

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
}
