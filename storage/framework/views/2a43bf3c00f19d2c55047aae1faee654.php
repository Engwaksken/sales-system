<?php $__env->startSection('title', 'Verify Login'); ?>
<?php $__env->startSection('subtitle', 'Two-Step Login Verification'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-intro">
    <h2>Check your email</h2>
    <p>
        Enter the 6-digit code sent to <strong><?php echo e($maskedEmail); ?></strong>.
        The code expires in <?php echo e($expiresMinutes); ?> minutes.
    </p>
</div>

<form method="POST" action="<?php echo e(route('login.otp.verify')); ?>" id="loginOtpForm">
    <?php echo csrf_field(); ?>

    <input type="hidden" name="otp" id="otpValue">

    <div class="otp-grid" aria-label="6 digit verification code">
        <?php for($i = 0; $i < 6; $i++): ?>
            <div class="auth-control">
                <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="1"
                    class="otp-box"
                    autocomplete="<?php echo e($i === 0 ? 'one-time-code' : 'off'); ?>"
                    data-otp-box
                >
            </div>
        <?php endfor; ?>
    </div>

    <button class="auth-submit" type="submit">
        <i class="fas fa-shield-check"></i>
        Verify & Continue to Dashboard
    </button>
</form>

<form method="POST" action="<?php echo e(route('login.otp.resend')); ?>" class="resend-row">
    <?php echo csrf_field(); ?>
    Didn't receive the code?
    <button type="submit" class="link-button">Resend OTP</button>
</form>

<a href="<?php echo e(route('login')); ?>" class="auth-back">
    <i class="fas fa-arrow-left"></i>
    Back to sign in
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
    const hidden = document.getElementById('otpValue');
    const form = document.getElementById('loginOtpForm');

    function sync() {
        hidden.value = boxes.map(box => box.value.replace(/\D/g, '')).join('');
    }

    boxes.forEach((box, index) => {
        box.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(-1);
            sync();

            if (this.value && index < boxes.length - 1) {
                boxes[index + 1].focus();
                boxes[index + 1].select();
            }
        });

        box.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && !this.value && index > 0) {
                boxes[index - 1].focus();
            }
        });

        box.addEventListener('paste', function (event) {
            const value = (event.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, 6);

            if (!value) return;

            event.preventDefault();

            value.split('').forEach((digit, i) => {
                if (boxes[i]) boxes[i].value = digit;
            });

            sync();

            const next = boxes[Math.min(value.length, boxes.length) - 1];
            if (next) next.focus();
        });
    });

    form.addEventListener('submit', function (event) {
        sync();

        if (hidden.value.length !== 6) {
            event.preventDefault();
            boxes.find(box => !box.value)?.focus();
        }
    });

    boxes[0]?.focus();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/auth/login-otp.blade.php ENDPATH**/ ?>