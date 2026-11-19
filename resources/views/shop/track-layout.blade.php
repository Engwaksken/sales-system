<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>@yield('title', 'Track your order') - {{ $siteSettings['site_name'] ?? config('app.name') }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--shop-primary:{{ $siteSettings['theme_primary'] ?? '#0f766e' }}}
.btn-primary{background:var(--shop-primary);color:#fff}
.btn-primary:hover{filter:brightness(.94)}
.text-primary{color:var(--shop-primary)}
.track-input{display:block;width:100%;min-height:45px;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:.9rem;outline:none}
.track-input:focus{border-color:var(--shop-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--shop-primary) 14%,transparent)}
.step-dot{width:34px;height:34px;border-radius:999px;display:grid;place-items:center;flex-shrink:0;font-size:.85rem}
.step-done .step-dot{background:var(--shop-primary);color:#fff}
.step-current .step-dot{background:#fff;color:var(--shop-primary);border:3px solid var(--shop-primary)}
.step-upcoming .step-dot{background:#f1f5f9;color:#94a3b8;border:1px solid #e2e8f0}
.step-line{position:absolute;left:16px;top:34px;bottom:-6px;width:2px;background:#e2e8f0}
.step-done .step-line{background:var(--shop-primary)}
</style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
<header class="bg-white border-b border-slate-200">
  <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
    <a href="{{ route('shop.index') }}" class="font-bold text-lg">{{ $siteSettings['site_name'] ?? config('app.name') }}</a>
    <a href="{{ route('shop.index') }}" class="text-sm text-slate-600 hover:text-slate-900"><i class="fas fa-bag-shopping mr-1"></i> Back to shop</a>
  </div>
</header>
<main class="max-w-3xl mx-auto px-4 py-8">
  @yield('content')
</main>
</body>
</html>
