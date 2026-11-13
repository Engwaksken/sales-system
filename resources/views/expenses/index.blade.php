@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-xl font-semibold text-slate-800 flex min-w-0 items-center gap-2"><i class="fa-solid fa-file-invoice-dollar text-slate-400"></i> Expenses</h1>
    @include('partials.import-button', ['module' => 'expenses'])
    <button type="button" onclick="openCreateModal('expenses-modal', 'expenses-form', '{{ route('expenses.store') }}')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New Expense</button>
</div>
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
@include('partials.stat-card', ['icon' => 'fa-file-invoice-dollar', 'color' => 'indigo', 'label' => 'Total Expenses', 'value' => $items->total()])
@include('partials.stat-card', ['icon' => 'fa-coins', 'color' => 'indigo', 'label' => 'Total Amount', 'value' => $stat1])
@include('partials.stat-card', ['icon' => 'fa-calendar-days', 'color' => 'amber', 'label' => 'This Month', 'value' => $stat2])
</div>
@php($isMoney = true)
@include('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney])
<div class="filter-toolbar">
<form method="GET" class="filter-toolbar__filters">
    <div class="filter-search">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search expenses…"
               class="rounded-md border-slate-300 text-sm pl-8 pr-3 py-1.5 w-56">
    </div>
    <button type="submit" class="filter-btn" title="Search" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
    <span class="filter-toolbar__divider" aria-hidden="true"></span>
    <label class="text-slate-500"><i class="fa-solid fa-filter mr-1"></i> Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" {{ request('period','all')=='all'?'selected':'' }}>All Time</option>
        <option value="today" {{ request('period')=='today'?'selected':'' }}>Today</option>
        <option value="week" {{ request('period')=='week'?'selected':'' }}>This Week</option>
        <option value="month" {{ request('period')=='month'?'selected':'' }}>This Month</option>
        <option value="year" {{ request('period')=='year'?'selected':'' }}>This Year</option>
    </select>
    <select name="branch_id" onchange="this.form.submit()" aria-label="Branch" class="rounded-md border-slate-300 text-sm">
        <option value="">All Branches</option>
        @foreach($branch_options as $opt)
        <option value="{{ $opt->id }}" @selected((string) request('branch_id') === (string) $opt->id)>{{ $opt->name }}</option>
        @endforeach
    </select>
    <select name="payment_method" onchange="this.form.submit()" aria-label="Payment method" class="rounded-md border-slate-300 text-sm">
        <option value="">All Payment Methods</option>
        @foreach($paymentMethods as $method)
        <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ $method }}</option>
        @endforeach
    </select>
    @if(request('q') || request('branch_id') || request('payment_method') || (request('period') && request('period') !== 'all'))
    <a href="{{ route('expenses.index') }}" class="filter-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
    @endif
</form>
<div class="filter-toolbar__actions">
<details class="export-menu">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="{{ route('expenses.export.csv') }}" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="title" checked class="rounded border-slate-300"> Title</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="amount" checked class="rounded border-slate-300"> Amount</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="payment_method" checked class="rounded border-slate-300"> Payment Method</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="expense_date" checked class="rounded border-slate-300"> Expense Date</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="category" checked class="rounded border-slate-300"> Category</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="description" checked class="rounded border-slate-300"> Description</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="{{ request('period', 'all') }}">
    <input type="hidden" name="q" value="{{ request('q', '') }}">
    <input type="hidden" name="branch_id" value="{{ request('branch_id', '') }}">
    <input type="hidden" name="payment_method" value="{{ request('payment_method', '') }}">
    <div class="flex items-center gap-2">
      <button formaction="{{ route('expenses.export.csv') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="{{ route('expenses.export.pdf') }}" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
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
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Title</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Amount</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Payment Method</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Expense Date</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Category</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Branch</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->title }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ number_format((float) $row->amount, 0) }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->payment_method ?: '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->expense_date?->format('d M Y') }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->category }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->branch?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->createdBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->updatedBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->created_at?->format('d M Y H:i') ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->updated_at?->format('d M Y H:i') ?? '—' }}</td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("expenses-modal", "expenses-form", @json($row))' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("expenses-modal", "expenses-form", "{{ route('expenses.update', $row->id) }}", @json($row))' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="{{ route('expenses.destroy', $row->id) }}" class="inline" data-confirm="Delete this record?">
@csrf @method('DELETE')
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
@empty
<tr><td colspan="11" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-4">@include('partials.table-pagination', ['paginator' => $items])</div>

