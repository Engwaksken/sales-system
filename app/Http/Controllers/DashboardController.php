<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** Months shown on the trend charts, oldest first, ending with the current month. */
    private const TREND_MONTHS = 7;

    /** A product with stock on hand but no sale in this many days counts as dead stock. */
    private const DEAD_STOCK_DAYS = 60;

    /**
     * A sale line's revenue net of VAT, with the invoice-level discount spread
     * across the lines in proportion to their value.
     */
    private const LINE_NET = '(COALESCE(si.total, si.price * si.quantity, 0) * CASE WHEN it.items_total > 0 THEN (COALESCE(s.total_amount, 0) - COALESCE(s.vat, 0)) * 1.0 / it.items_total ELSE 1 END)';

    /** A sale line's cost: quantity sold × the product's weighted average purchase cost. */
    private const LINE_COST = '(COALESCE(si.quantity, 0) * pc.unit_cost)';

    public function index()
    {
        $user = Auth::user();

        if ($user?->role === 'customer') {
            return $this->customerDashboard($user);
        }

        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $today = $now->copy()->endOfDay();

        // Same number of days last month, so the comparison is like for like.
        $prevStart = $monthStart->copy()->subMonthNoOverflow();
        $prevEnd = $prevStart->copy()->addDays($now->day - 1)->endOfDay()->min($prevStart->copy()->endOfMonth());

        $todaySales = Sale::whereDate('sale_date', $now)->sum('total_amount');
        $monthSales = (float) Sale::whereDate('sale_date', '>=', $monthStart->toDateString())->whereDate('sale_date', '<=', $today->toDateString())->sum('total_amount');
        $prevMonthSales = (float) Sale::whereDate('sale_date', '>=', $prevStart->toDateString())->whereDate('sale_date', '<=', $prevEnd->toDateString())->sum('total_amount');
        $monthExpenses = (float) Expense::whereDate('expense_date', '>=', $monthStart->toDateString())->whereDate('expense_date', '<=', $today->toDateString())->sum('amount');
        $pendingOrders = Order::where('status', 'pending')->count();
        $lowStock = DB::table('stock')->where('quantity', '<=', 5)->count();
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();

        $recentSales = Sale::latest()->take(8)->get();
        $recentOrders = Order::latest()->take(8)->get();

        $expiredCount = Stock::whereNotNull('expiry_date')->whereDate('expiry_date', '<', $now)->count();
        $expiringSoonCount = Stock::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $now)
            ->whereDate('expiry_date', '<=', $now->copy()->addDays(30))
            ->count();
        $expiringProducts = Stock::with(['product', 'branch'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $now->copy()->addDays(30))
            ->orderBy('expiry_date')
            ->take(8)
            ->get();

        $openChats = ChatConversation::where('status', 'open')
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', 'customer')->where('is_read', false)])
            ->latest()
            ->take(8)
            ->get();
        $openChatsCount = ChatConversation::where('status', 'open')->count();

        $outstandingCredit = (float) Sale::where('transaction_type', 'sale')->sum('balance_amount');
        $monthCollected = (float) Payment::where('transaction_type', 'sale')->where('status', 'successful')
            ->whereBetween('created_at', [$monthStart, $today])->sum('amount');

        // ── Margin ────────────────────────────────────────────────────────
        $margin = $this->marginSummary($this->saleLines($monthStart, $today));
        $prevMargin = $this->marginSummary($this->saleLines($prevStart, $prevEnd));
        $netProfit = $margin['gross_profit'] - $monthExpenses;

        // ── Leaderboards (this month) ────────────────────────────────────
        $topBranches = $this->branchLeaderboard($monthStart, $today);
        $topStaff = $this->staffLeaderboard($monthStart, $today);
        $monthlyBestBranch = $topBranches->first();
        $topStaffMember = $topStaff->first();

        [$topProducts, $weakProducts] = $this->productMargins($monthStart, $today);

        // ── Inventory ─────────────────────────────────────────────────────
        $stockValue = DB::table('stock')
            ->where('quantity', '>', 0)
            ->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as cost_value')
            ->selectRaw('COALESCE(SUM(quantity * selling_price), 0) as retail_value')
            ->selectRaw('COUNT(DISTINCT product_id) as products')
            ->first();
        $stockCostValue = (float) $stockValue->cost_value;
        $stockRetailValue = (float) $stockValue->retail_value;
        $stockedProducts = (int) $stockValue->products;

        $recentlySold = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->where('s.transaction_type', 'sale')
            ->where('s.sale_date', '>=', $now->copy()->subDays(self::DEAD_STOCK_DAYS)->toDateString())
            ->select('si.product_id');
        $deadStock = DB::table('stock')
            ->where('quantity', '>', 0)
            ->whereNotIn('product_id', $recentlySold)
            ->selectRaw('COUNT(DISTINCT product_id) as products')
            ->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as value')
            ->first();
        $deadStockCount = (int) $deadStock->products;
        $deadStockValue = (float) $deadStock->value;

        $branchCount = Branch::count();

        // ── Suppliers (this month) ───────────────────────────────────────
        $monthlySuppliers = Stock::query()
            ->join('suppliers', 'suppliers.id', '=', 'stock.supplier_id')
            ->whereNotNull('stock.supplier_id')
            ->whereBetween('stock.stock_date', [$monthStart, $today])
            ->groupBy('suppliers.id', 'suppliers.name')
            ->select(['suppliers.id', 'suppliers.name'])
            ->selectRaw('COUNT(DISTINCT stock.product_id) as product_count')
            ->selectRaw('COALESCE(SUM(stock.total_stock * stock.cost_price), 0) as total_amount')
            ->orderByDesc('total_amount')
            ->get();
        $monthlySupplierTotalAmount = (float) $monthlySuppliers->sum('total_amount');
        $monthlySupplierProductCount = (int) $monthlySuppliers->sum('product_count');

        // ── Trends ────────────────────────────────────────────────────────
        $months = $this->trendMonths();
        $chartLabels = $months->map(fn (Carbon $m) => $m->format('M'))->values();
        $chartSales = collect();
        $chartProfit = collect();
        $chartMargin = collect();
        $chartOrders = collect();
        foreach ($months as $m) {
            $from = $m->copy()->startOfMonth();
            $to = $m->copy()->endOfMonth();
            $summary = $this->marginSummary($this->saleLines($from, $to));

            $chartSales->push((float) Sale::whereDate('sale_date', '>=', $from->toDateString())->whereDate('sale_date', '<=', $to->toDateString())->sum('total_amount'));
            $chartProfit->push(round($summary['gross_profit'], 2));
            $chartMargin->push($summary['margin'] === null ? null : round($summary['margin'], 1));
            $chartOrders->push(Order::whereBetween('created_at', [$from, $to])->count());
        }

        return view('dashboard', compact(
            'todaySales', 'monthSales', 'prevMonthSales', 'monthExpenses', 'pendingOrders',
            'lowStock', 'totalCustomers', 'totalProducts', 'recentSales', 'recentOrders',
            'expiredCount', 'expiringSoonCount', 'expiringProducts',
            'openChats', 'openChatsCount', 'outstandingCredit', 'monthCollected',
            'margin', 'prevMargin', 'netProfit',
            'topBranches', 'topStaff', 'monthlyBestBranch', 'topStaffMember',
            'topProducts', 'weakProducts',
            'stockCostValue', 'stockRetailValue', 'stockedProducts',
            'deadStockCount', 'deadStockValue', 'branchCount',
            'monthlySuppliers', 'monthlySupplierTotalAmount', 'monthlySupplierProductCount',
            'chartLabels', 'chartSales', 'chartProfit', 'chartMargin', 'chartOrders'
        ));
    }

    private function customerDashboard(User $user)
    {
        $customer = Customer::query()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();
        $customerId = $customer?->id;

        $sales = fn () => Sale::query()->where('customer_id', $customerId ?? 0);
        $orders = fn () => Order::query()->where('customer_id', $customerId ?? 0);

        $todaySales = $sales()->whereDate('sale_date', now())->sum('total_amount');
        $monthSales = $sales()->whereYear('sale_date', now()->year)->whereMonth('sale_date', now()->month)->sum('total_amount');
        $monthExpenses = 0;
        $pendingOrders = $orders()->where('status', 'pending')->count();
        $lowStock = 0;
        $totalCustomers = $customer ? 1 : 0;
        $totalProducts = Product::where('is_active', true)->count();
        $recentSales = $sales()->latest()->take(8)->get();
        $recentOrders = $orders()->latest()->take(8)->get();
        $openChatsCount = 0;
        $outstandingCredit = (float) $sales()->sum('balance_amount');
        $monthCollected = (float) Payment::where('customer_id', $customerId ?? 0)->where('status', 'successful')
            ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('amount');

        $months = $this->trendMonths();
        $chartLabels = $months->map(fn (Carbon $m) => $m->format('M'))->values();
        $chartSales = $months->map(fn (Carbon $m) => (float) $sales()
            ->whereDate('sale_date', '>=', $m->copy()->startOfMonth()->toDateString())->whereDate('sale_date', '<=', $m->copy()->endOfMonth()->toDateString())
            ->sum('total_amount'))->values();
        $chartOrders = $months->map(fn (Carbon $m) => $orders()
            ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
            ->count())->values();

        return view('dashboard', compact(
            'todaySales', 'monthSales', 'monthExpenses', 'pendingOrders', 'lowStock',
            'totalCustomers', 'totalProducts', 'recentSales', 'recentOrders',
            'openChatsCount', 'outstandingCredit', 'monthCollected',
            'chartLabels', 'chartSales', 'chartOrders'
        ));
    }

    /** @return Collection<int, Carbon> */
    private function trendMonths(): Collection
    {
        return collect(range(self::TREND_MONTHS - 1, 0))
            ->map(fn ($ago) => now()->copy()->startOfMonth()->subMonthsNoOverflow($ago))
            ->values();
    }

    /** Weighted average purchase cost per product, from stock batches that carry a cost. */
    private function unitCosts(): Builder
    {
        $weight = 'CASE WHEN COALESCE(total_stock, 0) > 0 THEN total_stock ELSE 1 END';

        return DB::table('stock')
            ->whereNotNull('cost_price')
            ->where('cost_price', '>', 0)
            ->groupBy('product_id')
            ->select('product_id')
            ->selectRaw("SUM(cost_price * {$weight}) * 1.0 / SUM({$weight}) as unit_cost");
    }

    /** Sale lines in a date range, joined to their invoice totals and product unit cost. */
    private function saleLines(Carbon $from, Carbon $to): Builder
    {
        $itemTotals = DB::table('sale_items')
            ->groupBy('sale_id')
            ->select('sale_id')
            ->selectRaw('SUM(COALESCE(total, price * quantity, 0)) as items_total');

        return DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->joinSub($itemTotals, 'it', 'it.sale_id', '=', 'si.sale_id')
            ->leftJoinSub($this->unitCosts(), 'pc', 'pc.product_id', '=', 'si.product_id')
            ->where('s.transaction_type', 'sale')
            ->whereDate('s.sale_date', '>=', $from->toDateString())->whereDate('s.sale_date', '<=', $to->toDateString());
    }

    /** Adds revenue / costed revenue / COGS / quantity aggregates to a sale-lines query. */
    private function withMarginColumns(Builder $query): Builder
    {
        return $query
            ->selectRaw('COALESCE(SUM('.self::LINE_NET.'), 0) as revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN pc.unit_cost IS NOT NULL THEN '.self::LINE_NET.' ELSE 0 END), 0) as costed_revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN pc.unit_cost IS NOT NULL THEN '.self::LINE_COST.' ELSE 0 END), 0) as cogs')
            ->selectRaw('COALESCE(SUM(si.quantity), 0) as units');
    }

    /**
     * Gross profit and margin are measured only on lines whose product has a
     * known cost, so a missing cost price never shows up as 100% profit.
     * `coverage` says how much of revenue that measurement covers.
     */
    private function marginSummary(Builder $lines): array
    {
        return $this->marginFromRow($this->withMarginColumns($lines)->first());
    }

    private function marginFromRow(?object $row): array
    {
        $revenue = (float) ($row->revenue ?? 0);
        $costed = (float) ($row->costed_revenue ?? 0);
        $cogs = (float) ($row->cogs ?? 0);
        $profit = $costed - $cogs;

        return [
            'revenue' => $revenue,
            'costed_revenue' => $costed,
            'cogs' => $cogs,
            'gross_profit' => $profit,
            'margin' => $costed > 0 ? $profit / $costed * 100 : null,
            'coverage' => $revenue > 0 ? $costed / $revenue * 100 : null,
            'units' => (float) ($row->units ?? 0),
        ];
    }

    private function branchLeaderboard(Carbon $from, Carbon $to): Collection
    {
        $margins = $this->withMarginColumns($this->saleLines($from, $to))
            ->groupBy('s.branch_id')
            ->addSelect('s.branch_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->branch_id);

        return Sale::query()
            ->leftJoin('branches', 'branches.id', '=', 'sales.branch_id')
            ->where('sales.transaction_type', 'sale')
            ->whereDate('sales.sale_date', '>=', $from->toDateString())->whereDate('sales.sale_date', '<=', $to->toDateString())
            ->groupBy('sales.branch_id', 'branches.name')
            ->select(['sales.branch_id', 'branches.name'])
            ->selectRaw('COUNT(sales.id) as sales_count')
            ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
            ->orderByDesc('total_amount')
            ->toBase()
            ->get()
            ->map(fn ($row) => (object) array_merge([
                'id' => $row->branch_id,
                'name' => $row->name ?: 'Unassigned',
                'sales_count' => (int) $row->sales_count,
                'total_amount' => (float) $row->total_amount,
            ], $this->marginFromRow($margins->get((string) $row->branch_id))));
    }

    private function staffLeaderboard(Carbon $from, Carbon $to, int $limit = 8): Collection
    {
        $staffKey = 'COALESCE(s.user_id, s.created_by)';

        $margins = $this->withMarginColumns($this->saleLines($from, $to))
            ->groupByRaw($staffKey)
            ->selectRaw("{$staffKey} as staff_id")
            ->get()
            ->keyBy(fn ($row) => (string) $row->staff_id);

        $rows = DB::table('sales as s')
            ->where('s.transaction_type', 'sale')
            ->whereDate('s.sale_date', '>=', $from->toDateString())->whereDate('s.sale_date', '<=', $to->toDateString())
            ->whereRaw("{$staffKey} IS NOT NULL")
            ->groupByRaw($staffKey)
            ->selectRaw("{$staffKey} as staff_id")
            ->selectRaw('COUNT(s.id) as sales_count')
            ->selectRaw('COALESCE(SUM(s.total_amount), 0) as total_amount')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();

        $users = User::whereIn('id', $rows->pluck('staff_id'))->get(['id', 'name', 'role'])->keyBy('id');

        return $rows->map(function ($row) use ($users, $margins) {
            $user = $users->get($row->staff_id);
            $count = (int) $row->sales_count;
            $total = (float) $row->total_amount;

            return (object) array_merge([
                'id' => $row->staff_id,
                'name' => $user?->name ?: 'User #'.$row->staff_id,
                'role' => $user?->role,
                'sales_count' => $count,
                'total_amount' => $total,
                'average_sale' => $count > 0 ? $total / $count : 0,
            ], $this->marginFromRow($margins->get((string) $row->staff_id)));
        });
    }

    /**
     * @return array{0: Collection, 1: Collection} top products by gross profit,
     *         and costed products with the thinnest margins
     */
    private function productMargins(Carbon $from, Carbon $to): array
    {
        $rows = $this->withMarginColumns($this->saleLines($from, $to))
            ->groupBy('si.product_id')
            ->addSelect('si.product_id')
            ->get();

        $names = Product::whereIn('id', $rows->pluck('product_id')->filter())->pluck('name', 'id');

        $products = $rows->map(fn ($row) => (object) array_merge(
            ['id' => $row->product_id, 'name' => $names[$row->product_id] ?? 'Product #'.$row->product_id],
            $this->marginFromRow($row)
        ));

        $costed = $products->filter(fn ($p) => $p->margin !== null);

        return [
            $costed->sortByDesc('gross_profit')->take(8)->values(),
            $costed->sortBy('margin')->take(5)->values(),
        ];
    }
}
