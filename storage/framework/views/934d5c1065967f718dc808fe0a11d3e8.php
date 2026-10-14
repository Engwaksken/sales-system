<?php $__env->startSection('title', 'EfrisApiLog Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">EfrisApiLog #<?php echo e($efris_api_log->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Config Id</span><span class='text-slate-800'><?php echo e($efris_api_log->config?->id ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Operation Code</span><span class='text-slate-800'><?php echo e($efris_api_log->operation_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Operation Name</span><span class='text-slate-800'><?php echo e($efris_api_log->operation_name); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Http Method</span><span class='text-slate-800'><?php echo e($efris_api_log->http_method); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Endpoint Url</span><span class='text-slate-800'><?php echo e($efris_api_log->endpoint_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Reference Type</span><span class='text-slate-800'><?php echo e($efris_api_log->reference_type); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Reference Id</span><span class='text-slate-800'><?php echo e($efris_api_log->reference_id); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Request Body</span><span class='text-slate-800'><?php echo e($efris_api_log->request_body); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Response Body</span><span class='text-slate-800'><?php echo e($efris_api_log->response_body); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Http Status Code</span><span class='text-slate-800'><?php echo e($efris_api_log->http_status_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Efris Return Code</span><span class='text-slate-800'><?php echo e($efris_api_log->efris_return_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Efris Return Msg</span><span class='text-slate-800'><?php echo e($efris_api_log->efris_return_msg); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Success</span><span class='text-slate-800'><?php echo e($efris_api_log->is_success ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Latency Ms</span><span class='text-slate-800'><?php echo e($efris_api_log->latency_ms); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Requested At</span><span class='text-slate-800'><?php echo e($efris_api_log->requested_at); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Responded At</span><span class='text-slate-800'><?php echo e($efris_api_log->responded_at); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($efris_api_log->createdBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'><?php echo e($efris_api_log->updatedBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'><?php echo e($efris_api_log->created_at?->format('d M Y H:i') ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'><?php echo e($efris_api_log->updated_at?->format('d M Y H:i') ?? '—'); ?></span></div>
</div>
<a href="<?php echo e(route('efris_api_logs.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/efris_api_logs/show.blade.php ENDPATH**/ ?>