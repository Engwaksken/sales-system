<?php if(!empty($periodStats)): ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php echo $__env->make('partials.stat-card', ['icon' => 'fa-sun', 'color' => 'amber', 'label' => 'Today', 'value' => $isMoney ? 'UGX '.number_format($periodStats['today'], 0) : $periodStats['today']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.stat-card', ['icon' => 'fa-calendar-week', 'color' => 'sky', 'label' => 'This Week', 'value' => $isMoney ? 'UGX '.number_format($periodStats['week'], 0) : $periodStats['week']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.stat-card', ['icon' => 'fa-calendar-days', 'color' => 'violet', 'label' => 'This Month', 'value' => $isMoney ? 'UGX '.number_format($periodStats['month'], 0) : $periodStats['month']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.stat-card', ['icon' => 'fa-calendar', 'color' => 'rose', 'label' => 'This Year', 'value' => $isMoney ? 'UGX '.number_format($periodStats['year'], 0) : $periodStats['year']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php endif; ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/period-stats.blade.php ENDPATH**/ ?>