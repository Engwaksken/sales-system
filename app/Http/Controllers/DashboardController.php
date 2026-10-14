<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\ChatConversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user?->role === 'customer') {
            $customer = Customer::query()
                ->where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            $customerId = $customer?->id;

            $todaySales = Sale::query()
                ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                ->whereDate('sale_date', now())
                ->sum('total_amount');

            $monthSales = Sale::query()
                ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                ->whereMonth('sale_date', now()->month)
                ->whereYear('sale_date', now()->year)
                ->sum('total_amount');

            $monthExpenses = 0;
            $pendingOrders = $customerId
                ? Order::where('customer_id', $customerId)->where('status', 'pending')->count()
                : 0;

            $lowStock = 0;
            $totalCustomers = $customer ? 1 : 0;
            $totalProducts = Product::where('is_active', true)->count();

            $recentSales = $customerId
                ? Sale::where('customer_id', $customerId)->latest()->take(8)->get()
                : collect();

            $recentOrders = $customerId
                ? Order::where('customer_id', $customerId)->latest()->take(8)->get()
                : collect();

            $expiredCount = 0;
            $expiringSoonCount = 0;
            $expiringProducts = collect();
            $openChats = collect();
            $openChatsCount = 0;

            // Business-wide supplier/branch performance is intentionally hidden
            // from customer accounts.
            $monthlyBestBranch = null;
            $monthlySuppliers = collect();
            $monthlySupplierTotalAmount = 0;
            $monthlySupplierProductCount = 0;

            $chartLabels = collect(range(6, 0, -1))
                ->map(fn ($monthsAgo) => now()->copy()->subMonths($monthsAgo)->format('M'))
                ->push(now()->format('M'))
                ->values();

            $chartSales = collect(range(6, 0, -1))
                ->map(function ($monthsAgo) use ($customerId) {
                    $date = now()->copy()->subMonths($monthsAgo);

                    return (float) Sale::query()
                        ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                        ->whereYear('sale_date', $date->year)
                        ->whereMonth('sale_date', $date->month)
                        ->sum('total_amount');
                })
                ->push(
                    (float) Sale::query()
                        ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                        ->whereYear('sale_date', now()->year)
                        ->whereMonth('sale_date', now()->month)
                        ->sum('total_amount')
                )
                ->values();

            $chartOrders = collect(range(6, 0, -1))
                ->map(function ($monthsAgo) use ($customerId) {
                    $date = now()->copy()->subMonths($monthsAgo);

                    return (int) Order::query()
                        ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count();
                })
                ->push(
                    (int) Order::query()
                        ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->count()
                )
                ->values();

            return view('dashboard', compact(
                'todaySales',
                'monthSales',
                'monthExpenses',
                'pendingOrders',
                'lowStock',
                'totalCustomers',
                'totalProducts',
                'recentSales',
                'recentOrders',
                'expiredCount',
                'expiringSoonCount',
                'expiringProducts',
                'openChats',
                'openChatsCount'
            ));
        }

        $todaySales = Sale::whereDate('sale_date', now())->sum('total_amount');
        $monthSales = Sale::whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year)->sum('total_amount');
        $monthExpenses = Expense::whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount');
        $pendingOrders = Order::where('status', 'pending')->count();
        $lowStock = DB::table('stock')->where('quantity', '<=', 5)->count();
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();

        $recentSales = Sale::latest()->take(8)->get();
        $recentOrders = Order::latest()->take(8)->get();

        $expiredCount = Stock::whereNotNull('expiry_date')->whereDate('expiry_date', '<', now())->count();
        $expiringSoonCount = Stock::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->count();
        $expiringProducts = Stock::with(['product', 'branch'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->orderBy('expiry_date')
            ->take(8)
            ->get();

        $openChats = ChatConversation::where('status', 'open')
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', 'customer')->where('is_read', false)])
            ->latest()
            ->take(8)
            ->get();

        $openChatsCount = ChatConversation::where('status', 'open')->count();

        /*
         * Monthly Best Branch:
         * branch with the highest sales amount during the current calendar month.
         */
        $monthlyBestBranch = Sale::query()
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->whereMonth('sales.sale_date', now()->month)
            ->whereYear('sales.sale_date', now()->year)
            ->groupBy('branches.id', 'branches.name')
            ->select([
                'branches.id',
                'branches.name',
            ])
            ->selectRaw('COUNT(sales.id) as sales_count')
            ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
            ->orderByDesc('total_amount')
            ->first();

        /*
         * Monthly supplier performance:
         * Total amount = stock received this month × cost price.
         * Product count = number of distinct products received from that supplier.
         */
        $monthlySuppliers = Stock::query()
            ->join('suppliers', 'suppliers.id', '=', 'stock.supplier_id')
            ->whereNotNull('stock.supplier_id')
            ->whereMonth('stock.stock_date', now()->month)
            ->whereYear('stock.stock_date', now()->year)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->select([
                'suppliers.id',
                'suppliers.name',
            ])
            ->selectRaw('COUNT(DISTINCT stock.product_id) as product_count')
            ->selectRaw('COALESCE(SUM(stock.total_stock * stock.cost_price), 0) as total_amount')
            ->orderByDesc('total_amount')
            ->get();

        $monthlySupplierTotalAmount = (float) $monthlySuppliers->sum('total_amount');
        $monthlySupplierProductCount = (int) $monthlySuppliers->sum('product_count');

        $chartLabels = collect(range(6, 0, -1))
            ->map(fn ($monthsAgo) => now()->copy()->subMonths($monthsAgo)->format('M'))
            ->push(now()->format('M'))
            ->values();

        $chartSales = collect(range(6, 0, -1))
            ->map(function ($monthsAgo) {
                $date = now()->copy()->subMonths($monthsAgo);

                return (float) Sale::query()
                    ->whereYear('sale_date', $date->year)
                    ->whereMonth('sale_date', $date->month)
                    ->sum('total_amount');
            })
            ->push(
                (float) Sale::query()
                    ->whereYear('sale_date', now()->year)
                    ->whereMonth('sale_date', now()->month)
                    ->sum('total_amount')
            )
            ->values();

        $chartOrders = collect(range(6, 0, -1))
            ->map(function ($monthsAgo) {
                $date = now()->copy()->subMonths($monthsAgo);

                return (int) Order::query()
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
            })
            ->push(
                (int) Order::query()
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count()
            )
            ->values();


        return view('dashboard', compact(
            'todaySales', 'monthSales', 'monthExpenses', 'pendingOrders',
            'lowStock', 'totalCustomers', 'totalProducts', 'recentSales', 'recentOrders',
            'expiredCount', 'expiringSoonCount', 'expiringProducts',
            'openChats', 'openChatsCount',
            'monthlyBestBranch', 'monthlySuppliers',
            'monthlySupplierTotalAmount', 'monthlySupplierProductCount',
            'chartLabels', 'chartSales', 'chartOrders'
        ));
    }
}
