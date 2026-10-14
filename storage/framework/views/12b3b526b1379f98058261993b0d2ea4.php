<?php ($color = $color ?? 'indigo'); ?>
<div class="stat-card bg-white rounded-lg shadow p-4 flex items-center gap-4">
    <div class="w-11 h-11 rounded-lg bg-<?php echo e($color); ?>-50 text-<?php echo e($color); ?>-600 flex items-center justify-center text-lg flex-shrink-0">
        <i class="fa-solid <?php echo e($icon); ?>"></i>
    </div>
    <div class="min-w-0">
        <div class="text-xs text-slate-400 uppercase truncate"><?php echo e($label); ?></div>
        <div class="text-xl font-semibold text-slate-800 truncate"><?php echo e($value); ?></div>
    </div>
</div>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/stat-card.blade.php ENDPATH**/ ?>