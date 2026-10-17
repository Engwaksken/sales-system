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
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
@include('partials.stat-card', ['icon' => 'fa-warehouse', 'color' => 'indigo', 'label' => 'Total Stock', 'value' => $items->total()])
@include('partials.stat-card', ['icon' => 'fa-triangle-exclamation', 'color' => 'amber', 'label' => 'Low Stock (&le;5)', 'value' => $stat1])
@include('partials.stat-card', ['icon' => 'fa-coins', 'color' => 'indigo', 'label' => 'Total Stock Value', 'value' => $stat2])
</div>
@php($isMoney = true)
@include('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney])
<form method="GET" class="flex items-center gap-2 text-sm mb-4">
    
    <label class="text-slate-500 ml-2"><i class="fa-solid fa-filter"></i> Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" {{ request('period','all')=='all'?'selected':'' }}>All Time</option>
        <option value="today" {{ request('period')=='today'?'selected':'' }}>Today</option>
        <option value="week" {{ request('period')=='week'?'selected':'' }}>This Week</option>
        <option value="month" {{ request('period')=='month'?'selected':'' }}>This Month</option>
        <option value="year" {{ request('period')=='year'?'selected':'' }}>This Year</option>
    </select>
    @if(request('q') || (request('period') && request('period') !== 'all'))
    <a href="{{ route('stock.index') }}" class="text-slate-400 hover:text-slate-600 text-xs">Clear <i class="fa-solid fa-xmark"></i></a>
    @endif
</form>
<details class="mb-4 bg-white rounded-lg shadow">
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
    <div class="flex items-center gap-2">
      <button formaction="{{ route('stock.export.csv') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="{{ route('stock.export.pdf') }}" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
    </div>
  </form>
</details>
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
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Product</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Branch</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Quantity</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Selling Price</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Expiry Date</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->product?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->branch?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->quantity }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->selling_price }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->expiry_date }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->createdBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->updatedBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->created_at?->format('d M Y H:i') ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->updated_at?->format('d M Y H:i') ?? '—' }}</td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("stock-modal", "stock-form", @json($row))' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("stock-modal", "stock-form", "{{ route('stock.update', $row->id) }}", @json($row))' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="{{ route('stock.destroy', $row->id) }}" class="inline" onsubmit="return confirm('Delete this record?')">
@csrf @method('DELETE')
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
@empty
<tr><td colspan="10" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
@endforelse
</tbody>
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
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('stock-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection
