<?php $__env->startSection('title','Sign In'); ?>
<?php $__env->startSection('subtitle','Sales Management System'); ?>
<?php $__env->startSection('content'); ?>
<div class="auth-intro"><h2>Welcome back</h2><p>Sign in to your account.</p></div>
<form method="POST" action="<?php echo e(route('login')); ?>">
<?php echo csrf_field(); ?>
<div class="auth-field"><label for="email">Email address</label><div class="auth-control"><i class="fas fa-envelope leading-icon"></i><input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required autofocus></div></div>
<div class="auth-field"><label for="password">Password</label><div class="auth-control password-control"><i class="fas fa-lock leading-icon"></i><input id="password" type="password" name="password" autocomplete="current-password" required><button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Show password"><i class="fas fa-eye"></i></button></div></div>
<div class="auth-row"><label class="auth-check"><input type="checkbox" name="remember" value="1"> Remember me</label><a class="auth-link" href="<?php echo e(route('password.request')); ?>">Forgot password?</a></div>
<button class="auth-submit" type="submit"><i class="fas fa-right-to-bracket"></i> Sign in</button>

        <div class="auth-register-link">
            <span>New customer?</span>
            <a href="<?php echo e(route('customer.signup')); ?>">
                <i class="fas fa-user-plus"></i>
                Create Customer Account
            </a>
        </div>

</form>




<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/auth/login.blade.php ENDPATH**/ ?>