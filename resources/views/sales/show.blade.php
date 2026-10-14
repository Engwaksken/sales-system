@extends('layouts.app')
@section('title', 'Sale Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Sale #{{ $sale->id }}</h1>
@if($sale->orders->isNotEmpty())
@php($sourceOrder = $sale->orders->first())
<div class="mb-5 max-w-2xl rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
  <div>
    <div class="text-xs uppercase tracking-wide text-emerald-600 font-bold"><i class="fas fa-globe mr-1"></i> Online order pushed to POS</div>
    <div class="font-bold text-emerald-900 mt-1">{{ $sourceOrder->order_number ?: $sourceOrder->order_id }} · {{ $sourceOrder->customer_name }}</div>
  </div>
  <a href="{{ route('orders.index', ['q'=>$sourceOrder->order_number ?: $sourceOrder->order_id]) }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900"><i class="fas fa-arrow-up-right-from-square"></i> View Order</a>
</div>
@endif

<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Transaction Type</span><span class='text-slate-800'>{{ $sale->transaction_type }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Customer Id</span><span class='text-slate-800'>{{ $sale->customer?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Supplier Id</span><span class='text-slate-800'>{{ $sale->supplier?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>User Id</span><span class='text-slate-800'>{{ $sale->user?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Invoice No</span><span class='text-slate-800'>{{ $sale->invoice_no }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Total Amount</span><span class='text-slate-800'>{{ $sale->total_amount }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Discount</span><span class='text-slate-800'>{{ $sale->discount }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Vat</span><span class='text-slate-800'>{{ $sale->vat }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Payment Method</span><span class='text-slate-800'>{{ $sale->payment_method }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Sale Date</span><span class='text-slate-800'>{{ $sale->sale_date }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Payment Status</span><span class='text-slate-800'>{{ $sale->payment_status }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Partial Amount</span><span class='text-slate-800'>{{ $sale->partial_amount }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Balance Amount</span><span class='text-slate-800'>{{ $sale->balance_amount }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Branch Id</span><span class='text-slate-800'>{{ $sale->branch?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $sale->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $sale->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $sale->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $sale->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>

<div class="mt-6 bg-white shadow rounded-lg p-6 max-w-2xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
        <div>
          <h2 class="font-medium text-slate-800">Payments, Invoice &amp; Receipts</h2>
          <p class="text-xs text-slate-400 mt-1">Outstanding: UGX {{ number_format((float)$sale->balance_amount, 0) }}</p>
        </div>
        <div class="flex items-center gap-2">
        <a href="{{ route('sales.invoice-preview',$sale->id) }}" class="text-sm border border-emerald-200 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-md hover:bg-emerald-100 font-semibold"><i class="fas fa-eye"></i> Preview Invoice</a>
        @if((float)$sale->balance_amount > 0)
        <button type="button" onclick="openModal('record-payment-modal')" class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded-md hover:bg-indigo-700">
            <i class="fa-solid fa-money-bill-wave"></i> Pay
        </button>
        @endif
        </div>
    </div>
    @forelse ($sale->payments as $p)
    <div class="flex items-center justify-between py-2 border-b border-slate-100 text-sm">
        <div>
            <span class="font-medium">{{ $p->receipt_no }}</span>
            <span class="text-slate-400">&middot; {{ $p->method }} &middot; UGX {{ number_format($p->amount, 2) }}</span>
        </div>
        <a href="{{ route('payments.receipt-preview', $p->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
            <i class="fa-solid fa-eye"></i> Preview Receipt
        </a>
    </div>
    @empty
    <p class="text-sm text-slate-400">No payments recorded yet — this sale was fully paid at POS.</p>
    @endforelse
</div>

<div id="record-payment-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-sm w-full p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="font-semibold text-lg">Process Payment & Generate Receipt</h2>
      <button type="button" onclick="closeModal('record-payment-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form method="POST" action="{{ route('sales.record-payment', $sale->id) }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
        <input type="number" step="0.01" min="0.01" name="amount" max="{{ (float)$sale->balance_amount }}" value="{{ (float)$sale->balance_amount }}" required class="w-full rounded-md border-slate-300 shadow-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Method</label>
        <select name="method" required class="w-full rounded-md border-slate-300 shadow-sm">
          <option value="Cash">Cash</option>
          <option value="Mobile Money">Mobile Money</option>
          <option value="Card">Card</option>
          <option value="Bank Transfer">Bank Transfer</option>
        </select>
      </div>
      <div class="rounded-lg bg-blue-50 border border-blue-200 text-blue-700 px-3 py-2 text-xs leading-5">
        <i class="fas fa-envelope mr-1"></i>
        Saving payment generates a branded receipt and automatically emails it to the customer when an email address is available.
      </div>
      <button class="w-full bg-emerald-600 text-white py-2.5 rounded-md text-sm font-bold hover:bg-emerald-700">
        <i class="fas fa-check-circle mr-1"></i> Pay & Generate Receipt
      </button>
    </form>
  </div>
</div>

<form method="POST" action="{{ route('sales.efris-submit', $sale->id) }}" class="mt-4">
    @csrf
    <button class="bg-emerald-600 text-white px-4 py-2 rounded-md text-sm hover:bg-emerald-700"
            onclick="return confirm('Submit this sale to EFRIS as a T103 invoice?')">
        Submit to EFRIS
    </button>
</form>
@if ($errors->has('efris'))
    <div class="mt-3 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm max-w-2xl">{{ $errors->first('efris') }}</div>
@endif
<a href="{{ route('sales.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
