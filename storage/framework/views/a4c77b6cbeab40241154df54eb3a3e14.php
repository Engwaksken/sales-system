<?php $__env->startSection('title', 'EfrisApiLogs'); ?>
<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-list-check text-slate-400"></i> EfrisApiLogs</h1>
    
</div>
<div class="grid grid-cols-1 md:grid-cols-1 gap-4 mb-6">
<?php echo $__env->make('partials.stat-card', ['icon' => 'fa-list-check', 'color' => 'indigo', 'label' => 'Total EfrisApiLogs', 'value' => $items->total()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php ($isMoney = false); ?>
<?php echo $__env->make('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<form method="GET" class="flex items-center gap-2 text-sm mb-4">
    <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
        <input type="text" name="q" value="<?php echo e(request('q')); ?>" placeholder="Search efris api logs…"
               class="rounded-md border-slate-300 text-sm pl-8 pr-3 py-1.5 w-56">
    </div>
    <button class="text-sm text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-right"></i></button>
    <label class="text-slate-500 ml-2"><i class="fa-solid fa-filter"></i> Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" <?php echo e(request('period','all')=='all'?'selected':''); ?>>All Time</option>
        <option value="today" <?php echo e(request('period')=='today'?'selected':''); ?>>Today</option>
        <option value="week" <?php echo e(request('period')=='week'?'selected':''); ?>>This Week</option>
        <option value="month" <?php echo e(request('period')=='month'?'selected':''); ?>>This Month</option>
        <option value="year" <?php echo e(request('period')=='year'?'selected':''); ?>>This Year</option>
    </select>
    <?php if(request('q') || (request('period') && request('period') !== 'all')): ?>
    <a href="<?php echo e(route('efris_api_logs.index')); ?>" class="text-slate-400 hover:text-slate-600 text-xs">Clear <i class="fa-solid fa-xmark"></i></a>
    <?php endif; ?>
</form>
<details class="mb-4 bg-white rounded-lg shadow">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="<?php echo e(route('efris_api_logs.export.csv')); ?>" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="config_id" checked class="rounded border-slate-300"> Config</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="operation_code" checked class="rounded border-slate-300"> Operation Code</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="operation_name" checked class="rounded border-slate-300"> Operation Name</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="http_method" checked class="rounded border-slate-300"> Http Method</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="endpoint_url" checked class="rounded border-slate-300"> Endpoint Url</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="reference_type" checked class="rounded border-slate-300"> Reference Type</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="reference_id" checked class="rounded border-slate-300"> Reference Id</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="request_body" checked class="rounded border-slate-300"> Request Body</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="response_body" checked class="rounded border-slate-300"> Response Body</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="http_status_code" checked class="rounded border-slate-300"> Http Status Code</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="efris_return_code" checked class="rounded border-slate-300"> Efris Return Code</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="efris_return_msg" checked class="rounded border-slate-300"> Efris Return Msg</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="is_success" checked class="rounded border-slate-300"> Is Success</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="latency_ms" checked class="rounded border-slate-300"> Latency Ms</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="requested_at" checked class="rounded border-slate-300"> Requested At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="responded_at" checked class="rounded border-slate-300"> Responded At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="<?php echo e(request('period', 'all')); ?>">
    <input type="hidden" name="q" value="<?php echo e(request('q', '')); ?>">
    <div class="flex items-center gap-2">
      <button formaction="<?php echo e(route('efris_api_logs.export.csv')); ?>" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="<?php echo e(route('efris_api_logs.export.pdf')); ?>" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
    </div>
  </form>
</details>
<?php if(session('status')): ?>
<div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
</div>
<?php endif; ?>
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Operation Code</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Http Status Code</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Is Success</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Requested At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
<?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<tr><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->operation_code); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->http_status_code); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->is_success ? 'Yes' : 'No'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->requested_at); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->createdBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->updatedBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->created_at?->format('d M Y H:i') ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->updated_at?->format('d M Y H:i') ?? '—'); ?></td><td class="px-4 py-2 text-right text-sm">
<a href="<?php echo e(route('efris_api_logs.show', $row->id)); ?>" class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></a>
</td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr><td colspan="9" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="mt-4"><?php echo $__env->make('partials.table-pagination', ['paginator' => $items], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/efris_api_logs/index.blade.php ENDPATH**/ ?>