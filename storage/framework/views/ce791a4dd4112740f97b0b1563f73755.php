<?php
    $showSearch = $showSearch ?? true;
    $searchPlaceholder = $searchPlaceholder ?? 'Search…';
    $clearRoute = $clearRoute ?? url()->current();
?>

<form method="GET" class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
        <?php if($showSearch): ?>
            <div class="xl:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="<?php echo e(request('q')); ?>"
                    placeholder="<?php echo e($searchPlaceholder); ?>"
                    class="w-full rounded-lg border-slate-300 text-sm"
                >
            </div>
        <?php endif; ?>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-calendar-days mr-1"></i> Period
            </label>
            <select name="period" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="all" <?php echo e(request('period', 'all') === 'all' ? 'selected' : ''); ?>>All Time</option>
                <option value="today" <?php echo e(request('period') === 'today' ? 'selected' : ''); ?>>Today</option>
                <option value="week" <?php echo e(request('period') === 'week' ? 'selected' : ''); ?>>This Week</option>
                <option value="month" <?php echo e(request('period') === 'month' ? 'selected' : ''); ?>>This Month</option>
                <option value="year" <?php echo e(request('period') === 'year' ? 'selected' : ''); ?>>This Year</option>
                <option value="range" <?php echo e(request('period') === 'range' ? 'selected' : ''); ?>>Custom Range</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-calendar-day mr-1"></i> From
            </label>
            <input
                type="date"
                name="from"
                value="<?php echo e(request('from')); ?>"
                class="w-full rounded-lg border-slate-300 text-sm"
            >
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-calendar-check mr-1"></i> To
            </label>
            <input
                type="date"
                name="to"
                value="<?php echo e(request('to')); ?>"
                class="w-full rounded-lg border-slate-300 text-sm"
            >
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-building mr-1"></i> Branch
            </label>
            <select name="branch_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Branches</option>
                <?php $__currentLoopData = ($branch_options ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($branch->id); ?>" <?php echo e((string)request('branch_id') === (string)$branch->id ? 'selected' : ''); ?>>
                        <?php echo e($branch->name ?: ('Branch #'.$branch->id)); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-truck mr-1"></i> Supplier
            </label>
            <select name="supplier_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Suppliers</option>
                <?php $__currentLoopData = ($supplier_options ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($supplier->id); ?>" <?php echo e((string)request('supplier_id') === (string)$supplier->id ? 'selected' : ''); ?>>
                        <?php echo e($supplier->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            <i class="fa-solid fa-filter mr-1"></i> Apply Filters
        </button>

        <a href="<?php echo e($clearRoute); ?>"
           class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
            <i class="fa-solid fa-rotate-left mr-1"></i> Clear
        </a>

        <?php if(request('from') || request('to')): ?>
            <span class="text-xs text-slate-400">
                Range:
                <?php echo e(request('from') ?: 'Beginning'); ?>

                →
                <?php echo e(request('to') ?: 'Today'); ?>

            </span>
        <?php endif; ?>
    </div>
</form>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/business-filter-bar.blade.php ENDPATH**/ ?>