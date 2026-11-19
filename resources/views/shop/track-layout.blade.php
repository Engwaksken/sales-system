@php($brand = \App\Support\Brand::get())
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<meta name="theme-color" content="{{ $brand['primary'] }}">
<title>@yield('title', 'Track your order') · {{ $brand['name'] }}</title>
@if ($brand['favicon'])<link rel="icon" href="{{ $brand['favicon'] }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ urlencode($brand['font']) }}:wght@400;600;700;800;900&display=swap">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--shop-primary:{{ $brand['primary'] }};--on-primary:{{ $brand['onPrimary'] }};--primary-soft:{{ $brand['primarySoft'] }}}
body{font-family:'{{ $brand['font'] }}',system-ui,-apple-system,'Segoe UI',sans-serif}
.btn-primary{background:var(--shop-primary);color:var(--on-primary)}
.btn-primary:hover{filter:brightness(.94)}
.text-primary{color:var(--shop-primary)}
.bg-primary-soft{background:var(--primary-soft)}
.brand-band{background:linear-gradient(135deg,var(--shop-primary),color-mix(in srgb,var(--shop-primary) 70%,#0f172a));color:var(--on-primary)}
.track-input{display:block;width:100%;min-height:45px;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:.9rem;outline:none}
.track-input:focus{border-color:var(--shop-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--shop-primary) 14%,transparent)}
.step-dot{width:34px;height:34px;border-radius:999px;display:grid;place-items:center;flex-shrink:0;font-size:.85rem}
.step-done .step-dot{background:var(--shop-primary);color:var(--on-primary)}
.step-current .step-dot{background:#fff;color:var(--shop-primary);border:3px solid var(--shop-primary)}
.step-upcoming .step-dot{background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0}
.step-line{position:absolute;left:16px;top:34px;bottom:-6px;width:2px;background:#e2e8f0}
.step-done .step-line{background:var(--shop-primary)}
</style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">
<header class="bg-white border-b border-slate-200">
  <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
    <a href="{{ route('shop.index') }}" class="flex items-center gap-2 min-w-0">
      @if ($brand['logo'])
        <img src="{{ $brand['logo'] }}" alt="" class="h-9 w-auto max-w-[120px] object-contain">
      @else
        <span class="btn-primary grid h-9 w-9 place-items-center rounded-lg font-black">{{ mb_strtoupper(mb_substr($brand['name'], 0, 1)) }}</span>
      @endif
      <span class="truncate font-extrabold text-lg">{{ $brand['name'] }}</span>
    </a>
    <a href="{{ route('shop.index') }}" class="shrink-0 text-sm text-slate-600 hover:text-slate-900"><i class="fas fa-bag-shopping mr-1"></i><span class="hidden sm:inline">Back to shop</span></a>
  </div>
</header>
<main class="flex-1 w-full max-w-3xl mx-auto px-4 py-8">
  @yield('content')
</main>
<footer class="border-t border-slate-200 bg-white">
  <div class="max-w-3xl mx-auto px-4 py-5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
    <span>© {{ date('Y') }} {{ $brand['name'] }}</span>
    <span class="flex gap-4">
      <a href="{{ route('orders.track') }}" class="hover:text-slate-800">Track an order</a>
      <a href="{{ route('shop.index') }}" class="hover:text-slate-800">Shop</a>
    </span>
  </div>
</footer>
</body>
</html>
