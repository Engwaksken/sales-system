<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
@php
  $brand = \App\Support\Brand::get();
  $visibleCategories = $categories->filter(fn ($c) => $c->cnt > 0);
  $sortOptions = ['name_asc' => 'Name (A-Z)', 'newest' => 'Newest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'];
  $filterQuery = fn (array $changes = []) => array_filter(array_merge([
      'branch_id' => $branch->id, 'cat' => $catFilter ?: null, 'q' => $search !== '' ? $search : null,
      'sort' => $sort !== 'name_asc' ? $sort : null,
  ], $changes), fn ($v) => $v !== null && $v !== '');
@endphp
<title>Shop | {{ $brand['name'] }}{{ $branch->name !== $brand['name'] ? ' | '.$branch->name : '' }}</title>
<meta name="description" content="Shop online at {{ $brand['name'] }}, {{ $branch->name }}{{ $branch->location ? ', '.$branch->location : '' }}.">
<meta name="theme-color" content="{{ $brand['primary'] }}">
<meta property="og:title" content="{{ $brand['name'] }} | Shop online">
@if ($brand['logo'])<meta property="og:image" content="{{ $brand['logo'] }}">@endif
@if ($brand['favicon'])<link rel="icon" href="{{ $brand['favicon'] }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ urlencode($brand['font']) }}:wght@400;600;700;800;900&display=swap">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
:root{--shop-primary:{{ $brand['primary'] }};--shop-secondary:{{ $brand['secondary'] }};--on-primary:{{ $brand['onPrimary'] }};--primary-soft:{{ $brand['primarySoft'] }}}

/* One scale for every screen: Tailwind sizes are in rem, so raising the root size
   on large desktops and TVs grows text, spacing and the layout width together. */
html{font-size:16px;-webkit-text-size-adjust:100%}
@media (min-width:1800px){html{font-size:17px}}
@media (min-width:2300px){html{font-size:19px}}
@media (min-width:3000px){html{font-size:23px}}
@media (min-width:3600px){html{font-size:26px}}
body{font-family:'{{ $brand['font'] }}',system-ui,-apple-system,'Segoe UI',sans-serif;overflow-x:hidden}
.shop-container{width:100%;max-width:100rem;margin-inline:auto;padding-inline:clamp(.75rem,2.5vw,2.5rem)}
/* 27" monitors and TVs: use more of the width instead of wide empty margins. */
@media (min-width:2300px){.shop-container{max-width:122rem}}
/* The smallest phones: the logo alone keeps the cart on the first header row. */
@media (max-width:359px){.brand-name{display:none}}

