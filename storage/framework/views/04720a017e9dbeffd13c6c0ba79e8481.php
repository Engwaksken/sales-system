<?php $__env->startSection('title', 'Edit PaymentGateway'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit PaymentGateway</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('payment_gateways.update', $payment_gateway->id)); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Name</label>
  <input type="text" name="gateway_name" value="<?php echo e($payment_gateway->gateway_name); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Code</label>
  <input type="text" name="gateway_code" value="<?php echo e($payment_gateway->gateway_code); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Display Name</label>
  <input type="text" name="display_name" value="<?php echo e($payment_gateway->display_name); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Provider Type</label>
  <select name="provider_type" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='mobile_money' <?php echo e(($payment_gateway->provider_type == 'mobile_money') ? 'selected' : ''); ?>>mobile_money</option><option value='card' <?php echo e(($payment_gateway->provider_type == 'card') ? 'selected' : ''); ?>>card</option><option value='bank' <?php echo e(($payment_gateway->provider_type == 'bank') ? 'selected' : ''); ?>>bank</option><option value='wallet' <?php echo e(($payment_gateway->provider_type == 'wallet') ? 'selected' : ''); ?>>wallet</option><option value='mixed' <?php echo e(($payment_gateway->provider_type == 'mixed') ? 'selected' : ''); ?>>mixed</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e($payment_gateway->description); ?></textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" <?php echo e($payment_gateway->is_active ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_default" value="0">
  <input type="checkbox" name="is_default" value="1" <?php echo e($payment_gateway->is_default ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Default</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="sandbox_mode" value="0">
  <input type="checkbox" name="sandbox_mode" value="1" <?php echo e($payment_gateway->sandbox_mode ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Sandbox Mode</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Token Url</label>
  <input type="text" name="token_url" value="<?php echo e($payment_gateway->token_url); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Collect Url</label>
  <input type="text" name="collect_url" value="<?php echo e($payment_gateway->collect_url); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Base Url</label>
  <input type="text" name="base_url" value="<?php echo e($payment_gateway->base_url); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status Url</label>
  <input type="text" name="status_url" value="<?php echo e($payment_gateway->status_url); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Client Id</label>
  <input type="text" name="client_id" value="<?php echo e($payment_gateway->client_id); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Client Secret</label>
  <input type="text" name="client_secret" value="<?php echo e($payment_gateway->client_secret); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Wallet Guid</label>
  <input type="text" name="wallet_guid" value="<?php echo e($payment_gateway->wallet_guid); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Callback Url</label>
  <input type="text" name="callback_url" value="<?php echo e($payment_gateway->callback_url); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Return Url</label>
  <input type="text" name="return_url" value="<?php echo e($payment_gateway->return_url); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_collection" value="0">
  <input type="checkbox" name="supports_collection" value="1" <?php echo e($payment_gateway->supports_collection ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Collection</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_disbursement" value="0">
  <input type="checkbox" name="supports_disbursement" value="1" <?php echo e($payment_gateway->supports_disbursement ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Disbursement</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_mtn" value="0">
  <input type="checkbox" name="supports_mtn" value="1" <?php echo e($payment_gateway->supports_mtn ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Mtn</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_airtel" value="0">
  <input type="checkbox" name="supports_airtel" value="1" <?php echo e($payment_gateway->supports_airtel ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Airtel</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By</label>
  <select name="created_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By --</option>
    <?php $__currentLoopData = $created_by_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e(($payment_gateway->created_by == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Updated By</label>
  <select name="updated_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Updated By --</option>
    <?php $__currentLoopData = $updated_by_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>" <?php echo e(($payment_gateway->updated_by == $opt->id) ? 'selected' : ''); ?>><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="<?php echo e(route('payment_gateways.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/payment_gateways/edit.blade.php ENDPATH**/ ?>