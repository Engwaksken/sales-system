@extends('layouts.app')
@section('title', 'Edit Order')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit Order</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('orders.update', $order->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sale Id</label>
  <select name="sale_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Sale Id --</option>
    @foreach($sale_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order->sale_id == $opt->id) ? 'selected' : '' }}>{{ $opt->id }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Id</label>
  <input type="text" name="order_id" value="{{ $order->order_id }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Id</label>
  <select name="customer_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Customer Id --</option>
    @foreach($customer_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order->customer_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Name</label>
  <input type="text" name="customer_name" value="{{ $order->customer_name }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
  <input type="text" name="phone" value="{{ $order->phone }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
  <input type="text" name="email" value="{{ $order->email }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Location</label>
  <textarea name="delivery_location" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" required>{{ $order->delivery_location }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Zone Id</label>
  <select name="delivery_zone_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Delivery Zone Id --</option>
    @foreach($delivery_zone_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order->delivery_zone_id == $opt->id) ? 'selected' : '' }}>{{ $opt->zone_name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order->branch_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
  <input type="text" name="payment_method" value="{{ $order->payment_method }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Id</label>
  <select name="gateway_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Gateway Id --</option>
    @foreach($gateway_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order->gateway_id == $opt->id) ? 'selected' : '' }}>{{ $opt->display_name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Code</label>
  <input type="text" name="gateway_code" value="{{ $order->gateway_code }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Reference</label>
  <input type="text" name="gateway_reference" value="{{ $order->gateway_reference }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Status</label>
  <input type="text" name="gateway_status" value="{{ $order->gateway_status }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Message</label>
  <textarea name="gateway_message" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ $order->gateway_message }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Mm Phone</label>
  <input type="text" name="mm_phone" value="{{ $order->mm_phone }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Mm Network</label>
  <input type="text" name="mm_network" value="{{ $order->mm_network }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Status</label>
  <input type="text" name="payment_status" value="{{ $order->payment_status }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Total Amount</label>
  <input type="number" step='0.01' name="total_amount" value="{{ $order->total_amount }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Discount</label>
  <input type="number" step='0.01' name="discount" value="{{ $order->discount }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount Paid</label>
  <input type="number" step='0.01' name="amount_paid" value="{{ $order->amount_paid }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Balance Amount</label>
  <input type="number" step='0.01' name="balance_amount" value="{{ $order->balance_amount }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
  <input type="text" name="status" value="{{ $order->status }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Orders Count</label>
  <input type="number" name="orders_count" value="{{ $order->orders_count }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
  <textarea name="notes" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ $order->notes }}</textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_guest_order" value="0">
  <input type="checkbox" name="is_guest_order" value="1" {{ $order->is_guest_order ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Guest Order</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By User Id</label>
  <select name="created_by_user_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By User Id --</option>
    @foreach($created_by_user_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order->created_by_user_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Paid At</label>
  <input type="datetime-local" name="paid_at" value="{{ $order->paid_at }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Zone</label>
  <input type="text" name="delivery_zone" value="{{ $order->delivery_zone }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Fee</label>
  <input type="number" step='0.01' name="delivery_fee" value="{{ $order->delivery_fee }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Subtotal</label>
  <input type="number" step='0.01' name="subtotal" value="{{ $order->subtotal }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Number</label>
  <input type="text" name="order_number" value="{{ $order->order_number }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
  <textarea name="address" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ $order->address }}</textarea>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('orders.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
