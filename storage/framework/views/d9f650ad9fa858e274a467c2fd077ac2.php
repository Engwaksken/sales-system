<?php $__env->startSection('title', 'Report: '.str($type)->replace('_', ' ')->title()); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
    <div>
        <h1 class="text-xl font-black text-slate-800">
            <i class="fas fa-chart-column mr-2 text-indigo-600"></i>
            <?php echo e(str($type)->replace('_', ' ')->title()); ?> Report
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Reporting period:
            <?php echo e($from->format('d M Y')); ?> – <?php echo e($to->format('d M Y')); ?>

        </p>
    </div>

    <form method="GET" action="<?php echo e(route('reports.show', $type)); ?>"
          class="grid gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">From</label>
            <input type="date" name="from" value="<?php echo e($from->format('Y-m-d')); ?>"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">To</label>
            <input type="date" name="to" value="<?php echo e($to->format('Y-m-d')); ?>"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                <i class="fas fa-building mr-1"></i> Branch Name
            </label>
            <select name="branch_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Branches</option>
                <?php $__currentLoopData = $branch_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($branch->id); ?>" <?php if((string)request('branch_id') === (string)$branch->id): echo 'selected'; endif; ?>>
                        <?php echo e($branch->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                <i class="fas fa-truck mr-1"></i> Supplier Name
            </label>
            <select name="supplier_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Suppliers</option>
                <?php $__currentLoopData = $supplier_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($supplier->id); ?>" <?php if((string)request('supplier_id') === (string)$supplier->id): echo 'selected'; endif; ?>>
                        <?php echo e($supplier->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <?php if($type === 'general_ledger'): ?>
            <input type="hidden" name="account_code" value="<?php echo e($selectedAccountCode ?? ''); ?>">
            <input type="hidden" name="journal_type" value="<?php echo e($selectedJournalType ?? ''); ?>">
            <input type="hidden" name="q" value="<?php echo e($ledgerSearch ?? ''); ?>">
        <?php endif; ?>

        <div class="sm:col-span-2 xl:col-span-4 flex justify-end gap-2">
            <a href="<?php echo e(route('reports.show', $type)); ?>"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600">
                <i class="fas fa-rotate-left mr-1"></i> Clear
            </a>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
        </div>
    </form>
</div>

<div class="mb-5 flex flex-wrap gap-2">
    <?php $__currentLoopData = $reportTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reportType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('reports.show', $reportType).'?'.http_build_query(request()->only(['from','to','branch_id','supplier_id','per_page']))); ?>"
           class="rounded-full px-3 py-1.5 text-xs font-bold
                <?php echo e($type === $reportType
                    ? 'bg-indigo-600 text-white'
                    : 'border border-slate-200 bg-white text-slate-600 hover:border-indigo-300'); ?>">
            <i class="fas
                <?php echo e($reportType === 'general_ledger' ? 'fa-book-open' :
                   ($reportType === 'stock' ? 'fa-boxes-stacked' :
                   ($reportType === 'sales' ? 'fa-cash-register' : 'fa-chart-simple'))); ?>

                mr-1"></i>
            <?php echo e(str($reportType)->replace('_', ' ')->title()); ?>

        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php if($type === 'general_ledger'): ?>
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <?php $__currentLoopData = [
            ['Ledger Entries', number_format($ledgerSummary['entries'] ?? 0), 'fa-list-ol'],
            ['Opening Balance', $currency.' '.number_format((float)($ledgerSummary['opening_balance'] ?? 0), 2), 'fa-arrow-right-to-bracket'],
            ['Total Debits', $currency.' '.number_format((float)($ledgerSummary['total_debit'] ?? 0), 2), 'fa-arrow-trend-up'],
            ['Total Credits', $currency.' '.number_format((float)($ledgerSummary['total_credit'] ?? 0), 2), 'fa-arrow-trend-down'],
            ['Closing Balance', $currency.' '.number_format((float)($ledgerSummary['closing_balance'] ?? 0), 2), 'fa-scale-balanced'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wide text-slate-400"><?php echo e($label); ?></div>
                        <div class="mt-2 text-lg font-black text-slate-800"><?php echo e($value); ?></div>
                    </div>
                    <i class="fas <?php echo e($icon); ?> text-indigo-500"></i>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <form method="GET" action="<?php echo e(route('reports.show', 'general_ledger')); ?>"
          class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3">
        <input type="hidden" name="from" value="<?php echo e($from->format('Y-m-d')); ?>">
        <input type="hidden" name="to" value="<?php echo e($to->format('Y-m-d')); ?>">
        <input type="hidden" name="branch_id" value="<?php echo e(request('branch_id')); ?>">
        <input type="hidden" name="supplier_id" value="<?php echo e(request('supplier_id')); ?>">

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                Search Ledger
            </label>
            <input type="search" name="q" value="<?php echo e($ledgerSearch ?? ''); ?>"
                   placeholder="Journal, reference, account..."
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                Account Name
            </label>
            <select name="account_code" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Accounts</option>
                <?php $__currentLoopData = $accountOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($account->account_code); ?>"
                            <?php if(($selectedAccountCode ?? '') === $account->account_code): echo 'selected'; endif; ?>>
                        <?php echo e($account->account_code); ?> — <?php echo e($account->account_name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                Journal Type
            </label>
            <select name="journal_type" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Journal Types</option>
                <?php $__currentLoopData = $journalTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $journalType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($journalType); ?>"
                            <?php if(($selectedJournalType ?? '') === $journalType): echo 'selected'; endif; ?>>
                        <?php echo e(str($journalType)->replace('_', ' ')->title()); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="md:col-span-3 flex justify-end">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">
                <i class="fas fa-filter mr-1"></i> Filter Ledger
            </button>
        </div>
    </form>
<?php endif; ?>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    <?php $__currentLoopData = $headers ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                            <?php echo e($header); ?>

                        </th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                <?php $__empty_0 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_0 = false; ?>
                    <tr class="hover:bg-slate-50/70">
                        <?php $__currentLoopData = $row; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td class="px-4 py-3 align-top text-slate-700">
                                <?php if(in_array($key, $moneyColumns ?? [], true)): ?>
                                    <span class="whitespace-nowrap font-semibold">
                                        <?php echo e($currency); ?> <?php echo e(number_format((float)$value, 2)); ?>

                                    </span>
                                <?php elseif(is_bool($value)): ?>
                                    <?php echo e($value ? 'Yes' : 'No'); ?>

                                <?php elseif(is_scalar($value) || $value === null): ?>
                                    <?php echo e($value ?? '—'); ?>

                                <?php else: ?>
                                    
                                    <?php echo e(json_encode($value, JSON_UNESCAPED_UNICODE)); ?>

                                <?php endif; ?>
                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_0): ?>
                    <tr>
                        <td colspan="<?php echo e(max(count($headers ?? []), 1)); ?>"
                            class="px-4 py-12 text-center text-slate-400">
                            <i class="fas fa-folder-open mb-2 block text-3xl"></i>
                            No data found for the selected filters.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php if($rows instanceof \Illuminate\Contracts\Pagination\Paginator): ?>
    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <span>
                Showing
                <strong class="text-slate-700"><?php echo e(number_format($rows->firstItem() ?? 0)); ?></strong>
                –
                <strong class="text-slate-700"><?php echo e(number_format($rows->lastItem() ?? 0)); ?></strong>
                of
                <strong class="text-slate-700"><?php echo e(number_format($rows->total())); ?></strong>
            </span>

            <form method="GET" action="<?php echo e(route('reports.show', $type)); ?>" class="flex items-center gap-2">
                <?php $__currentLoopData = request()->except(['page', 'per_page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(is_array($value)): ?>
                        <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subKey => $subValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <input type="hidden" name="<?php echo e($key); ?>[<?php echo e($subKey); ?>]" value="<?php echo e($subValue); ?>">
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <label class="font-bold text-slate-500">Rows</label>
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="rounded-lg border-slate-300 bg-white px-2 py-1 text-xs">
                    <?php $__currentLoopData = [10,20,50,100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($size); ?>"
                            <?php if((int)request('per_page', $rows->perPage()) === $size): echo 'selected'; endif; ?>>
                            <?php echo e($size); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        </div>

        <div>
            <?php echo e($rows->onEachSide(1)->links()); ?>

        </div>
    </div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/reports/show.blade.php ENDPATH**/ ?>