<?php $__env->startSection('title','Forgot Password'); ?>
<?php $__env->startSection('subtitle','Account recovery'); ?>
<?php $__env->startSection('content'); ?>
<div class="auth-intro"><h2>Forgot your password?</h2><p>Enter your registered email address and we will send you a 6-digit verification code.</p></div>
<form method="POST" action="<?php echo e(route('password.email')); ?>"><?php echo csrf_field(); ?>
<div class="auth-field"><label for="email">Email address</label><div class="auth-control"><i class="fas fa-envelope leading-icon"></i><input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required autofocus></div></div>
<button class="auth-submit" type="submit"><i class="fas fa-paper-plane"></i> Send verification code</button>
</form>
<a class="auth-back" href="<?php echo e(route('login')); ?>"><i class="fas fa-arrow-left"></i> Back to sign in</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/auth/forgot-password.blade.php ENDPATH**/ ?>