<?php $__env->startSection('title', 'New User'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">New User</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('users.store')); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
  <input type="text" name="name" value="<?php echo e(old('name')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
  <input type="text" name="email" value="<?php echo e(old('email')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
  <div class="relative"><input type="password" name="password" autocomplete="new-password" class="w-full rounded-md border-slate-300 shadow-sm pr-10"><button type="button" data-password-toggle class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" aria-label="Show password"><i class="fas fa-eye"></i></button></div>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
  <select name="role" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='admin' <?php echo e((old('role') == 'admin') ? 'selected' : ''); ?>>admin</option><option value='cashier' <?php echo e((old('role') == 'cashier') ? 'selected' : ''); ?>>cashier</option><option value='customer' <?php echo e((old('role') == 'customer') ? 'selected' : ''); ?>>customer</option><option value='manager' <?php echo e((old('role') == 'manager') ? 'selected' : ''); ?>>manager</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Branch Id --</option>
    <?php $__currentLoopData = $branch_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e((old('branch_id') == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Profile Pic</label>
  <input type="text" name="profile_pic" value="<?php echo e(old('profile_pic')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Verification Code</label>
  <input type="text" name="verification_code" value="<?php echo e(old('verification_code')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Code Expires</label>
  <input type="datetime-local" name="code_expires" value="<?php echo e(old('code_expires')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Remember Token</label>
  <input type="text" name="remember_token" value="<?php echo e(old('remember_token')); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="<?php echo e(route('users.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('partials.password-toggle-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/users/create.blade.php ENDPATH**/ ?>