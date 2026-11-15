@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $isCustomer = auth()->user()?->role === 'customer';
    $ugx = fn ($v) => 'UGX '.number_format((float) $v, 0);
    $pct = fn ($v) => $v === null ? 'N/A' : number_format((float) $v, 1).'%';

    // Percentage change, or null when there is nothing to compare against.
    $change = fn ($now, $before) => (float) $before != 0.0 ? (($now - $before) / abs($before)) * 100 : null;

    // Cards link to the list behind the number, only when the user may open it.
    $can = fn (string $permission) => \App\Http\Middleware\CheckPermission::allows(auth()->user(), $permission);
    $monthStart = now()->startOfMonth()->toDateString();
    $today = now()->toDateString();
@endphp

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-xl font-black text-slate-800">
            <i class="fas fa-gauge-high mr-2 text-indigo-600"></i> Dashboard
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $isCustomer ? 'Your purchases, orders and account activity.' : 'Live sales, margin and inventory across every branch, '.now()->format('F Y').' to date.' }}
        </p>
    </div>
    @unless($isCustomer)
        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Updated {{ now()->format('H:i') }}
        </span>
    @endunless
</div>

@unless($isCustomer)
    {{-- Headline: sales, margin, profit --}}
    @php
        $salesChange = $change($monthSales, $prevMonthSales);
        $profitChange = $change($margin['gross_profit'], $prevMargin['gross_profit']);
        $marginPts = ($margin['margin'] !== null && $prevMargin['margin'] !== null) ? $margin['margin'] - $prevMargin['margin'] : null;
        $heroes = [
            ['Month Sales', $ugx($monthSales), $salesChange, '%', 'fas fa-chart-line', 'text-indigo-600 bg-indigo-50',
                $can('manage_sales') ? route('sales.index', ['from' => $monthStart, 'to' => $today]) : null],
            ['Gross Profit', $ugx($margin['gross_profit']), $profitChange, '%', 'fas fa-sack-dollar', 'text-emerald-600 bg-emerald-50',
                route('reports.show', ['type' => 'profit'])],
            ['Gross Margin', $pct($margin['margin']), $marginPts, ' pts', 'fas fa-percent', 'text-sky-600 bg-sky-50',
                route('reports.show', ['type' => 'profit'])],
            ['Net Profit', $ugx($netProfit), null, '', 'fas fa-scale-balanced', $netProfit < 0 ? 'text-rose-600 bg-rose-50' : 'text-amber-600 bg-amber-50',
                route('reports.show', ['type' => 'income_statement'])],
        ];
    @endphp
    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($heroes as [$label, $value, $delta, $unit, $icon, $tone, $url])
            <{{ $url ? 'a' : 'div' }} @if($url) href="{{ $url }}" title="Open {{ $label }}" @endif
                class="stat-card block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm {{ $url ? 'hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-300' : '' }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-[11px] font-black uppercase tracking-wide text-slate-400">{{ $label }}</div>
                        <div class="mt-2 truncate text-2xl font-black text-slate-800">{{ $value }}</div>
                    </div>
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $tone }}"><i class="{{ $icon }}"></i></div>
                </div>
                <div class="mt-2 text-xs font-semibold">
                    @if($label === 'Net Profit')
                        <span class="text-slate-500">Gross profit − {{ $ugx($monthExpenses) }} expenses</span>
                    @elseif($delta === null)
                        <span class="text-slate-400">No comparison for last month yet</span>
                    @else
                        <span class="{{ $delta >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            <i class="fas {{ $delta >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                            {{ ($delta >= 0 ? '+' : '').number_format($delta, 1).$unit }}
                        </span>
                        <span class="text-slate-400">vs same days last month</span>
                    @endif
                </div>
            </{{ $url ? 'a' : 'div' }}>
        @endforeach
    </div>
@endunless

<div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @php
        $cards = $isCustomer ? [
            ['My Purchases Today', $ugx($todaySales), 'fas fa-cash-register', null],
            ['My Purchases This Month', $ugx($monthSales), 'fas fa-chart-line', null],
            ['Paid This Month', $ugx($monthCollected), 'fas fa-hand-holding-dollar', null],
            ['My Balance Due', $ugx($outstandingCredit), 'fas fa-file-invoice-dollar', null],
            ['Pending Orders', number_format((int) $pendingOrders), 'fas fa-cart-shopping', null],
            ['Products Available', number_format((int) $totalProducts), 'fas fa-box-open', route('shop.index')],
            ['Account', 'Active', 'fas fa-user-check', route('notification-settings.edit')],
            ['Support', number_format((int) $openChatsCount), 'fas fa-comments', route('shop.index').'#chat'],
        ] : [
            ["Today's Sales", $ugx($todaySales), 'fas fa-cash-register', $can('manage_sales') ? route('sales.index', ['from' => $today, 'to' => $today]) : null],
            ['Collected This Month', $ugx($monthCollected), 'fas fa-hand-holding-dollar', $can('manage_sales') ? route('payments.index', ['period' => 'month']) : null],
            ['Outstanding Credit', $ugx($outstandingCredit), 'fas fa-file-invoice-dollar', $can('manage_sales') ? route('sales.index', ['payment_status' => 'outstanding']) : null],
            ['Month Expenses', $ugx($monthExpenses), 'fas fa-receipt', $can('manage_expenses') ? route('expenses.index', ['period' => 'month']) : null],
            ['Stock Value (Cost)', $ugx($stockCostValue), 'fas fa-warehouse', route('reports.show', ['type' => 'stock'])],
            ['Low Stock', number_format((int) $lowStock), 'fas fa-triangle-exclamation', $can('manage_stock') ? route('stock.index', ['low' => 1]) : null],
            ['Pending Orders', number_format((int) $pendingOrders), 'fas fa-clock', $can('manage_orders') ? route('orders.index', ['status' => 'pending']) : null],
            ['Open Chats', number_format((int) $openChatsCount), 'fas fa-comments', $can('manage_chats') ? route('chat.index') : null],
        ];
    @endphp
    @foreach($cards as [$label, $value, $icon, $url])
        <{{ $url ? 'a' : 'div' }} @if($url) href="{{ $url }}" title="Open {{ $label }}" @endif
            class="stat-card block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm {{ $url ? 'hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-300' : '' }}">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <div class="text-[10px] font-black uppercase tracking-wide text-slate-400">{{ $label }}</div>
                    <div class="mt-2 truncate text-lg font-black text-slate-800">{{ $value }}</div>
                </div>
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600">
                    <i class="{{ $icon }}"></i>
                </div>
            </div>
            @if($url)<div class="mt-2 text-[11px] font-semibold text-indigo-600">View details <i class="fas fa-arrow-right text-[9px]"></i></div>@endif
        </{{ $url ? 'a' : 'div' }}>
    @endforeach
</div>

@unless($isCustomer)
    {{-- Champions of the month --}}
    <div class="mb-5 grid gap-3 lg:grid-cols-2">
        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-sky-50 text-sky-600"><i class="fas fa-trophy text-2xl"></i></div>
            <div class="min-w-0 flex-1">
                <div class="text-[11px] font-black uppercase tracking-wide text-slate-400">Top Branch This Month</div>
                <div class="truncate text-lg font-black text-slate-800">{{ $monthlyBestBranch?->name ?? 'No sales yet' }}</div>
                @if($monthlyBestBranch)
                    <div class="text-xs text-slate-500">
                        {{ $ugx($monthlyBestBranch->total_amount) }}, {{ number_format($monthlyBestBranch->sales_count) }} sales, {{ $pct($monthlyBestBranch->margin) }} margin
                    </div>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-amber-50 text-amber-600"><i class="fas fa-medal text-2xl"></i></div>
            <div class="min-w-0 flex-1">
                <div class="text-[11px] font-black uppercase tracking-wide text-slate-400">Top Salesperson This Month</div>
                <div class="truncate text-lg font-black text-slate-800">{{ $topStaffMember?->name ?? 'No sales yet' }}</div>
                @if($topStaffMember)
                    <div class="text-xs text-slate-500">
                        {{ $ugx($topStaffMember->total_amount) }}, {{ number_format($topStaffMember->sales_count) }} sales, {{ $ugx($topStaffMember->gross_profit) }} gross profit
                    </div>
                @endif
            </div>
        </div>
    </div>
@endunless

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap gap-1 border-b border-slate-200 p-2">
        @php
            $tabs = $isCustomer
                ? ['overview' => ['fa-chart-pie', 'Overview'], 'activity' => ['fa-clock-rotate-left', 'Recent Activity']]
                : [
                    'overview' => ['fa-chart-pie', 'Overview'],
                    'leaders' => ['fa-ranking-star', 'Branches & Staff'],
                    'inventory' => ['fa-boxes-stacked', 'Inventory'],
                    'activity' => ['fa-clock-rotate-left', 'Recent Activity'],
                    'operations' => ['fa-truck-field', 'Suppliers'],
                ];
        @endphp
        @foreach($tabs as $key => [$icon, $label])
            <button type="button" class="dashboard-tab rounded-xl px-4 py-2 text-sm font-bold text-slate-600" data-tab="{{ $key }}">
                <i class="fas {{ $icon }} mr-1"></i> {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Overview --}}
    <section class="dashboard-panel p-4" data-panel="overview">
        @php
            // [canvas id, title, subtitle, icon, extra grid classes]
            $charts = $isCustomer ? [
                ['salesTrendChart', 'My Purchase Trend', 'Last 7 months, UGX', 'fa-chart-line text-indigo-600', ''],
                ['ordersTrendChart', 'My Orders Trend', 'Last 7 months', 'fa-chart-column text-emerald-600', ''],
            ] : [
                ['salesProfitChart', 'Sales vs Gross Profit', 'Last 7 months, UGX', 'fa-chart-column text-indigo-600', 'lg:col-span-2'],
                ['marginChart', 'Gross Margin', 'Last 7 months, % of costed sales', 'fa-percent text-sky-600', ''],
                ['branchChart', 'Sales by Branch', 'This month, UGX', 'fa-store text-indigo-600', ''],
                ['productChart', 'Most Profitable Products', 'This month, gross profit, UGX', 'fa-box text-emerald-600', ''],
                ['ordersTrendChart', 'Online Orders', 'Last 7 months', 'fa-cart-shopping text-amber-600', ''],
            ];
        @endphp
        <div class="mb-3 flex justify-end gap-2">
            <button type="button" class="chart-toggle-all rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-indigo-300" data-expand="1">
                <i class="fas fa-up-right-and-down-left-from-center mr-1"></i> Expand all
            </button>
            <button type="button" class="chart-toggle-all rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-indigo-300" data-expand="0">
                <i class="fas fa-down-left-and-up-right-to-center mr-1"></i> Collapse all
            </button>
        </div>
        <div class="grid items-start gap-5 {{ $isCustomer ? 'lg:grid-cols-2' : 'lg:grid-cols-3' }}">
            @foreach($charts as [$id, $title, $subtitle, $icon, $span])
                <div class="chart-card rounded-xl border border-slate-200 {{ $span }}" data-chart="{{ $id }}">
                    <button type="button" class="chart-toggle flex w-full items-center justify-between gap-3 p-4 text-left" aria-expanded="true" aria-controls="{{ $id }}-body">
                        <span>
                            <span class="block font-black text-slate-800"><i class="fas {{ $icon }} mr-1"></i> {{ $title }}</span>
                            <span class="block text-xs text-slate-400">{{ $subtitle }}</span>
                        </span>
                        <i class="chart-chevron fas fa-chevron-up text-slate-400 transition-transform"></i>
                    </button>
                    <div id="{{ $id }}-body" class="chart-body px-4 pb-4">
                        <div class="h-72"><canvas id="{{ $id }}"></canvas></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @unless($isCustomer)
        {{-- Branches & Staff --}}
        <section class="dashboard-panel hidden p-4" data-panel="leaders">
            <div class="grid gap-5 xl:grid-cols-2">
                @foreach([
                    ['Branch Leaderboard', 'fa-store', $topBranches, 'branch'],
                    ['Staff Leaderboard', 'fa-user-tie', $topStaff, 'staff'],
                ] as [$title, $icon, $rows, $kind])
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between border-b bg-slate-50 px-4 py-3">
                            <span class="font-black text-slate-700"><i class="fas {{ $icon }} mr-1 text-indigo-500"></i> {{ $title }}</span>
                            <span class="text-xs text-slate-400">{{ now()->format('F') }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-left text-[10px] font-black uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-2">#</th>
                                        <th class="px-4 py-2">{{ $kind === 'branch' ? 'Branch' : 'Staff' }}</th>
                                        <th class="px-4 py-2 text-right">Sales</th>
                                        <th class="px-4 py-2 text-right">Revenue</th>
                                        <th class="px-4 py-2 text-right">Gross Profit</th>
                                        <th class="px-4 py-2 text-right">Margin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rows as $i => $row)
                                        <tr class="border-t border-slate-100">
                                            <td class="px-4 py-3">
                                                @if($i < 3)
                                                    <i class="fas fa-medal {{ ['text-amber-500', 'text-slate-400', 'text-orange-700'][$i] }}" title="Rank {{ $i + 1 }}"></i>
                                                @else
                                                    <span class="text-xs font-bold text-slate-400">{{ $i + 1 }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-800">{{ $row->name }}</div>
                                                @if($kind === 'staff')
                                                    <div class="text-xs text-slate-400">{{ ucfirst((string) $row->role) }}, avg {{ $ugx($row->average_sale) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">{{ number_format($row->sales_count) }}</td>
                                            <td class="px-4 py-3 text-right font-bold">{{ number_format($row->total_amount, 0) }}</td>
                                            <td class="px-4 py-3 text-right">{{ number_format($row->gross_profit, 0) }}</td>
                                            <td class="px-4 py-3 text-right font-semibold">{{ $pct($row->margin) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No sales recorded this month.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Inventory --}}
        <section class="dashboard-panel hidden p-4" data-panel="inventory">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Stock at Cost', $ugx($stockCostValue), number_format($stockedProducts).' products on hand', 'fa-warehouse', 'text-indigo-600 bg-indigo-50'],
                    ['Stock at Selling Price', $ugx($stockRetailValue), $ugx(max($stockRetailValue - $stockCostValue, 0)).' profit waiting on shelves', 'fa-tags', 'text-emerald-600 bg-emerald-50'],
                    ['Dead Stock', $ugx($deadStockValue), number_format($deadStockCount).' products unsold in 60 days', 'fa-box-archive', 'text-rose-600 bg-rose-50'],
                    ['Expiry Watch', number_format($expiredCount).' expired', number_format($expiringSoonCount).' batches expire within 30 days', 'fa-hourglass-half', 'text-amber-600 bg-amber-50'],
                ] as [$label, $value, $sub, $icon, $tone])
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-[10px] font-black uppercase tracking-wide text-slate-400">{{ $label }}</div>
                                <div class="mt-2 truncate text-lg font-black text-slate-800">{{ $value }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $sub }}</div>
                            </div>
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $tone }}"><i class="fas {{ $icon }}"></i></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <div class="border-b bg-slate-50 px-4 py-3 font-black text-slate-700">
                        <i class="fas fa-arrow-trend-down mr-1 text-rose-500"></i> Thinnest Margins This Month
                    </div>
                    @forelse($weakProducts as $product)
                        <div class="grid grid-cols-3 gap-2 border-b border-slate-100 px-4 py-3 text-sm">
                            <div class="truncate font-semibold">{{ $product->name }}</div>
                            <div class="text-right">{{ number_format($product->costed_revenue, 0) }}</div>
                            <div class="text-right font-bold {{ $product->margin < 0 ? 'text-rose-700' : 'text-slate-700' }}">
                                @if($product->margin < 0)<i class="fas fa-circle-exclamation mr-1" title="Selling below cost"></i>@endif
                                {{ $pct($product->margin) }}
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-400">No costed sales this month.</div>
                    @endforelse
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <div class="border-b bg-slate-50 px-4 py-3 font-black text-slate-700">
                        <i class="fas fa-hourglass-half mr-1 text-amber-500"></i> Expiring or Expired Batches
                    </div>
                    @forelse($expiringProducts as $batch)
                        <div class="grid grid-cols-3 gap-2 border-b border-slate-100 px-4 py-3 text-sm">
                            <div class="truncate font-semibold">{{ $batch->product?->name ?? 'Product #'.$batch->product_id }}</div>
                            <div class="truncate text-slate-500">{{ $batch->branch?->name ?? '-' }}, {{ number_format((float) $batch->quantity) }}</div>
                            <div class="text-right text-xs font-bold {{ $batch->expiry_date->isPast() ? 'text-rose-700' : 'text-amber-700' }}">
                                {{ $batch->expiry_date->isPast() ? 'Expired' : 'Expires' }} {{ $batch->expiry_date->format('d M Y') }}
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-400">Nothing expiring in the next 30 days.</div>
                    @endforelse
                </div>
            </div>
        </section>
    @endunless

    {{-- Recent Activity --}}
    <section class="dashboard-panel hidden p-4" data-panel="activity">
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="border-b bg-slate-50 px-4 py-3 font-black text-slate-700">
                    <i class="fas fa-receipt mr-1 text-indigo-500"></i>
                    {{ $isCustomer ? 'My Recent Purchases' : 'Recent Sales' }}
                </div>
                @forelse($recentSales as $sale)
                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 px-4 py-3 text-sm">
                        <div class="truncate font-semibold">{{ $sale->invoice_no ?? ('Sale #'.$sale->id) }}</div>
                        <div class="text-right font-bold">{{ number_format((float) $sale->total_amount, 0) }}</div>
                        <div class="text-right text-xs text-slate-400">{{ optional($sale->sale_date)->format('d M Y') ?? $sale->sale_date }}</div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-400">No recent purchases.</div>
                @endforelse
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="border-b bg-slate-50 px-4 py-3 font-black text-slate-700">
                    <i class="fas fa-cart-shopping mr-1 text-amber-500"></i>
                    {{ $isCustomer ? 'My Recent Orders' : 'Recent Online Orders' }}
                </div>
                @forelse($recentOrders as $order)
                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 px-4 py-3 text-sm">
                        <div class="truncate font-semibold">{{ $order->order_number ?? $order->order_id ?? ('Order #'.$order->id) }}</div>
                        <div class="truncate">{{ $order->customer_name ?? 'Customer' }}</div>
                        <div class="text-right text-xs font-semibold text-slate-500">{{ str_replace('_', ' ', $order->status ?? 'pending') }}</div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-400">No recent orders.</div>
                @endforelse
            </div>
        </div>
    </section>

    @unless($isCustomer)
        {{-- Suppliers --}}
        <section class="dashboard-panel hidden p-4" data-panel="operations">
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-black uppercase text-slate-400">Stock Received This Month</div>
                        <div class="mt-2 text-xl font-black text-slate-800">{{ $ugx($monthlySupplierTotalAmount) }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ number_format($monthlySuppliers->count()) }} supplier(s), {{ number_format($monthlySupplierProductCount) }} product line(s)</div>
                    </div>
                    <div class="grid h-12 w-12 place-items-center rounded-xl bg-amber-50 text-amber-600"><i class="fas fa-truck-field text-xl"></i></div>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($monthlySuppliers as $supplier)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-black text-slate-800">{{ $supplier->name }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ number_format((int) $supplier->product_count) }} distinct product(s)</div>
                            </div>
                            <i class="fas fa-truck text-amber-500"></i>
                        </div>
                        <div class="mt-3 text-lg font-black text-slate-800">{{ $ugx($supplier->total_amount) }}</div>
                    </div>
                @empty
                    <div class="col-span-full rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">No supplier activity this month.</div>
                @endforelse
            </div>
        </section>
    @endunless
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
    const tabs = document.querySelectorAll('.dashboard-tab');
    const panels = document.querySelectorAll('.dashboard-panel');

    function activate(tab) {
        tabs.forEach(item => item.classList.remove('bg-indigo-600', 'text-white'));
        panels.forEach(panel => panel.classList.add('hidden'));
        tab.classList.add('bg-indigo-600', 'text-white');
        document.querySelector(`[data-panel="${tab.dataset.tab}"]`)?.classList.remove('hidden');
    }

    tabs.forEach(tab => tab.addEventListener('click', () => activate(tab)));
    if (tabs[0]) activate(tabs[0]);

    // Collapsible chart cards; each card remembers its state in this browser.
    const STORE_KEY = 'dashboard.collapsedCharts';
    let collapsed = [];
    try { collapsed = JSON.parse(localStorage.getItem(STORE_KEY) || '[]'); } catch (e) { collapsed = []; }

    function setCard(card, open) {
        card.querySelector('.chart-body').classList.toggle('hidden', !open);
        card.querySelector('.chart-toggle').setAttribute('aria-expanded', open ? 'true' : 'false');
        card.querySelector('.chart-chevron').classList.toggle('rotate-180', !open);
        const id = card.dataset.chart;
        collapsed = collapsed.filter(c => c !== id);
        if (!open) collapsed.push(id);
        try { localStorage.setItem(STORE_KEY, JSON.stringify(collapsed)); } catch (e) { /* storage unavailable */ }
    }

    const cards = document.querySelectorAll('.chart-card');
    cards.forEach(card => {
        if (collapsed.includes(card.dataset.chart)) setCard(card, false);
        card.querySelector('.chart-toggle').addEventListener('click', () => {
            setCard(card, card.querySelector('.chart-body').classList.contains('hidden'));
        });
    });
    document.querySelectorAll('.chart-toggle-all').forEach(btn => btn.addEventListener('click', () => {
        cards.forEach(card => setCard(card, btn.dataset.expand === '1'));
    }));

    if (!window.Chart) return;

    // Validated categorical palette (fixed order) and recessive chart chrome.
    const BLUE = '#2a78d6', ORANGE = '#eb6834', AQUA = '#1baf7a';
    Chart.defaults.color = '#52514e';
    Chart.defaults.font.family = 'inherit';
    Chart.defaults.borderColor = '#e7e5e4';
    Chart.defaults.plugins.tooltip.mode = 'index';
    Chart.defaults.plugins.tooltip.intersect = false;

    const money = v => 'UGX ' + Math.round(v).toLocaleString();
    const compact = v => Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(v);
    const moneyTooltip = { callbacks: { label: ctx => `${ctx.dataset.label ? ctx.dataset.label + ': ' : ''}${money(ctx.parsed[ctx.chart.options.indexAxis === 'y' ? 'x' : 'y'])}` } };
    const bar = { borderRadius: 4, borderSkipped: 'start', maxBarThickness: 28 };
    const base = { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false } };

    const labels = @json($chartLabels ?? []);
    const sales = @json($chartSales ?? []);
    const orders = @json($chartOrders ?? []);

    const el = id => document.getElementById(id);

    @if($isCustomer)
        new Chart(el('salesTrendChart'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Purchases', data: sales, borderColor: BLUE, backgroundColor: BLUE + '22', fill: true, tension: .35, borderWidth: 2, pointRadius: 4 }] },
            options: { ...base, plugins: { legend: { display: false }, tooltip: moneyTooltip }, scales: { y: { beginAtZero: true, ticks: { callback: compact } }, x: { grid: { display: false } } } }
        });
    @else
        const profit = @json($chartProfit);
        const margin = @json($chartMargin);
        const branches = @json($topBranches->map(fn ($b) => ['name' => $b->name, 'total' => $b->total_amount])->values());
        const products = @json($topProducts->map(fn ($p) => ['name' => $p->name, 'profit' => round($p->gross_profit, 2)])->values());

        new Chart(el('salesProfitChart'), {
            type: 'bar',
            data: { labels, datasets: [
                { label: 'Sales', data: sales, backgroundColor: BLUE, ...bar },
                { label: 'Gross profit', data: profit, backgroundColor: ORANGE, ...bar },
            ] },
            options: { ...base, plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: moneyTooltip },
                scales: { y: { beginAtZero: true, ticks: { callback: compact } }, x: { grid: { display: false } } } }
        });

        new Chart(el('marginChart'), {
            type: 'line',
            data: { labels, datasets: [{ label: 'Gross margin', data: margin, borderColor: BLUE, backgroundColor: BLUE, borderWidth: 2, pointRadius: 4, tension: .3, spanGaps: true }] },
            options: { ...base, plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => `Gross margin: ${ctx.parsed.y.toFixed(1)}%` } } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => v + '%' } }, x: { grid: { display: false } } } }
        });

        const horizontal = (id, rows, key, color, label) => new Chart(el(id), {
            type: 'bar',
            data: { labels: rows.map(r => r.name), datasets: [{ label, data: rows.map(r => r[key]), backgroundColor: color, ...bar }] },
            options: { ...base, indexAxis: 'y', plugins: { legend: { display: false }, tooltip: moneyTooltip },
                scales: { x: { beginAtZero: true, ticks: { callback: compact } }, y: { grid: { display: false } } } }
        });
        horizontal('branchChart', branches, 'total', BLUE, 'Sales');
        horizontal('productChart', products, 'profit', AQUA, 'Gross profit');
    @endif

    new Chart(el('ordersTrendChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Orders', data: orders, backgroundColor: @json($isCustomer ? '#1baf7a' : '#2a78d6'), borderRadius: 4, borderSkipped: 'start', maxBarThickness: 28 }] },
        options: { ...base, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
    });
})();
</script>
@endpush
