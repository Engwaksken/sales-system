<?php $__env->startSection('title', 'Orders'); ?>
<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-cart-shopping text-slate-400"></i> Orders</h1>
    <button type="button" onclick="openCreateModal('orders-modal', 'orders-form', '<?php echo e(route('orders.store')); ?>')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New Order</button>
</div>
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
<?php echo $__env->make('partials.stat-card', ['icon' => 'fa-cart-shopping', 'color' => 'indigo', 'label' => 'Total Orders', 'value' => $items->total()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.stat-card', ['icon' => 'fa-coins', 'color' => 'indigo', 'label' => 'Total Total Amount', 'value' => $stat1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.stat-card', ['icon' => 'fa-hourglass-half', 'color' => 'amber', 'label' => 'Pending', 'value' => $stat2], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php ($isMoney = true); ?>
<?php echo $__env->make('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<form method="GET" class="flex items-center gap-2 text-sm mb-4">
    <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
        <input type="text" name="q" value="<?php echo e(request('q')); ?>" placeholder="Search orders…"
               class="rounded-md border-slate-300 text-sm pl-8 pr-3 py-1.5 w-56">
    </div>
    <button class="text-sm text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-right"></i></button>
    <label class="text-slate-500 ml-2"><i class="fa-solid fa-filter"></i> Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" <?php echo e(request('period','all')=='all'?'selected':''); ?>>All Time</option>
        <option value="today" <?php echo e(request('period')=='today'?'selected':''); ?>>Today</option>
        <option value="week" <?php echo e(request('period')=='week'?'selected':''); ?>>This Week</option>
        <option value="month" <?php echo e(request('period')=='month'?'selected':''); ?>>This Month</option>
        <option value="year" <?php echo e(request('period')=='year'?'selected':''); ?>>This Year</option>
    </select>
    <?php if(request('q') || (request('period') && request('period') !== 'all')): ?>
    <a href="<?php echo e(route('orders.index')); ?>" class="text-slate-400 hover:text-slate-600 text-xs">Clear <i class="fa-solid fa-xmark"></i></a>
    <?php endif; ?>
</form>
<details class="mb-4 bg-white rounded-lg shadow">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="<?php echo e(route('orders.export.csv')); ?>" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="sale_id" checked class="rounded border-slate-300"> Sale</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="order_id" checked class="rounded border-slate-300"> Order Id</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="customer_id" checked class="rounded border-slate-300"> Customer</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="customer_name" checked class="rounded border-slate-300"> Customer Name</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="phone" checked class="rounded border-slate-300"> Phone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="email" checked class="rounded border-slate-300"> Email</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_location" checked class="rounded border-slate-300"> Delivery Location</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_zone_id" checked class="rounded border-slate-300"> Delivery Zone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="payment_method" checked class="rounded border-slate-300"> Payment Method</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_id" checked class="rounded border-slate-300"> Gateway</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_code" checked class="rounded border-slate-300"> Gateway Code</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_reference" checked class="rounded border-slate-300"> Gateway Reference</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_status" checked class="rounded border-slate-300"> Gateway Status</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="gateway_message" checked class="rounded border-slate-300"> Gateway Message</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="mm_phone" checked class="rounded border-slate-300"> Mm Phone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="mm_network" checked class="rounded border-slate-300"> Mm Network</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="payment_status" checked class="rounded border-slate-300"> Payment Status</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="total_amount" checked class="rounded border-slate-300"> Total Amount</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="discount" checked class="rounded border-slate-300"> Discount</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="amount_paid" checked class="rounded border-slate-300"> Amount Paid</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="balance_amount" checked class="rounded border-slate-300"> Balance Amount</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="status" checked class="rounded border-slate-300"> Status</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="orders_count" checked class="rounded border-slate-300"> Orders Count</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="notes" checked class="rounded border-slate-300"> Notes</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="is_guest_order" checked class="rounded border-slate-300"> Is Guest Order</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by_user_id" checked class="rounded border-slate-300"> Created By User</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="paid_at" checked class="rounded border-slate-300"> Paid At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_zone" checked class="rounded border-slate-300"> Delivery Zone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="delivery_fee" checked class="rounded border-slate-300"> Delivery Fee</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="subtotal" checked class="rounded border-slate-300"> Subtotal</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="order_number" checked class="rounded border-slate-300"> Order Number</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="address" checked class="rounded border-slate-300"> Address</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="<?php echo e(request('period', 'all')); ?>">
    <input type="hidden" name="q" value="<?php echo e(request('q', '')); ?>">
    <div class="flex items-center gap-2">
      <button formaction="<?php echo e(route('orders.export.csv')); ?>" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="<?php echo e(route('orders.export.pdf')); ?>" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
    </div>
  </form>
</details>
<?php if(session('status')): ?>
<div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Order Number</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Customer Name</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Total Amount</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Status</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Payment Status</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
<?php $__empty_0 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_0 = false; ?>
<tr><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->order_number); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->customer_name); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->total_amount); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->status); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->payment_status); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->createdBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->updatedBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->created_at?->format('d M Y H:i') ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->updated_at?->format('d M Y H:i') ?? '—'); ?></td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("orders-modal", "orders-form", <?php echo json_encode($row, 15, 512) ?>)' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("orders-modal", "orders-form", "<?php echo e(route('orders.update', $row->id)); ?>", <?php echo json_encode($row, 15, 512) ?>)' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="<?php echo e(route('orders.destroy', $row->id)); ?>" class="inline" onsubmit="return confirm('Delete this record?')">
<?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_0): ?>
<tr><td colspan="10" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="mt-4"><?php echo $__env->make('partials.table-pagination', ['paginator' => $items], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>

<div id="orders-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="orders-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-cart-shopping text-indigo-500"></i> New Order</h2>
      <button type="button" onclick="closeModal('orders-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form id="orders-form" method="POST" action="<?php echo e(route('orders.store')); ?>" class="space-y-4">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="_method" value="POST">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sale Id</label>
  <select name="sale_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Sale Id --</option>
    <?php $__currentLoopData = $sale_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->id); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Id</label>
  <input type="text" name="order_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Id</label>
  <select name="customer_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Customer Id --</option>
    <?php $__currentLoopData = $customer_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Name</label>
  <input type="text" name="customer_name" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
  <input type="text" name="phone" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
  <input type="text" name="email" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Location</label>
  <textarea name="delivery_location" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" required></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Zone Id</label>
  <select name="delivery_zone_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Delivery Zone Id --</option>
    <?php $__currentLoopData = $delivery_zone_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->zone_name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    <?php $__currentLoopData = $branch_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method</label>
  <input type="text" name="payment_method" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Id</label>
  <select name="gateway_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Gateway Id --</option>
    <?php $__currentLoopData = $gateway_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->display_name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Code</label>
  <input type="text" name="gateway_code" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Reference</label>
  <input type="text" name="gateway_reference" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Status</label>
  <input type="text" name="gateway_status" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Message</label>
  <textarea name="gateway_message" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Mm Phone</label>
  <input type="text" name="mm_phone" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Mm Network</label>
  <input type="text" name="mm_network" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Payment Status</label>
  <input type="text" name="payment_status" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Total Amount</label>
  <input type="number" step='0.01' name="total_amount" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Discount</label>
  <input type="number" step='0.01' name="discount" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount Paid</label>
  <input type="number" step='0.01' name="amount_paid" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Balance Amount</label>
  <input type="number" step='0.01' name="balance_amount" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
  <input type="text" name="status" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Orders Count</label>
  <input type="number" name="orders_count" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
  <textarea name="notes" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_guest_order" value="0">
  <input type="checkbox" name="is_guest_order" value="1" class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Guest Order</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By User Id</label>
  <select name="created_by_user_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By User Id --</option>
    <?php $__currentLoopData = $created_by_user_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Paid At</label>
  <input type="datetime-local" name="paid_at" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Zone</label>
  <input type="text" name="delivery_zone" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Fee</label>
  <input type="number" step='0.01' name="delivery_fee" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Subtotal</label>
  <input type="number" step='0.01' name="subtotal" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Number</label>
  <input type="text" name="order_number" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
  <textarea name="address" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('orders-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/orders/index.blade.php ENDPATH**/ ?>