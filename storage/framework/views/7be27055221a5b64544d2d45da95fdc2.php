<?php $__env->startSection('title', 'PaymentGateway Details'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="text-xl font-semibold text-slate-800 mb-4">PaymentGateway #<?php echo e($payment_gateway->id); ?></h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Name</span><span class='text-slate-800'><?php echo e($payment_gateway->gateway_name); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Gateway Code</span><span class='text-slate-800'><?php echo e($payment_gateway->gateway_code); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Display Name</span><span class='text-slate-800'><?php echo e($payment_gateway->display_name); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Provider Type</span><span class='text-slate-800'><?php echo e($payment_gateway->provider_type); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Description</span><span class='text-slate-800'><?php echo e($payment_gateway->description); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Active</span><span class='text-slate-800'><?php echo e($payment_gateway->is_active ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Default</span><span class='text-slate-800'><?php echo e($payment_gateway->is_default ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Sandbox Mode</span><span class='text-slate-800'><?php echo e($payment_gateway->sandbox_mode ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Token Url</span><span class='text-slate-800'><?php echo e($payment_gateway->token_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Collect Url</span><span class='text-slate-800'><?php echo e($payment_gateway->collect_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Base Url</span><span class='text-slate-800'><?php echo e($payment_gateway->base_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Status Url</span><span class='text-slate-800'><?php echo e($payment_gateway->status_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Client Id</span><span class='text-slate-800'><?php echo e($payment_gateway->client_id); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Client Secret</span><span class='text-slate-800'><?php echo e($payment_gateway->client_secret); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Wallet Guid</span><span class='text-slate-800'><?php echo e($payment_gateway->wallet_guid); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Callback Url</span><span class='text-slate-800'><?php echo e($payment_gateway->callback_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Return Url</span><span class='text-slate-800'><?php echo e($payment_gateway->return_url); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Supports Collection</span><span class='text-slate-800'><?php echo e($payment_gateway->supports_collection ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Supports Disbursement</span><span class='text-slate-800'><?php echo e($payment_gateway->supports_disbursement ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Supports Mtn</span><span class='text-slate-800'><?php echo e($payment_gateway->supports_mtn ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Supports Airtel</span><span class='text-slate-800'><?php echo e($payment_gateway->supports_airtel ? 'Yes' : 'No'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'><?php echo e($payment_gateway->createdBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'><?php echo e($payment_gateway->updatedBy?->name ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'><?php echo e($payment_gateway->created_at?->format('d M Y H:i') ?? '—'); ?></span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'><?php echo e($payment_gateway->updated_at?->format('d M Y H:i') ?? '—'); ?></span></div>
</div>
<a href="<?php echo e(route('payment_gateways.index')); ?>" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/payment_gateways/show.blade.php ENDPATH**/ ?>