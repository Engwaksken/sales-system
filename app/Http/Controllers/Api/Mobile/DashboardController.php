<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\NotificationInbox;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * The staff dashboard in the app: statistic cards (each says which module and
 * filter it opens), chart series and recent records. A user only gets the cards
 * and charts their permissions allow; non-admins see their own branch.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        // Business-wide totals are for staff only; customers use /customer/home.
        abort_if(($user?->role ?: 'customer') === 'customer', 403);

        $branchId = $user->role === 'admin' ? null : $user->branch_id;
        $can = fn (string $permission) => CheckPermission::allows($user, $permission);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $stats = [];
        if ($can('manage_sales')) {
            $stats[] = $this->stat('today_sales', "Today's Sales", $this->sum('sales', 'total_amount', 'sale_date', $today, $today, $branchId), 'point_of_sale', 'money', 'sales', ['from' => $today, 'to' => $today]);
            $stats[] = $this->stat('month_sales', 'Month Sales', $this->sum('sales', 'total_amount', 'sale_date', $monthStart, $today, $branchId), 'trending_up', 'money', 'sales', ['from' => $monthStart, 'to' => $today]);
            $stats[] = $this->stat('outstanding', 'Outstanding Credit', $this->scoped('sales', $branchId)?->where('balance_amount', '>', 0)->sum('balance_amount') ?? 0, 'account_balance_wallet', 'money', 'sales', ['status' => 'outstanding']);
            $stats[] = $this->stat('month_collected', 'Collected This Month', $this->collected($monthStart, $today, $branchId), 'payments', 'money', 'payments', ['from' => $monthStart, 'to' => $today, 'status' => 'successful']);
        }
        if ($can('manage_statements')) {
            $stats[] = $this->stat('gross_profit', 'Gross Profit', $this->grossProfit($monthStart, $today, $branchId), 'savings', 'money', 'report', ['type' => 'profit']);
        }
        if ($can('manage_expenses')) {
            $stats[] = $this->stat('month_expenses', 'Month Expenses', $this->sum('expenses', 'amount', 'expense_date', $monthStart, $today, $branchId), 'receipt_long', 'money', 'expenses');
        }
        if ($can('manage_orders')) {
            $stats[] = $this->stat('pending_orders', 'Pending Orders', $this->scoped('orders', $branchId)?->where('status', 'pending')->count() ?? 0, 'shopping_cart', 'number', 'orders', ['status' => 'pending']);
        }
        if ($can('manage_stock')) {
            $stats[] = $this->stat('low_stock', 'Low Stock', $this->scoped('stock', $branchId)?->where('quantity', '<=', 5)->count() ?? 0, 'warning_amber', 'number', 'stock', ['low' => 1]);
        }
        if ($can('manage_products')) {
            $stats[] = $this->stat('products', 'Products', Schema::hasTable('products') ? DB::table('products')->count() : 0, 'inventory_2', 'number', 'products');
        }
        if ($can('manage_customers')) {
            $stats[] = $this->stat('customers', 'Customers', $this->scoped('customers', $branchId, true)?->count() ?? 0, 'people', 'number', 'customers');
        }
        if ($can('manage_chats') && Schema::hasTable('chat_conversations')) {
            $stats[] = $this->stat('open_chats', 'Open Chats', DB::table('chat_conversations')->where('status', 'open')->count(), 'forum', 'number', 'chats', ['status' => 'open']);
        }
        $stats[] = $this->stat('notifications', 'Unread Notifications', $this->unread($user), 'notifications', 'number', 'notifications');

        return response()->json([
            'stats' => $stats,
            'charts' => $this->charts($can, $branchId),
            'recent_sales' => $can('manage_sales') ? $this->recentSales($branchId) : [],
            'recent_orders' => $can('manage_orders') ? $this->recentOrders($branchId) : [],
            'branch_scoped' => $branchId !== null,
            'currency' => $this->setting('currency', 'UGX'),
        ]);
    }

    private function stat(string $key, string $label, mixed $value, string $icon, string $type, string $module, array $filter = []): array
    {
        return compact('key', 'label', 'icon', 'type', 'module') + [
            'value' => $type === 'money' ? round((float) $value, 2) : (int) $value,
            'filter' => (object) $filter,
        ];
    }

    /** A table query limited to the branch (or all branches when $branchId is null). */
    private function scoped(string $table, ?int $branchId, bool $sharedWhenNull = false): ?Builder
    {
        if (! Schema::hasTable($table)) {
            return null;
        }
        $query = DB::table($table);
        if ($branchId && Schema::hasColumn($table, 'branch_id')) {
            $column = $table.'.branch_id';
            $sharedWhenNull
                ? $query->where(fn ($q) => $q->where($column, $branchId)->orWhereNull($column))
                : $query->where($column, $branchId);
        }

        return $query;
    }

    private function sum(string $table, string $amount, string $date, string $from, string $to, ?int $branchId): float
    {
        $query = $this->scoped($table, $branchId);
        if (! $query || ! Schema::hasColumn($table, $date)) {
            return 0;
        }

        return (float) $query->whereDate($date, '>=', $from)->whereDate($date, '<=', $to)->sum($amount);
    }

    private function collected(string $from, string $to, ?int $branchId): float
    {
        if (! Schema::hasTable('payments')) {
            return 0;
        }

        return (float) DB::table('payments')
            ->where('payments.status', 'successful')
            ->whereDate('payments.created_at', '>=', $from)->whereDate('payments.created_at', '<=', $to)
            ->when($branchId, fn ($q) => $q->whereIn('payments.sale_id', DB::table('sales')->select('id')->where('branch_id', $branchId)))
            ->sum('payments.amount');
    }

    /** Sales revenue minus the cost of what was sold (stock cost price), for the period. */
    private function grossProfit(string $from, string $to, ?int $branchId): float
    {
        try {
            $rows = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereDate('sales.sale_date', '>=', $from)->whereDate('sales.sale_date', '<=', $to)
                ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
                ->select('sale_items.product_id', 'sales.branch_id', 'sale_items.quantity', 'sale_items.total')
                ->get();

            $costs = DB::table('stock')->whereNotNull('cost_price')
                ->select('product_id', 'branch_id', DB::raw('MIN(cost_price) as cost'))
                ->groupBy('product_id', 'branch_id')->get()
                ->keyBy(fn ($r) => $r->product_id.'-'.$r->branch_id);

            return round($rows->sum(fn ($r) => (float) $r->total
                - (float) $r->quantity * (float) ($costs[$r->product_id.'-'.$r->branch_id]->cost ?? 0)), 2);
        } catch (Throwable) {
            return 0;
        }
    }

    private function unread(User $user): int
    {
        try {
            return NotificationInbox::unreadSystem($user)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /** @param  callable(string): bool  $can */
    private function charts(callable $can, ?int $branchId): array
    {
        $charts = [];

        if ($can('manage_sales')) {
            // Sales (and expenses when allowed) for the last 7 months.
            $months = collect(range(6, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
            $showExpenses = $can('manage_expenses');
            $charts['monthly'] = $months->map(function (Carbon $m) use ($branchId, $showExpenses) {
                $from = $m->toDateString();
                $to = $m->copy()->endOfMonth()->toDateString();

                return [
                    'label' => $m->format('M'),
                    'sales' => $this->sum('sales', 'total_amount', 'sale_date', $from, $to, $branchId),
                    'expenses' => $showExpenses ? $this->sum('expenses', 'amount', 'expense_date', $from, $to, $branchId) : null,
                ];
            })->values();

            // Daily sales for the last 14 days.
            $from = now()->subDays(13)->toDateString();
            $daily = Schema::hasTable('sales')
                ? $this->scoped('sales', $branchId)->whereDate('sale_date', '>=', $from)
                    ->selectRaw('DATE(sale_date) as day, SUM(total_amount) as total')->groupBy('day')->pluck('total', 'day')
                : collect();
            $charts['daily'] = collect(range(13, 0))->map(function ($i) use ($daily) {
                $day = now()->subDays($i);

                return ['label' => $day->format('d M'), 'value' => (float) ($daily[$day->toDateString()] ?? 0)];
            })->values();

            // Top products and payment methods this month.
            $monthStart = now()->startOfMonth()->toDateString();
            $charts['top_products'] = Schema::hasTable('sale_items')
                ? DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->whereDate('sales.sale_date', '>=', $monthStart)
                    ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
                    ->groupBy('products.id', 'products.name')
                    ->selectRaw('products.name as name, SUM(sale_items.quantity) as quantity, SUM(sale_items.total) as revenue')
                    ->orderByDesc('revenue')->limit(5)->get()
                    ->map(fn ($r) => ['name' => $r->name, 'quantity' => (float) $r->quantity, 'revenue' => (float) $r->revenue])
                : [];
            $charts['payment_methods'] = Schema::hasTable('payments')
                ? DB::table('payments')
                    ->where('status', 'successful')->whereDate('created_at', '>=', $monthStart)
                    ->when($branchId, fn ($q) => $q->whereIn('sale_id', DB::table('sales')->select('id')->where('branch_id', $branchId)))
                    ->groupBy('method')->selectRaw('method, SUM(amount) as amount')
                    ->orderByDesc('amount')->get()
                    ->map(fn ($r) => ['label' => $r->method ?: 'Other', 'value' => (float) $r->amount])
                : [];
        }

        if ($can('manage_orders') && Schema::hasTable('orders')) {
            $charts['order_stages'] = $this->scoped('orders', $branchId)
                ->groupBy('status')->selectRaw('status, COUNT(*) as total')->get()
                ->map(fn ($r) => [
                    'key' => $r->status,
                    'label' => \App\Models\Order::STAGES[$r->status] ?? ucfirst(str_replace('_', ' ', (string) $r->status)),
                    'value' => (int) $r->total,
                ])->values();
        }

        return $charts;
    }

    private function recentSales(?int $branchId): array
    {
        if (! Schema::hasTable('sales')) {
            return [];
        }

        return $this->scoped('sales', $branchId)
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->orderByDesc('sales.id')->limit(5)
            ->get(['sales.id', 'sales.invoice_no', 'sales.total_amount', 'sales.payment_status', 'sales.sale_date', 'customers.name as customer_name'])
            ->map(fn ($r) => (array) $r)->all();
    }

    private function recentOrders(?int $branchId): array
    {
        if (! Schema::hasTable('orders')) {
            return [];
        }

        return $this->scoped('orders', $branchId)
            ->orderByDesc('id')->limit(5)
            ->get(['id', 'order_number', 'order_id', 'customer_name', 'total_amount', 'status', 'created_at'])
            ->map(fn ($r) => (array) $r + ['status_label' => \App\Models\Order::STAGES[$r->status] ?? $r->status])->all();
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        return DB::table('settings')->where('setting_key', $key)->value('setting_value') ?? $default;
    }
}
