@extends('layouts.app')
@section('title', 'OrderPaymentReceipt Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">OrderPaymentReceipt #{{ $order_payment_receipt->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Order Id</span><span class='text-slate-800'>{{ $order_payment_receipt->order?->id ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Order Number</span><span class='text-slate-800'>{{ $order_payment_receipt->order_number }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Receipt Number</span><span class='text-slate-800'>{{ $order_payment_receipt->receipt_number }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Customer Id</span><span class='text-slate-800'>{{ $order_payment_receipt->customer?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Customer Name</span><span class='text-slate-800'>{{ $order_payment_receipt->customer_name }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Phone</span><span class='text-slate-800'>{{ $order_payment_receipt->phone }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Email</span><span class='text-slate-800'>{{ $order_payment_receipt->email }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Payment Method</span><span class='text-slate-800'>{{ $order_payment_receipt->payment_method }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Code</span><span class='text-slate-800'>{{ $order_payment_receipt->gateway_code }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Reference</span><span class='text-slate-800'>{{ $order_payment_receipt->gateway_reference }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Status</span><span class='text-slate-800'>{{ $order_payment_receipt->gateway_status }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Amount Paid</span><span class='text-slate-800'>{{ $order_payment_receipt->amount_paid }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Currency</span><span class='text-slate-800'>{{ $order_payment_receipt->currency }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Notes</span><span class='text-slate-800'>{{ $order_payment_receipt->notes }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $order_payment_receipt->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $order_payment_receipt->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $order_payment_receipt->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $order_payment_receipt->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('order_payment_receipts.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
