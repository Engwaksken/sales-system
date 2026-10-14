<?php $__env->startSection('title', 'Customer Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Customer #<?php echo e($customer->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Branch Id</span><span class='text-slate-800'><?php echo e($customer->branch?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Name</span><span class='text-slate-800'><?php echo e($customer->name); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Phone</span><span class='text-slate-800'><?php echo e($customer->phone); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Email</span><span class='text-slate-800'><?php echo e($customer->email); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Address</span><span class='text-slate-800'><?php echo e($customer->address); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($customer->createdBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'><?php echo e($customer->updatedBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'><?php echo e($customer->created_at?->format('d M Y H:i') ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'><?php echo e($customer->updated_at?->format('d M Y H:i') ?? '—'); ?></span></div>
</div>
<a href="<?php echo e(route('customers.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/customers/show.blade.php ENDPATH**/ ?>