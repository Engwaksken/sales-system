<?php $__env->startSection('title', 'Edit EfrisConfig'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit EfrisConfig</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('efris_config.update', $efris_config->id)); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    <?php $__currentLoopData = $branch_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e(($efris_config->branch_id == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Tin</label>
  <input type="text" name="tin" value="<?php echo e($efris_config->tin); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Name</label>
  <input type="text" name="taxpayer_name" value="<?php echo e($efris_config->taxpayer_name); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Address</label>
  <textarea name="taxpayer_address" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e($efris_config->taxpayer_address); ?></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Email</label>
  <input type="text" name="taxpayer_email" value="<?php echo e($efris_config->taxpayer_email); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Phone</label>
  <input type="text" name="taxpayer_phone" value="<?php echo e($efris_config->taxpayer_phone); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Business Type</label>
  <input type="text" name="business_type" value="<?php echo e($efris_config->business_type); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="vat_registered" value="0">
  <input type="checkbox" name="vat_registered" value="1" <?php echo e($efris_config->vat_registered ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Vat Registered</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Environment</label>
  <select name="environment" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='sandbox' <?php echo e(($efris_config->environment == 'sandbox') ? 'selected' : ''); ?>>sandbox</option><option value='production' <?php echo e(($efris_config->environment == 'production') ? 'selected' : ''); ?>>production</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Private Key Pem</label>
  <textarea name="private_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e($efris_config->private_key_pem); ?></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Public Key Pem</label>
  <textarea name="public_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e($efris_config->public_key_pem); ?></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Ura Public Key Pem</label>
  <textarea name="ura_public_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e($efris_config->ura_public_key_pem); ?></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Portal Username</label>
  <input type="text" name="portal_username" value="<?php echo e($efris_config->portal_username); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Portal Password Enc</label>
  <textarea name="portal_password_enc" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e($efris_config->portal_password_enc); ?></textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" <?php echo e($efris_config->is_active ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By</label>
  <select name="created_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By --</option>
    <?php $__currentLoopData = $created_by_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e(($efris_config->created_by == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="<?php echo e(route('efris_config.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/efris_config/edit.blade.php ENDPATH**/ ?>