@extends('layouts.app')
@section('title', 'Receipt Preview')
@section('content')
@php($customer = $payment->customer ?? $payment->sale?->customer)
<div class="max-w-5xl mx-auto">
    @if(session('status'))
        <div data-flash class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 flex items-start gap-3 text-sm">
            <i class="fas fa-circle-check mt-0.5"></i>
            <div>
                <strong class="block">Payment successful</strong>
                <span>{{ session('status') }} The receipt is also sent to the customer's email when available.</span>
            </div>
        </div>
    @endif
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
        <div>
            <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Document Preview</div>
            <h1 class="text-xl font-extrabold text-slate-800 mt-1">
                <i class="fas fa-receipt text-slate-400 mr-1"></i>
                Receipt {{ $payment->receipt_no }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Review the payment receipt before downloading or printing it.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($payment->sale_id)
                <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-600 text-sm font-bold">
                    <i class="fas fa-arrow-left"></i> Back to Sales
                </a>
            @else
                <a href="{{ route('payments.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-600 text-sm font-bold">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            @endif
            <a href="{{ route('payments.receipt-print', ['payment'=>$payment->id,'paper'=>'80mm']) }}" target="_blank"
               class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-bold">
                <i class="fas fa-print"></i> Print 80mm / XP-80C
            </a>
            <a href="{{ route('payments.receipt-print', ['payment'=>$payment->id,'paper'=>'58mm']) }}" target="_blank"
               class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-bold">
                <i class="fas fa-print"></i> 58mm
            </a>
            <a href="{{ route('payments.receipt-print', ['payment'=>$payment->id,'paper'=>'a5']) }}" target="_blank"
               class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-bold">
                <i class="fas fa-print"></i> A5
            </a>
            <a href="{{ route('payments.receipt-print', ['payment'=>$payment->id,'paper'=>'a4']) }}" target="_blank"
               class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-bold">
                <i class="fas fa-print"></i> A4
            </a>
            <a href="{{ route('payments.receipt-download', $payment->id) }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                <i class="fas fa-download"></i> Download After Preview
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_290px] gap-5">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <iframe
                id="receiptFrame"
                src="{{ route('payments.receipt-pdf', $payment->id) }}"
                class="w-full border-0 bg-slate-100"
                style="height:720px"
                title="Receipt {{ $payment->receipt_no }} preview"></iframe>
        </div>

        <aside class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 h-fit">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                @if(!empty($siteSettings['system_logo']))
                    <img src="{{ asset($siteSettings['system_logo']) }}" class="h-10 w-10 object-contain rounded-lg border border-slate-100 p-1" alt="Logo">
                @endif
                <div>
                    <strong class="text-slate-800">{{ $branding['site_name'] }}</strong>
                    <div class="text-xs text-slate-400">Payment receipt</div>
                </div>
            </div>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-xs uppercase text-slate-400">Customer</dt><dd class="font-bold text-slate-700">{{ $customer?->name ?? 'Walk-in Customer' }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-400">Amount</dt><dd class="font-extrabold text-emerald-700">{{ $branding['currency'] }} {{ number_format((float)$payment->amount, 0) }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-400">Method</dt><dd class="font-bold text-slate-700">{{ $payment->method }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-400">Invoice</dt><dd class="font-bold text-slate-700">{{ $payment->invoice_no ?: '—' }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-400">Status</dt><dd class="font-bold text-slate-700">{{ str($payment->status)->title() }}</dd></div>
            </dl>
        </aside>
    </div>
</div>
@endsection