<div id="expenses-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="expenses-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-file-invoice-dollar text-indigo-500"></i> New Expense</h2>
      <button type="button" onclick="closeModal('expenses-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <div class="mb-4 flex gap-2 border-b pb-3" role="tablist"><button type="button" class="expense-tab rounded bg-indigo-600 px-3 py-2 text-sm text-white" data-panel="cash-expense-panel">Cash expense</button><button type="button" class="expense-tab rounded border px-3 py-2 text-sm" data-panel="stock-issue-panel">Stock for staff / guest</button></div>
    <form id="expenses-form" method="POST" action="{{ route('expenses.store') }}" class="space-y-4" data-expense-panel="cash-expense-panel">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
  <input type="text" name="title" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
  <input type="number" step='0.01' name="amount" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
  <select name="payment_method" class="w-full rounded-md border-slate-300 shadow-sm">
    <option value="">-- Select Payment Method --</option>
    @foreach($paymentMethods as $method)
    <option value="{{ $method }}">{{ $method }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Expense Date</label>
  <input type="date" name="expense_date" data-default-now class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
  <select name="category" class="w-full rounded-md border-slate-300 shadow-sm" required><option value="">-- Select Category --</option>@foreach($expenseCategories as $category)<option value="{{ $category->name }}">{{ $category->name }}</option>@endforeach</select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" @selected((string) $defaultBranchId === (string) $opt->id)>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('expenses-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
    <form id="stock-issue-panel" data-expense-panel="stock-issue-panel" class="hidden space-y-4" method="POST" action="{{ route('stock-issues.store') }}">
      @csrf
      <p class="text-sm text-slate-500">Record stock given away or consumed without creating a cash expense or sale.</p>
      <label class="block text-sm font-medium text-slate-700">Product and branch<select name="stock_selection" required class="mt-1 w-full rounded-md border-slate-300" onchange="document.getElementById('issue-product-id').value=this.selectedOptions[0].dataset.product;document.getElementById('issue-branch-id').value=this.selectedOptions[0].dataset.branch;document.getElementById('issue-available').textContent=this.selectedOptions[0].dataset.available || '—';"><option value="">-- Select stock --</option>@foreach($availableStock as $stock)<option value="{{ $stock->id }}" data-product="{{ $stock->product_id }}" data-branch="{{ $stock->branch_id }}" data-available="{{ $stock->quantity }}">{{ $stock->product?->name }} — {{ $stock->branch?->name }} ({{ $stock->quantity }} available)</option>@endforeach</select></label>
      <input id="issue-product-id" type="hidden" name="product_id"><input id="issue-branch-id" type="hidden" name="branch_id">
      <label class="block text-sm font-medium text-slate-700">Issue type<select name="type" required class="mt-1 w-full rounded-md border-slate-300"><option value="staff_consumption">Staff consumption</option><option value="gratis">Guest / gratis</option></select></label>
      <p class="text-sm text-slate-500">Available: <span id="issue-available">—</span></p>
      <label class="block text-sm font-medium text-slate-700">Quantity<input type="number" name="quantity" min="1" step="1" required class="mt-1 w-full rounded-md border-slate-300"></label>
      <label class="block text-sm font-medium text-slate-700">Staff / guest name and notes<textarea name="reason" rows="2" maxlength="1000" class="mt-1 w-full rounded-md border-slate-300"></textarea></label>
      <div class="pt-2"><button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white">Record stock issue</button></div>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('.expense-tab').forEach(tab => tab.addEventListener('click', () => {
  document.querySelectorAll('.expense-tab').forEach(button => { button.classList.remove('bg-indigo-600','text-white'); button.classList.add('border'); });
  tab.classList.add('bg-indigo-600','text-white'); tab.classList.remove('border');
  document.querySelectorAll('[data-expense-panel]').forEach(panel => panel.classList.toggle('hidden', panel.id !== tab.dataset.panel));
}));
</script>
@endsection
