<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<?php $isCustomer = auth()->user()?->role === 'customer'; ?>

<div class="mb-5">
    <h1 class="text-xl font-black text-slate-800">
        <i class="fas fa-gauge-high mr-2 text-indigo-600"></i> Dashboard
    </h1>
    <p class="mt-1 text-sm text-slate-500">
        <?php echo e($isCustomer ? 'Your purchases, orders and account activity.' : 'Business overview and performance.'); ?>

    </p>
</div>

<div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6">
    <?php $__currentLoopData = [
        [$isCustomer ? 'My Purchases Today' : "Today's Sales", 'UGX '.number_format((float)$todaySales,0), 'fas fa-cash-register'],
        [$isCustomer ? 'My Purchases This Month' : 'Month Sales', 'UGX '.number_format((float)$monthSales,0), 'fas fa-chart-line'],
        [$isCustomer ? 'Pending Orders' : 'Month Expenses', $isCustomer ? number_format((int)$pendingOrders) : 'UGX '.number_format((float)$monthExpenses,0), $isCustomer ? 'fas fa-cart-shopping' : 'fas fa-receipt'],
        [$isCustomer ? 'Products Available' : 'Pending Orders', $isCustomer ? number_format((int)$totalProducts) : number_format((int)$pendingOrders), $isCustomer ? 'fas fa-box-open' : 'fas fa-clock'],
        [$isCustomer ? 'Account' : 'Low Stock', $isCustomer ? 'Active' : number_format((int)$lowStock), $isCustomer ? 'fas fa-user-check' : 'fas fa-triangle-exclamation'],
        [$isCustomer ? 'Support' : 'Open Chats', number_format((int)$openChatsCount), 'fas fa-comments'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label,$value,$icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <div class="text-[10px] font-black uppercase tracking-wide text-slate-400"><?php echo e($label); ?></div>
                    <div class="mt-2 truncate text-lg font-black text-slate-800"><?php echo e($value); ?></div>
                </div>
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600">
                    <i class="<?php echo e($icon); ?>"></i>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap gap-1 border-b border-slate-200 p-2">
        <button type="button" class="dashboard-tab active rounded-xl px-4 py-2 text-sm font-bold" data-tab="overview">
            <i class="fas fa-chart-pie mr-1"></i> Overview
        </button>
        <button type="button" class="dashboard-tab rounded-xl px-4 py-2 text-sm font-bold" data-tab="activity">
            <i class="fas fa-clock-rotate-left mr-1"></i> Recent Activity
        </button>
        <?php if (! ($isCustomer)): ?>
            <button type="button" class="dashboard-tab rounded-xl px-4 py-2 text-sm font-bold" data-tab="operations">
                <i class="fas fa-building-columns mr-1"></i> Operations
            </button>
        <?php endif; ?>
    </div>

    <section class="dashboard-panel p-4" data-panel="overview">
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 p-4">
                <h3 class="font-black text-slate-800">
                    <i class="fas fa-chart-line mr-1 text-indigo-600"></i>
                    <?php echo e($isCustomer ? 'My Purchase Trend' : 'Sales Trend'); ?>

                </h3>
                <p class="mb-3 text-xs text-slate-400">Last 7 months</p>
                <div class="h-72"><canvas id="salesTrendChart"></canvas></div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <h3 class="font-black text-slate-800">
                    <i class="fas fa-chart-column mr-1 text-emerald-600"></i>
                    <?php echo e($isCustomer ? 'My Orders Trend' : 'Orders Trend'); ?>

                </h3>
                <p class="mb-3 text-xs text-slate-400">Last 7 months</p>
                <div class="h-72"><canvas id="ordersTrendChart"></canvas></div>
            </div>
        </div>
    </section>

    <section class="dashboard-panel hidden p-4" data-panel="activity">
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="border-b bg-slate-50 px-4 py-3 font-black text-slate-700">
                    <i class="fas fa-receipt mr-1 text-indigo-500"></i>
                    <?php echo e($isCustomer ? 'My Recent Purchases' : 'Recent Sales'); ?>

                </div>
                <?php $__empty_1 = true; $__currentLoopData = $recentSales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 px-4 py-3 text-sm">
                        <div class="truncate font-semibold"><?php echo e($sale->invoice_no ?? ('Sale #'.$sale->id)); ?></div>
                        <div class="text-right font-bold"><?php echo e(number_format((float)$sale->total_amount,0)); ?></div>
                        <div class="text-right text-xs text-slate-400"><?php echo e(optional($sale->sale_date)->format('d M Y') ?? $sale->sale_date); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="px-4 py-8 text-center text-sm text-slate-400">No recent purchases.</div>
                <?php endif; ?>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="border-b bg-slate-50 px-4 py-3 font-black text-slate-700">
                    <i class="fas fa-cart-shopping mr-1 text-amber-500"></i>
                    <?php echo e($isCustomer ? 'My Recent Orders' : 'Recent Online Orders'); ?>

                </div>
                <?php $__empty_1 = true; $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="grid grid-cols-3 gap-2 border-b border-slate-100 px-4 py-3 text-sm">
                        <div class="truncate font-semibold"><?php echo e($order->order_number ?? $order->order_id ?? ('Order #'.$order->id)); ?></div>
                        <div class="truncate"><?php echo e($order->customer_name ?? 'Customer'); ?></div>
                        <div class="text-right text-xs font-semibold text-slate-500"><?php echo e(str_replace('_',' ', $order->status ?? 'pending')); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="px-4 py-8 text-center text-sm text-slate-400">No recent orders.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (! ($isCustomer)): ?>
        <section class="dashboard-panel hidden p-4" data-panel="operations">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-black uppercase text-slate-400">Monthly Best Branch</div>
                            <div class="mt-2 text-xl font-black text-slate-800"><?php echo e($monthlyBestBranch?->name ?? 'No Sales Yet'); ?></div>
                            <div class="mt-1 text-sm font-bold text-sky-700">UGX <?php echo e(number_format((float)($monthlyBestBranch?->total_amount ?? 0),0)); ?></div>
                        </div>
                        <div class="grid h-12 w-12 place-items-center rounded-xl bg-sky-50 text-sky-600"><i class="fas fa-trophy text-xl"></i></div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-black uppercase text-slate-400">Monthly Suppliers</div>
                            <div class="mt-2 text-xl font-black text-slate-800"><?php echo e(number_format($monthlySuppliers->count())); ?></div>
                            <div class="mt-1 text-sm font-bold text-amber-700">UGX <?php echo e(number_format($monthlySupplierTotalAmount,0)); ?></div>
                        </div>
                        <div class="grid h-12 w-12 place-items-center rounded-xl bg-amber-50 text-amber-600"><i class="fas fa-truck-field text-xl"></i></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <?php $__empty_1 = true; $__currentLoopData = $monthlySuppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-black text-slate-800"><?php echo e($supplier->name); ?></div>
                                <div class="mt-1 text-xs text-slate-400"><?php echo e(number_format((int)$supplier->product_count)); ?> distinct product(s)</div>
                            </div>
                            <i class="fas fa-truck text-amber-500"></i>
                        </div>
                        <div class="mt-3 text-lg font-black text-slate-800">UGX <?php echo e(number_format((float)$supplier->total_amount,0)); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="col-span-full rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">No supplier activity this month.</div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
    const tabs = document.querySelectorAll('.dashboard-tab');
    const panels = document.querySelectorAll('.dashboard-panel');

    function activate(tab) {
        tabs.forEach(item => item.classList.remove('bg-indigo-600','text-white'));
        panels.forEach(panel => panel.classList.add('hidden'));
        tab.classList.add('bg-indigo-600','text-white');
        document.querySelector(`[data-panel="${tab.dataset.tab}"]`)?.classList.remove('hidden');
    }

    tabs.forEach(tab => tab.addEventListener('click', () => activate(tab)));
    if (tabs[0]) activate(tabs[0]);

    if (!window.Chart) return;

    const labels = <?php echo json_encode($chartLabels ?? [], 15, 512) ?>;
    const sales = <?php echo json_encode($chartSales ?? [], 15, 512) ?>;
    const orders = <?php echo json_encode($chartOrders ?? [], 15, 512) ?>;

    new Chart(document.getElementById('salesTrendChart'), {
        type: 'line',
        data: { labels, datasets: [{ data: sales, tension:.35, fill:true, borderWidth:2, pointRadius:3 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
    });

    new Chart(document.getElementById('ordersTrendChart'), {
        type: 'bar',
        data: { labels, datasets: [{ data: orders, borderWidth:1, borderRadius:8 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/dashboard.blade.php ENDPATH**/ ?>