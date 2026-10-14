<?php $__env->startSection('title', 'EFRIS Invoices'); ?>
<?php $__env->startSection('content'); ?>
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800">EFRIS Invoices</h1>
    <span class="text-sm text-slate-400">Submitted from a Sale's detail page &mdash; see "Submit to EFRIS"</span>
</div>
<?php if(session('status')): ?>
<div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Invoice No</th>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>TIN</th>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Gross Amount</th>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Status</th>
    <th class="px-4 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
<?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<tr>
    <td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->invoice_no); ?></td>
    <td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->tin); ?></td>
    <td class='px-4 py-2 text-sm text-slate-700'><?php echo e(number_format($row->gross_amount, 2)); ?></td>
    <td class='px-4 py-2 text-sm text-slate-700'><?php echo e($row->status); ?></td>
    <td class="px-4 py-2 text-right text-sm">
        <a href="<?php echo e(route('efris_invoices.show', $row->id)); ?>" class="text-slate-500 hover:text-slate-700">View</a>
    </td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">No EFRIS invoices submitted yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="mt-4"><?php echo $__env->make('partials.table-pagination', ['paginator' => $items], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/efris_invoices/index.blade.php ENDPATH**/ ?>