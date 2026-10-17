@extends('layouts.app')
@section('title', 'ProductBranches')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-code-branch text-slate-400"></i> ProductBranches</h1>
    @include('partials.import-button', ['module' => 'product_branches'])
    <button type="button" onclick="openCreateModal('product_branches-modal', 'product_branches-form', '{{ route('product_branches.store') }}')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New ProductBranch</button>
</div>
<div class="grid grid-cols-1 md:grid-cols-1 gap-4 mb-6">
@include('partials.stat-card', ['icon' => 'fa-code-branch', 'color' => 'indigo', 'label' => 'Total ProductBranches', 'value' => $items->total()])
</div>
@php($isMoney = false)
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
    <a href="{{ route('product_branches.index') }}" class="text-slate-400 hover:text-slate-600 text-xs">Clear <i class="fa-solid fa-xmark"></i></a>
    @endif
</form>
<details class="mb-4 bg-white rounded-lg shadow">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="{{ route('product_branches.export.csv') }}" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="product_id" checked class="rounded border-slate-300"> Product</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="{{ request('period', 'all') }}">
    <input type="hidden" name="q" value="{{ request('q', '') }}">
    <div class="flex items-center gap-2">
      <button formaction="{{ route('product_branches.export.csv') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="{{ route('product_branches.export.pdf') }}" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
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
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Product</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Branch</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->product?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->branch?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->createdBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->updatedBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->created_at?->format('d M Y H:i') ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->updated_at?->format('d M Y H:i') ?? '—' }}</td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("product_branches-modal", "product_branches-form", @json($row))' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("product_branches-modal", "product_branches-form", "{{ route('product_branches.update', $row->id) }}", @json($row))' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="{{ route('product_branches.destroy', $row->id) }}" class="inline" onsubmit="return confirm('Delete this record?')">
@csrf @method('DELETE')
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
@empty
<tr><td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-4">@include('partials.table-pagination', ['paginator' => $items])</div>

<div id="product_branches-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="product_branches-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-code-branch text-indigo-500"></i> New ProductBranch</h2>
      <button type="button" onclick="closeModal('product_branches-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form id="product_branches-form" method="POST" action="{{ route('product_branches.store') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Id</label>
  <select name="product_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Product Id --</option>
    @foreach($product_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('product_branches-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection
