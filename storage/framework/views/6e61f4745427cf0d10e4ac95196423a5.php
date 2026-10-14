<?php
    $branding = $branding ?? \App\Support\DocumentBranding::get();
?>

<?php if(!empty($branding['logo_data_uri'])): ?>
    <div class="pdf-logo-watermark">
        <img src="<?php echo e($branding['logo_data_uri']); ?>" alt="">
    </div>
<?php endif; ?>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/pdf-watermark.blade.php ENDPATH**/ ?>