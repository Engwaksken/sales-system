<div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
    <?php echo $__env->make('partials.stat-card', [
        'icon' => 'fa-building',
        'color' => 'sky',
        'label' => 'Branches',
        'value' => $branchStat ?? 0
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('partials.stat-card', [
        'icon' => 'fa-truck',
        'color' => 'amber',
        'label' => 'Suppliers',
        'value' => $supplierStat ?? 0
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('partials.stat-card', [
        'icon' => 'fa-filter-circle-dollar',
        'color' => 'indigo',
        'label' => $filteredStatLabel ?? 'Filtered Records',
        'value' => $filteredStat ?? 0
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('partials.stat-card', [
        'icon' => 'fa-database',
        'color' => 'emerald',
        'label' => $totalStatLabel ?? 'All Records',
        'value' => $totalStat ?? 0
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/branch-supplier-stat-cards.blade.php ENDPATH**/ ?>