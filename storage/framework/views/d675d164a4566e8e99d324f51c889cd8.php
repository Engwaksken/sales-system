<?php $__env->startSection('title', 'Customer Sign Up'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-heading">
    <h1>Create Customer Account</h1>
    <p>Register your customer account, then verify your email with the OTP we send you.</p>
</div>

<?php if($errors->any()): ?>
    <div class="auth-alert auth-alert-error">
        <i class="fas fa-circle-exclamation"></i>
        <div>
            <strong>We could not create your account.</strong>
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<form method="POST"
      action="<?php echo e(route('customer.register')); ?>"
      class="customer-signup-form auth-main-form">
    <?php echo csrf_field(); ?>

    <div class="auth-field">
        <label for="customer_name">Full Name</label>
        <div class="auth-control">
            <i class="fas fa-user leading-icon"></i>
            <input id="customer_name"
                   type="text"
                   name="name"
                   value="<?php echo e(old('name')); ?>"
                   placeholder="Enter your full name"
                   autocomplete="name"
                   required
                   autofocus>
        </div>
    </div>

    <div class="auth-field">
        <label for="customer_email">Email Address</label>
        <div class="auth-control">
            <i class="fas fa-envelope leading-icon"></i>
            <input id="customer_email"
                   type="email"
                   name="email"
                   value="<?php echo e(old('email')); ?>"
                   placeholder="name@example.com"
                   autocomplete="email"
                   required>
        </div>
    </div>

    <div class="auth-field">
        <label for="customer_phone">Phone Number</label>
        <div class="auth-control">
            <i class="fas fa-phone leading-icon"></i>
            <input id="customer_phone"
                   type="tel"
                   name="phone"
                   value="<?php echo e(old('phone')); ?>"
                   placeholder="e.g. 0700 000 000"
                   autocomplete="tel"
                   required>
        </div>
    </div>

    <div class="auth-field">
        <label for="customer_address">Address</label>
        <div class="auth-control auth-control-textarea">
            <i class="fas fa-location-dot leading-icon textarea-icon"></i>
            <textarea id="customer_address"
                      name="address"
                      rows="3"
                      placeholder="Enter your address"
                      autocomplete="street-address"><?php echo e(old('address')); ?></textarea>
        </div>
    </div>

    <div class="customer-password-grid">
        <div class="auth-field">
            <label for="customer_password">Password</label>
            <div class="auth-control password-control">
                <i class="fas fa-lock leading-icon"></i>
                <input id="customer_password"
                       type="password"
                       name="password"
                       placeholder="Create password"
                       autocomplete="new-password"
                       required>
                <button type="button"
                        class="password-toggle"
                        onclick="togglePassword(this)"
                        aria-label="Show password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <p class="auth-hint">
                At least 8 characters with uppercase, lowercase and a number.
            </p>
        </div>

        <div class="auth-field">
            <label for="customer_password_confirmation">Confirm Password</label>
            <div class="auth-control password-control">
                <i class="fas fa-shield-halved leading-icon"></i>
                <input id="customer_password_confirmation"
                       type="password"
                       name="password_confirmation"
                       placeholder="Repeat password"
                       autocomplete="new-password"
                       required>
                <button type="button"
                        class="password-toggle"
                        onclick="togglePassword(this)"
                        aria-label="Show password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    </div>

    <label class="auth-check customer-remember">
        <input type="checkbox"
               name="remember"
               value="1"
               <?php echo e(old('remember') ? 'checked' : ''); ?>>
        <span>Remember me after OTP verification</span>
    </label>

    <button type="submit" class="auth-submit customer-submit">
        <i class="fas fa-user-check"></i>
        Create Account &amp; Send OTP
    </button>

    <div class="auth-register-link auth-register-link-bottom">
        <span>Already have an account?</span>
        <a href="<?php echo e(route('login')); ?>">
            <i class="fas fa-arrow-left"></i>
            Back to Sign In
        </a>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/auth/customer-signup.blade.php ENDPATH**/ ?>