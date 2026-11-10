@extends('layouts.app')
@section('title', 'Stock')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-warehouse text-slate-400"></i> Stock</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('stock.report.pdf') }}" class="text-sm text-slate-500 hover:text-slate-700"><i class="fa-solid fa-file-pdf"></i> Detailed Stock Report (PDF)</a>
        @include('partials.import-button', ['module' => 'stock'])
        <button type="button" onclick="openCreateModal('stock-modal', 'stock-form', '{{ route('stock.store') }}')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New Stock</button>
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
@include('partials.stat-card', ['icon' => 'fa-warehouse', 'color' => 'indigo', 'label' => 'Stock Records', 'value' => $items->total()])
@include('partials.stat-card', ['icon' => 'fa-cubes', 'color' => 'emerald', 'label' => 'Units In Stock', 'value' => number_format((float) $totals->quantity, 0).' of '.number_format((float) $totals->total_stock, 0)])
@include('partials.stat-card', ['icon' => 'fa-triangle-exclamation', 'color' => 'amber', 'label' => 'Low Stock (≤ 5)', 'value' => $stat1])
@include('partials.stat-card', ['icon' => 'fa-coins', 'color' => 'indigo', 'label' => 'Total Stock Value', 'value' => $stat2])
</div>
<section class="mb-6 rounded-lg bg-white p-4 shadow" aria-labelledby="barcode-lookup-heading" data-barcode-lookup data-endpoint="{{ route('stock.lookup-barcode') }}">
    <h2 id="barcode-lookup-heading" class="mb-3 text-sm font-semibold text-slate-700"><i class="fa-solid fa-barcode mr-2 text-indigo-500" aria-hidden="true"></i>Barcode lookup</h2>
    <form class="flex flex-col gap-2 sm:flex-row" data-barcode-lookup-form>
        <label for="stock-barcode" class="sr-only">Barcode</label>
        <input id="stock-barcode" name="barcode" type="text" inputmode="numeric" autocomplete="off" autofocus required
               class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:flex-1"
               placeholder="Scan or enter a barcode" aria-describedby="barcode-lookup-help barcode-lookup-status">
        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"><i class="fa-solid fa-magnifying-glass mr-1" aria-hidden="true"></i>Look up</button>
    </form>
    <p id="barcode-lookup-help" class="mt-2 text-xs text-slate-500">Scan a barcode or type it in, then submit to find matching stock.</p>
    <div id="barcode-lookup-status" class="mt-3 text-sm" role="status" aria-live="polite" aria-atomic="true" data-barcode-lookup-status>
        <p data-state="idle">Ready to scan or enter a barcode.</p>
        <p data-state="loading" hidden>Looking up barcode…</p>
        <p data-state="no-match" hidden>No matching stock was found.</p>
        <p data-state="error" hidden>Unable to look up this barcode. Please try again.</p>
        <div data-state="success" hidden aria-label="Barcode lookup results" data-image-base="{{ asset('storage') }}">
            <div class="flex items-start gap-3">
                <img data-result-image src="" alt="Product image" class="hidden h-16 w-16 shrink-0 rounded-lg border border-slate-200 object-cover">
                <div class="min-w-0 flex-1">
                    <div data-result-name class="text-base font-semibold text-slate-800"></div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                        <span>SKU: <span data-result-sku class="font-medium text-slate-700"></span></span>
                        <span>Barcode: <span data-result-barcode class="font-medium text-slate-700"></span></span>
                        <span data-result-status class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@php($isMoney = true)
