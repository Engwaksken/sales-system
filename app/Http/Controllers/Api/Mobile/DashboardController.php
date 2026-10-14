<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return response()->json([
            'stats' => [
                [
                    'key' => 'today_sales',
                    'label' => "Today's Sales",
                    'value' => $this->sumBetween('sales', 'total_amount', 'sale_date', $today, $today),
                    'icon' => 'point_of_sale',
                    'type' => 'money',
                ],
                [
                    'key' => 'month_sales',
                    'label' => 'This Month Sales',
                    'value' => $this->sumBetween('sales', 'total_amount', 'sale_date', $monthStart, $today),
                    'icon' => 'trending_up',
                    'type' => 'money',
                ],
                [
                    'key' => 'month_expenses',
                    'label' => 'This Month Expenses',
                    'value' => $this->sumBetween('expenses', 'amount', 'expense_date', $monthStart, $today),
                    'icon' => 'receipt_long',
                    'type' => 'money',
                ],
                [
                    'key' => 'pending_orders',
                    'label' => 'Pending Orders',
                    'value' => $this->countWhere('orders', 'status', 'pending'),
                    'icon' => 'shopping_cart',
                    'type' => 'number',
                ],
                [
                    'key' => 'customers',
                    'label' => 'Customers',
                    'value' => $this->count('customers'),
                    'icon' => 'people',
                    'type' => 'number',
                ],
                [
                    'key' => 'products',
                    'label' => 'Products',
                    'value' => $this->count('products'),
                    'icon' => 'inventory_2',
                    'type' => 'number',
                ],
                [
                    'key' => 'payments',
                    'label' => 'Payments',
                    'value' => $this->count('payments'),
                    'icon' => 'payments',
                    'type' => 'number',
                ],
                [
                    'key' => 'notifications',
                    'label' => 'Notifications',
                    'value' => $this->countWhere('notifications_app', 'is_read', 0),
                    'icon' => 'notifications',
                    'type' => 'number',
                ],
            ],
            'recent_sales' => $this->recent('sales', 5),
            'recent_orders' => $this->recent('orders', 5),
            'currency' => $this->setting('currency', 'UGX'),
        ]);
    }

    private function count(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function countWhere(string $table, string $column, mixed $value): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) return 0;
        return DB::table($table)->where($column, $value)->count();
    }

    private function sumBetween(
        string $table,
        string $amountColumn,
        string $dateColumn,
        string $from,
        string $to
    ): float {
        if (
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, $amountColumn) ||
            ! Schema::hasColumn($table, $dateColumn)
        ) return 0;

        return (float) DB::table($table)
            ->whereDate($dateColumn, '>=', $from)
            ->whereDate($dateColumn, '<=', $to)
            ->sum($amountColumn);
    }

    private function recent(string $table, int $limit): array
    {
        if (! Schema::hasTable($table)) return [];

        $query = DB::table($table);
        $columns = Schema::getColumnListing($table);

        if (in_array('created_at', $columns, true)) {
            $query->orderByDesc('created_at');
        } elseif (in_array('id', $columns, true)) {
            $query->orderByDesc('id');
        }

        return $query->limit($limit)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) return $default;

        $keyColumn = Schema::hasColumn('settings', 'setting_key') ? 'setting_key' : 'key';
        $valueColumn = Schema::hasColumn('settings', 'setting_value') ? 'setting_value' : 'value';

        return DB::table('settings')->where($keyColumn, $key)->value($valueColumn) ?? $default;
    }
}