.bg-brand{background:var(--shop-primary);color:var(--on-primary)}
.bg-brand:hover{filter:brightness(.93)}
.text-brand{color:var(--shop-primary)}
.bg-brand-soft{background:var(--primary-soft)}
.brand-hero{background:linear-gradient(135deg,var(--shop-primary),color-mix(in srgb,var(--shop-primary) 65%,#0f172a));color:var(--on-primary)}
.shop-search:focus,.shop-select:focus{outline:none;border-color:var(--shop-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--shop-primary) 15%,transparent)}

/* Keyboard and TV-remote users must always see where focus is. */
:focus-visible{outline:3px solid var(--shop-primary);outline-offset:2px}

/* Product grid: two columns on phones, then as many ~13rem cards as fit. */
.product-grid{display:grid;gap:clamp(.6rem,1.2vw,1.25rem);grid-template-columns:repeat(2,minmax(0,1fr))}
@media (min-width:640px){.product-grid{grid-template-columns:repeat(auto-fill,minmax(13rem,1fr))}}
@media (max-width:330px){.product-grid{grid-template-columns:minmax(0,1fr)}}
.product-card{display:flex;flex-direction:column;min-width:0;background:#fff;border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;transition:box-shadow .15s,transform .15s}
.product-card:hover{box-shadow:0 10px 28px rgba(15,23,42,.08);transform:translateY(-2px)}
.product-media{aspect-ratio:1/1;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden}
.product-media img{width:100%;height:100%;object-fit:contain;padding:.6rem}
.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

/* Category chips (phones and tablets). */
.chip-row{display:flex;gap:.5rem;overflow-x:auto;scrollbar-width:none;padding-bottom:.25rem;scroll-snap-type:x proximity}
.chip-row::-webkit-scrollbar{display:none}
.chip{flex:0 0 auto;scroll-snap-align:start;white-space:nowrap;border:1px solid #e2e8f0;background:#fff;color:#475569;border-radius:999px;padding:.4rem .85rem;font-size:.82rem;font-weight:600}
.chip.active{background:var(--shop-primary);border-color:var(--shop-primary);color:var(--on-primary)}

/* New products slider. */
.new-products-track{display:flex;gap:clamp(.6rem,1.2vw,1rem);overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;scroll-snap-type:x mandatory;padding:.15rem .1rem .75rem}
.new-products-track::-webkit-scrollbar{display:none}
.new-product-slide{flex:0 0 clamp(10.5rem,42vw,15rem);scroll-snap-align:start;min-width:0}
.new-product-image{aspect-ratio:4/3;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden}
.new-product-image img{width:100%;height:100%;object-fit:contain;padding:.6rem}
.new-slider-btn{width:2.4rem;height:2.4rem;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#475569;box-shadow:0 5px 16px rgba(15,23,42,.08);display:grid;place-items:center;transition:.15s}
.new-slider-btn:hover{color:var(--shop-primary);border-color:var(--shop-primary);transform:translateY(-1px)}

@media (prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation:none!important;transition:none!important}}
</style>
</head>
<body class="bg-slate-50 text-slate-900">

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
  <div class="shop-container flex flex-wrap items-center gap-x-3 gap-y-2 py-2.5 sm:flex-nowrap sm:gap-5">
    <a href="{{ route('shop.index', ['branch_id' => $branch->id]) }}" class="flex min-w-0 items-center gap-2 shrink-0">
      @if ($brand['logo'])
        <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" class="h-9 w-auto max-w-[7rem] object-contain">
      @else
        <span class="bg-brand grid h-9 w-9 shrink-0 place-items-center rounded-lg font-black">{{ mb_strtoupper(mb_substr($brand['name'], 0, 1)) }}</span>
      @endif
      <span class="brand-name min-w-0 leading-tight {{ $brand['logo'] ? 'hidden md:block' : '' }}">
        <span class="block max-w-[11rem] truncate font-extrabold lg:max-w-[16rem]">{{ $brand['name'] }}</span>
        <span class="hidden truncate text-xs text-slate-500 sm:block">{{ $branch->name }}</span>
      </span>
    </a>

    <form method="GET" role="search" class="order-last basis-full sm:order-none sm:basis-auto sm:flex-1 sm:max-w-2xl min-w-0">
      <label for="shopSearch" class="sr-only">Search products</label>
      <div class="relative">
        <i class="fas fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
        <input id="shopSearch" type="search" name="q" value="{{ $search }}" placeholder="Search products..." enterkeyhint="search"
               class="shop-search w-full rounded-full border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm">
      </div>
      <input type="hidden" name="branch_id" value="{{ $branch->id }}">
      @if ($catFilter)<input type="hidden" name="cat" value="{{ $catFilter }}">@endif
      @if ($sort !== 'name_asc')<input type="hidden" name="sort" value="{{ $sort }}">@endif
    </form>

    <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-3">
      <a href="{{ route('orders.track') }}" class="inline-flex h-10 items-center gap-1.5 rounded-full px-3 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 whitespace-nowrap" aria-label="Track order">
        <i class="fas fa-truck-fast"></i><span class="hidden lg:inline">Track order</span>
      </a>
      <button type="button" onclick="openCheckout()" class="bg-brand relative inline-flex h-10 items-center gap-1.5 rounded-full px-4 text-sm font-semibold whitespace-nowrap" aria-label="Open cart">
        <i class="fas fa-cart-shopping"></i><span class="hidden sm:inline">Cart</span>
        <span class="rounded-full bg-white/25 px-1.5 text-xs" id="cartCount">0</span>
      </button>
    </div>
  </div>
</header>

<section class="shop-container mt-4">
  <div class="brand-hero flex flex-wrap items-center justify-between gap-4 rounded-2xl px-5 py-5 shadow-sm sm:px-8 sm:py-7 2xl:py-9">
    <div class="min-w-0">
      <p class="text-xs font-bold uppercase tracking-wider opacity-75">Welcome to</p>
      <h1 class="text-[clamp(1.4rem,3.2vw,2.4rem)] font-black leading-tight">{{ $brand['name'] }}</h1>
      <p class="mt-1 text-sm opacity-85">
        <i class="fas fa-location-dot mr-1"></i>Shopping at {{ $branch->name }}{{ $branch->location ? ' | '.$branch->location : '' }}
      </p>
    </div>
    <div class="flex flex-wrap gap-2 text-sm">
      @if ($branch->contact)
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $branch->contact) }}" class="rounded-full bg-white/15 px-3 py-1.5 font-semibold hover:bg-white/25"><i class="fas fa-phone mr-1"></i>{{ $branch->contact }}</a>
      @endif
      <a href="{{ route('orders.track') }}" class="rounded-full bg-white/15 px-3 py-1.5 font-semibold hover:bg-white/25"><i class="fas fa-truck-fast mr-1"></i>Track an order</a>
    </div>
  </div>
</section>

@if ($slides->isNotEmpty())
<div class="shop-container mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 sm:gap-4">
  @foreach ($slides->take(3) as $slide)
  <div class="min-w-0 rounded-xl bg-white p-4 shadow-sm border border-slate-200 {{ $loop->last && $loop->count === 3 ? 'sm:col-span-2 lg:col-span-1' : '' }}">
    <div class="text-xs font-semibold uppercase text-brand">{{ $slide->badge_text }}</div>
    <div class="font-semibold text-slate-800">{{ $slide->title }}</div>
    <div class="text-sm text-slate-500 line-clamp-2">{{ $slide->subtitle }}</div>
  </div>
  @endforeach
</div>
@endif

@if(($newProducts ?? collect())->isNotEmpty())
<section class="shop-container mt-7" aria-labelledby="newProductsTitle">
  <div class="mb-3 flex items-end justify-between gap-4">
    <div class="min-w-0">
      <div class="text-xs font-bold uppercase tracking-wider text-brand">
        <i class="fas fa-wand-magic-sparkles mr-1"></i> Just added
      </div>
      <h2 id="newProductsTitle" class="mt-1 text-xl font-extrabold text-slate-900 md:text-2xl">New Products</h2>
      <p class="mt-1 text-sm text-slate-500">Discover the latest products now available at {{ $branch->name }}.</p>
    </div>
    <div class="hidden items-center gap-2 sm:flex">
      <button type="button" class="new-slider-btn" onclick="slideNewProducts(-1)" aria-label="Previous new products">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button type="button" class="new-slider-btn" onclick="slideNewProducts(1)" aria-label="Next new products">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>

  <div id="newProductsTrack" class="new-products-track">
    @foreach($newProducts as $newProduct)
      @php
        $newImage = $newProduct->image_url
            ? route('shop.product-image', ['product' => $newProduct->id])
            : null;
      @endphp
      <article class="new-product-slide overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
        <div class="new-product-image relative">
          @if($newImage)
            <img src="{{ $newImage }}" alt="{{ $newProduct->name }}" loading="lazy"
                 onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')">
            <div class="hidden text-4xl text-slate-300"><i class="fas fa-box-open"></i></div>
          @else
            <div class="text-4xl text-slate-300"><i class="fas fa-box-open"></i></div>
          @endif
          <span class="bg-brand absolute left-3 top-3 rounded-full px-2 py-1 text-[0.625rem] font-bold uppercase tracking-wide">New</span>
        </div>

        <div class="p-3 sm:p-4">
          @if($newProduct->category_name)
            <div class="truncate text-[0.7rem] font-semibold uppercase tracking-wide text-slate-400">{{ $newProduct->category_name }}</div>
          @endif
          <h3 class="mt-1 truncate font-bold text-slate-800" title="{{ $newProduct->name }}">{{ $newProduct->name }}</h3>
          <div class="mt-3 flex items-center justify-between gap-2">
            <div class="min-w-0">
              <div class="text-xs text-slate-400">Price</div>
              <div class="truncate font-extrabold text-slate-900">UGX {{ number_format((float)$newProduct->price, 0) }}</div>
            </div>
            <button type="button"
                    onclick='addFromCard({{ (int)$newProduct->id }}, @json($newProduct->name), {{ (float)$newProduct->price }}, {{ (int)$newProduct->stock }})'
                    class="bg-brand grid h-10 w-10 shrink-0 place-items-center rounded-full shadow-sm"
                    title="Add {{ $newProduct->name }} to cart"
                    aria-label="Add {{ $newProduct->name }} to cart">
              <i class="fas fa-cart-plus"></i>
            </button>
          </div>
        </div>
      </article>
    @endforeach
  </div>
</section>
@endif

<div class="shop-container py-6 lg:flex lg:items-start lg:gap-8">
  {{-- Desktop sidebar --}}
  <aside class="hidden w-60 shrink-0 lg:block 2xl:w-64" aria-label="Filters">
    <div class="sticky top-24 space-y-5 rounded-2xl border border-slate-200 bg-white p-4">
      <div>
        <div class="mb-2 text-sm font-bold text-slate-800">Categories</div>
        <nav class="max-h-[60vh] space-y-0.5 overflow-y-auto pr-1">
          <a href="?{{ http_build_query($filterQuery(['cat' => null])) }}"
             class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-sm {{ !$catFilter ? 'bg-brand-soft text-brand font-bold' : 'text-slate-600 hover:bg-slate-50' }}">All products</a>
          @foreach ($visibleCategories as $cat)
            <a href="?{{ http_build_query($filterQuery(['cat' => $cat->id])) }}"
               class="flex items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-sm {{ $catFilter == $cat->id ? 'bg-brand-soft text-brand font-bold' : 'text-slate-600 hover:bg-slate-50' }}">
              <span class="truncate">{{ $cat->category_name }}</span><span class="text-xs text-slate-400">{{ $cat->cnt }}</span>
            </a>
          @endforeach
        </nav>
      </div>
      @if ($branches->count() > 1)
      <div>
        <label for="branchSelectDesktop" class="mb-2 block text-sm font-bold text-slate-800">Branch</label>
        <select id="branchSelectDesktop" onchange="location.href='?branch_id='+this.value" class="shop-select w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm">
          @foreach ($branches as $b)
            <option value="{{ $b->id }}" {{ $b->id === $branch->id ? 'selected' : '' }}>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      @endif
    </div>
  </aside>

  <main class="min-w-0 flex-1">
    {{-- Phone and tablet filters --}}
    <div class="mb-4 space-y-3 lg:hidden">
      @if ($visibleCategories->isNotEmpty())
      <nav class="chip-row" aria-label="Categories">
        <a href="?{{ http_build_query($filterQuery(['cat' => null])) }}" class="chip {{ !$catFilter ? 'active' : '' }}">All</a>
        @foreach ($visibleCategories as $cat)
          <a href="?{{ http_build_query($filterQuery(['cat' => $cat->id])) }}" class="chip {{ $catFilter == $cat->id ? 'active' : '' }}">{{ $cat->category_name }} <span class="opacity-60">{{ $cat->cnt }}</span></a>
        @endforeach
      </nav>
      @endif
      @if ($branches->count() > 1)
      <div class="flex items-center gap-2">
        <label for="branchSelectMobile" class="shrink-0 text-sm font-semibold text-slate-600"><i class="fas fa-store mr-1 text-brand"></i>Branch</label>
        <select id="branchSelectMobile" onchange="location.href='?branch_id='+this.value" class="shop-select min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm">
          @foreach ($branches as $b)
            <option value="{{ $b->id }}" {{ $b->id === $branch->id ? 'selected' : '' }}>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      @endif
    </div>

    {{-- Results toolbar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-slate-500">
        <strong class="text-slate-800">{{ $products->count() }}</strong> {{ \Illuminate\Support\Str::plural('product', $products->count()) }}
        @if ($search !== '') for "<strong class="text-slate-800">{{ $search }}</strong>" <a href="?{{ http_build_query($filterQuery(['q' => null])) }}" class="ml-1 text-brand font-semibold">Clear</a>@endif
      </p>
      <form method="GET" class="flex items-center gap-2">
        @foreach ($filterQuery(['sort' => null]) as $key => $value)
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <label for="shopSort" class="text-sm text-slate-500">Sort</label>
        <select id="shopSort" name="sort" onchange="this.form.submit()" class="shop-select rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm">
          @foreach ($sortOptions as $value => $label)
            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
          @endforeach
        </select>
        <noscript><button class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">Apply</button></noscript>
      </form>
    </div>

    <div class="product-grid">
      @forelse ($products as $p)
      <article id="pc-{{ $p->id }}" class="product-card">
        <div class="product-media">
          @if ($p->image_url)
            <img src="{{ route('shop.product-image', ['product' => $p->id]) }}" alt="{{ $p->name }}" loading="lazy"
                 onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">
            <div class="hidden text-4xl text-slate-300"><i class="fas fa-box-open"></i></div>
          @else
            <div class="text-4xl text-slate-300"><i class="fas fa-box-open"></i></div>
          @endif
        </div>
        <div class="flex flex-1 flex-col p-3 sm:p-4">
          @if ($p->category_name)<div class="truncate text-[0.7rem] font-semibold uppercase tracking-wide text-slate-400">{{ $p->category_name }}</div>@endif
          <h3 class="line-clamp-2 text-sm font-semibold text-slate-800 sm:text-[0.95rem]" title="{{ $p->name }}">{{ $p->name }}</h3>
          <div class="mt-1.5 font-extrabold text-brand">UGX {{ number_format($p->price, 0) }}</div>
          <div class="mb-3 text-xs text-slate-400">{{ $p->stock }} in stock</div>
          <button type="button"
                  onclick='addFromCard({{ (int) $p->id }}, @json($p->name), {{ (float) $p->price }}, {{ (int) $p->stock }})'
                  class="bg-brand mt-auto inline-flex min-h-[2.5rem] items-center justify-center gap-1.5 rounded-lg px-2 text-sm font-semibold">
            <i class="fas fa-cart-plus"></i> <span>Add to cart</span>
          </button>
        </div>
      </article>
      @empty
      <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-500">
        <i class="fas fa-box-open mb-2 text-3xl text-slate-300"></i>
        <p>No products found.</p>
        @if ($search !== '' || $catFilter)
          <a href="?branch_id={{ $branch->id }}" class="mt-2 inline-block font-semibold text-brand">Show all products</a>
        @endif
      </div>
      @endforelse
    </div>
  </main>
</div>

<footer class="mt-6 border-t border-slate-200 bg-white">
  <div class="shop-container grid gap-6 py-8 text-sm sm:grid-cols-2 lg:grid-cols-3">
    <div>
      <div class="flex items-center gap-2">
        @if ($brand['logo'])
          <img src="{{ $brand['logo'] }}" alt="" class="h-8 w-auto max-w-[6rem] object-contain">
        @endif
        <span class="font-extrabold">{{ $brand['name'] }}</span>
      </div>
      <p class="mt-2 text-slate-500">Order online and pay on delivery or by mobile money.</p>
    </div>
    <div>
      <div class="mb-2 font-bold text-slate-800">{{ $branch->name }}</div>
      <ul class="space-y-1 text-slate-500">
        @if ($branch->location)<li><i class="fas fa-location-dot w-4 text-brand"></i> {{ $branch->location }}</li>@endif
        @if ($branch->contact)<li><a href="tel:{{ preg_replace('/[^\d+]/', '', $branch->contact) }}" class="hover:text-slate-800"><i class="fas fa-phone w-4 text-brand"></i> {{ $branch->contact }}</a></li>@endif
        @if ($branch->branch_email)<li><a href="mailto:{{ $branch->branch_email }}" class="break-all hover:text-slate-800"><i class="fas fa-envelope w-4 text-brand"></i> {{ $branch->branch_email }}</a></li>@endif
      </ul>
    </div>
    <div>
      <div class="mb-2 font-bold text-slate-800">Your orders</div>
      <ul class="space-y-1 text-slate-500">
        <li><a href="{{ route('orders.track') }}" class="hover:text-slate-800"><i class="fas fa-truck-fast w-4 text-brand"></i> Track an order</a></li>
        <li><button type="button" onclick="openCheckout()" class="hover:text-slate-800"><i class="fas fa-cart-shopping w-4 text-brand"></i> View cart</button></li>
      </ul>
    </div>
  </div>
  <div class="border-t border-slate-100 py-4 pb-24 text-center text-xs text-slate-400 sm:pb-4">© {{ date('Y') }} {{ $brand['name'] }}. All rights reserved.</div>
</footer>


<!-- Custom Order Success Popup -->
<style>
.order-success-overlay{
  position:fixed;inset:0;z-index:70;display:none;align-items:center;justify-content:center;
  padding:20px;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)
}
.order-success-overlay.show{display:flex}
.order-success-card{
  width:min(29rem,100%);background:#fff;border-radius:20px;overflow:hidden;
  border:1px solid #e2e8f0;box-shadow:0 30px 80px rgba(15,23,42,.30);
  animation:orderSuccessIn .18s ease-out
}
@keyframes orderSuccessIn{
  from{opacity:0;transform:translateY(8px) scale(.985)}
  to{opacity:1;transform:translateY(0) scale(1)}
}
.order-success-top{
  padding:26px 24px 18px;text-align:center;
  background:linear-gradient(180deg,#ecfdf5 0%,#fff 100%)
}
.order-success-icon{
  width:70px;height:70px;margin:0 auto 14px;border-radius:50%;
  display:grid;place-items:center;background:#059669;color:#fff;
  font-size:1.8rem;box-shadow:0 10px 28px rgba(5,150,105,.25)
}
.order-success-top h3{margin:0;color:#0f172a;font-size:1.3rem;font-weight:900}
.order-success-top p{margin:7px 0 0;color:#64748b;font-size:.88rem;line-height:1.5}
.order-success-body{padding:0 24px 22px}
.order-ref-box{
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  padding:13px 14px;border:1px dashed #a7f3d0;border-radius:12px;background:#f0fdf4
}
.order-ref-box span{color:#64748b;font-size:.75rem}
.order-ref-box strong{color:#065f46;font-size:.9rem;word-break:break-all}
.order-success-message{
  margin-top:14px;padding:12px 14px;border-radius:11px;background:#f8fafc;
  color:#475569;font-size:.84rem;line-height:1.55
}
.order-success-actions{display:flex;gap:10px;margin-top:18px}
.order-success-actions button{
  min-height:44px;border-radius:10px;font-weight:800;cursor:pointer;flex:1
}
.order-success-secondary{border:1px solid #cbd5e1;background:#fff;color:#475569}
.order-success-primary{border:0;background:var(--shop-primary,#0f766e);color:var(--on-primary,#fff)}
.order-success-track{display:flex;align-items:center;justify-content:center;min-height:46px;margin-top:16px;border-radius:10px;background:var(--shop-primary,#0f766e);color:var(--on-primary,#fff);font-weight:900;text-decoration:none}
.order-success-track:hover{filter:brightness(.94)}
@media(max-width:520px){
  .order-success-overlay{align-items:flex-end;padding:0}
  .order-success-card{border-radius:20px 20px 0 0}
  .order-success-actions{flex-direction:column}
}
</style>

<div id="orderSuccessPopup" class="order-success-overlay" role="dialog" aria-modal="true" aria-labelledby="orderSuccessTitle">
  <div class="order-success-card">
    <div class="order-success-top">
      <div class="order-success-icon"><i class="fas fa-check"></i></div>
      <h3 id="orderSuccessTitle">Order placed successfully</h3>
      <p>Your order has been received and is now being processed.</p>
    </div>
    <div class="order-success-body">
      <div class="order-ref-box">
        <span>Order Reference</span>
        <strong id="orderSuccessRef">-</strong>
      </div>

      <div id="orderSuccessDetails" class="mt-3 grid grid-cols-2 gap-2 text-sm">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <span class="block text-xs text-slate-400">Order Total</span>
          <strong id="orderSuccessTotal" class="text-slate-800">-</strong>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <span class="block text-xs text-slate-400">Payment</span>
          <strong id="orderSuccessPayment" class="text-slate-800">-</strong>
        </div>
      </div>

      <div id="orderSuccessMessage" class="order-success-message"></div>

      <a id="orderSuccessTrack" href="{{ route('orders.track') }}" class="order-success-track">
        <i class="fas fa-truck-fast mr-1"></i> Track my order
      </a>

      <div class="order-success-actions">
        <button type="button" class="order-success-secondary" onclick="closeOrderSuccessPopup()">
          <i class="fas fa-xmark mr-1"></i> Close
        </button>
        <button type="button" class="order-success-secondary" onclick="continueShoppingAfterOrder()">
          <i class="fas fa-bag-shopping mr-1"></i> Continue Shopping
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Checkout modal -->
<style>
.checkout-modal-card{width:min(48rem,100%);max-height:min(92vh,54rem);display:flex;flex-direction:column;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 28px 80px rgba(15,23,42,.25)}
.checkout-modal-head{padding:18px 20px;border-bottom:1px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:16px;position:sticky;top:0;z-index:2}
.checkout-modal-head h2{margin:0;font-size:1.15rem;font-weight:800;color:#0f172a}
.checkout-modal-head p{margin:2px 0 0;color:#64748b;font-size:.78rem}
.checkout-close{width:36px;height:36px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;display:grid;place-items:center;cursor:pointer}
.checkout-close:hover{background:#f8fafc;color:#0f172a}
.checkout-scroll{overflow-y:auto;padding:20px}
.checkout-summary{border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;background:#fff;margin-bottom:20px}
.checkout-summary-title{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.checkout-summary-title strong{font-size:.88rem;color:#334155}
.cart-order-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:12px;align-items:center;padding:11px 14px;border-bottom:1px solid #f1f5f9}
.cart-qty{display:inline-flex;align-items:center;border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;background:#fff}
.cart-qty button{width:34px;height:34px;display:grid;place-items:center;color:#334155;background:#fff;border:0;cursor:pointer}
.cart-qty button:hover:not(:disabled){background:#f1f5f9}
.cart-qty button:disabled{color:#cbd5e1;cursor:not-allowed}
.cart-qty input{width:46px;height:34px;border:0!important;border-left:1px solid #e2e8f0!important;border-right:1px solid #e2e8f0!important;text-align:center;font-weight:800;color:#0f172a;padding:0!important;box-shadow:none!important;-moz-appearance:textfield}
.cart-qty input::-webkit-outer-spin-button,.cart-qty input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.cart-order-end{display:flex;align-items:center;gap:6px}
.cart-remove{width:34px;height:34px;border-radius:10px;border:0;background:transparent;color:#94a3b8;display:grid;place-items:center;cursor:pointer}
.cart-remove:hover{background:#fef2f2;color:#dc2626}
.cart-limit{font-size:.7rem;color:#b45309;margin-top:2px}
.cart-undo{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 14px;background:#fffbeb;border-bottom:1px solid #fde68a;color:#92400e;font-size:.8rem}
.cart-undo[hidden]{display:none}
.cart-undo button{border:0;background:transparent;color:var(--shop-primary,#0f766e);font-weight:800;cursor:pointer}
.cart-clear{border:0;background:transparent;color:#dc2626;font-size:.75rem;font-weight:700;cursor:pointer}
.cart-clear:hover{text-decoration:underline}
.cart-order-row:last-child{border-bottom:0}
.cart-order-name{font-weight:700;color:#1e293b}
.cart-order-meta{font-size:.73rem;color:#94a3b8;margin-top:2px}
.cart-order-price{text-align:right;font-weight:800;color:#0f172a;white-space:nowrap;min-width:92px}
.checkout-totals{padding:12px 14px;background:#fcfcfd}
.checkout-total-line{display:flex;justify-content:space-between;gap:18px;padding:4px 0;color:#475569}
.checkout-total-line.total{margin-top:5px;padding-top:10px;border-top:1px solid #e2e8f0;color:#0f172a;font-weight:900;font-size:1.05rem}
.checkout-section{margin-top:20px}
.checkout-section-title{display:flex;align-items:center;gap:9px;margin-bottom:12px;color:#0f172a;font-size:.92rem;font-weight:800}
.checkout-section-title span{width:32px;height:32px;border-radius:9px;background:color-mix(in srgb,var(--shop-primary,#0f766e) 10%,white);color:var(--shop-primary,#0f766e);display:grid;place-items:center}
.checkout-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.checkout-field{min-width:0}
.checkout-field.full{grid-column:1/-1}
.checkout-field label{display:block;margin-bottom:6px;color:#334155;font-size:.78rem;font-weight:800}
.checkout-field label .required{color:#dc2626}
.checkout-control{position:relative}
.checkout-control>i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;z-index:1}
.checkout-control.textarea>i{top:15px;transform:none}
.checkout-control input,.checkout-control select,.checkout-control textarea{display:block;width:100%;min-height:45px;border:1px solid #cbd5e1!important;border-radius:10px!important;background:#fff!important;color:#0f172a!important;padding:10px 12px 10px 39px!important;font-size:.88rem!important;line-height:1.35;outline:none;box-shadow:none!important;transition:border-color .15s,box-shadow .15s}
.checkout-control select{appearance:auto}
.checkout-control textarea{min-height:82px;resize:vertical}
.checkout-control input:focus,.checkout-control select:focus,.checkout-control textarea:focus{border-color:var(--shop-primary,#0f766e)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--shop-primary,#0f766e) 12%,transparent)!important}
.checkout-field small{display:block;margin-top:5px;color:#94a3b8;font-size:.7rem}
.checkout-error{display:flex;gap:9px;align-items:flex-start;margin-top:16px;padding:11px 12px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#b91c1c;font-size:.8rem;line-height:1.45}
.checkout-actions{display:flex;gap:10px;justify-content:flex-end;padding-top:18px;margin-top:18px;border-top:1px solid #e2e8f0}
.checkout-cancel{min-height:44px;padding:10px 17px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#475569;font-weight:800;cursor:pointer}
.checkout-place{min-height:44px;padding:10px 20px;border:0;border-radius:10px;background:var(--shop-primary,#0f766e);color:var(--on-primary,#fff);font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:150px}
.checkout-place:hover{filter:brightness(.94)}
.checkout-place:disabled{opacity:.65;cursor:not-allowed}
.mm-panel{grid-column:1/-1;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:14px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff}
.mm-panel.hidden{display:none}
@media(max-width:640px){.cart-order-row{grid-template-columns:minmax(0,1fr) auto;row-gap:8px}.cart-order-end{grid-column:1/-1;justify-content:space-between}.cart-order-price{text-align:left;min-width:0}#checkoutModal{padding:0!important;align-items:flex-end!important}.checkout-modal-card{max-height:94vh;border-radius:18px 18px 0 0}.checkout-modal-head{padding:15px 16px}.checkout-scroll{padding:16px}.checkout-grid,.mm-panel{grid-template-columns:1fr}.checkout-field.full,.mm-panel{grid-column:auto}.checkout-actions{position:sticky;bottom:-16px;margin-left:-16px;margin-right:-16px;padding:12px 16px 16px;background:#fff}.checkout-cancel{display:none}.checkout-place{width:100%}}
</style>

<div id="checkoutModal" class="fixed inset-0 bg-slate-950/50 hidden items-center justify-center z-50 p-4" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle">
  <div class="checkout-modal-card">
    <div class="checkout-modal-head">
      <div class="flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl text-white grid place-items-center" style="background:var(--shop-primary,#0f766e)">
          <i class="fas fa-bag-shopping"></i>
        </span>
        <div>
          <h2 id="checkoutTitle">Your Order</h2>
          <p>Review your cart and provide your checkout details.</p>
        </div>
      </div>
      <button type="button" onclick="closeCheckout()" class="checkout-close" aria-label="Close checkout">
        <i class="fas fa-xmark"></i>
      </button>
    </div>

    <div class="checkout-scroll">
      <div class="checkout-summary">
        <div class="checkout-summary-title">
          <strong><i class="fas fa-cart-shopping mr-1"></i> Order summary</strong>
          <span class="flex items-center gap-3">
            <span id="checkoutItemCount" class="text-xs text-slate-500"></span>
            <button type="button" id="cartClearBtn" class="cart-clear" onclick="clearCart()">Clear cart</button>
          </span>
        </div>
        <div id="cartUndo" class="cart-undo" hidden>
          <span id="cartUndoText"></span>
          <button type="button" onclick="undoRemove()">Undo</button>
        </div>
        <div id="cartItems"></div>
        <div id="cartTotals" class="checkout-totals"></div>
      </div>

      <form id="coForm" novalidate>
        <input type="hidden" name="branch_id" value="{{ $branch->id }}">

        <div class="checkout-section">
          <div class="checkout-section-title"><span><i class="fas fa-user"></i></span> Customer Details</div>
          <div class="checkout-grid">
            <div class="checkout-field full">
              <label for="coCustomerName">Full name <span class="required">*</span></label>
              <div class="checkout-control">
                <i class="fas fa-user"></i>
                <input id="coCustomerName" type="text" name="customer_name" placeholder="Enter your full name" autocomplete="name" maxlength="150" required>
              </div>
            </div>

            <div class="checkout-field">
              <label for="coPhone">Phone number <span class="required">*</span></label>
              <div class="checkout-control">
                <i class="fas fa-phone"></i>
                <input id="coPhone" type="tel" name="phone" placeholder="e.g. 0704118739" autocomplete="tel" maxlength="30" required>
              </div>
              <small>Ugandan formats such as 070..., +25670... are accepted.</small>
            </div>

            <div class="checkout-field">
              <label for="coEmail">Email address</label>
              <div class="checkout-control">
                <i class="fas fa-envelope"></i>
                <input id="coEmail" type="email" name="email" placeholder="name@example.com" autocomplete="email" maxlength="150">
              </div>
              <small>Optional. Used for your order confirmation.</small>
            </div>

            <div class="checkout-field full">
              <label for="coDeliveryLocation">Delivery address / pickup instructions <span class="required">*</span></label>
              <div class="checkout-control textarea">
                <i class="fas fa-location-dot"></i>
                <textarea id="coDeliveryLocation" name="delivery_location" placeholder="Enter area, street, landmark or pickup instructions" maxlength="1000" required></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="checkout-section">
          <div class="checkout-section-title"><span><i class="fas fa-truck"></i></span> Delivery & Payment</div>
          <div class="checkout-grid">
            <div class="checkout-field">
              <label for="zSel">Delivery zone</label>
              <div class="checkout-control">
                <i class="fas fa-map-location-dot"></i>
                <select name="delivery_zone_id" id="zSel">
                  <option value="">Pickup / no delivery zone</option>
                  @foreach ($deliveryZones as $z)
                    <option value="{{ $z->id }}" data-fee="{{ $z->fee }}">{{ $z->zone_name }} - UGX {{ number_format($z->fee, 0) }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="checkout-field">
              <label for="payMethod">Payment method <span class="required">*</span></label>
              <div class="checkout-control">
                <i class="fas fa-wallet"></i>
                <select name="payment_method" id="payMethod" required>
                  <option value="">Select payment method</option>
                  <option value="cash-on-delivery">Cash on Delivery</option>
                  @if ($mobileGateways->isNotEmpty())
                    <option value="mobile-money">Mobile Money</option>
                  @endif
                </select>
              </div>
            </div>

            <div id="mmFields" class="mm-panel hidden">
              <div class="checkout-field">
                <label for="gatewayCode">Payment gateway <span class="required">*</span></label>
                <div class="checkout-control">
                  <i class="fas fa-building-columns"></i>
                  <select id="gatewayCode" name="gateway_code">
                    <option value="">Select gateway</option>
                    @foreach ($mobileGateways as $g)
                      <option value="{{ $g->gateway_code }}">{{ $g->display_name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="checkout-field">
                <label for="mmNetwork">Network <span class="required">*</span></label>
                <div class="checkout-control">
                  <i class="fas fa-signal"></i>
                  <select id="mmNetwork" name="mm_network">
                    <option value="">Select network</option>
                    <option value="MTN">MTN</option>
                    <option value="AIRTEL">Airtel</option>
                  </select>
                </div>
              </div>

              <div class="checkout-field full">
                <label for="mmPhone">Mobile money number <span class="required">*</span></label>
                <div class="checkout-control">
                  <i class="fas fa-mobile-screen-button"></i>
                  <input type="tel" name="mm_phone" id="mmPhone" placeholder="e.g. 0704118739" maxlength="30">
                </div>
              </div>
            </div>

            <div class="checkout-field full">
              <label for="coNotes">Order notes</label>
              <div class="checkout-control textarea">
                <i class="fas fa-note-sticky"></i>
                <textarea id="coNotes" name="notes" placeholder="Optional notes about your order" maxlength="2000"></textarea>
              </div>
            </div>
          </div>
        </div>

        <div id="alertBox" class="checkout-error hidden" role="alert">
          <i class="fas fa-circle-exclamation mt-0.5"></i>
          <span id="alertText"></span>
        </div>

        <div class="checkout-actions">
          <button type="button" onclick="closeCheckout()" class="checkout-cancel">
            Cancel
          </button>
          <button type="submit" id="placeBtn" class="checkout-place">
            <i class="fas fa-circle-check"></i>
            <span>Place Order</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function slideNewProducts(direction) {
  const track = document.getElementById('newProductsTrack');
  if (!track) return;
  const card = track.querySelector('.new-product-slide');
  const step = card ? card.getBoundingClientRect().width : 240;
  // Move by roughly a screenful of cards, whatever the screen size.
  const distance = Math.max(step, Math.floor(track.clientWidth / step) * step);
  track.scrollBy({left: direction * distance, behavior: 'smooth'});
}

const BID = {{ $branch->id }};
let cart = JSON.parse(localStorage.getItem('shop_cart_'+BID) || '[]');
const DELIVERY_ZONES = @json($deliveryZones->map(fn($z) => ['id' => $z->id, 'fee' => (float) $z->fee]));

function saveCart(){
  localStorage.setItem('shop_cart_'+BID, JSON.stringify(cart));
  updateBadge();
}

function updateBadge(){
  const badge = document.getElementById('cartCount');
  if (badge) {
    badge.textContent = cart.reduce((sum, item) => sum + item.qty, 0);
  }
}

function showOrderSuccessPopup(orderNumber, message, totalAmount = null, paymentLabel = null) {
  const popup = document.getElementById('orderSuccessPopup');
  const ref = document.getElementById('orderSuccessRef');
  const messageBox = document.getElementById('orderSuccessMessage');
  const totalBox = document.getElementById('orderSuccessTotal');
  const paymentBox = document.getElementById('orderSuccessPayment');

  if (!popup) {
    console.error('Order success popup container was not found.');
    return;
  }

  if (ref) ref.textContent = orderNumber || '-';
  if (messageBox) messageBox.textContent = message || 'Your order has been received successfully.';
  if (totalBox) {
    totalBox.textContent = totalAmount !== null
      ? 'UGX ' + Number(totalAmount).toLocaleString()
      : '-';
  }
  if (paymentBox) paymentBox.textContent = paymentLabel || '-';

  popup.classList.add('show');
  document.body.classList.add('overflow-hidden');
}

function closeOrderSuccessPopup() {
  const popup = document.getElementById('orderSuccessPopup');
  if (popup) {
    popup.classList.remove('show');
  }
  document.body.classList.remove('overflow-hidden');
}

// Keep the signed tracking link on this device and point the popup's
// "Track order" button at it.
function rememberOrderForTracking(data) {
  const trackBtn = document.getElementById('orderSuccessTrack');
  if (trackBtn) trackBtn.href = data.tracking_url || '{{ route('orders.track') }}?ref=' + encodeURIComponent(data.order_number || '');
  if (!data.tracking_url) return;
  try {
    let orders = JSON.parse(localStorage.getItem('shopRecentOrders') || '[]');
    if (!Array.isArray(orders)) orders = [];
    orders = orders.filter(o => o && o.ref !== data.order_number);
    orders.unshift({ ref: data.order_number, url: data.tracking_url, placed: new Date().toISOString() });
    localStorage.setItem('shopRecentOrders', JSON.stringify(orders.slice(0, 10)));
  } catch (_) {}
}

function continueShoppingAfterOrder() {
  closeOrderSuccessPopup();
  window.scrollTo({top: 0, behavior: 'smooth'});
}

document.getElementById('orderSuccessPopup')?.addEventListener('click', function(event) {
  if (event.target === this) {
    closeOrderSuccessPopup();
  }
});

function addFromCard(id, name, price, stock){
  const existing = cart.find(i => i.id === id);
  if (existing) {
    existing.stock = stock;
    existing.price = price;
    if (existing.qty >= stock) {
      appAlert(`Only ${stock} of "${name}" in stock, and they are all in your cart.`);
      return;
    }
    existing.qty++;
  } else {
    if (stock < 1) return;
    cart.push({id, name, price, stock, qty: 1});
  }
  saveCart();
}

/* ---- Editing the cart at checkout -------------------------------------- */

// Carts saved before stock was remembered have no limit here; checkout re-checks stock.
function maxQty(item){
  return Number.isFinite(Number(item.stock)) && Number(item.stock) > 0 ? Number(item.stock) : 9999;
}

let lastRemoved = null;
let undoTimer = null;

function setQty(id, qty){
  const item = cart.find(i => i.id === id);
  if (!item) return;
  qty = Math.floor(Number(qty));
  if (!Number.isFinite(qty) || qty < 1) {
    removeItem(id);
    return;
  }
  item.qty = Math.min(qty, maxQty(item));
  saveCart();
  renderCart();
}

function changeQty(id, delta){
  const item = cart.find(i => i.id === id);
  if (item) setQty(id, item.qty + delta);
}

function removeItem(id){
  const index = cart.findIndex(i => i.id === id);
  if (index < 0) return;
  lastRemoved = {item: cart[index], index};
  cart.splice(index, 1);
  saveCart();
  renderCart();
  showUndo(`Removed ${lastRemoved.item.name}.`);
}

function clearCart(){
  if (!cart.length) return;
  lastRemoved = {all: cart.slice()};
  cart = [];
  saveCart();
  renderCart();
  showUndo('Cart cleared.');
}

function undoRemove(){
  if (!lastRemoved) return;
  if (lastRemoved.all) {
    cart = lastRemoved.all;
  } else if (!cart.find(i => i.id === lastRemoved.item.id)) {
    cart.splice(Math.min(lastRemoved.index, cart.length), 0, lastRemoved.item);
  }
  lastRemoved = null;
  hideUndo();
  saveCart();
  renderCart();
}

function showUndo(text){
  const bar = document.getElementById('cartUndo');
  document.getElementById('cartUndoText').textContent = text;
  bar.hidden = false;
  clearTimeout(undoTimer);
  undoTimer = setTimeout(hideUndo, 6000);
}

function hideUndo(){
  const bar = document.getElementById('cartUndo');
  if (bar) bar.hidden = true;
}

// One listener for every row's buttons and quantity box.
document.getElementById('cartItems').addEventListener('click', function(event){
  const button = event.target.closest('button[data-action]');
  if (!button) return;
  const id = Number(button.closest('[data-id]').dataset.id);
  if (button.dataset.action === 'inc') changeQty(id, 1);
  if (button.dataset.action === 'dec') changeQty(id, -1);
  if (button.dataset.action === 'remove') removeItem(id);
});
document.getElementById('cartItems').addEventListener('change', function(event){
  if (!event.target.matches('input[data-qty]')) return;
  setQty(Number(event.target.closest('[data-id]').dataset.id), event.target.value);
});
document.getElementById('cartItems').addEventListener('keydown', function(event){
  if (event.key === 'Enter' && event.target.matches('input[data-qty]')) {
    event.preventDefault();
    event.target.blur();
  }
});

function currentFee(){
  const sel = document.getElementById('zSel');
  const z = DELIVERY_ZONES.find(z => String(z.id) === sel.value);
  return z ? z.fee : 0;
}

function renderCart(){
  const wrap = document.getElementById('cartItems');
  const count = cart.reduce((sum, item) => sum + item.qty, 0);
  document.getElementById('checkoutItemCount').textContent = `${count} item${count === 1 ? '' : 's'}`;

  document.getElementById('cartClearBtn').hidden = !cart.length;
  document.getElementById('placeBtn').disabled = !cart.length;

  if (!cart.length) {
    wrap.innerHTML = `<div class="text-center text-slate-400 py-7">
      <i class="fas fa-cart-shopping text-2xl mb-2"></i>
      <p class="text-sm mb-3">Your cart is empty.</p>
      <button type="button" class="checkout-cancel" style="display:inline-block" onclick="closeCheckout()">Continue shopping</button>
    </div>`;
  } else {
    wrap.innerHTML = '';
    cart.forEach(item => {
      const max = maxQty(item);
      const name = escapeCheckoutHtml(item.name);
      const row = document.createElement('div');
      row.className = 'cart-order-row';
      row.dataset.id = item.id;
      row.innerHTML = `
        <div class="min-w-0">
          <div class="cart-order-name">${name}</div>
          <div class="cart-order-meta">UGX ${Number(item.price).toLocaleString()} each</div>
          ${item.qty >= max && max < 9999 ? `<div class="cart-limit">Only ${max} in stock</div>` : ''}
        </div>
        <div class="cart-qty" role="group" aria-label="Quantity of ${name}">
          <button type="button" data-action="dec" aria-label="${item.qty === 1 ? 'Remove' : 'Reduce'} ${name}">
            <i class="fas ${item.qty === 1 ? 'fa-trash-can' : 'fa-minus'} text-xs"></i>
          </button>
          <input type="number" inputmode="numeric" min="1" max="${max}" value="${item.qty}" data-qty aria-label="Quantity of ${name}">
          <button type="button" data-action="inc" aria-label="Add one more ${name}" ${item.qty >= max ? 'disabled' : ''}>
            <i class="fas fa-plus text-xs"></i>
          </button>
        </div>
        <div class="cart-order-end">
          <div class="cart-order-price">UGX ${(Number(item.price) * item.qty).toLocaleString()}</div>
          <button type="button" class="cart-remove" data-action="remove" aria-label="Remove ${name}" title="Remove">
            <i class="fas fa-xmark"></i>
          </button>
        </div>`;
      wrap.appendChild(row);
    });
  }

  const subtotal = cart.reduce((sum, item) => sum + (Number(item.price) * item.qty), 0);
  const fee = currentFee();
  document.getElementById('cartTotals').innerHTML = `
    <div class="checkout-total-line"><span>Subtotal</span><span>UGX ${subtotal.toLocaleString()}</span></div>
    <div class="checkout-total-line"><span>Delivery</span><span>UGX ${fee.toLocaleString()}</span></div>
    <div class="checkout-total-line total"><span>Total</span><span>UGX ${(subtotal + fee).toLocaleString()}</span></div>`;
}

function escapeCheckoutHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function openCheckout(){
  if (!cart.length) {
    appAlert('Your cart is empty.');
    return;
  }
  clearAlert();
  renderCart();
  const modal = document.getElementById('checkoutModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.classList.add('overflow-hidden');
  setTimeout(() => document.getElementById('coCustomerName')?.focus(), 50);
}

function closeCheckout(){
  const modal = document.getElementById('checkoutModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.classList.remove('overflow-hidden');
}

document.getElementById('checkoutModal').addEventListener('click', function(event){
  if (event.target === this) closeCheckout();
});

document.addEventListener('keydown', function(event){
  if (event.key === 'Escape' && !document.getElementById('checkoutModal').classList.contains('hidden')) {
    closeCheckout();
  }
});

document.getElementById('payMethod').addEventListener('change', function(){
  const mobile = this.value === 'mobile-money';
  const panel = document.getElementById('mmFields');
  panel.classList.toggle('hidden', !mobile);

  document.getElementById('gatewayCode').required = mobile;
  document.getElementById('mmPhone').required = mobile;
  document.getElementById('mmNetwork').required = mobile;
});

document.getElementById('zSel').addEventListener('change', renderCart);

function showAlert(msg){
  const box = document.getElementById('alertBox');
  document.getElementById('alertText').textContent = msg;
  box.classList.remove('hidden');
  box.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}
function clearAlert(){
  document.getElementById('alertBox').classList.add('hidden');
  document.getElementById('alertText').textContent = '';
}

function normalizeCheckoutPhone(value){
  return String(value || '').replace(/[^\d+]/g, '');
}

// --- CSRF handling -------------------------------------------------------
// The token rendered into the page goes stale if the tab outlives the session
// (SESSION_LIFETIME) or the session is rotated by a login/logout elsewhere,
// which made checkout fail with "CSRF token mismatch" (HTTP 419). We fetch a
// fresh token right before submitting and retry once if the server still 419s.
function currentCsrfToken(){
  return document.querySelector('meta[name=csrf-token]').content;
}

async function refreshCsrfToken(){
  const res = await fetch('{{ route('csrf.token') }}', {
    method: 'GET',
    credentials: 'same-origin',
    cache: 'no-store',
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  });
  if (!res.ok) throw new Error('Could not refresh the security token.');
  const data = await res.json();
  if (!data.token) throw new Error('Could not refresh the security token.');
  document.querySelector('meta[name=csrf-token]').content = data.token;
  return data.token;
}

// POST with a fresh CSRF token; on a 419 refresh the token and retry once.
// `makeBody` is a function so the body can be rebuilt for the retry.
async function csrfPost(url, makeBody, extraHeaders = {}){
  const send = (token) => fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...extraHeaders,
      'X-CSRF-TOKEN': token,
    },
    body: makeBody(),
  });

  let token;
  try { token = await refreshCsrfToken(); } catch (_) { token = currentCsrfToken(); }

  let res = await send(token);
  if (res.status === 419) {
    token = await refreshCsrfToken();
    res = await send(token);
  }
  return res;
}

// Pages restored from the back/forward cache carry an old token, refresh it.
window.addEventListener('pageshow', (e) => { if (e.persisted) refreshCsrfToken().catch(() => {}); });

document.getElementById('coForm').addEventListener('submit', placeOrder);

async function placeOrder(event){
  event?.preventDefault();
  clearAlert();

  if (!cart.length) {
    showAlert('Your cart is empty.');
    return;
  }

  const form = document.getElementById('coForm');

  // Normalize phone input before browser/server validation.
  form.phone.value = normalizeCheckoutPhone(form.phone.value);
  if (form.mm_phone) form.mm_phone.value = normalizeCheckoutPhone(form.mm_phone.value);

  if (!form.checkValidity()) {
    form.reportValidity();
    showAlert('Please complete all required checkout fields.');
    return;
  }

  // IMPORTANT: Laravel validates items as an ARRAY.
  // Appending one JSON string as "items" caused validation to fail and the old
  // frontend then tried to parse Laravel's HTML validation response as JSON,
  // which was displayed incorrectly as "Network error".
  const buildCheckoutBody = () => {
    const body = new FormData(form);
    body.delete('_token'); // the header carries the fresh token
    cart.forEach((item, index) => {
      body.append(`items[${index}][id]`, String(item.id));
      body.append(`items[${index}][qty]`, String(item.qty));
    });
    return body;
  };
  const fd = buildCheckoutBody();

  const btn = document.getElementById('placeBtn');
  const btnLabel = btn.querySelector('span');
  const btnIcon = btn.querySelector('i');
  btn.disabled = true;
  btnLabel.textContent = 'Processing...';
  btnIcon.className = 'fas fa-spinner fa-spin';

  try {
    const res = await csrfPost('{{ route('checkout.store') }}', buildCheckoutBody);

    if (res.status === 419) {
      throw new Error('Your session expired. Please refresh the page and place your order again. Your cart is saved.');
    }

    const contentType = res.headers.get('content-type') || '';
    let data = null;

    if (contentType.includes('application/json')) {
      data = await res.json();
    } else {
      const raw = await res.text();
      console.error('Unexpected checkout response:', res.status, raw);
      throw new Error(`The server returned an unexpected response (${res.status}). Please refresh and try again.`);
    }

    if (!res.ok || data.status !== 'ok') {
      const firstValidationError = data.errors
        ? Object.values(data.errors).flat().find(Boolean)
        : null;
      showAlert(firstValidationError || data.msg || data.message || 'Order could not be placed. Please check your details and try again.');
      return;
    }

    // The server has successfully created the order at this point.
    // Clear the cart immediately so refreshing/revisiting the shop cannot
    // accidentally submit the same order again.
    cart = [];
    saveCart();
    form.reset();
    document.getElementById('mmFields').classList.add('hidden');
    closeCheckout();
    updateBadge();

    const orderTotal = Number(data.total_raw || 0);
    rememberOrderForTracking(data);

    if (data.payment_method === 'mobile-money') {
      const mmPhone = form.mm_phone ? form.mm_phone.value.trim() : '';
      const mmNetwork = form.mm_network ? form.mm_network.value : '';

      // Values were reset above, so recover submitted values from FormData.
      const submittedMmPhone = String(fd.get('mm_phone') || mmPhone || '');
      const submittedMmNetwork = String(fd.get('mm_network') || mmNetwork || '');

      try {
        const paymentRes = await csrfPost('{{ route('iotec.collect') }}', () => JSON.stringify({
          order_id: data.order_number,
          amount: data.total_raw,
          phone: submittedMmPhone,
          network: submittedMmNetwork,
        }), { 'Content-Type': 'application/json' });

        let paymentData = null;
        try { paymentData = await paymentRes.json(); } catch (_) {}

        if (!paymentRes.ok) {
          showOrderSuccessPopup(
            data.order_number,
            'Your order was placed successfully, but the mobile money prompt could not be sent. Please contact support with your order reference to complete payment.',
            orderTotal,
            'Mobile Money - Prompt Pending'
          );
        } else {
          showOrderSuccessPopup(
            data.order_number,
            'Your order was placed successfully. A mobile money payment prompt has been sent to '+submittedMmPhone+'. Please approve it on your phone.',
            orderTotal,
            'Mobile Money'
          );
        }
      } catch (paymentError) {
        console.error('Mobile money prompt error:', paymentError);
        showOrderSuccessPopup(
          data.order_number,
          'Your order was placed successfully. We could not send the mobile money prompt right now. Keep your order reference and contact support to complete payment.',
          orderTotal,
          'Mobile Money - Prompt Pending'
        );
      }
    } else {
      showOrderSuccessPopup(
        data.order_number,
        'Your order has been received successfully. Payment will be made by Cash on Delivery. Keep the order reference for tracking and follow-up.',
        orderTotal,
        'Cash on Delivery'
      );
    }
  } catch (error) {
    console.error('Checkout error:', error);
    showAlert(window.networkErrorMessage
      ? networkErrorMessage(error, 'Order could not be placed. Please try again.')
      : 'We could not connect to the server. Please check your internet connection and try again.');
  } finally {
    btn.disabled = false;
    btnLabel.textContent = 'Place Order';
    btnIcon.className = 'fas fa-circle-check';
  }
}

updateBadge();
</script>

<!-- Professional Chat widget -->
<style>
.chat-bubble{background:var(--shop-primary)}.chat-bubble:hover{filter:brightness(.92)}.chat-window{height:min(39rem,calc(100vh - 7rem));height:min(39rem,calc(100dvh - 7rem));width:min(24.5rem,calc(100vw - 1.75rem))}.chat-head{background:linear-gradient(135deg,var(--shop-primary),var(--shop-secondary))}.chat-field{position:relative}.chat-field>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8}.chat-field textarea+i{top:17px;transform:none}.chat-field input,.chat-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:10px;background:#fff;padding:10px 12px 10px 38px;outline:none}.chat-field textarea{min-height:78px;resize:vertical}.chat-field input:focus,.chat-field textarea:focus{border-color:var(--shop-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--shop-primary) 12%,transparent)}.chat-primary{background:var(--shop-primary)}.chat-primary:hover{filter:brightness(.92)}.chat-message-customer{background:var(--shop-primary);color:#fff}.chat-send-input{min-width:0;flex:1;border:1px solid #cbd5e1;border-radius:10px;padding:10px 11px;outline:none}.chat-send-input:focus{border-color:var(--shop-primary)}
</style>
<button id="chat-bubble" onclick="toggleChat()" aria-label="Open customer chat" class="chat-bubble fixed bottom-5 right-5 z-40 text-white w-14 h-14 rounded-full shadow-xl flex items-center justify-center">
  <i class="fas fa-comments text-xl"></i>
</button>
<div id="chat-window" class="chat-window hidden fixed bottom-24 right-4 md:right-5 z-40 bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden">
  <div class="chat-head text-white px-4 py-3.5 flex items-center justify-between">
    <div class="flex items-center gap-2"><span class="w-9 h-9 rounded-full bg-white/15 grid place-items-center"><i class="fas fa-headset"></i></span><div><strong class="block text-sm">Chat with us</strong><small class="text-white/75">Sales & customer support</small></div></div>
    <button type="button" onclick="toggleChat()" class="w-8 h-8 rounded-lg hover:bg-white/10" aria-label="Close chat"><i class="fas fa-xmark"></i></button>
  </div>
  <div id="chat-contact-form" class="p-4 flex-1 overflow-y-auto text-sm">
    <p class="text-slate-500 mb-4 leading-5">Please provide your details so our sales team can assist you and follow up if the chat disconnects.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div><label class="block font-semibold text-slate-700 mb-1">First name *</label><div class="chat-field"><i class="fas fa-user"></i><input type="text" id="chat-first-name" autocomplete="given-name" placeholder="First name"></div></div>
      <div><label class="block font-semibold text-slate-700 mb-1">Last name *</label><div class="chat-field"><i class="fas fa-user"></i><input type="text" id="chat-last-name" autocomplete="family-name" placeholder="Last name"></div></div>
      <div class="sm:col-span-2"><label class="block font-semibold text-slate-700 mb-1">Email address *</label><div class="chat-field"><i class="fas fa-envelope"></i><input type="email" id="chat-email" autocomplete="email" placeholder="name@example.com"></div></div>
      <div class="sm:col-span-2"><label class="block font-semibold text-slate-700 mb-1">Phone number *</label><div class="chat-field"><i class="fas fa-phone"></i><input type="tel" id="chat-phone" autocomplete="tel" placeholder="e.g. +256 7xx xxx xxx"></div></div>
      <div class="sm:col-span-2"><label class="block font-semibold text-slate-700 mb-1">How can we help?</label><div class="chat-field"><textarea id="chat-first-message" placeholder="Type your question or sales enquiry..."></textarea><i class="fas fa-message"></i></div></div>
    </div>
    <div id="chat-start-error" class="hidden mt-3 text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2 text-xs"></div>
    <button type="button" onclick="startChat()" class="chat-primary mt-4 w-full text-white py-2.5 rounded-lg text-sm font-semibold flex items-center justify-center gap-2"><i class="fas fa-comment-dots"></i> Start Chat</button>
  </div>
  <div id="chat-thread-wrap" class="hidden flex-1 flex-col min-h-0">
    <div id="chat-thread" class="flex-1 overflow-y-auto p-4 space-y-3 text-sm bg-slate-50"></div>
    <form id="chat-send-form" class="border-t border-slate-200 bg-white p-3 flex items-end gap-2" onsubmit="return sendChatMessage(event)">
      <label class="w-10 h-10 flex-shrink-0 rounded-lg border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 cursor-pointer grid place-items-center" title="Attach file"><i class="fas fa-paperclip"></i><input type="file" id="chat-attachment-input" class="hidden"></label>
      <input type="text" id="chat-message-input" placeholder="Type a message..." class="chat-send-input" autocomplete="off">
      <button type="submit" class="chat-primary text-white w-10 h-10 flex-shrink-0 rounded-lg grid place-items-center" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
    </form>
  </div>
</div>
<script>
const chatStorageKey='chat_conversation_id:{{ auth()->check() ? auth()->id() : "guest" }}';const chatCanStart={{ auth()->check() && auth()->user()->role === 'customer' ? 'true' : 'false' }};let chatConversationId=chatCanStart?(new URLSearchParams(location.search).get('chat_conversation')||localStorage.getItem(chatStorageKey)):null;let chatPollTimer=null;
function toggleChat(){const w=document.getElementById('chat-window');w.classList.toggle('hidden');if(!w.classList.contains('hidden')&&chatConversationId)showChatThread()}
function chatEscape(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML}
async function startChat(){const firstName=document.getElementById('chat-first-name').value.trim(),lastName=document.getElementById('chat-last-name').value.trim(),email=document.getElementById('chat-email').value.trim(),phone=document.getElementById('chat-phone').value.trim(),message=document.getElementById('chat-first-message').value.trim(),err=document.getElementById('chat-start-error');err.classList.add('hidden');if(!chatCanStart){err.innerHTML='Customer chat requires a customer account. <a class="font-bold underline" href="{{ route('login') }}">Sign in</a> or <a class="font-bold underline" href="{{ url('/customer/signup') }}">create an account</a>.';err.classList.remove('hidden');return}if(!firstName||!lastName||!email||!phone){err.textContent='First name, last name, email and phone number are required.';err.classList.remove('hidden');return}if(!/^\S+@\S+\.\S+$/.test(email)){err.textContent='Please enter a valid email address.';err.classList.remove('hidden');return}try{const res=await fetch('{{ route('chat.start') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({first_name:firstName,last_name:lastName,email,phone,branch_id:BID,message})});const data=await res.json();if(!res.ok){const msg=data?.message||Object.values(data?.errors||{}).flat()[0]||'Could not start chat.';throw new Error(msg)}chatConversationId=data.conversation_id;localStorage.setItem(chatStorageKey,chatConversationId);showChatThread()}catch(e){err.textContent=e.message||'Could not start chat. Please try again.';err.classList.remove('hidden')}}
function showChatThread(){document.getElementById('chat-contact-form').classList.add('hidden');const wrap=document.getElementById('chat-thread-wrap');wrap.classList.remove('hidden');wrap.classList.add('flex');pollChatThread();if(chatPollTimer)clearInterval(chatPollTimer);chatPollTimer=setInterval(pollChatThread,4000)}
async function pollChatThread(){if(!chatConversationId)return;try{const res=await fetch(`/chat/${chatConversationId}/messages`);if(!res.ok)throw new Error('Unable to load chat');const data=await res.json(),thread=document.getElementById('chat-thread'),atBottom=thread.scrollTop+thread.clientHeight>=thread.scrollHeight-30;thread.innerHTML=(data.messages||[]).map(m=>`<div class="max-w-[85%] ${m.sender_type==='customer'?'ml-auto text-right':''}"><div class="inline-block text-left rounded-xl px-3 py-2 shadow-sm ${m.sender_type==='customer'?'chat-message-customer':'bg-white border border-slate-200 text-slate-700'}">${m.message?chatEscape(m.message):''}${m.attachment_url?`<div class="mt-1"><a href="${m.attachment_url}" target="_blank" rel="noopener" class="underline text-xs"><i class="fas fa-paperclip"></i> View attachment</a></div>`:''}</div><div class="text-[10px] text-slate-400 mt-1">${chatEscape(m.created_at)}</div></div>`).join('')||'<p class="text-slate-400 text-xs text-center py-4">No messages yet. Send us a message below.</p>';if(atBottom)thread.scrollTop=thread.scrollHeight}catch(e){}}
async function sendChatMessage(evt){evt.preventDefault();const input=document.getElementById('chat-message-input'),fileInput=document.getElementById('chat-attachment-input'),message=input.value.trim(),file=fileInput.files[0];if(!message&&!file)return false;const fd=new FormData();if(message)fd.append('message',message);if(file)fd.append('attachment',file);try{const res=await fetch(`/chat/${chatConversationId}/message`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:fd});if(!res.ok)throw new Error('Message failed');input.value='';fileInput.value='';await pollChatThread()}catch(e){showChatError(e)}return false}
</script>

<script>
// The shop has no app layout, so give it the small helpers the layout normally provides.
window.appAlert = window.appAlert || function (message) { window.alert(message); };
function showChatError(error) {
  appAlert(window.networkErrorMessage ? networkErrorMessage(error, 'Your message could not be sent. Please try again.') : 'Your message could not be sent. Please try again.');
}
</script>
@include('partials.network-status')
</body>
</html>
