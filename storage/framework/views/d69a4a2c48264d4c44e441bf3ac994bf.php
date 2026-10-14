<?php $__env->startSection('title', 'ChartOfAccount Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">ChartOfAccount #<?php echo e($chart_of_account->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Account Code</span><span class='text-slate-800'><?php echo e($chart_of_account->account_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Account Name</span><span class='text-slate-800'><?php echo e($chart_of_account->account_name); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Account Type</span><span class='text-slate-800'><?php echo e($chart_of_account->account_type); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Account Category</span><span class='text-slate-800'><?php echo e($chart_of_account->account_category); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Normal Balance</span><span class='text-slate-800'><?php echo e($chart_of_account->normal_balance); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Active</span><span class='text-slate-800'><?php echo e($chart_of_account->is_active ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Parent Code</span><span class='text-slate-800'><?php echo e($chart_of_account->parent_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Description</span><span class='text-slate-800'><?php echo e($chart_of_account->description); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($chart_of_account->createdBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'><?php echo e($chart_of_account->updatedBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'><?php echo e($chart_of_account->created_at?->format('d M Y H:i') ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'><?php echo e($chart_of_account->updated_at?->format('d M Y H:i') ?? '—'); ?></span></div>
</div>
<a href="<?php echo e(route('chart_of_accounts.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/chart_of_accounts/show.blade.php ENDPATH**/ ?>