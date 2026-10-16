@extends('layouts.app')
@section('title', 'Orders')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-cart-shopping text-slate-400"></i> Orders</h1>
    <button type="button" onclick="openCreateModal('orders-modal', 'orders-form', '{{ route('orders.store') }}')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New Order</button>
</div>
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
@include('partials.stat-card', ['icon' => 'fa-cart-shopping', 'color' => 'indigo', 'label' => 'Total Orders', 'value' => $items->total()])
@include('partials.stat-card', ['icon' => 'fa-coins', 'color' => 'indigo', 'label' => 'Total Total Amount', 'value' => $stat1])
@include('partials.stat-card', ['icon' => 'fa-hourglass-half', 'color' => 'amber', 'label' => 'Pending', 'value' => $stat2])
</div>
@php($isMoney = true)
@include('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney])
<form method="GET" class="flex items-center gap-2 text-sm mb-4">
    <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search orders…"
               class="rounded-md border-slate-300 text-sm pl-8 pr-3 py-1.5 w-56">
    </div>
    <button class="text-sm text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-right"></i></button>
    <label class="text-slate-500 ml-2"><i class="fa-solid fa-filter"></i> Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" {{ request('period','all')=='all'?'selected':'' }}>All Time</option>
        <option value="today" {{ request('period')=='today'?'selected':'' }}>Today</option>
        <option value="week" {{ request('period')=='week'?'selected':'' }}>This Week</option>
        <option value="month" {{ request('period')=='month'?'selected':'' }}>This Month</option>
        <option value="year" {{ request('period')=='year'?'selected':'' }}>This Year</option>
    </select>
    @if(request('q') || (request('period') && request('period') !== 'all'))
    <a href="{{ route('orders.index') }}" class="text-slate-400 hover:text-slate-600 text-xs">Clear <i class="fa-solid fa-xmark"></i></a>
    @endif
</form>
<details class="mb-4 bg-white rounded-lg shadow">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="{{ route('orders.export.csv') }}" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="sale_id" checked class="rounded border-slate-300"> Sale</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="order_id" checked class="rounded border-slate-300"> Order Id</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="customer_id" checked class="rounded border-slate-300"> Customer</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="customer_name" checked class="rounded border-slate-300"> Customer Name</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="phone" checked class="rounded border-slate-300"> Phone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="email" checked class="rounded border-slate-300"> Email</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_location" checked class="rounded border-slate-300"> Delivery Location</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_zone_id" checked class="rounded border-slate-300"> Delivery Zone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="payment_method" checked class="rounded border-slate-300"> Payment Method</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_id" checked class="rounded border-slate-300"> Gateway</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_code" checked class="rounded border-slate-300"> Gateway Code</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_reference" checked class="rounded border-slate-300"> Gateway Reference</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_status" checked class="rounded border-slate-300"> Gateway Status</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_message" checked class="rounded border-slate-300"> Gateway Message</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="mm_phone" checked class="rounded border-slate-300"> Mm Phone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="mm_network" checked class="rounded border-slate-300"> Mm Network</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="payment_status" checked class="rounded border-slate-300"> Payment Status</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="total_amount" checked class="rounded border-slate-300"> Total Amount</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="discount" checked class="rounded border-slate-300"> Discount</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="amount_paid" checked class="rounded border-slate-300"> Amount Paid</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="balance_amount" checked class="rounded border-slate-300"> Balance Amount</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="status" checked class="rounded border-slate-300"> Status</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="orders_count" checked class="rounded border-slate-300"> Orders Count</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="notes" checked class="rounded border-slate-300"> Notes</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="is_guest_order" checked class="rounded border-slate-300"> Is Guest Order</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by_user_id" checked class="rounded border-slate-300"> Created By User</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="paid_at" checked class="rounded border-slate-300"> Paid At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_zone" checked class="rounded border-slate-300"> Delivery Zone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_fee" checked class="rounded border-slate-300"> Delivery Fee</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="subtotal" checked class="rounded border-slate-300"> Subtotal</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="order_number" checked class="rounded border-slate-300"> Order Number</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="address" checked class="rounded border-slate-300"> Address</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="{{ request('period', 'all') }}">
    <input type="hidden" name="q" value="{{ request('q', '') }}">
    <div class="flex items-center gap-2">
      <button formaction="{{ route('orders.export.csv') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="{{ route('orders.export.pdf') }}" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
    </div>
  </form>
</details>
@if(session('status'))
<div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Order Number</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Customer Name</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Total Amount</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Status</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Payment Status</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->order_number }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->customer_name }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->total_amount }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->status }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->payment_status }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->createdBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->updatedBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->created_at?->format('d M Y H:i') ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->updated_at?->format('d M Y H:i') ?? '—' }}</td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("orders-modal", "orders-form", @json($row))' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("orders-modal", "orders-form", "{{ route('orders.update', $row->id) }}", @json($row))' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="{{ route('orders.destroy', $row->id) }}" class="inline" onsubmit="return confirm('Delete this record?')">
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

