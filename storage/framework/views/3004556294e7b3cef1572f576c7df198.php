<?php $__env->startSection('title', 'Setting Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">Setting #<?php echo e($setting->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Setting Key</span><span class='text-slate-800'><?php echo e($setting->setting_key); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Setting Value</span><span class='text-slate-800'><?php echo e($setting->setting_value); ?></span></div>
</div>
<a href="<?php echo e(route('settings.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/settings/show.blade.php ENDPATH**/ ?>