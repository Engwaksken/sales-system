<?php
    $branding = $branding ?? \App\Support\DocumentBranding::get();
?>

<table class="pdf-brand-header">
    <tr>
        <td class="pdf-brand-logo-cell">
            <?php if(!empty($branding['logo_data_uri'])): ?>
                <img class="pdf-brand-logo" src="<?php echo e($branding['logo_data_uri']); ?>" alt="Logo">
            <?php endif; ?>
        </td>

        <td class="pdf-brand-company">
            <div class="pdf-brand-name"><?php echo e($branding['site_name']); ?></div>

            <?php if(!empty($branding['business_phone'])): ?>
                <div><?php echo e($branding['business_phone']); ?></div>
            <?php endif; ?>

            <?php if(!empty($branding['business_email'])): ?>
                <div><?php echo e($branding['business_email']); ?></div>
            <?php endif; ?>

            <?php if(!empty($branding['business_address'])): ?>
                <div><?php echo e($branding['business_address']); ?></div>
            <?php endif; ?>
        </td>

        <td class="pdf-brand-doc-title">
            <?php echo e($documentTitle ?? ($title ?? '')); ?>

        </td>
    </tr>
</table>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/pdf-brand-header.blade.php ENDPATH**/ ?>