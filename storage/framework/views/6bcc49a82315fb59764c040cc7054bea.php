<?php
    $siteSettings = $siteSettings ?? [];
    $systemName = $siteSettings['site_name'] ?? config('app.name', 'NSales');

    $configuredLogo = trim((string)($siteSettings['system_logo'] ?? ''));
    $fallbackLogo = file_exists(public_path('assets/icons/logo.png'))
        ? 'assets/icons/logo.png'
        : null;

    $logoPath = $configuredLogo !== '' ? $configuredLogo : $fallbackLogo;
    $systemLogo = $logoPath ? asset(ltrim($logoPath, '/')) : null;

    $favicon = !empty($siteSettings['system_favicon'])
        ? asset(ltrim($siteSettings['system_favicon'], '/'))
        : null;

    $primary = $siteSettings['theme_primary'] ?? '#0f766e';
    $secondary = $siteSettings['theme_secondary'] ?? '#0ea5e9';
    $accent = $siteSettings['theme_accent'] ?? '#f59e0b';
    $fontFamily = $siteSettings['font_family'] ?? 'Lato';
    $fontSize = max(12, min(20, (int)($siteSettings['font_size'] ?? 14)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo $__env->yieldContent('title'); ?> - <?php echo e($systemName); ?></title>

    <?php if($favicon): ?>
        <link rel="icon" href="<?php echo e($favicon); ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/auth.css')); ?>">

    <style>
        :root{
            --auth-primary:<?php echo e($primary); ?>;
            --auth-secondary:<?php echo e($secondary); ?>;
            --auth-accent:<?php echo e($accent); ?>;
            --auth-font:"<?php echo e($fontFamily); ?>",Lato,Arial,sans-serif;
            --auth-font-size:<?php echo e($fontSize); ?>px
        }
    </style>
</head>
<body>
<div class="auth-page">
    

    <main class="auth-card">
        <div class="auth-brand">
            <div class="auth-logo">
                <?php if($systemLogo): ?>
                    <img src="<?php echo e($systemLogo); ?>" alt="<?php echo e($systemName); ?> logo">
                <?php else: ?>
                    <i class="fas fa-chart-line"></i>
                <?php endif; ?>
            </div>

            <div>
                <h1><?php echo e($systemName); ?></h1>
                <p><?php echo $__env->yieldContent('subtitle', 'Secure sales management'); ?></p>
            </div>
        </div>

        <?php if(session('status')): ?>
            <div class="auth-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?php echo e(session('status')); ?></span>
            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="auth-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>

        <div class="auth-footer">
            <i class="fas fa-shield-halved"></i>
            Password + email OTP protected access
        </div>
    </main>
</div>

<script>
function togglePassword(button) {
    const wrap = button.closest('.password-control');
    const input = wrap.querySelector('input');
    const icon = button.querySelector('i');
    const showing = input.type === 'text';

    input.type = showing ? 'password' : 'text';
    icon.className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
    button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
}
</script>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/kemmytec/nsales/resources/views/layouts/auth.blade.php ENDPATH**/ ?>