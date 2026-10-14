<?php $__env->startComponent('mail::message'); ?>
# Order Update: <?php echo e($stageLabel); ?>


Hello <?php echo e($order->customer_name); ?>,

<?php echo e($customerMessage); ?>


**Order reference:** <?php echo e($order->order_number); ?>  
**Current stage:** <?php echo e($stageLabel); ?>  
**Order total:** UGX <?php echo e(number_format((float) $order->total_amount, 0)); ?>  
**Payment status:** <?php echo e(ucwords(str_replace('_', ' ', $order->payment_status))); ?>


<?php if(in_array($order->status, ['ready_for_pickup', 'ready_for_delivery'])): ?>
Your order has now been transferred to our POS/payment desk for final invoicing and payment processing.
<?php endif; ?>

<?php if($order->status === 'ready_for_pickup'): ?>
Please wait for the pickup team to confirm the collection point and timing before travelling.
<?php endif; ?>

<?php if($order->status === 'ready_for_delivery'): ?>
Our team is preparing the order for dispatch to **<?php echo e($order->delivery_location); ?>**.
<?php endif; ?>

<?php if($order->status === 'out_for_delivery'): ?>
Your order is on the way to **<?php echo e($order->delivery_location); ?>**.
<?php endif; ?>

<?php if($order->status === 'completed'): ?>
Thank you for shopping with us. Your order is complete.
<?php endif; ?>

Thanks,<br>
<?php echo e($order->branch?->name ?? config('app.name')); ?>

<?php echo $__env->renderComponent(); ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/emails/order-stage-updated.blade.php ENDPATH**/ ?>