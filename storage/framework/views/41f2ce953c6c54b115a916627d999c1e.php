<?php $__env->startSection('title','Verify Code'); ?>
<?php $__env->startSection('subtitle','OTP verification'); ?>
<?php $__env->startSection('content'); ?>
<div class="auth-intro"><h2>Verify your identity</h2><p>Enter the 6-digit code sent to <strong><?php echo e($maskedEmail); ?></strong>. The code expires in <?php echo e($expiresMinutes); ?> minutes.</p></div>
<form id="otpForm" method="POST" action="<?php echo e(route('password.otp.verify')); ?>"><?php echo csrf_field(); ?>
<input type="hidden" name="otp" id="otpValue">
<div class="otp-grid" id="otpGrid"><?php for($i=0;$i<6;$i++): ?><input class="otp-box" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="OTP digit <?php echo e($i+1); ?>"><?php endfor; ?></div>
<button class="auth-submit" type="submit"><i class="fas fa-shield-check"></i> Verify code</button>
</form>
<form class="resend-row" method="POST" action="<?php echo e(route('password.otp.resend')); ?>"><?php echo csrf_field(); ?><span>Didn't receive the code? </span><button class="link-button" type="submit">Resend code</button></form>
<a class="auth-back" href="<?php echo e(route('password.request')); ?>"><i class="fas fa-arrow-left"></i> Use another email</a>
<?php $__env->startPush('scripts'); ?><script>
const otpBoxes=[...document.querySelectorAll('.otp-box')];const otpHidden=document.getElementById('otpValue');
function syncOtp(){otpHidden.value=otpBoxes.map(x=>x.value).join('')}
otpBoxes.forEach((box,i)=>{box.addEventListener('input',()=>{box.value=box.value.replace(/\D/g,'').slice(0,1);syncOtp();if(box.value&&i<5)otpBoxes[i+1].focus()});box.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!box.value&&i>0)otpBoxes[i-1].focus()});box.addEventListener('paste',e=>{e.preventDefault();const digits=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);digits.split('').forEach((d,j)=>{if(otpBoxes[j])otpBoxes[j].value=d});syncOtp();otpBoxes[Math.min(digits.length,5)]?.focus()})});
document.getElementById('otpForm').addEventListener('submit',syncOtp);otpBoxes[0]?.focus();
</script><?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/kemmytec/nsales/resources/views/auth/verify-otp.blade.php ENDPATH**/ ?>