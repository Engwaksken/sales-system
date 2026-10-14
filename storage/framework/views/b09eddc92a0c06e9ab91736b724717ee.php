<?php $__env->startSection('title', 'New Payment'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">New Payment</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('payments.store')); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Transaction Type</label>
  <select name="transaction_type" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='sale' <?php echo e((old('transaction_type') == 'sale') ? 'selected' : ''); ?>>sale</option><option value='purchase' <?php echo e((old('transaction_type') == 'purchase') ? 'selected' : ''); ?>>purchase</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Invoice No</label>
  <input type="text" name="invoice_no" value="<?php echo e(old('invoice_no')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Receipt No</label>
  <input type="text" name="receipt_no" value="<?php echo e(old('receipt_no')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Customer Id</label>
  <select name="customer_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Customer Id --</option>
    <?php $__currentLoopData = $customer_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e((old('customer_id') == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Supplier Id</label>
  <select name="supplier_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Supplier Id --</option>
    <?php $__currentLoopData = $supplier_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e((old('supplier_id') == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sale Id</label>
  <select name="sale_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Sale Id --</option>
    <?php $__currentLoopData = $sale_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e((old('sale_id') == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->id); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
  <input type="number" step='0.01' name="amount" value="<?php echo e(old('amount')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Method</label>
  <input type="text" name="method" value="<?php echo e(old('method')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
  <select name="status" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='successful' <?php echo e((old('status') == 'successful') ? 'selected' : ''); ?>>successful</option><option value='failed' <?php echo e((old('status') == 'failed') ? 'selected' : ''); ?>>failed</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Received By</label>
  <select name="received_by" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Received By --</option>
    <?php $__currentLoopData = $received_by_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e((old('received_by') == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="<?php echo e(route('payments.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/payments/create.blade.php ENDPATH**/ ?>