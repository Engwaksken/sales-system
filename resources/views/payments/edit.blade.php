@extends('layouts.app')
@section('title', 'Edit Payment')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit Payment</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('payments.update', $payment->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Transaction Type</label>
  <select name="transaction_type" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='sale' {{ ($payment->transaction_type == 'sale') ? 'selected' : '' }}>sale</option><option value='purchase' {{ ($payment->transaction_type == 'purchase') ? 'selected' : '' }}>purchase</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Invoice No</label>
  <input type="text" name="invoice_no" value="{{ $payment->invoice_no }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Receipt No</label>
  <input type="text" name="receipt_no" value="{{ $payment->receipt_no }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Id</label>
  <select name="customer_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Customer Id --</option>
    @foreach($customer_options as $opt)
    <option value="{{ $opt->id }}" {{ ($payment->customer_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Supplier Id</label>
  <select name="supplier_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Supplier Id --</option>
    @foreach($supplier_options as $opt)
    <option value="{{ $opt->id }}" {{ ($payment->supplier_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sale Id</label>
  <select name="sale_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Sale Id --</option>
    @foreach($sale_options as $opt)
    <option value="{{ $opt->id }}" {{ ($payment->sale_id == $opt->id) ? 'selected' : '' }}>{{ $opt->id }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
  <input type="number" step='0.01' name="amount" value="{{ $payment->amount }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Method</label>
  <input type="text" name="method" value="{{ $payment->method }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
  <select name="status" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='successful' {{ ($payment->status == 'successful') ? 'selected' : '' }}>successful</option><option value='failed' {{ ($payment->status == 'failed') ? 'selected' : '' }}>failed</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Received By</label>
  <select name="received_by" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Received By --</option>
    @foreach($received_by_options as $opt)
    <option value="{{ $opt->id }}" {{ ($payment->received_by == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('payments.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
