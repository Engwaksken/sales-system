<?php $__env->startComponent('mail::message'); ?>
# Your Invoice

Thank you for your purchase. Invoice **<?php echo e($sale->invoice_no); ?>** is attached to this email.

**Date:** <?php echo e($sale->sale_date); ?>

**Total:** UGX <?php echo e(number_format($sale->total_amount, 0)); ?>

**Payment method:** <?php echo e($sale->payment_method); ?>


If you have any questions about this invoice, just reply to this email.

Thanks,<br>
<?php echo e(config('app.name')); ?>

<?php echo $__env->renderComponent(); ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/emails/invoice.blade.php ENDPATH**/ ?>