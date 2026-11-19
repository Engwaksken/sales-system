@extends('shop.track-layout')
@section('title', 'Track your order')
@section('content')
<div class="max-w-md mx-auto">
  <div class="text-center mb-6">
    <div class="mx-auto mb-3 w-14 h-14 rounded-full grid place-items-center text-xl text-white btn-primary"><i class="fas fa-truck-fast"></i></div>
    <h1 class="text-2xl font-extrabold">Track your order</h1>
    <p class="text-sm text-slate-500 mt-1">Enter your order reference and the phone number you used at checkout.</p>
  </div>

  <div id="recentOrders" class="hidden mb-5 rounded-xl border border-slate-200 bg-white p-4">
    <div class="text-xs font-bold uppercase text-slate-400 mb-2">Your recent orders on this device</div>
    <ul id="recentOrdersList" class="divide-y divide-slate-100"></ul>
  </div>

  <form method="POST" action="{{ route('orders.track.lookup') }}" class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 shadow-sm">
    @csrf
    @if ($errors->any())
      <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        <i class="fas fa-circle-exclamation mr-1"></i> {{ $errors->first() }}
      </div>
    @endif
    <div>
      <label for="reference" class="block mb-1.5 text-sm font-bold text-slate-700">Order reference</label>
      <input id="reference" name="reference" class="track-input uppercase" required maxlength="50"
             placeholder="ORD-20261119-A1B2C3" value="{{ old('reference', $reference) }}">
    </div>
    <div>
      <label for="phone" class="block mb-1.5 text-sm font-bold text-slate-700">Phone number</label>
      <input id="phone" name="phone" type="tel" class="track-input" required maxlength="30"
             placeholder="07XXXXXXXX" value="{{ old('phone') }}">
    </div>
    <button type="submit" class="btn-primary w-full min-h-[46px] rounded-lg font-bold">
      <i class="fas fa-magnifying-glass mr-1"></i> Track order
    </button>
  </form>
</div>

<script>
(function () {
  let orders = [];
  try { orders = JSON.parse(localStorage.getItem('shopRecentOrders') || '[]'); } catch (_) {}
  if (!Array.isArray(orders) || !orders.length) return;
  const list = document.getElementById('recentOrdersList');
  orders.forEach(o => {
    if (!o || !o.ref || !o.url) return;
    const li = document.createElement('li');
    li.className = 'py-2 flex items-center justify-between gap-3';
    const left = document.createElement('div');
    left.innerHTML = '<div class="font-semibold text-sm"></div><div class="text-xs text-slate-400"></div>';
    left.children[0].textContent = o.ref;
    left.children[1].textContent = o.placed ? new Date(o.placed).toLocaleString() : '';
    const a = document.createElement('a');
    a.href = o.url;
    a.className = 'text-sm font-bold text-primary whitespace-nowrap';
    a.textContent = 'View status →';
    li.append(left, a);
    list.appendChild(li);
  });
  if (list.children.length) document.getElementById('recentOrders').classList.remove('hidden');
})();
</script>
@endsection
