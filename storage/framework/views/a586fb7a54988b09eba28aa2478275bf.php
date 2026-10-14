<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($branch->name); ?> - Shop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
</head>
<body class="bg-slate-50 text-slate-900">
<style>
.new-products-slider{position:relative}
.new-products-track{display:flex;gap:1rem;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;scroll-snap-type:x mandatory;padding:.15rem .1rem .75rem}
.new-products-track::-webkit-scrollbar{display:none}
.new-product-slide{flex:0 0 min(260px,78vw);scroll-snap-align:start}
.new-product-image{height:160px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden}
.new-product-image img{width:100%;height:100%;object-fit:contain;padding:10px}
.new-slider-btn{width:38px;height:38px;border-radius:999px;background:#fff;border:1px solid #e2e8f0;color:#475569;box-shadow:0 5px 16px rgba(15,23,42,.08);display:grid;place-items:center;transition:.15s}
.new-slider-btn:hover{color:var(--shop-primary,#0f766e);border-color:var(--shop-primary,#0f766e);transform:translateY(-1px)}
@media(min-width:768px){.new-product-slide{flex-basis:240px}}
</style>

<header class="bg-white border-b border-slate-200 sticky top-0 z-10">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
    <div class="font-bold text-lg"><?php echo e($branch->name); ?></div>
    <form method="GET" class="flex-1 max-w-md">
      <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Search products…"
             class="w-full rounded-full border-slate-300 px-4 py-1.5 text-sm">
      <input type="hidden" name="branch_id" value="<?php echo e($branch->id); ?>">
    </form>
    <button onclick="openCheckout()" class="relative bg-indigo-600 text-white px-4 py-1.5 rounded-full text-sm">
      Cart (<span id="cartCount">0</span>)
    </button>
  </div>
</header>

<?php if($slides->isNotEmpty()): ?>
<div class="max-w-6xl mx-auto px-4 mt-4 grid md:grid-cols-3 gap-4">
  <?php $__currentLoopData = $slides->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="rounded-xl overflow-hidden bg-white shadow p-4">
    <div class="text-xs uppercase text-indigo-500 font-semibold"><?php echo e($slide->badge_text); ?></div>
    <div class="font-semibold text-slate-800"><?php echo e($slide->title); ?></div>
    <div class="text-sm text-slate-500"><?php echo e($slide->subtitle); ?></div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>

<?php if(($newProducts ?? collect())->isNotEmpty()): ?>
<section class="max-w-6xl mx-auto px-4 mt-7">
  <div class="flex items-end justify-between gap-4 mb-3">
    <div>
      <div class="text-xs uppercase tracking-wider font-bold" style="color:var(--shop-primary,#0f766e)">
        <i class="fas fa-sparkles mr-1"></i> Just added
      </div>
      <h2 class="text-xl md:text-2xl font-extrabold text-slate-900 mt-1">New Products</h2>
      <p class="text-sm text-slate-500 mt-1">Discover the latest products now available at <?php echo e($branch->name); ?>.</p>
    </div>
    <div class="hidden sm:flex items-center gap-2">
      <button type="button" class="new-slider-btn" onclick="slideNewProducts(-1)" aria-label="Previous new products">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button type="button" class="new-slider-btn" onclick="slideNewProducts(1)" aria-label="Next new products">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>

  <div class="new-products-slider">
    <div id="newProductsTrack" class="new-products-track">
      <?php $__currentLoopData = $newProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $newProduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $newImage = $newProduct->image_url
              ? route('shop.product-image', ['product' => $newProduct->id])
              : null;
        ?>
        <article class="new-product-slide bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition">
          <div class="new-product-image relative">
            <?php if($newImage): ?>
              <img src="<?php echo e($newImage); ?>" alt="<?php echo e($newProduct->name); ?>" loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')">
              <div class="hidden text-slate-300 text-4xl"><i class="fas fa-box-open"></i></div>
            <?php else: ?>
              <div class="text-slate-300 text-4xl"><i class="fas fa-box-open"></i></div>
            <?php endif; ?>
            <span class="absolute top-3 left-3 text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-full text-white"
                  style="background:var(--shop-primary,#0f766e)">
              New
            </span>
          </div>

          <div class="p-4">
            <?php if($newProduct->category_name): ?>
              <div class="text-[11px] uppercase tracking-wide text-slate-400 font-semibold truncate">
                <?php echo e($newProduct->category_name); ?>

              </div>
            <?php endif; ?>
            <h3 class="font-bold text-slate-800 mt-1 truncate" title="<?php echo e($newProduct->name); ?>"><?php echo e($newProduct->name); ?></h3>
            <div class="flex items-center justify-between gap-3 mt-3">
              <div>
                <div class="text-xs text-slate-400">Price</div>
                <div class="font-extrabold text-slate-900">UGX <?php echo e(number_format((float)$newProduct->price, 0)); ?></div>
              </div>
              <button type="button"
                      onclick='addFromCard(<?php echo e((int)$newProduct->id); ?>, <?php echo json_encode($newProduct->name, 15, 512) ?>, <?php echo e((float)$newProduct->price); ?>, <?php echo e((int)$newProduct->stock); ?>)'
                      class="w-10 h-10 rounded-full text-white grid place-items-center shadow-sm"
                      style="background:var(--shop-primary,#0f766e)"
                      title="Add <?php echo e($newProduct->name); ?> to cart"
                      aria-label="Add <?php echo e($newProduct->name); ?> to cart">
                <i class="fas fa-cart-plus"></i>
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="max-w-6xl mx-auto px-4 py-6 flex gap-6">
  <aside class="w-48 flex-shrink-0 hidden md:block">
    <div class="font-semibold text-sm text-slate-700 mb-2">Categories</div>
    <a href="?branch_id=<?php echo e($branch->id); ?>" class="block py-1 text-sm <?php echo e(!$catFilter ? 'text-indigo-600 font-medium' : 'text-slate-600'); ?>">All</a>
    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php if($cat->cnt > 0): ?>
      <a href="?branch_id=<?php echo e($branch->id); ?>&cat=<?php echo e($cat->id); ?>"
         class="block py-1 text-sm <?php echo e($catFilter == $cat->id ? 'text-indigo-600 font-medium' : 'text-slate-600'); ?>">
        <?php echo e($cat->category_name); ?> (<?php echo e($cat->cnt); ?>)
      </a>
      <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <div class="font-semibold text-sm text-slate-700 mt-6 mb-2">Branch</div>
    <select onchange="location.href='?branch_id='+this.value" class="w-full text-sm rounded-md border-slate-300">
      <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($b->id); ?>" <?php echo e($b->id === $branch->id ? 'selected' : ''); ?>><?php echo e($b->name); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </aside>

  <div class="flex-1 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div id="pc-<?php echo e($p->id); ?>" class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
      <div class="h-36 bg-slate-100 flex items-center justify-center text-slate-300 text-xs">
        <?php if($p->image_url): ?>
          <img src="<?php echo e(route('shop.product-image', ['product' => $p->id])); ?>"
               class="h-full w-full object-contain p-2"
               alt="<?php echo e($p->name); ?>"
               loading="lazy"
               onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">
          <div class="hidden h-full w-full items-center justify-center text-slate-300 text-4xl">
            <i class="fas fa-box-open"></i>
          </div>
        <?php else: ?>
          No image
        <?php endif; ?>
      </div>
      <div class="p-3 flex-1 flex flex-col">
        <div class="text-sm font-medium text-slate-800"><?php echo e($p->name); ?></div>
        <div class="text-xs text-slate-400"><?php echo e($p->category_name); ?></div>
        <div class="mt-1 font-semibold text-indigo-600">UGX <?php echo e(number_format($p->price, 0)); ?></div>
        <div class="text-xs text-slate-400 mb-2"><?php echo e($p->stock); ?> in stock</div>
        <button onclick="addFromCard(<?php echo e($p->id); ?>, '<?php echo e(addslashes($p->name)); ?>', <?php echo e($p->price); ?>, <?php echo e($p->stock); ?>)"
                class="mt-auto bg-indigo-600 text-white text-sm py-1.5 rounded-md hover:bg-indigo-700">
          Add to cart
        </button>
      </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-span-full text-center text-slate-400 py-12">No products found.</div>
    <?php endif; ?>
  </div>
</div>


<!-- Custom Order Success Popup -->
<style>
.order-success-overlay{
  position:fixed;inset:0;z-index:70;display:none;align-items:center;justify-content:center;
  padding:20px;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)
}
.order-success-overlay.show{display:flex}
.order-success-card{
  width:min(460px,100%);background:#fff;border-radius:20px;overflow:hidden;
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
.order-success-primary{border:0;background:var(--shop-primary,#0f766e);color:#fff}
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

      <div class="order-success-actions">
        <button type="button" class="order-success-secondary" onclick="closeOrderSuccessPopup()">
          <i class="fas fa-xmark mr-1"></i> Close
        </button>
        <button type="button" class="order-success-primary" onclick="continueShoppingAfterOrder()">
          <i class="fas fa-bag-shopping mr-1"></i> Continue Shopping
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Checkout modal -->
<style>
.checkout-modal-card{width:min(760px,100%);max-height:min(92vh,860px);display:flex;flex-direction:column;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 28px 80px rgba(15,23,42,.25)}
.checkout-modal-head{padding:18px 20px;border-bottom:1px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:16px;position:sticky;top:0;z-index:2}
.checkout-modal-head h2{margin:0;font-size:1.15rem;font-weight:800;color:#0f172a}
.checkout-modal-head p{margin:2px 0 0;color:#64748b;font-size:.78rem}
.checkout-close{width:36px;height:36px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;display:grid;place-items:center;cursor:pointer}
.checkout-close:hover{background:#f8fafc;color:#0f172a}
.checkout-scroll{overflow-y:auto;padding:20px}
.checkout-summary{border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;background:#fff;margin-bottom:20px}
.checkout-summary-title{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.checkout-summary-title strong{font-size:.88rem;color:#334155}
.cart-order-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:11px 14px;border-bottom:1px solid #f1f5f9}
.cart-order-row:last-child{border-bottom:0}
.cart-order-name{font-weight:700;color:#1e293b}
.cart-order-meta{font-size:.73rem;color:#94a3b8;margin-top:2px}
.cart-order-price{text-align:right;font-weight:800;color:#0f172a;white-space:nowrap}
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
.checkout-place{min-height:44px;padding:10px 20px;border:0;border-radius:10px;background:var(--shop-primary,#0f766e);color:#fff;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:150px}
.checkout-place:hover{filter:brightness(.94)}
.checkout-place:disabled{opacity:.65;cursor:not-allowed}
.mm-panel{grid-column:1/-1;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:14px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff}
.mm-panel.hidden{display:none}
@media(max-width:640px){#checkoutModal{padding:0!important;align-items:flex-end!important}.checkout-modal-card{max-height:94vh;border-radius:18px 18px 0 0}.checkout-modal-head{padding:15px 16px}.checkout-scroll{padding:16px}.checkout-grid,.mm-panel{grid-template-columns:1fr}.checkout-field.full,.mm-panel{grid-column:auto}.checkout-actions{position:sticky;bottom:-16px;margin-left:-16px;margin-right:-16px;padding:12px 16px 16px;background:#fff}.checkout-cancel{display:none}.checkout-place{width:100%}}
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
          <span id="checkoutItemCount" class="text-xs text-slate-500"></span>
        </div>
        <div id="cartItems"></div>
        <div id="cartTotals" class="checkout-totals"></div>
      </div>

      <form id="coForm" novalidate>
        <input type="hidden" name="branch_id" value="<?php echo e($branch->id); ?>">

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
                  <?php $__currentLoopData = $deliveryZones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $z): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($z->id); ?>" data-fee="<?php echo e($z->fee); ?>"><?php echo e($z->zone_name); ?> - UGX <?php echo e(number_format($z->fee, 0)); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                  <?php if($mobileGateways->isNotEmpty()): ?>
                    <option value="mobile-money">Mobile Money</option>
                  <?php endif; ?>
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
                    <?php $__currentLoopData = $mobileGateways; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($g->gateway_code); ?>"><?php echo e($g->display_name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
  const distance = card ? card.getBoundingClientRect().width + 16 : 260;
  track.scrollBy({left: direction * distance * 2, behavior: 'smooth'});
}

const BID = <?php echo e($branch->id); ?>;
let cart = JSON.parse(localStorage.getItem('shop_cart_'+BID) || '[]');
const DELIVERY_ZONES = <?php echo json_encode($deliveryZones->map(fn($z) => ['id' => $z->id, 'fee' => (float) $z->fee]), 512) ?>;

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
    if (existing.qty < stock) existing.qty++;
  } else {
    cart.push({id, name, price, qty: 1});
  }
  saveCart();
}

function currentFee(){
  const sel = document.getElementById('zSel');
  const z = DELIVERY_ZONES.find(z => String(z.id) === sel.value);
  return z ? z.fee : 0;
}

function renderCart(){
  const wrap = document.getElementById('cartItems');
  const count = cart.reduce((sum, item) => sum + item.qty, 0);
  document.getElementById('checkoutItemCount').textContent = `${count} item${count === 1 ? '' : 's'}`;

  if (!cart.length) {
    wrap.innerHTML = '<div class="text-center text-slate-400 py-7"><i class="fas fa-cart-shopping text-2xl mb-2"></i><p class="text-sm">Your cart is empty.</p></div>';
  } else {
    wrap.innerHTML = '';
    cart.forEach(item => {
      const row = document.createElement('div');
      row.className = 'cart-order-row';
      row.innerHTML = `
        <div>
          <div class="cart-order-name">${escapeCheckoutHtml(item.name)}</div>
          <div class="cart-order-meta">UGX ${Number(item.price).toLocaleString()} × ${item.qty}</div>
        </div>
        <div class="cart-order-price">UGX ${(Number(item.price) * item.qty).toLocaleString()}</div>`;
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
    alert('Your cart is empty.');
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

  const fd = new FormData(form);

  // IMPORTANT: Laravel validates items as an ARRAY.
  // Appending one JSON string as "items" caused validation to fail and the old
  // frontend then tried to parse Laravel's HTML validation response as JSON,
  // which was displayed incorrectly as "Network error".
  cart.forEach((item, index) => {
    fd.append(`items[${index}][id]`, String(item.id));
    fd.append(`items[${index}][qty]`, String(item.qty));
  });

  const btn = document.getElementById('placeBtn');
  const btnLabel = btn.querySelector('span');
  const btnIcon = btn.querySelector('i');
  btn.disabled = true;
  btnLabel.textContent = 'Processing...';
  btnIcon.className = 'fas fa-spinner fa-spin';

  try {
    const res = await fetch('<?php echo e(route('checkout.store')); ?>', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: fd,
    });

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

    if (data.payment_method === 'mobile-money') {
      const mmPhone = form.mm_phone ? form.mm_phone.value.trim() : '';
      const mmNetwork = form.mm_network ? form.mm_network.value : '';

      // Values were reset above, so recover submitted values from FormData.
      const submittedMmPhone = String(fd.get('mm_phone') || mmPhone || '');
      const submittedMmNetwork = String(fd.get('mm_network') || mmNetwork || '');

      try {
        const paymentRes = await fetch('<?php echo e(route('iotec.collect')); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
          },
          body: JSON.stringify({
            order_id: data.order_number,
            amount: data.total_raw,
            phone: submittedMmPhone,
            network: submittedMmNetwork,
          }),
        });

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
    showAlert(error.message || 'We could not connect to the server. Please check your internet connection and try again.');
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
:root{--shop-primary:<?php echo e($siteSettings['theme_primary'] ?? '#0f766e'); ?>;--shop-secondary:<?php echo e($siteSettings['theme_secondary'] ?? '#0ea5e9'); ?>}
.chat-bubble{background:var(--shop-primary)}.chat-bubble:hover{filter:brightness(.92)}.chat-window{height:min(620px,calc(100vh - 110px));width:min(390px,calc(100vw - 28px))}.chat-head{background:linear-gradient(135deg,var(--shop-primary),var(--shop-secondary))}.chat-field{position:relative}.chat-field>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8}.chat-field textarea+i{top:17px;transform:none}.chat-field input,.chat-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:10px;background:#fff;padding:10px 12px 10px 38px;outline:none}.chat-field textarea{min-height:78px;resize:vertical}.chat-field input:focus,.chat-field textarea:focus{border-color:var(--shop-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--shop-primary) 12%,transparent)}.chat-primary{background:var(--shop-primary)}.chat-primary:hover{filter:brightness(.92)}.chat-message-customer{background:var(--shop-primary);color:#fff}.chat-send-input{min-width:0;flex:1;border:1px solid #cbd5e1;border-radius:10px;padding:10px 11px;outline:none}.chat-send-input:focus{border-color:var(--shop-primary)}
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
let chatConversationId=localStorage.getItem('chat_conversation_id');let chatPollTimer=null;
function toggleChat(){const w=document.getElementById('chat-window');w.classList.toggle('hidden');if(!w.classList.contains('hidden')&&chatConversationId)showChatThread()}
function chatEscape(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML}
async function startChat(){const firstName=document.getElementById('chat-first-name').value.trim(),lastName=document.getElementById('chat-last-name').value.trim(),email=document.getElementById('chat-email').value.trim(),phone=document.getElementById('chat-phone').value.trim(),message=document.getElementById('chat-first-message').value.trim(),err=document.getElementById('chat-start-error');err.classList.add('hidden');if(!firstName||!lastName||!email||!phone){err.textContent='First name, last name, email and phone number are required.';err.classList.remove('hidden');return}if(!/^\S+@\S+\.\S+$/.test(email)){err.textContent='Please enter a valid email address.';err.classList.remove('hidden');return}try{const res=await fetch('<?php echo e(route('chat.start')); ?>',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({first_name:firstName,last_name:lastName,email,phone,branch_id:BID,message})});const data=await res.json();if(!res.ok){const msg=data?.message||Object.values(data?.errors||{}).flat()[0]||'Could not start chat.';throw new Error(msg)}chatConversationId=data.conversation_id;localStorage.setItem('chat_conversation_id',chatConversationId);showChatThread()}catch(e){err.textContent=e.message||'Could not start chat. Please try again.';err.classList.remove('hidden')}}
function showChatThread(){document.getElementById('chat-contact-form').classList.add('hidden');const wrap=document.getElementById('chat-thread-wrap');wrap.classList.remove('hidden');wrap.classList.add('flex');pollChatThread();if(chatPollTimer)clearInterval(chatPollTimer);chatPollTimer=setInterval(pollChatThread,4000)}
async function pollChatThread(){if(!chatConversationId)return;try{const res=await fetch(`/chat/${chatConversationId}/messages`);if(!res.ok)throw new Error('Unable to load chat');const data=await res.json(),thread=document.getElementById('chat-thread'),atBottom=thread.scrollTop+thread.clientHeight>=thread.scrollHeight-30;thread.innerHTML=(data.messages||[]).map(m=>`<div class="max-w-[85%] ${m.sender_type==='customer'?'ml-auto text-right':''}"><div class="inline-block text-left rounded-xl px-3 py-2 shadow-sm ${m.sender_type==='customer'?'chat-message-customer':'bg-white border border-slate-200 text-slate-700'}">${m.message?chatEscape(m.message):''}${m.attachment_url?`<div class="mt-1"><a href="${m.attachment_url}" target="_blank" rel="noopener" class="underline text-xs"><i class="fas fa-paperclip"></i> View attachment</a></div>`:''}</div><div class="text-[10px] text-slate-400 mt-1">${chatEscape(m.created_at)}</div></div>`).join('')||'<p class="text-slate-400 text-xs text-center py-4">No messages yet. Send us a message below.</p>';if(atBottom)thread.scrollTop=thread.scrollHeight}catch(e){}}
async function sendChatMessage(evt){evt.preventDefault();const input=document.getElementById('chat-message-input'),fileInput=document.getElementById('chat-attachment-input'),message=input.value.trim(),file=fileInput.files[0];if(!message&&!file)return false;const fd=new FormData();if(message)fd.append('message',message);if(file)fd.append('attachment',file);try{const res=await fetch(`/chat/${chatConversationId}/message`,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:fd});if(!res.ok)throw new Error('Message failed');input.value='';fileInput.value='';await pollChatThread()}catch(e){alert('Your message could not be sent. Please try again.')}return false}
</script>

</body>
</html>
<?php /**PATH /home/kemmytec/nsales/resources/views/shop/index.blade.php ENDPATH**/ ?>