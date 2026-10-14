@extends('layouts.app')
@section('title', 'New Sale')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New Sale</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('sales.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Transaction Type</label>
  <select name="transaction_type" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='sale' {{ (old('transaction_type') == 'sale') ? 'selected' : '' }}>sale</option><option value='purchase' {{ (old('transaction_type') == 'purchase') ? 'selected' : '' }}>purchase</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Id</label>
  <select name="customer_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Customer Id --</option>
    @foreach($customer_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('customer_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Supplier Id</label>
  <select name="supplier_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Supplier Id --</option>
    @foreach($supplier_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('supplier_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">User Id</label>
  <select name="user_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select User Id --</option>
    @foreach($user_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('user_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Invoice No</label>
  <input type="text" name="invoice_no" value="{{ old('invoice_no') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Total Amount</label>
  <input type="number" step='0.01' name="total_amount" value="{{ old('total_amount') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Discount</label>
  <input type="number" step='0.01' name="discount" value="{{ old('discount') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Vat</label>
  <input type="number" step='0.01' name="vat" value="{{ old('vat') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
  <input type="text" name="payment_method" value="{{ old('payment_method') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sale Date</label>
  <input type="date" name="sale_date" value="{{ old('sale_date') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Status</label>
  <input type="text" name="payment_status" value="{{ old('payment_status') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Partial Amount</label>
  <input type="number" step='0.01' name="partial_amount" value="{{ old('partial_amount') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Balance Amount</label>
  <input type="number" step='0.01' name="balance_amount" value="{{ old('balance_amount') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('branch_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('sales.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