@include('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney])
<div class="filter-toolbar">
<form method="GET" class="filter-toolbar__filters">
    <label class="text-slate-500"><i class="fa-solid fa-filter"></i> Product:</label>
    <select name="product_id" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm max-w-[220px]">
        <option value="">All Products</option>
        @foreach($product_options->sortBy('name') as $opt)
        <option value="{{ $opt->id }}" @selected((string) request('product_id') === (string) $opt->id)>{{ $opt->name }}</option>
        @endforeach
    </select>
    <label class="text-slate-500 ml-2">Branch:</label>
    <select name="branch_id" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="">All Branches</option>
        @foreach($branch_options as $opt)
        <option value="{{ $opt->id }}" @selected((string) request('branch_id') === (string) $opt->id)>{{ $opt->name }}</option>
        @endforeach
    </select>
    <label class="text-slate-500 ml-2">Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" {{ request('period','all')=='all'?'selected':'' }}>All Time</option>
        <option value="today" {{ request('period')=='today'?'selected':'' }}>Today</option>
        <option value="week" {{ request('period')=='week'?'selected':'' }}>This Week</option>
        <option value="month" {{ request('period')=='month'?'selected':'' }}>This Month</option>
        <option value="year" {{ request('period')=='year'?'selected':'' }}>This Year</option>
    </select>
    @if(request('q') || request('product_id') || request('branch_id') || (request('period') && request('period') !== 'all'))
    <a href="{{ route('stock.index') }}" class="filter-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
    @endif
</form>
<div class="filter-toolbar__actions">
<details class="export-menu">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="{{ route('stock.export.csv') }}" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="supplier_id" checked class="rounded border-slate-300"> Supplier</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="product_id" checked class="rounded border-slate-300"> Product</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="total_stock" checked class="rounded border-slate-300"> Total Stock</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="quantity" checked class="rounded border-slate-300"> Quantity</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="unit_type" checked class="rounded border-slate-300"> Unit Type</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="cost_price" checked class="rounded border-slate-300"> Cost Price</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="expiry_date" checked class="rounded border-slate-300"> Expiry Date</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="selling_price" checked class="rounded border-slate-300"> Selling Price</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="{{ request('period', 'all') }}">
    <input type="hidden" name="q" value="{{ request('q', '') }}">
    <input type="hidden" name="product_id" value="{{ request('product_id', '') }}">
    <input type="hidden" name="branch_id" value="{{ request('branch_id', '') }}">
    <div class="flex items-center gap-2">
      <button formaction="{{ route('stock.export.csv') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="{{ route('stock.export.pdf') }}" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
    </div>
  </form>
</details>
</div>
</div>
@if(session('status'))
<div data-flash class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Product</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Branch</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Total Stock</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>In Stock</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Selling Price</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Expiry Date</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->product?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->branch?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ rtrim(rtrim(number_format((float) $row->total_stock, 2), '0'), '.') }}</td><td class='px-4 py-2 text-sm font-semibold {{ (float) $row->quantity <= 5 ? 'text-amber-700' : 'text-slate-700' }}'>{{ rtrim(rtrim(number_format((float) $row->quantity, 2), '0'), '.') }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->selling_price }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->expiry_date }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->createdBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->updatedBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->created_at?->format('d M Y H:i') ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->updated_at?->format('d M Y H:i') ?? '—' }}</td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("stock-modal", "stock-form", @json($row))' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("stock-modal", "stock-form", "{{ route('stock.update', $row->id) }}", @json($row))' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="{{ route('stock.destroy', $row->id) }}" class="inline" data-confirm="Delete this record?">
@csrf @method('DELETE')
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
@empty
<tr><td colspan="11" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
@endforelse
</tbody>
@if($items->total() > 0)
<tfoot class="bg-slate-50 font-semibold">
<tr>
<td class="px-4 py-2 text-sm text-slate-700" colspan="2">Total{{ request('product_id') || request('branch_id') || (request('period') && request('period') !== 'all') ? ' (filtered)' : '' }}</td>
<td class="px-4 py-2 text-sm text-slate-800">{{ rtrim(rtrim(number_format((float) $totals->total_stock, 2), '0'), '.') }}</td>
<td class="px-4 py-2 text-sm text-slate-800">{{ rtrim(rtrim(number_format((float) $totals->quantity, 2), '0'), '.') }}</td>
<td class="px-4 py-2 text-sm text-slate-500" colspan="7">Value at cost: {{ $stat2 }}</td>
</tr>
</tfoot>
@endif
</table>
</div>
<div class="mt-4">@include('partials.table-pagination', ['paginator' => $items])</div>

