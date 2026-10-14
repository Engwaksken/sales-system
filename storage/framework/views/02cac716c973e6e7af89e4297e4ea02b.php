<?php $__env->startSection('title', 'ProductBranches'); ?>
<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-code-branch text-slate-400"></i> ProductBranches</h1>
    <button type="button" onclick="openCreateModal('product_branches-modal', 'product_branches-form', '<?php echo e(route('product_branches.store')); ?>')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New ProductBranch</button>
</div>
<div class="grid grid-cols-1 md:grid-cols-1 gap-4 mb-6">
<?php echo $__env->make('partials.stat-card', ['icon' => 'fa-code-branch', 'color' => 'indigo', 'label' => 'Total ProductBranches', 'value' => $items->total()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php ($isMoney = false); ?>
<?php echo $__env->make('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<form method="GET" class="flex items-center gap-2 text-sm mb-4">
    
    <label class="text-slate-500 ml-2"><i class="fa-solid fa-filter"></i> Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" <?php echo e(request('period','all')=='all'?'selected':''); ?>>All Time</option>
        <option value="today" <?php echo e(request('period')=='today'?'selected':''); ?>>Today</option>
        <option value="week" <?php echo e(request('period')=='week'?'selected':''); ?>>This Week</option>
        <option value="month" <?php echo e(request('period')=='month'?'selected':''); ?>>This Month</option>
        <option value="year" <?php echo e(request('period')=='year'?'selected':''); ?>>This Year</option>
    </select>
    <?php if(request('q') || (request('period') && request('period') !== 'all')): ?>
    <a href="<?php echo e(route('product_branches.index')); ?>" class="text-slate-400 hover:text-slate-600 text-xs">Clear <i class="fa-solid fa-xmark"></i></a>
    <?php endif; ?>
</form>
<details class="mb-4 bg-white rounded-lg shadow">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="<?php echo e(route('product_branches.export.csv')); ?>" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="product_id" checked class="rounded border-slate-300"> Product</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="<?php echo e(request('period', 'all')); ?>">
    <input type="hidden" name="q" value="<?php echo e(request('q', '')); ?>">
    <div class="flex items-center gap-2">
      <button formaction="<?php echo e(route('product_branches.export.csv')); ?>" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="<?php echo e(route('product_branches.export.pdf')); ?>" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
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
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Product</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Branch</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
<?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<tr><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->product?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->branch?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->createdBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->updatedBy?->name ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->created_at?->format('d M Y H:i') ?? '—'); ?></td><td class='px-4 py-2 text-sm text-slate-500'><?php echo e($row->updated_at?->format('d M Y H:i') ?? '—'); ?></td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("product_branches-modal", "product_branches-form", <?php echo json_encode($row, 15, 512) ?>)' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("product_branches-modal", "product_branches-form", "<?php echo e(route('product_branches.update', $row->id)); ?>", <?php echo json_encode($row, 15, 512) ?>)' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="<?php echo e(route('product_branches.destroy', $row->id)); ?>" class="inline" onsubmit="return confirm('Delete this record?')">
<?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr><td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="mt-4"><?php echo $__env->make('partials.table-pagination', ['paginator' => $items], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>

<div id="product_branches-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="product_branches-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-code-branch text-indigo-500"></i> New ProductBranch</h2>
      <button type="button" onclick="closeModal('product_branches-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form id="product_branches-form" method="POST" action="<?php echo e(route('product_branches.store')); ?>" class="space-y-4">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="_method" value="POST">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Id</label>
  <select name="product_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Product Id --</option>
    <?php $__currentLoopData = $product_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    <?php $__currentLoopData = $branch_options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($opt->id); ?>"><?php echo e($opt->name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('product_branches-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/product_branches/index.blade.php ENDPATH**/ ?>