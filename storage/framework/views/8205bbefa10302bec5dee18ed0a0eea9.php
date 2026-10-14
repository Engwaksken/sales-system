<?php $__env->startSection('title', 'New EmailSetting'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">New EmailSetting</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('email_settings.store')); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Host</label>
  <input type="text" name="smtp_host" value="<?php echo e(old('smtp_host')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Port</label>
  <input type="number" name="smtp_port" value="<?php echo e(old('smtp_port')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp User</label>
  <input type="text" name="smtp_user" value="<?php echo e(old('smtp_user')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Password</label>
  <div class="relative"><input type="password" name="smtp_password" autocomplete="new-password" class="w-full rounded-md border-slate-300 shadow-sm pr-10" required><button type="button" data-password-toggle class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" aria-label="Show password"><i class="fas fa-eye"></i></button></div>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Secure</label>
  <input type="text" name="smtp_secure" value="<?php echo e(old('smtp_secure')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">From Email</label>
  <input type="text" name="from_email" value="<?php echo e(old('from_email')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">From Name</label>
  <input type="text" name="from_name" value="<?php echo e(old('from_name')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="<?php echo e(route('email_settings.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('partials.password-toggle-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/email_settings/create.blade.php ENDPATH**/ ?>