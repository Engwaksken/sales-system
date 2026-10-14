<?php $__env->startSection('title', 'Slide Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Slide #<?php echo e($slide->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Title</span><span class='text-slate-800'><?php echo e($slide->title); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Subtitle</span><span class='text-slate-800'><?php echo e($slide->subtitle); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Badge Text</span><span class='text-slate-800'><?php echo e($slide->badge_text); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Image Url</span><span class='text-slate-800'><?php echo e($slide->image_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Cta Label</span><span class='text-slate-800'><?php echo e($slide->cta_label); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Cta Link</span><span class='text-slate-800'><?php echo e($slide->cta_link); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Bg Color</span><span class='text-slate-800'><?php echo e($slide->bg_color); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Sort Order</span><span class='text-slate-800'><?php echo e($slide->sort_order); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Active</span><span class='text-slate-800'><?php echo e($slide->is_active ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($slide->createdBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'><?php echo e($slide->updatedBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'><?php echo e($slide->created_at?->format('d M Y H:i') ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'><?php echo e($slide->updated_at?->format('d M Y H:i') ?? '—'); ?></span></div>
</div>
<a href="<?php echo e(route('slides.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/slides/show.blade.php ENDPATH**/ ?>