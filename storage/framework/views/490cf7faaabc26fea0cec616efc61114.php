<?php $__env->startSection('title', 'IotecCollection Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">IotecCollection #<?php echo e($iotec_collection->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Order Id</span><span class='text-slate-800'><?php echo e($iotec_collection->order_id); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Phone</span><span class='text-slate-800'><?php echo e($iotec_collection->phone); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Network</span><span class='text-slate-800'><?php echo e($iotec_collection->network); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Amount</span><span class='text-slate-800'><?php echo e($iotec_collection->amount); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Iotec Ref</span><span class='text-slate-800'><?php echo e($iotec_collection->iotec_ref); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Status</span><span class='text-slate-800'><?php echo e($iotec_collection->status); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Raw Response</span><span class='text-slate-800'><?php echo e($iotec_collection->raw_response); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($iotec_collection->createdBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'><?php echo e($iotec_collection->updatedBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'><?php echo e($iotec_collection->created_at?->format('d M Y H:i') ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'><?php echo e($iotec_collection->updated_at?->format('d M Y H:i') ?? '—'); ?></span></div>
</div>
<a href="<?php echo e(route('iotec_collections.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/iotec_collections/show.blade.php ENDPATH**/ ?>