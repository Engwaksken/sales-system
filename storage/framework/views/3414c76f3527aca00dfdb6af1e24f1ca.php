<?php $__env->startComponent('mail::message'); ?>
# Payment Received

We've received your payment. Receipt **<?php echo e($receiptNo); ?>** is attached to this email.

**Amount:** UGX <?php echo e(number_format((float) $amount, 0)); ?>

**Method:** <?php echo e($method); ?>


Thanks,<br>
<?php echo e(config('app.name')); ?>

<?php echo $__env->renderComponent(); ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/emails/receipt.blade.php ENDPATH**/ ?>