<?php $__env->startSection('title', 'Invoice Preview'); ?>
<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
        <div>
            <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Document Preview</div>
            <h1 class="text-xl font-extrabold text-slate-800 mt-1">
                <i class="fas fa-file-invoice text-slate-400 mr-1"></i>
                Invoice <?php echo e($sale->invoice_no); ?>

            </h1>
            <p class="text-sm text-slate-500 mt-1">Review the invoice before downloading or printing it.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?php echo e(route('sales.index')); ?>" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-600 text-sm font-bold">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="<?php echo e(route('sales.invoice-print', ['sale'=>$sale->id,'paper'=>'80mm'])); ?>" target="_blank"
               class="px-4 py-2 rounded-lg bg-slate-800 text-white text-sm font-bold">
                <i class="fas fa-print"></i> Print 80mm / XP-80C
            </a>
            <a href="<?php echo e(route('sales.invoice-print', ['sale'=>$sale->id,'paper'=>'a5'])); ?>" target="_blank"
               class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-bold">
                <i class="fas fa-print"></i> A5
            </a>
            <a href="<?php echo e(route('sales.invoice-print', ['sale'=>$sale->id,'paper'=>'a4'])); ?>" target="_blank"
               class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-bold">
                <i class="fas fa-print"></i> A4
            </a>
            <a href="<?php echo e(route('sales.invoice-download', $sale->id)); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                <i class="fas fa-download"></i> Download After Preview
            </a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center gap-3">
            <?php if(!empty($siteSettings['system_logo'])): ?>
                <img src="<?php echo e(asset($siteSettings['system_logo'])); ?>" class="h-9 w-9 object-contain rounded-lg border border-slate-100 p-1 bg-white" alt="Logo">
            <?php endif; ?>
            <div>
                <strong class="block text-slate-800"><?php echo e($branding['site_name']); ?></strong>
                <span class="text-xs text-slate-400">Invoice preview · <?php echo e($sale->customer?->name ?? 'Walk-in Customer'); ?></span>
            </div>
        </div>
        <iframe
            id="invoiceFrame"
            src="<?php echo e(route('sales.invoice-pdf', $sale->id)); ?>"
            class="w-full border-0 bg-slate-100"
            style="height:calc(100vh - 260px);min-height:620px"
            title="Invoice <?php echo e($sale->invoice_no); ?> preview"></iframe>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/sales/invoice-preview.blade.php ENDPATH**/ ?>