@extends('shop.track-layout')
@section('title', 'Order '.$order->order_number)
@section('content')
@php
  $paymentLabel = ucwords(str_replace(['-', '_'], ' ', (string) $order->payment_method));
  $paymentStatus = (string) $order->payment_status;
  $paymentBadge = match ($paymentStatus) {
      'paid', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
      'failed', 'cancelled' => 'bg-red-50 text-red-700 border-red-200',
      default => 'bg-amber-50 text-amber-700 border-amber-200',
  };
@endphp
<div class="space-y-5">
  <div class="{{ $cancelled ? 'bg-red-600 text-white' : 'brand-band' }} rounded-2xl p-5 shadow-sm flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="text-xs font-bold uppercase opacity-75">Order reference</div>
      <h1 class="text-2xl font-extrabold break-all">{{ $order->order_number ?: $order->order_id }}</h1>
      <p class="text-sm opacity-80 mt-1">Placed {{ $order->created_at?->format('d M Y, H:i') }}@if($order->branch) · {{ $order->branch->name }}@endif</p>
    </div>
    <div class="sm:text-right">
      <div class="text-xs font-bold uppercase opacity-75">Current status</div>
      <div class="text-xl font-extrabold">{{ $order->stageLabel() }}</div>
      <div class="text-xs opacity-75">Updated {{ $order->updated_at?->diffForHumans() }}</div>
    </div>
  </div>

  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    @if ($cancelled)
      <div class="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
        <i class="fas fa-ban mt-0.5"></i>
        <div><div class="font-bold">This order was cancelled.</div><div class="text-sm">If you think this is a mistake, please contact us with your order reference.</div></div>
      </div>
    @else
      <ol class="space-y-5">
        @foreach ($steps as $step)
          <li class="relative flex items-center gap-3 step-{{ $step['state'] }}">
            @unless ($loop->last)<span class="step-line"></span>@endunless
            <span class="step-dot">
              @if ($step['state'] === 'done')<i class="fas fa-check"></i>
              @elseif ($step['state'] === 'current')<i class="fas fa-circle text-[8px]"></i>
              @else {{ $loop->iteration }} @endif
            </span>
            <span class="{{ $step['state'] === 'upcoming' ? 'text-slate-400' : 'font-bold text-slate-800' }}">
              {{ $step['label'] }}
              @if ($step['state'] === 'current')<span class="ml-1 text-xs font-semibold text-primary">— current</span>@endif
              @if ($step['at'])<span class="block text-xs font-normal text-slate-400">{{ $step['at']->format('d M Y, H:i') }}</span>@endif
            </span>
          </li>
        @endforeach
      </ol>
    @endif
  </section>

  <section class="grid gap-3 sm:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
      <div class="text-xs font-bold uppercase text-slate-400 mb-1">Payment</div>
      <div class="font-semibold">{{ $paymentLabel }}</div>
      <span class="mt-1 inline-block rounded-full border px-2 py-0.5 text-xs font-bold {{ $paymentBadge }}">{{ ucwords(str_replace('_', ' ', $paymentStatus ?: 'pending')) }}</span>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
      <div class="text-xs font-bold uppercase text-slate-400 mb-1">Delivery</div>
      <div class="font-semibold">{{ $order->delivery_zone ?: 'Delivery / pickup' }}</div>
      <div class="text-sm text-slate-500 break-words">{{ $order->delivery_location }}</div>
    </div>
  </section>

  <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <h2 class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold">Items</h2>
    @foreach ($order->order_items as $item)
      <div class="flex justify-between gap-4 border-b border-slate-100 px-4 py-3 text-sm">
        <span>{{ $item->product_name }} <span class="text-slate-400">× {{ $item->quantity }}</span></span>
        <span class="whitespace-nowrap">UGX {{ number_format((float) $item->subtotal, 0) }}</span>
      </div>
    @endforeach
    <div class="space-y-1 px-4 py-3 text-sm">
      <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>UGX {{ number_format((float) $order->subtotal, 0) }}</span></div>
      <div class="flex justify-between text-slate-500"><span>Delivery fee</span><span>UGX {{ number_format((float) $order->delivery_fee, 0) }}</span></div>
      <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-extrabold"><span>Total</span><span>UGX {{ number_format((float) $order->total_amount, 0) }}</span></div>
    </div>
  </section>

  @if ($order->branch && ($order->branch->contact || $order->branch->branch_email))
  <section class="bg-primary-soft rounded-xl p-4 text-sm flex flex-wrap items-center gap-x-5 gap-y-2">
    <span class="font-bold text-slate-800"><i class="fas fa-headset mr-1 text-primary"></i> Need help? {{ $order->branch->name }}</span>
    @if ($order->branch->contact)
      <a href="tel:{{ preg_replace('/[^\d+]/', '', $order->branch->contact) }}" class="font-semibold text-primary"><i class="fas fa-phone mr-1"></i>{{ $order->branch->contact }}</a>
    @endif
    @if ($order->branch->branch_email)
      <a href="mailto:{{ $order->branch->branch_email }}?subject={{ rawurlencode('Order '.$order->order_number) }}" class="font-semibold text-primary"><i class="fas fa-envelope mr-1"></i>{{ $order->branch->branch_email }}</a>
    @endif
  </section>
  @endif

  <div class="flex flex-wrap gap-3">
    <button type="button" onclick="location.reload()" class="btn-primary rounded-lg px-4 py-2.5 text-sm font-bold"><i class="fas fa-rotate mr-1"></i> Refresh status</button>
    <button type="button" id="copyLinkBtn" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700"><i class="fas fa-link mr-1"></i> Copy tracking link</button>
  </div>
  <p class="text-xs text-slate-400">Bookmark this page or keep the link — anyone with it can see this order's status.</p>
</div>

<script>
@unless ($cancelled || in_array($order->status, ['completed', 'delivered'], true))
// Keep an open tracking page current without the customer having to reload.
setInterval(() => { if (document.visibilityState === 'visible') location.reload(); }, 60000);
@endunless
document.getElementById('copyLinkBtn').addEventListener('click', async function () {
  try { await navigator.clipboard.writeText(location.href); this.innerHTML = '<i class="fas fa-check mr-1"></i> Link copied'; }
  catch (_) { window.prompt('Copy this tracking link:', location.href); }
});
</script>
@endsection
