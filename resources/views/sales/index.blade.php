@extends('layouts.app')

@section('title', 'Sales / POS')

@section('content')
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
    <div>
        <h1 class="text-xl md:text-2xl font-extrabold text-slate-800 flex items-center gap-2">
            <i class="fas fa-cash-register text-emerald-600"></i>
            Sales / POS
        </h1>
        <p class="text-sm text-slate-500 mt-1">Process sales, online-order payments, invoices and customer receipts.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button"
                onclick="openPosModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
            <i class="fas fa-cash-register"></i>
            New Sale (POS)
        </button>

        <button type="button"
                onclick="openCreateModal('sales-modal', 'sales-form', '{{ route('sales.store') }}')"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
            <i class="fas fa-plus"></i>
            Manual Entry
        </button>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
    @include('partials.stat-card', [
        'icon' => 'fa-cash-register',
        'color' => 'indigo',
        'label' => 'Total Sales',
        'value' => $items->total()
    ])

    @include('partials.stat-card', [
        'icon' => 'fa-coins',
        'color' => 'emerald',
        'label' => 'Total Sales Value',
        'value' => $stat1
    ])

    @include('partials.stat-card', [
        'icon' => 'fa-hand-holding-dollar',
        'color' => 'sky',
        'label' => 'Total Collected',
        'value' => 'UGX '.number_format($collected, 0)
    ])

    <a href="{{ route('sales.index', ['payment_status' => 'outstanding']) }}" title="Show sales with a balance due" class="block">
        @include('partials.stat-card', [
            'icon' => 'fa-hand-holding-dollar',
            'color' => 'amber',
            'label' => 'Outstanding Credit',
            'value' => 'UGX '.number_format($receivables, 0)
        ])
    </a>
</div>

