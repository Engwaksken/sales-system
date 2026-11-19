@extends('layouts.app')
@section('title', 'Order Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Order {{ $order->order_number ?: '#'.$order->id }}</h1>

@if(session('status'))
<div data-flash class="mb-4 max-w-2xl rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="mb-4 max-w-2xl rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">{{ $errors->first() }}</div>
@endif

<div class="bg-white shadow rounded-lg p-6 max-w-2xl mb-4">
  <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
    <h2 class="font-semibold text-slate-800">Order stage</h2>
    <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">{{ $order->stageLabel() }}</span>
  </div>
  <form method="POST" action="{{ route('orders.stage', $order) }}" class="grid gap-3 sm:grid-cols-[1fr_2fr_auto] sm:items-end">
    @csrf
    @method('PATCH')
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Move to</label>
      <select name="status" class="w-full rounded-md border-slate-300 shadow-sm">
        @foreach(\App\Models\Order::STAGES as $key => $label)
          <option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Internal note <span class="text-slate-400">(optional)</span></label>
      <input type="text" name="note" maxlength="1000" class="w-full rounded-md border-slate-300 shadow-sm">
    </div>
    <button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">Update stage</button>
  </form>
  <p class="mt-2 text-xs text-slate-500">
    @if($order->email) The customer is emailed at {{ $order->email }} when the stage changes. @else This customer has no email address, so share the tracking link below with them. @endif
  </p>

  @if($order->order_number || $order->order_id)
  <div class="mt-4 border-t border-slate-100 pt-4">
    <label class="block text-sm font-medium text-slate-700 mb-1">Customer tracking link</label>
    <div class="flex gap-2">
      <input id="trackingLink" type="text" readonly value="{{ $order->trackingUrl() }}" class="w-full rounded-md border-slate-300 bg-slate-50 text-xs shadow-sm">
      <button type="button" class="rounded-md border border-slate-300 px-3 text-sm whitespace-nowrap"
              onclick="navigator.clipboard.writeText(document.getElementById('trackingLink').value).then(() => this.textContent = 'Copied')">Copy</button>
    </div>
  </div>
  @endif
</div>

@if($order->order_logs->isNotEmpty())
<div class="bg-white shadow rounded-lg p-6 max-w-2xl mb-4">
  <h2 class="font-semibold text-slate-800 mb-3">History</h2>
  <ul class="space-y-2 text-sm">
    @foreach($order->order_logs as $log)
      <li class="flex gap-3"><span class="w-32 shrink-0 text-slate-400">{{ $log->created_at?->format('d M Y H:i') }}</span><span class="text-slate-700">{{ $log->description }}</span></li>
    @endforeach
  </ul>
</div>
@endif

<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Sale Id</span><span class='text-slate-800'>{{ $order->sale?->id ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Order Id</span><span class='text-slate-800'>{{ $order->order_id }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Customer Id</span><span class='text-slate-800'>{{ $order->customer?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Customer Name</span><span class='text-slate-800'>{{ $order->customer_name }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Phone</span><span class='text-slate-800'>{{ $order->phone }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Email</span><span class='text-slate-800'>{{ $order->email }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Delivery Location</span><span class='text-slate-800'>{{ $order->delivery_location }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Delivery Zone Id</span><span class='text-slate-800'>{{ $order->delivery_zone?->zone_name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Branch Id</span><span class='text-slate-800'>{{ $order->branch?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Payment Method</span><span class='text-slate-800'>{{ $order->payment_method }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Id</span><span class='text-slate-800'>{{ $order->gateway?->display_name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Code</span><span class='text-slate-800'>{{ $order->gateway_code }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Reference</span><span class='text-slate-800'>{{ $order->gateway_reference }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Status</span><span class='text-slate-800'>{{ $order->gateway_status }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Message</span><span class='text-slate-800'>{{ $order->gateway_message }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Mm Phone</span><span class='text-slate-800'>{{ $order->mm_phone }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Mm Network</span><span class='text-slate-800'>{{ $order->mm_network }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Payment Status</span><span class='text-slate-800'>{{ $order->payment_status }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Total Amount</span><span class='text-slate-800'>{{ $order->total_amount }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Discount</span><span class='text-slate-800'>{{ $order->discount }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Amount Paid</span><span class='text-slate-800'>{{ $order->amount_paid }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Balance Amount</span><span class='text-slate-800'>{{ $order->balance_amount }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Status</span><span class='text-slate-800'>{{ $order->status }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Orders Count</span><span class='text-slate-800'>{{ $order->orders_count }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Notes</span><span class='text-slate-800'>{{ $order->notes }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Guest Order</span><span class='text-slate-800'>{{ $order->is_guest_order ? 'Yes' : 'No' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By User Id</span><span class='text-slate-800'>{{ $order->created_by_user?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Paid At</span><span class='text-slate-800'>{{ $order->paid_at }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Delivery Zone</span><span class='text-slate-800'>{{ $order->delivery_zone }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Delivery Fee</span><span class='text-slate-800'>{{ $order->delivery_fee }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Subtotal</span><span class='text-slate-800'>{{ $order->subtotal }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Order Number</span><span class='text-slate-800'>{{ $order->order_number }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Address</span><span class='text-slate-800'>{{ $order->address }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $order->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $order->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $order->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $order->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('orders.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
