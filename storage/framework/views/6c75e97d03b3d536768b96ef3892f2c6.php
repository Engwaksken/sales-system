<?php $__env->startSection('title', 'EfrisInvoice Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">EfrisInvoice #<?php echo e($efris_invoice->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Sale Id</span><span class='text-slate-800'><?php echo e($efris_invoice->sale_id); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Config Id</span><span class='text-slate-800'><?php echo e($efris_invoice->config_id); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Device Id</span><span class='text-slate-800'><?php echo e($efris_invoice->device_id); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Invoice No</span><span class='text-slate-800'><?php echo e($efris_invoice->invoice_no); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Tin</span><span class='text-slate-800'><?php echo e($efris_invoice->tin); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Issued At</span><span class='text-slate-800'><?php echo e($efris_invoice->issued_at); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gross Amount</span><span class='text-slate-800'><?php echo e($efris_invoice->gross_amount); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Status</span><span class='text-slate-800'><?php echo e($efris_invoice->status); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Qr Code</span><span class='text-slate-800'><?php echo e($efris_invoice->qr_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Verification Code</span><span class='text-slate-800'><?php echo e($efris_invoice->verification_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Request Json</span><span class='text-slate-800'><?php echo e($efris_invoice->request_json); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Response Json</span><span class='text-slate-800'><?php echo e($efris_invoice->response_json); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($efris_invoice->created_by); ?></span></div>
</div>
<a href="<?php echo e(route('efris_invoices.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/efris_invoices/show.blade.php ENDPATH**/ ?>