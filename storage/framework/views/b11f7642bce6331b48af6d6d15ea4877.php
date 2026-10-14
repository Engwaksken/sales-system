<?php $__env->startSection('title', 'New EfrisDevice'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">New EfrisDevice</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('efris_devices.store')); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Config Id</label>
  <select name="config_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Config Id --</option>
    <?php $__currentLoopData = $config_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e((old('config_id') == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->id); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    <?php $__currentLoopData = $branch_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e((old('branch_id') == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Device Serial No</label>
  <input type="text" name="device_serial_no" value="<?php echo e(old('device_serial_no')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Device Type</label>
  <input type="text" name="device_type" value="<?php echo e(old('device_type')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Device Name</label>
  <input type="text" name="device_name" value="<?php echo e(old('device_name')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Activation Code</label>
  <input type="text" name="activation_code" value="<?php echo e(old('activation_code')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Activation Status</label>
  <select name="activation_status" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='pending' <?php echo e((old('activation_status') == 'pending') ? 'selected' : ''); ?>>pending</option><option value='activated' <?php echo e((old('activation_status') == 'activated') ? 'selected' : ''); ?>>activated</option><option value='suspended' <?php echo e((old('activation_status') == 'suspended') ? 'selected' : ''); ?>>suspended</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Activated At</label>
  <input type="datetime-local" name="activated_at" value="<?php echo e(old('activated_at')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Counter</label>
  <input type="number" name="invoice_counter" value="<?php echo e(old('invoice_counter')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
  <textarea name="notes" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e(old('notes')); ?></textarea>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="<?php echo e(route('efris_devices.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/efris_devices/create.blade.php ENDPATH**/ ?>