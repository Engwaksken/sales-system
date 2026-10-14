<?php $__env->startComponent('mail::message'); ?>
# Thanks for your order, <?php echo e($order->customer_name); ?>!

Your order **<?php echo e($order->order_number); ?>** from **<?php echo e($branch->name); ?>** has been received.

<?php $__env->startComponent('mail::table'); ?>
| Item | Qty | Price | Subtotal |
|:-----|:---:|------:|---------:|
<?php $__currentLoopData = $order->order_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
| <?php echo e($item->product_name); ?> | <?php echo e($item->quantity); ?> | <?php echo e(number_format($item->price, 0)); ?> | <?php echo e(number_format($item->subtotal, 0)); ?> |
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php echo $__env->renderComponent(); ?>

**Delivery fee:** UGX <?php echo e(number_format($order->delivery_fee, 0)); ?>

**Total:** UGX <?php echo e(number_format($order->total_amount, 0)); ?>


**Delivery to:** <?php echo e($order->delivery_location); ?>

**Payment method:** <?php echo e(ucwords(str_replace('-', ' ', $order->payment_method))); ?>


<?php if($order->payment_method === 'mobile-money'): ?>
You'll receive a mobile money prompt on your phone shortly to complete payment.
<?php else: ?>
Please have payment ready for delivery.
<?php endif; ?>

Thanks,<br>
<?php echo e($branch->name); ?>

<?php echo $__env->renderComponent(); ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/emails/order-confirmation.blade.php ENDPATH**/ ?>