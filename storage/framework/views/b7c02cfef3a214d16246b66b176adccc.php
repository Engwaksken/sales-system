<?php $__env->startComponent('mail::message'); ?>
# Welcome, <?php echo e($user->name); ?>!

An account has been created for you on **<?php echo e(config('app.name')); ?>**.

- **Email:** <?php echo e($user->email); ?>

- **Role:** <?php echo e(ucfirst($user->role)); ?>


<?php $__env->startComponent('mail::button', ['url' => $loginUrl]); ?>
Log in now
<?php echo $__env->renderComponent(); ?>

If you weren't expecting this, please contact your administrator.

Thanks,<br>
<?php echo e(config('app.name')); ?>

<?php echo $__env->renderComponent(); ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/emails/welcome.blade.php ENDPATH**/ ?>