<div id="orders-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="orders-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-cart-shopping text-indigo-500"></i> New Order</h2>
      <button type="button" onclick="closeModal('orders-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form id="orders-form" method="POST" action="{{ route('orders.store') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <div class="mb-4 flex gap-2 border-b border-slate-200" role="tablist" aria-label="Order form sections">
        <button type="button" role="tab" aria-selected="true" data-tabgroup="orders-form" data-tab="customer" onclick="switchTab('orders-form','customer')" class="border-b-2 border-indigo-600 px-3 py-2 text-sm font-medium text-indigo-600">Customer &amp; delivery</button>
        <button type="button" role="tab" aria-selected="false" data-tabgroup="orders-form" data-tab="payment" onclick="switchTab('orders-form','payment')" class="border-b-2 border-transparent px-3 py-2 text-sm font-medium text-slate-500">Payment</button>
        <button type="button" role="tab" aria-selected="false" data-tabgroup="orders-form" data-tab="details" onclick="switchTab('orders-form','details')" class="border-b-2 border-transparent px-3 py-2 text-sm font-medium text-slate-500">Order details</button>
      </div>
      <div data-tabpanel="orders-form" data-tab="customer" class="space-y-4">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sale Id</label>
  <select name="sale_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Sale Id --</option>
    @foreach($sale_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->id }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Id</label>
  <input type="text" name="order_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Id</label>
  <select name="customer_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Customer Id --</option>
    @foreach($customer_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Name</label>
  <input type="text" name="customer_name" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
  <input type="text" name="phone" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
  <input type="text" name="email" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Location</label>
  <textarea name="delivery_location" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" required></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Zone Id</label>
  <select name="delivery_zone_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Delivery Zone Id --</option>
    @foreach($delivery_zone_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->zone_name }}</option>
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
      </div>
<div data-tabpanel="orders-form" data-tab="payment" class="hidden space-y-4">
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
  <input type="text" name="payment_method" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Id</label>
  <select name="gateway_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Gateway Id --</option>
    @foreach($gateway_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->display_name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Code</label>
  <input type="text" name="gateway_code" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Reference</label>
  <input type="text" name="gateway_reference" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Status</label>
  <input type="text" name="gateway_status" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Message</label>
  <textarea name="gateway_message" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Mm Phone</label>
  <input type="text" name="mm_phone" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Mm Network</label>
  <input type="text" name="mm_network" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
      </div>
<div data-tabpanel="orders-form" data-tab="details" class="hidden space-y-4">
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Status</label>
  <input type="text" name="payment_status" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Total Amount</label>
  <input type="number" step='0.01' name="total_amount" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Discount</label>
  <input type="number" step='0.01' name="discount" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount Paid</label>
  <input type="number" step='0.01' name="amount_paid" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Balance Amount</label>
  <input type="number" step='0.01' name="balance_amount" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
  <input type="text" name="status" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Orders Count</label>
  <input type="number" name="orders_count" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
  <textarea name="notes" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_guest_order" value="0">
  <input type="checkbox" name="is_guest_order" value="1" class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Guest Order</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By User Id</label>
  <select name="created_by_user_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By User Id --</option>
    @foreach($created_by_user_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Paid At</label>
  <input type="datetime-local" name="paid_at" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Zone</label>
  <input type="text" name="delivery_zone" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Fee</label>
  <input type="number" step='0.01' name="delivery_fee" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Subtotal</label>
  <input type="number" step='0.01' name="subtotal" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Number</label>
  <input type="text" name="order_number" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
  <textarea name="address" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
      </div>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('orders-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection
