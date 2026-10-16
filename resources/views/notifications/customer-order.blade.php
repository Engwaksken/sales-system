@extends('layouts.app')
@section('title', 'Order details')
@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <h1 class="text-xl font-semibold text-slate-800">Order {{ $order->order_number ?? $order->order_id ?? ('#'.$order->id) }}</h1>
    <section class="rounded-xl bg-white p-5 shadow-sm">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs uppercase text-slate-400">Status</dt><dd class="font-medium text-slate-800">{{ ucfirst(str_replace('_', ' ', $order->status ?? 'pending')) }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-400">Placed</dt><dd class="text-slate-800">{{ $order->created_at?->format('d M Y H:i') ?? '—' }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-400">Total</dt><dd class="text-slate-800">{{ $order->total_amount ?? $order->subtotal ?? '—' }}</dd></div>
            <div><dt class="text-xs uppercase text-slate-400">Payment status</dt><dd class="text-slate-800">{{ ucfirst(str_replace('_', ' ', $order->payment_status ?? 'pending')) }}</dd></div>
        </dl>
    </section>
    @if($order->order_items?->isNotEmpty())
        <section class="overflow-hidden rounded-xl bg-white shadow-sm"><h2 class="border-b px-5 py-3 font-semibold">Items</h2>
            @foreach($order->order_items as $item)
                <div class="flex justify-between gap-4 border-b px-5 py-3 last:border-0"><span>{{ $item->product_name ?? $item->name ?? ('Item #'.$item->id) }} <span class="text-slate-500">× {{ $item->quantity ?? 1 }}</span></span><span>{{ $item->total ?? $item->subtotal ?? '' }}</span></div>
            @endforeach
        </section>
    @endif
    <a href="{{ route('shop.index') }}" class="inline-block text-sm text-indigo-600">&larr; Back to shop</a>
</div>
@endsection
