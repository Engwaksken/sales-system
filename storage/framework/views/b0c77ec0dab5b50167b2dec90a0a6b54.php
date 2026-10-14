<?php $__env->startSection('title', 'Edit ChartOfAccount'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit ChartOfAccount</h1>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('chart_of_accounts.update', $chart_of_account->id)); ?>" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
<?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Account Code</label>
  <input type="text" name="account_code" value="<?php echo e($chart_of_account->account_code); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Account Name</label>
  <input type="text" name="account_name" value="<?php echo e($chart_of_account->account_name); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Account Type</label>
  <select name="account_type" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='Asset' <?php echo e(($chart_of_account->account_type == 'Asset') ? 'selected' : ''); ?>>Asset</option><option value='Liability' <?php echo e(($chart_of_account->account_type == 'Liability') ? 'selected' : ''); ?>>Liability</option><option value='Equity' <?php echo e(($chart_of_account->account_type == 'Equity') ? 'selected' : ''); ?>>Equity</option><option value='Revenue' <?php echo e(($chart_of_account->account_type == 'Revenue') ? 'selected' : ''); ?>>Revenue</option><option value='COGS' <?php echo e(($chart_of_account->account_type == 'COGS') ? 'selected' : ''); ?>>COGS</option><option value='Expense' <?php echo e(($chart_of_account->account_type == 'Expense') ? 'selected' : ''); ?>>Expense</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Account Category</label>
  <input type="text" name="account_category" value="<?php echo e($chart_of_account->account_category); ?>" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Normal Balance</label>
  <select name="normal_balance" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='Debit' <?php echo e(($chart_of_account->normal_balance == 'Debit') ? 'selected' : ''); ?>>Debit</option><option value='Credit' <?php echo e(($chart_of_account->normal_balance == 'Credit') ? 'selected' : ''); ?>>Credit</option></select>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" <?php echo e($chart_of_account->is_active ? 'checked' : ''); ?> class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Parent Code</label>
  <input type="text" name="parent_code" value="<?php echo e($chart_of_account->parent_code); ?>" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ><?php echo e($chart_of_account->description); ?></textarea>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="<?php echo e(route('chart_of_accounts.index')); ?>" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/chart_of_accounts/edit.blade.php ENDPATH**/ ?>