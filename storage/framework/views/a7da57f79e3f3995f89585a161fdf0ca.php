<?php $__env->startSection('title', 'OrderItem Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">OrderItem #<?php echo e($order_item->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Order Id</span><span class='text-slate-800'><?php echo e($order_item->order?->id ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Product Id</span><span class='text-slate-800'><?php echo e($order_item->product?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Product Name</span><span class='text-slate-800'><?php echo e($order_item->product_name); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Sku</span><span class='text-slate-800'><?php echo e($order_item->sku); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Quantity</span><span class='text-slate-800'><?php echo e($order_item->quantity); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Price</span><span class='text-slate-800'><?php echo e($order_item->price); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Subtotal</span><span class='text-slate-800'><?php echo e($order_item->subtotal); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Cost Price</span><span class='text-slate-800'><?php echo e($order_item->cost_price); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($order_item->createdBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'><?php echo e($order_item->updatedBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'><?php echo e($order_item->created_at?->format('d M Y H:i') ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'><?php echo e($order_item->updated_at?->format('d M Y H:i') ?? '—'); ?></span></div>
</div>
<a href="<?php echo e(route('order_items.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/order_items/show.blade.php ENDPATH**/ ?>