<div id="stock-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="stock-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-warehouse text-indigo-500"></i> New Stock</h2>
      <button type="button" onclick="closeModal('stock-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form id="stock-form" method="POST" action="{{ route('stock.store') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200" role="tablist" aria-label="Stock form sections">
        <button type="button" role="tab" id="stock-tab-product" aria-controls="stock-panel-product" aria-selected="true" tabindex="0" data-tabgroup="stock-form-sections" data-tab="0" onclick="switchTab('stock-form-sections', '0')" class="border-b-2 border-indigo-600 px-3 py-2 text-sm font-medium text-indigo-600">Product & branches</button>
        <button type="button" role="tab" id="stock-tab-inventory" aria-controls="stock-panel-inventory" aria-selected="false" tabindex="-1" data-tabgroup="stock-form-sections" data-tab="1" onclick="switchTab('stock-form-sections', '1')" class="border-b-2 border-transparent px-3 py-2 text-sm font-medium text-slate-500">Stock & pricing</button>
      </div>
      <section id="stock-panel-product" role="tabpanel" aria-labelledby="stock-tab-product" data-tabpanel="stock-form-sections" data-tab="0" class="space-y-4">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Supplier Id</label>
  <select name="supplier_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Supplier Id --</option>
    @foreach($supplier_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Id</label>
  <select name="product_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Product Id --</option>
    @foreach($product_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div data-mode-only="edit">
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div data-mode-only="create">
  @include('partials.branch-checklist', ['branches' => $branch_options, 'label' => 'Add to branches',
      'hint' => 'The same quantity and prices are added to every selected branch. Where the product already has stock from the same supplier, the quantity is added to it.'])
</div>
      </section>
      <section id="stock-panel-inventory" role="tabpanel" aria-labelledby="stock-tab-inventory" data-tabpanel="stock-form-sections" data-tab="1" class="hidden space-y-4">
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Total Stock</label>
  <input type="number" name="total_stock" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Quantity</label>
  <input type="number" name="quantity" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Unit Type</label>
  <input type="text" name="unit_type" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Cost Price</label>
  <input type="number" step='0.01' name="cost_price" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Expiry Date</label>
  <input type="date" name="expiry_date" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Selling Price</label>
  <input type="number" step='0.01' name="selling_price" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
      </section>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('stock-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const section = document.querySelector('[data-barcode-lookup]');
    if (!section) return;

    const form = section.querySelector('[data-barcode-lookup-form]');
    const input = document.getElementById('stock-barcode');
    const status = section.querySelector('[data-barcode-lookup-status]');
    const endpoint = section.dataset.endpoint;

    if (!form || !input || !status || !endpoint) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const states = status.querySelectorAll('[data-state]');
    const success = status.querySelector('[data-state="success"]');

    const resultName = success ? success.querySelector('[data-result-name]') : null;
    const resultSku = success ? success.querySelector('[data-result-sku]') : null;
    const resultBarcode = success ? success.querySelector('[data-result-barcode]') : null;
    const resultStatus = success ? success.querySelector('[data-result-status]') : null;
    const resultImage = success ? success.querySelector('[data-result-image]') : null;
    const imageBase = success ? (success.dataset.imageBase || '') : '';

    function showState(name) {
        states.forEach(el => {
            el.hidden = el.getAttribute('data-state') !== name;
        });
    }

    function clearResult() {
        if (resultName) resultName.textContent = '';
        if (resultSku) resultSku.textContent = '';
        if (resultBarcode) resultBarcode.textContent = '';
        if (resultStatus) {
            resultStatus.textContent = '';
            resultStatus.className = '';
        }
        if (resultImage) {
            resultImage.removeAttribute('src');
            resultImage.hidden = true;
        }
    }

    function resolveImageUrl(imageUrl) {
        const value = String(imageUrl || '').trim();
        if (!value) return '';
        if (/^https?:\/\//i.test(value)) return '';
        return imageBase + '/' + value.replace(/^\/+/, '').replace(/^storage\//, '');
    }

    function renderStatus(active) {
        if (!resultStatus) return;
        resultStatus.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold';
        if (active) {
            resultStatus.classList.add('bg-emerald-100', 'text-emerald-700');
            resultStatus.textContent = 'Active';
        } else {
            resultStatus.classList.add('bg-slate-200', 'text-slate-600');
            resultStatus.textContent = 'Inactive';
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const value = input.value.trim();
        if (!value) {
            input.focus();
            return;
        }

        clearResult();
        showState('loading');

        if (submitButton) submitButton.disabled = true;

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('barcode', value);

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                if (resultName) resultName.textContent = data.name ?? '';
                if (resultSku) resultSku.textContent = data.sku ?? '';
                if (resultBarcode) resultBarcode.textContent = data.barcode ?? '';
                renderStatus(data.is_active === true);

                const imageUrl = resolveImageUrl(data.image_url);
                if (resultImage && imageUrl) {
                    resultImage.src = imageUrl;
                    resultImage.hidden = false;
                }

                showState('success');
            } else if (response.status === 404) {
                showState('no-match');
            } else {
                showState('error');
            }
        } catch (error) {
            showState('error');
        } finally {
            if (submitButton) submitButton.disabled = false;
            input.focus();
        }
    });
})();
</script>
@endpush