@include('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => true])

@if(session('status'))
    <div data-flash class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <i class="fas fa-circle-check mr-1"></i>
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div data-flash class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <div class="font-bold mb-1"><i class="fas fa-triangle-exclamation mr-1"></i> Please check the following:</div>
        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4">
    <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col sm:flex-row sm:flex-wrap gap-2 text-sm">
        <div class="relative">
            <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300"></i>
            <input type="search"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Search invoice or payment method..."
                   class="w-full sm:w-72 rounded-lg border-slate-300 pl-9">
        </div>

        <select name="period" class="rounded-lg border-slate-300" onchange="this.form.submit()">
            <option value="all" @selected(request('period', 'all') === 'all')>All Time</option>
            <option value="today" @selected(request('period') === 'today')>Today</option>
            <option value="week" @selected(request('period') === 'week')>This Week</option>
            <option value="month" @selected(request('period') === 'month')>This Month</option>
            <option value="year" @selected(request('period') === 'year')>This Year</option>
        </select>

        <select name="payment_status" class="rounded-lg border-slate-300" onchange="this.form.submit()">
            <option value="">Any Payment Status</option>
            <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
            <option value="partial" @selected(request('payment_status') === 'partial')>Part Paid</option>
            <option value="pending" @selected(request('payment_status') === 'pending')>Unpaid / Credit</option>
            <option value="outstanding" @selected(request('payment_status') === 'outstanding')>Any Balance Due</option>
        </select>

        <select name="product_id" class="rounded-lg border-slate-300 sm:max-w-[200px]" onchange="this.form.submit()">
            <option value="">All Products</option>
            @foreach($product_options as $product)
                <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>

        <select name="payment_method" class="rounded-lg border-slate-300" onchange="this.form.submit()">
            <option value="">All Payment Methods</option>
            @foreach(array_merge($paymentMethods, ['Credit']) as $method)
                <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ $method === 'Credit' ? 'On Credit (balance due)' : $method }}</option>
            @endforeach
        </select>

        <select name="customer_id" class="rounded-lg border-slate-300 sm:max-w-[200px]" onchange="this.form.submit()">
            <option value="">All Customers</option>
            @foreach($customer_options as $customer)
                <option value="{{ $customer->id }}" @selected((string) request('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>

        <button class="rounded-lg bg-slate-800 px-3 py-2 font-bold text-white">
            <i class="fas fa-filter"></i> Apply
        </button>

        @if(request('q') || request('period', 'all') !== 'all' || request('payment_status') || request('customer_id') || request('product_id') || request('payment_method'))
            <a href="{{ route('sales.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 font-bold text-slate-500 hover:bg-slate-50">
                <i class="fas fa-rotate-left mr-1"></i> Reset
            </a>
        @endif
    </form>

    <div class="flex items-center gap-2">
        <a href="{{ route('sales.export.csv', request()->query()) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">
            <i class="fas fa-file-csv text-emerald-600"></i> CSV
        </a>
        <a href="{{ route('sales.export.pdf', request()->query()) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">
            <i class="fas fa-file-pdf text-red-500"></i> PDF
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-[1380px] w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3 text-left">Invoice</th>
                    <th class="px-4 py-3 text-left">Receipt(s)</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Total</th>
                    <th class="px-4 py-3 text-left">Paid</th>
                    <th class="px-4 py-3 text-left">Balance</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @if($items->count() > 0)
                    @foreach($items as $row)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $row->invoice_no ?: ('SALE-'.$row->id) }}</div>
                                @if($row->orders->count() > 0)
                                    <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                        <i class="fas fa-globe"></i>
                                        Online Order
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 min-w-[250px]">
                                @php
                                    $saleReceipts = $row->payments
                                        ->where('status', 'successful')
                                        ->sortByDesc('id')
                                        ->values();
                                @endphp

                                @if($saleReceipts->isNotEmpty())
                                    <div class="flex flex-col items-start gap-1.5">
                                        @foreach($saleReceipts as $payment)
                                            <a href="{{ route('payments.receipt-preview', $payment->id) }}"
                                               title="Preview receipt {{ $payment->receipt_no }}"
                                               class="group inline-flex max-w-full items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100">
                                                <i class="fas fa-receipt shrink-0"></i>

                                                <span class="truncate">
                                                    {{ $payment->receipt_no ?: ('Receipt #'.$payment->id) }}
                                                </span>

                                                <span class="shrink-0 text-[10px] font-semibold text-emerald-600">
                                                    UGX {{ number_format((float)$payment->amount, 0) }}
                                                </span>
                                            </a>
                                        @endforeach

                                        @if($saleReceipts->count() > 1)
                                            <span class="text-[10px] font-bold text-slate-400">
                                                <i class="fas fa-layer-group mr-1"></i>
                                                {{ $saleReceipts->count() }} receipts
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-400">
                                        <i class="fas fa-receipt"></i>
                                        No receipt yet
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-700">{{ $row->customer?->name ?: ($row->orders->first()?->customer_name ?: 'Walk-in Customer') }}</div>
                                @if($row->orders->count() > 0)
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ $row->orders->first()?->order_number ?: $row->orders->first()?->order_id }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap font-extrabold text-slate-800">
                                UGX {{ number_format((float) $row->total_amount, 0) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-emerald-700 font-semibold">
                                UGX {{ number_format((float) $row->partial_amount, 0) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap font-semibold {{ (float) $row->balance_amount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                UGX {{ number_format((float) $row->balance_amount, 0) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($row->payment_status === 'paid')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">Paid</span>
                                @elseif($row->payment_status === 'partial')
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">Part Paid</span>
                                @elseif($row->customer_id && $row->orders->isEmpty())
                                    <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">On Credit</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Pending</span>
                                @endif
                                <div class="mt-1 text-[10px] font-semibold text-slate-400">{{ $row->payment_method }}</div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-slate-500">
                                {{ $row->sale_date?->format('d M Y') ?: '—' }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @if(in_array($row->payment_status, ['pending', 'partial'], true) && (float) $row->balance_amount > 0)
                                        <button type="button"
                                                title="Record another payment and generate a new receipt"
                                                class="quick-pay-button inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 font-bold text-white hover:bg-emerald-700"
                                                data-sale-id="{{ $row->id }}"
                                                data-invoice="{{ $row->invoice_no }}"
                                                data-customer="{{ $row->customer?->name ?: ($row->orders->first()?->customer_name ?: 'Customer') }}"
                                                data-balance="{{ (float) $row->balance_amount }}"
                                                data-order="{{ $row->orders->first()?->order_number ?: $row->orders->first()?->order_id }}">
                                            <i class="fas fa-money-bill-wave"></i>
                                            Pay
                                        </button>
                                    @endif

                                    <button type="button"
                                            title="View sale"
                                            class="sale-view-button inline-grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                                            data-id="{{ $row->id }}"
                                            data-invoice="{{ $row->invoice_no }}"
                                            data-customer="{{ $row->customer?->name ?: ($row->orders->first()?->customer_name ?: 'Walk-in Customer') }}"
                                            data-supplier="{{ $row->supplier?->name ?: '—' }}"
                                            data-branch="{{ $row->branch?->name ?: '—' }}"
                                            data-user="{{ $row->user?->name ?: $row->createdBy?->name ?: '—' }}"
                                            data-total="{{ number_format((float)$row->total_amount, 2) }}"
                                            data-discount="{{ number_format((float)$row->discount, 2) }}"
                                            data-vat="{{ number_format((float)$row->vat, 2) }}"
                                            data-method="{{ $row->payment_method ?: '—' }}"
                                            data-status="{{ ucfirst($row->payment_status ?: 'pending') }}"
                                            data-partial="{{ number_format((float)$row->partial_amount, 2) }}"
                                            data-balance="{{ number_format((float)$row->balance_amount, 2) }}"
                                            data-payments="{{ $row->payments->where('status', 'successful')->sortBy('id')->values()->map(fn ($p) => ['receipt' => $p->receipt_no, 'method' => $p->method, 'amount' => (float) $p->amount, 'date' => $p->created_at?->format('d M Y H:i'), 'url' => route('payments.receipt-preview', $p->id)])->toJson() }}"
                                            data-date="{{ $row->sale_date?->format('d M Y') ?: '—' }}"
                                            data-products="{{ $row->sale_items->map(fn($item) => ($item->product?->name ?: 'Product').' × '.$item->quantity)->join(', ') ?: '—' }}">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <a href="{{ route('sales.invoice-preview', $row->id) }}"
                                       title="Preview Invoice Before Download"
                                       class="inline-grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-indigo-600 hover:bg-indigo-50">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>

                                    <a href="{{ route('sales.delivery-note-preview', $row->id) }}"
                                       title="Delivery Note"
                                       class="inline-grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-sky-600 hover:bg-sky-50">
                                        <i class="fas fa-truck"></i>
                                    </a>

                                    <form method="POST"
                                          action="{{ route('sales.destroy', $row->id) }}"
                                          class="inline"
                                          data-confirm="Delete this sale?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="Delete sale"
                                                class="inline-grid h-8 w-8 place-items-center rounded-lg border border-red-100 text-red-600 hover:bg-red-50">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                            <i class="fas fa-cash-register mb-2 block text-3xl"></i>
                            <strong class="block text-slate-500">No sales found</strong>
                            <span class="text-xs">Create a POS sale or change the current filters.</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $items->withQueryString()->links() }}</div>

{{-- Manual Sale Modal --}}
<div id="sales-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h2 id="sales-modal-title" class="text-lg font-extrabold text-slate-800">
                    <i class="fas fa-file-circle-plus mr-1 text-indigo-500"></i> Manual Sale Entry
                </h2>
                <p class="mt-1 text-xs text-slate-400">Use POS for normal product sales. Manual entry is for adjustments/imported records.</p>
            </div>
            <button type="button" onclick="closeModal('sales-modal')" class="h-9 w-9 rounded-lg text-slate-400 hover:bg-slate-100">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <form id="sales-form" method="POST" action="{{ route('sales.store') }}" class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="transaction_type" value="sale">

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">
                    <i class="fas fa-user mr-1 text-slate-400"></i> Customer Name
                </label>
                <select name="customer_id" class="w-full rounded-lg border-slate-300">
                    <option value="">Walk-in Customer</option>
                    @foreach($customer_options as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-slate-400">Select the customer by name, not customer ID.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">
                    <i class="fas fa-truck mr-1 text-slate-400"></i> Supplier Name
                </label>
                <select name="supplier_id" class="w-full rounded-lg border-slate-300">
                    <option value="">No Supplier / Not Applicable</option>
                    @foreach($supplier_options as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-slate-400">Select the supplier by name where applicable.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700"><i class="fas fa-building mr-1 text-slate-400"></i> Branch Name</label>
                <select name="branch_id" class="w-full rounded-lg border-slate-300">
                    <option value="">Select Branch</option>
                    @foreach($branch_options as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">
                    <i class="fas fa-user-shield mr-1 text-slate-400"></i> Recorded By
                </label>
                <select name="user_id" class="w-full rounded-lg border-slate-300">
                    <option value="">Current User / Automatic</option>
                    @foreach($user_options as $userOption)
                        <option value="{{ $userOption->id }}" {{ auth()->id() === $userOption->id ? 'selected' : '' }}>
                            {{ $userOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Invoice No.</label>
                <input type="text" name="invoice_no" class="w-full rounded-lg border-slate-300" placeholder="Auto/generated or manual ref">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Sale Date</label>
                <input type="date" name="sale_date" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border-slate-300">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Total Amount</label>
                <input type="number" step="0.01" min="0" name="total_amount" class="w-full rounded-lg border-slate-300">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Amount Received</label>
                <input type="number" step="0.01" min="0" name="payments[0][amount]" value="0" class="w-full rounded-lg border-slate-300">
                <p class="mt-1 text-[11px] text-slate-400">Less than the total (or 0) leaves the balance on the customer's credit. Requires a registered customer.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Payment Method</label>
                <select name="payments[0][method]" class="w-full rounded-lg border-slate-300">
                    @foreach($paymentMethods as $method)<option value="{{ $method }}">{{ $method }}</option>@endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">Discount</label>
                <input type="number" step="0.01" min="0" name="discount" value="0" required class="w-full rounded-lg border-slate-300">
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold text-slate-700">VAT</label>
                <input type="number" step="0.01" min="0" name="vat" value="0" required class="w-full rounded-lg border-slate-300">
            </div>

            <div class="md:col-span-2 flex items-center gap-2 border-t border-slate-100 pt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                    <i class="fas fa-floppy-disk mr-1"></i> Save Manual Entry
                </button>
                <button type="button" onclick="closeModal('sales-modal')" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-500">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- New POS Sale Modal --}}
<div id="sales-pos-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800"><i class="fas fa-cash-register mr-1 text-emerald-600"></i> New Sale (POS)</h2>
                <p class="mt-1 text-xs text-slate-400">Select a branch and sell only products currently available in stock.</p>
            </div>
            <button type="button" onclick="closeModal('sales-pos-modal')" class="h-9 w-9 rounded-lg text-slate-400 hover:bg-slate-100"><i class="fas fa-xmark"></i></button>
        </div>

        <form id="pos-form" method="POST" action="{{ route('sales.pos-store') }}" class="space-y-5 p-5">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Branch Name <span class="text-red-500">*</span></label>
                    <select id="pos-branch" name="branch_id" class="w-full rounded-lg border-slate-300" onchange="posBranchChanged()" required>
                        <option value="">Select Branch</option>
                        @foreach($branch_options as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-bold text-slate-700">Customer Name</label>
                    <select id="pos-customer" name="customer_id" class="w-full rounded-lg border-slate-300" onchange="posRecalc()">
                        <option value="" data-balance="0">Walk-in Customer (pays in full)</option>
                        @foreach($customer_options as $customer)
                            <option value="{{ $customer->id }}" data-balance="{{ (float) $customer->outstanding_balance }}">{{ $customer->name }}{{ (float) $customer->outstanding_balance > 0 ? ' — owes UGX '.number_format((float) $customer->outstanding_balance, 0) : '' }}</option>
                        @endforeach
                    </select>
                    <p id="pos-customer-hint" class="mt-1 text-[11px] text-slate-400">Registered customers can pay part now or buy on credit.</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <strong class="text-sm text-slate-700"><i class="fas fa-boxes-stacked mr-1"></i> Sale Items</strong>
                    <button type="button" onclick="posAddRow()" class="text-sm font-bold text-indigo-600 hover:text-indigo-800"><i class="fas fa-plus mr-1"></i> Add Item</button>
                </div>
                <div class="overflow-x-auto p-3">
                    <table class="min-w-[680px] w-full text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-slate-400">
                                <th class="pb-2 text-left">Product</th>
                                <th class="pb-2 text-left">Qty</th>
                                <th class="pb-2 text-right">Price</th>
                                <th class="pb-2 text-right">Subtotal</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody id="pos-items-body"></tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Discount</label>
                    <input type="number" id="pos-discount" name="discount" value="0" min="0" step="0.01" class="w-full rounded-lg border-slate-300" oninput="posRecalc()">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">VAT</label>
                    <input type="number" id="pos-vat" name="vat" value="0" min="0" step="0.01" class="w-full rounded-lg border-slate-300" oninput="posRecalc()">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_280px]">
                <div class="rounded-xl border border-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <strong class="text-sm text-slate-700"><i class="fas fa-wallet mr-1"></i> Payments</strong>
                        <div class="flex items-center gap-3 text-sm font-bold">
                            <button type="button" onclick="tenderAdd('pos')" class="text-indigo-600 hover:text-indigo-800"><i class="fas fa-plus mr-1"></i> Split Payment</button>
                            <button type="button" id="pos-credit-button" onclick="posOnCredit()" class="text-rose-600 hover:text-rose-800"><i class="fas fa-handshake mr-1"></i> All on Credit</button>
                        </div>
                    </div>
                    <div id="pos-tenders" class="space-y-2 p-3"></div>
                    <p class="px-3 pb-3 text-[11px] text-slate-400">Each payment line produces its own receipt. Remove every line, or set it to 0, to sell on credit.</p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-sm">
                    <div class="flex justify-between text-slate-500"><span>Subtotal</span><span id="pos-subtotal-display">UGX 0</span></div>
                    <div class="mt-2 flex justify-between border-t border-slate-200 pt-2 text-lg font-extrabold text-slate-800"><span>Total</span><span id="pos-total-display">UGX 0</span></div>
                    <div class="mt-2 flex justify-between font-bold text-emerald-700"><span>Paying now</span><span id="pos-paid-display">UGX 0</span></div>
                    <div class="mt-1 flex justify-between font-bold text-slate-500"><span id="pos-balance-label">Balance on credit</span><span id="pos-balance-display">UGX 0</span></div>
                    <div id="pos-payment-warning" class="mt-3 hidden rounded-lg px-3 py-2 text-xs font-semibold"></div>
                </div>
            </div>

            <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                <button type="submit" id="pos-submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"><i class="fas fa-check mr-1"></i> <span id="pos-submit-label">Record Sale</span></button>
                <button type="button" onclick="closeModal('sales-pos-modal')" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-500">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Quick Payment Modal --}}
<div id="quick-pay-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/55 p-4">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-extrabold text-slate-800">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-100 text-emerald-700"><i class="fas fa-money-bill-wave"></i></span>
                    Process Payment
                </h2>
                <p id="quickPayReference" class="mt-1 text-xs text-slate-400"></p>
            </div>
            <button type="button" onclick="closeQuickPay()" class="h-9 w-9 rounded-lg text-slate-400 hover:bg-slate-100"><i class="fas fa-xmark"></i></button>
        </div>

        <form id="quickPayForm" method="POST" class="space-y-4 p-5">
            @csrf

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Customer</div>
                <div id="quickPayCustomer" class="mt-1 font-bold text-slate-800">—</div>
                <div class="mt-3 text-xs font-bold uppercase tracking-wide text-slate-400">Outstanding Balance</div>
                <div id="quickPayBalance" class="mt-1 text-2xl font-extrabold text-emerald-700">UGX 0</div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-700">Payments <span class="text-red-500">*</span></span>
                    <button type="button" onclick="tenderAdd('quick')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800"><i class="fas fa-plus mr-1"></i> Split Payment</button>
                </div>
                <div id="quick-tenders" class="space-y-2"></div>
                <div class="mt-2 flex justify-between text-xs font-bold">
                    <span class="text-slate-500">Paying now: <span id="quickPayPaid" class="text-emerald-700">UGX 0</span></span>
                    <span class="text-slate-500">Left after this: <span id="quickPayLeft" class="text-amber-700">UGX 0</span></span>
                </div>
                <p id="quickPayWarning" class="mt-1 hidden text-xs font-semibold text-red-600"></p>
                <p class="mt-1 text-xs text-slate-400">Pay all or part of the balance. Each line gets its own receipt.</p>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-700">
                <i class="fas fa-envelope mr-1"></i>
                A successful payment generates a branded receipt and emails it to the customer when an email address is available.
            </div>

            <button id="quickPaySubmit" type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 py-2.5 font-extrabold text-white hover:bg-emerald-700">
                <i class="fas fa-check-circle"></i>
                Pay & Generate Receipt
            </button>
        </form>
    </div>
</div>
@endsection


<div id="sale-view-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-wide text-indigo-500">Sale Details</div>
                <h3 id="saleViewTitle" class="mt-1 text-lg font-black text-slate-900">Sale</h3>
            </div>
            <button type="button" onclick="closeSaleViewModal()"
                    class="grid h-9 w-9 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="max-h-[75vh] overflow-y-auto p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Invoice No', 'saleViewInvoice', 'fa-file-invoice'],
                    ['Customer', 'saleViewCustomer', 'fa-user'],
                    ['Supplier', 'saleViewSupplier', 'fa-truck'],
                    ['Branch', 'saleViewBranch', 'fa-building'],
                    ['Recorded By', 'saleViewUser', 'fa-user-shield'],
                    ['Sale Date', 'saleViewDate', 'fa-calendar-day'],
                    ['Payment Method', 'saleViewMethod', 'fa-wallet'],
                    ['Payment Status', 'saleViewStatus', 'fa-circle-check'],
                    ['Total Amount', 'saleViewTotal', 'fa-money-bill-wave'],
                    ['Discount', 'saleViewDiscount', 'fa-percent'],
                    ['VAT', 'saleViewVat', 'fa-receipt'],
                    ['Balance', 'saleViewBalance', 'fa-scale-balanced'],
                ] as [$label, $id, $icon])
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                            <i class="fas {{ $icon }} mr-1"></i>{{ $label }}
                        </div>
                        <div id="{{ $id }}" class="mt-1 break-words font-bold text-slate-800">—</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">
                    <i class="fas fa-boxes-stacked mr-1"></i> Products
                </div>
                <div id="saleViewProducts" class="mt-2 text-sm font-semibold leading-6 text-slate-700">—</div>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wide text-slate-400">
                    <span><i class="fas fa-receipt mr-1"></i> Payments &amp; Receipts</span>
                    <span id="saleViewPaid" class="text-emerald-700"></span>
                </div>
                <div id="saleViewPayments" class="mt-2 divide-y divide-slate-100 text-sm"></div>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-3">
            <button type="button" onclick="closeSaleViewModal()"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const STOCK_DATA = @json($stockData);
let posRowCount = 0;

function posAddRow() {
    const branchId = document.getElementById('pos-branch').value;
    const tbody = document.getElementById('pos-items-body');
    const index = posRowCount++;
    const products = STOCK_DATA.filter(item => String(item.branch_id) === String(branchId));

    const row = document.createElement('tr');
    const options = ['<option value="">Select product</option>']
        .concat(products.map(product => `<option value="${product.product_id}" data-price="${product.price}" data-available="${product.available}">${escapeHtml(product.name)} (${product.available} in stock)</option>`))
        .join('');

    row.innerHTML = `
        <td class="py-1 pr-2">
            <select name="items[${index}][product_id]" class="w-full rounded-lg border-slate-300" onchange="posRowChanged(this)" required>${options}</select>
        </td>
        <td class="py-1 pr-2"><input type="number" name="items[${index}][quantity]" min="1" value="1" class="w-24 rounded-lg border-slate-300" oninput="posRowChanged(this)" required></td>
        <td class="pos-price py-1 pr-2 text-right">0</td>
        <td class="pos-subtotal py-1 pr-2 text-right font-bold">0</td>
        <td class="py-1 text-right"><button type="button" class="text-red-500" onclick="this.closest('tr').remove(); posRecalc();"><i class="fas fa-trash"></i></button></td>`;

    tbody.appendChild(row);
}

function posRowChanged(element) {
    const row = element.closest('tr');
    const select = row.querySelector('select');
    const qtyInput = row.querySelector('input[type="number"]');
    const option = select.selectedOptions[0];
    const price = option ? Number(option.dataset.price || 0) : 0;
    const available = option ? Number(option.dataset.available || 0) : 0;

    if (available > 0 && Number(qtyInput.value) > available) qtyInput.value = available;

    const quantity = Number(qtyInput.value || 0);
    row.querySelector('.pos-price').textContent = price.toLocaleString();
    row.querySelector('.pos-subtotal').textContent = (price * quantity).toLocaleString();
    posRecalc();
}

function posRecalc() {
    let subtotal = 0;
    document.querySelectorAll('#pos-items-body tr').forEach(row => {
        const select = row.querySelector('select');
        const quantity = Number(row.querySelector('input[type="number"]').value || 0);
        const option = select.selectedOptions[0];
        const price = option ? Number(option.dataset.price || 0) : 0;
        subtotal += price * quantity;
    });

    const discount = Number(document.getElementById('pos-discount').value || 0);
    const vat = Number(document.getElementById('pos-vat').value || 0);
    const total = Math.max(subtotal - discount + vat, 0);
    posTotal = Math.round(total * 100) / 100;

    // A single untouched payment line follows the total, so a normal cash sale needs no typing.
    const rows = tenderRows('pos');
    if (rows.length === 1 && rows[0].dataset.auto === '1') {
        rows[0].querySelector('.tender-amount').value = posTotal ? posTotal.toFixed(2) : '';
    }

    document.getElementById('pos-subtotal-display').textContent = money(subtotal);
    document.getElementById('pos-total-display').textContent = money(total);
    posPaymentSummary();
}

function posPaymentSummary() {
    const customer = document.getElementById('pos-customer');
    const option = customer.selectedOptions[0];
    const isRegistered = Boolean(customer.value);
    const owed = Number(option?.dataset.balance || 0);
    const paid = tenderSum('pos');
    const balance = Math.round((posTotal - paid) * 100) / 100;

    document.getElementById('pos-paid-display').textContent = money(paid);
    document.getElementById('pos-balance-label').textContent = balance < 0 ? 'Overpaid by' : 'Balance on credit';
    document.getElementById('pos-balance-display').textContent = money(Math.abs(balance));

    const hint = document.getElementById('pos-customer-hint');
    hint.textContent = isRegistered
        ? (owed > 0 ? `Already owes ${money(owed)} from earlier sales.` : 'No outstanding balance. Can pay part now or buy on credit.')
        : 'Walk-in customers pay in full. Pick a registered customer to sell on credit.';
    hint.className = `mt-1 text-[11px] ${isRegistered && owed > 0 ? 'font-semibold text-amber-700' : 'text-slate-400'}`;

    let warning = '';
    let tone = '';
    let blocked = false;
    let label = 'Record Sale';

    if (posTotal > 0 && balance < -0.004) {
        warning = `Payments are ${money(-balance)} more than the total. Reduce a payment line.`;
        tone = 'bg-red-50 text-red-700';
        blocked = true;
    } else if (posTotal > 0 && balance > 0.004 && !isRegistered) {
        warning = 'Walk-in customers must pay the full amount. Select a registered customer to sell on credit or take a part payment.';
        tone = 'bg-red-50 text-red-700';
        blocked = true;
    } else if (posTotal > 0 && balance > 0.004) {
        warning = `${money(balance)} goes on ${escapeHtml(option.textContent.split(' — ')[0])}'s credit. New balance owed: ${money(owed + balance)}.`;
        tone = 'bg-amber-50 text-amber-800';
        label = paid > 0 ? 'Record Part-Paid Sale' : 'Record Credit Sale';
    } else if (tenderRows('pos').filter(r => Number(r.querySelector('.tender-amount').value) > 0).length > 1) {
        label = 'Record Split-Paid Sale';
    }

    const box = document.getElementById('pos-payment-warning');
    box.className = `mt-3 rounded-lg px-3 py-2 text-xs font-semibold ${warning ? tone : 'hidden'}`;
    box.innerHTML = warning;
    document.getElementById('pos-submit').disabled = blocked;
    document.getElementById('pos-submit-label').textContent = label;
    document.getElementById('pos-credit-button').classList.toggle('hidden', !isRegistered);
}

function posOnCredit() {
    document.getElementById('pos-tenders').innerHTML = '';
    posPaymentSummary();
}

// ---- Payment lines shared by the POS and the "Pay" modal --------------------
const PAYMENT_METHODS = @json($paymentMethods);
const tenderCounters = { pos: 0, quick: 0 };
let posTotal = 0;
let quickBalance = 0;

function money(value) {
    return `UGX ${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
}

function tenderRows(group) {
    return [...document.querySelectorAll(`#${group}-tenders .tender-row`)];
}

function tenderSum(group) {
    const sum = tenderRows(group).reduce((acc, row) => acc + Number(row.querySelector('.tender-amount').value || 0), 0);
    return Math.round(sum * 100) / 100;
}

function tenderDue(group) {
    return group === 'pos' ? posTotal : quickBalance;
}

function tenderChanged(group) {
    group === 'pos' ? posPaymentSummary() : quickPaySummary();
}

/** Adds a payment line. Without an amount it takes whatever is still unpaid. */
function tenderAdd(group, method = null, amount = null) {
    const container = document.getElementById(`${group}-tenders`);
    const index = tenderCounters[group]++;
    const remaining = Math.max(Math.round((tenderDue(group) - tenderSum(group)) * 100) / 100, 0);
    const used = tenderRows(group).map(r => r.querySelector('select').value);
    const chosen = method || PAYMENT_METHODS.find(m => !used.includes(m)) || PAYMENT_METHODS[0];

    const row = document.createElement('div');
    row.className = 'tender-row flex items-center gap-2';
    row.dataset.auto = amount === null && tenderRows(group).length === 0 ? '1' : '0';
    row.innerHTML = `
        <select name="payments[${index}][method]" class="w-40 shrink-0 rounded-lg border-slate-300 text-sm">
            ${PAYMENT_METHODS.map(m => `<option value="${escapeHtml(m)}" ${m === chosen ? 'selected' : ''}>${escapeHtml(m)}</option>`).join('')}
        </select>
        <input type="number" name="payments[${index}][amount]" min="0" step="0.01" placeholder="0" class="tender-amount min-w-0 flex-1 rounded-lg border-slate-300 text-sm">
        <button type="button" title="Fill with the amount still unpaid" class="tender-rest shrink-0 rounded-lg border border-slate-200 px-2 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50">Rest</button>
        <button type="button" title="Remove payment line" class="tender-remove grid h-9 w-9 shrink-0 place-items-center rounded-lg text-red-500 hover:bg-red-50"><i class="fas fa-trash"></i></button>`;

    const input = row.querySelector('.tender-amount');
    const value = amount === null ? remaining : Number(amount || 0);
    input.value = value > 0 ? value.toFixed(2) : '';

    input.addEventListener('input', () => { row.dataset.auto = '0'; tenderChanged(group); });
    row.querySelector('select').addEventListener('change', () => tenderChanged(group));
    row.querySelector('.tender-rest').addEventListener('click', () => {
        const others = tenderSum(group) - Number(input.value || 0);
        const rest = Math.max(Math.round((tenderDue(group) - others) * 100) / 100, 0);
        input.value = rest > 0 ? rest.toFixed(2) : '';
        row.dataset.auto = '0';
        tenderChanged(group);
    });
    row.querySelector('.tender-remove').addEventListener('click', () => { row.remove(); tenderChanged(group); });

    container.appendChild(row);
    tenderChanged(group);
    return row;
}

/** Rebuilds payment lines from a failed submit's values. */
function tenderRestore(group, pairs) {
    const lines = new Map();
    pairs.forEach(([name, value]) => {
        const match = name.match(/^payments\[(\d+)\]\[(method|amount)\]$/);
        if (!match) return;
        if (!lines.has(match[1])) lines.set(match[1], {});
        lines.get(match[1])[match[2]] = value;
    });
    document.getElementById(`${group}-tenders`).innerHTML = '';
    [...lines.values()].forEach(line => tenderAdd(group, line.method || null, line.amount ?? 0));
    tenderChanged(group);
}

// Rebuild sale item rows after a failed submit (see layout "form:restored").
document.getElementById('pos-form')?.addEventListener('form:restored', event => {
    const rows = new Map();
    event.detail.pairs.forEach(([name, value]) => {
        const match = name.match(/^items\[(\d+)\]\[(\w+)\]$/);
        if (!match) return;
        const index = Number(match[1]);
        if (!rows.has(index)) rows.set(index, {});
        rows.get(index)[match[2]] = value;
    });
    if (!rows.size) return;

    const tbody = document.getElementById('pos-items-body');
    tbody.innerHTML = '';
    const indexes = [...rows.keys()].sort((a, b) => a - b);
    indexes.forEach(index => {
        posRowCount = index; // keep the submitted index so error keys still match
        posAddRow();
        const row = tbody.lastElementChild;
        const select = row.querySelector('select');
        const qty = row.querySelector('input[type="number"]');
        select.value = rows.get(index).product_id ?? '';
        if (rows.get(index).quantity !== undefined) qty.value = rows.get(index).quantity;
        posRowChanged(select);
    });
    posRowCount = indexes[indexes.length - 1] + 1;
    posRecalc();
});

document.getElementById('pos-form')?.addEventListener('form:restored', event => {
    tenderRestore('pos', event.detail.pairs);
    posRecalc();
});

document.getElementById('pos-form')?.addEventListener('submit', event => {
    if (document.getElementById('pos-submit').disabled) event.preventDefault();
});

function posBranchChanged() {
    document.getElementById('pos-items-body').innerHTML = '';
    posRowCount = 0;
    posAddRow();
    posRecalc();
}

function openPosModal() {
    document.getElementById('pos-form').reset();
    document.getElementById('pos-items-body').innerHTML = '';
    document.getElementById('pos-tenders').innerHTML = '';
    posRowCount = 0;
    posTotal = 0;
    tenderAdd('pos', 'Cash');
    posRecalc();
    openModal('sales-pos-modal');
}

function openQuickPayFromButton(button) {
    const saleId = button.dataset.saleId;
    const invoice = button.dataset.invoice || `Sale ${saleId}`;
    const customer = button.dataset.customer || 'Customer';
    const balance = Number(button.dataset.balance || 0);
    const order = button.dataset.order || '';

    const form = document.getElementById('quickPayForm');
    form.action = `{{ url('/sales') }}/${saleId}/payments`;

    document.getElementById('quickPayReference').textContent = order
        ? `Online Order ${order} · Invoice ${invoice}`
        : `Invoice ${invoice}`;
    document.getElementById('quickPayCustomer').textContent = customer;
    document.getElementById('quickPayBalance').textContent = money(balance);

    quickBalance = Math.round(balance * 100) / 100;
    document.getElementById('quick-tenders').innerHTML = '';
    const row = tenderAdd('quick', 'Cash');

    const submit = document.getElementById('quickPaySubmit');
    submit.innerHTML = '<i class="fas fa-check-circle"></i> Pay & Generate Receipt';

    const modal = document.getElementById('quick-pay-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => row.querySelector('.tender-amount').focus(), 50);
}

function quickPaySummary() {
    const paid = tenderSum('quick');
    const left = Math.round((quickBalance - paid) * 100) / 100;
    const warning = document.getElementById('quickPayWarning');
    const over = left < -0.004;

    document.getElementById('quickPayPaid').textContent = money(paid);
    document.getElementById('quickPayLeft').textContent = money(Math.max(left, 0));
    warning.textContent = over ? `That is ${money(-left)} more than the balance due.` : (paid <= 0 ? 'Enter at least one payment amount.' : '');
    warning.classList.toggle('hidden', !over && paid > 0);
    document.getElementById('quickPaySubmit').disabled = over || paid <= 0;
}

function closeQuickPay() {
    const modal = document.getElementById('quick-pay-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

document.querySelectorAll('.quick-pay-button').forEach(button => {
    button.addEventListener('click', () => openQuickPayFromButton(button));
});

document.getElementById('quick-pay-modal')?.addEventListener('click', event => {
    if (event.target === event.currentTarget) closeQuickPay();
});

document.getElementById('quickPayForm')?.addEventListener('submit', event => {
    const button = document.getElementById('quickPaySubmit');
    if (button.disabled) { event.preventDefault(); return; }
    // Disable on the next tick so the layout can still snapshot the values for restore.
    setTimeout(() => {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
    }, 0);
});

// After a rejected payment, reopen the same sale with the lines that were entered.
document.getElementById('quickPayForm')?.addEventListener('form:restored', event => {
    const saleId = (event.currentTarget.action.match(/\/sales\/(\d+)\/payments/) || [])[1];
    const button = saleId && document.querySelector(`.quick-pay-button[data-sale-id="${saleId}"]`);
    if (!button) { closeQuickPay(); return; }
    openQuickPayFromButton(button);
    tenderRestore('quick', event.detail.pairs);
});

function openSaleViewModal(button) {
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    };

    setText('saleViewTitle', `Sale #${button.dataset.id || ''}`);
    setText('saleViewInvoice', button.dataset.invoice);
    setText('saleViewCustomer', button.dataset.customer);
    setText('saleViewSupplier', button.dataset.supplier);
    setText('saleViewBranch', button.dataset.branch);
    setText('saleViewUser', button.dataset.user);
    setText('saleViewDate', button.dataset.date);
    setText('saleViewMethod', button.dataset.method);
    setText('saleViewStatus', button.dataset.status);
    setText('saleViewTotal', `UGX ${button.dataset.total || '0.00'}`);
    setText('saleViewDiscount', `UGX ${button.dataset.discount || '0.00'}`);
    setText('saleViewVat', `UGX ${button.dataset.vat || '0.00'}`);
    setText('saleViewBalance', `UGX ${button.dataset.balance || '0.00'}`);
    setText('saleViewProducts', button.dataset.products);

    let payments = [];
    try { payments = JSON.parse(button.dataset.payments || '[]'); } catch (e) { payments = []; }
    const list = document.getElementById('saleViewPayments');
    const paidTotal = payments.reduce((acc, p) => acc + Number(p.amount || 0), 0);
    setText('saleViewPaid', payments.length ? `${payments.length} payment${payments.length > 1 ? 's' : ''} · ${money(paidTotal)}` : '');
    list.innerHTML = payments.length
        ? payments.map(p => `
            <div class="flex items-center justify-between gap-3 py-2">
                <div>
                    <a href="${escapeHtml(p.url)}" class="font-bold text-indigo-600 hover:underline"><i class="fas fa-receipt mr-1"></i>${escapeHtml(p.receipt || 'Receipt')}</a>
                    <div class="text-xs text-slate-400">${escapeHtml(p.date || '')} · ${escapeHtml(p.method || '')}</div>
                </div>
                <div class="font-bold text-emerald-700">${money(p.amount)}</div>
            </div>`).join('')
        : '<div class="py-2 text-sm text-slate-400">No payments yet — the full amount is on credit.</div>';

    const modal = document.getElementById('sale-view-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeSaleViewModal() {
    const modal = document.getElementById('sale-view-modal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

document.querySelectorAll('.sale-view-button').forEach(button => {
    button.addEventListener('click', () => openSaleViewModal(button));
});

document.getElementById('sale-view-modal')?.addEventListener('click', event => {
    if (event.target === event.currentTarget) closeSaleViewModal();
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeSaleViewModal();
});

</script>
@endpush
