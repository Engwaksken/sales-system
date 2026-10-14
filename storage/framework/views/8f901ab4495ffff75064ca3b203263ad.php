<?php
    $branding = $branding ?? \App\Support\DocumentBranding::get();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 24px 26px; }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        font-family: DejaVu Sans, sans-serif;
        color: #1f2937;
        font-size: 10px;
        position: relative;
    }

    .pdf-logo-watermark {
        position: fixed;
        top: 28%;
        left: 25%;
        width: 50%;
        opacity: .055;
        z-index: -10;
        text-align: center;
    }

    .pdf-logo-watermark img {
        width: 100%;
        max-height: 245px;
        object-fit: contain;
    }

    .pdf-brand-header {
        width: 100%;
        border-collapse: collapse;
        padding-bottom: 12px;
        border-bottom: 3px solid <?php echo e($branding['theme_primary']); ?>;
    }

    .pdf-brand-header td {
        border: 0;
        padding: 0 0 9px;
        vertical-align: middle;
    }

    .pdf-brand-logo-cell { width: 88px; }
    .pdf-brand-logo { max-width: 76px; max-height: 50px; }

    .pdf-brand-company {
        line-height: 1.45;
        color: #64748b;
        font-size: 8.5px;
    }

    .pdf-brand-name {
        font-size: 16px;
        font-weight: bold;
        color: #111827;
        margin-bottom: 2px;
    }

    .pdf-brand-doc-title {
        width: 34%;
        text-align: right;
        color: <?php echo e($branding['theme_primary']); ?>;
        font-size: 13px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .meta {
        width: 100%;
        margin-top: 14px;
        border-collapse: collapse;
    }

    .meta td {
        padding: 7px 8px;
        border-bottom: 1px solid #e5e7eb;
    }

    .meta .label {
        width: 42%;
        color: #9ca3af;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .meta .value {
        text-align: right;
        font-weight: bold;
        color: #374151;
    }

    .amount {
        margin-top: 15px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 13px;
        text-align: center;
    }

    .amount .small {
        color: #059669;
        font-size: 8px;
        text-transform: uppercase;
    }

    .amount .big {
        margin-top: 4px;
        color: #065f46;
        font-size: 21px;
        font-weight: bold;
    }

    .thanks {
        margin-top: 16px;
        text-align: center;
        color: #6b7280;
        line-height: 1.55;
    }

    .footer {
        margin-top: 18px;
        padding-top: 9px;
        border-top: 1px dashed #d1d5db;
        text-align: center;
        color: #9ca3af;
        font-size: 8px;
        line-height: 1.5;
    }
</style>
</head>
<body>
    <?php echo $__env->make('partials.pdf-watermark', ['branding' => $branding], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.pdf-brand-header', [
        'branding' => $branding,
        'documentTitle' => 'Payment Receipt'
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <table class="meta">
        <tr>
            <td class="label">Receipt Number</td>
            <td class="value"><?php echo e($receiptNo); ?></td>
        </tr>

        <tr>
            <td class="label">Invoice / Reference</td>
            <td class="value"><?php echo e($reference ?: '—'); ?></td>
        </tr>

        <tr>
            <td class="label">Date</td>
            <td class="value"><?php echo e($date); ?></td>
        </tr>

        <tr>
            <td class="label">Received From</td>
            <td class="value"><?php echo e($customerName); ?></td>
        </tr>

        <tr>
            <td class="label">Payment Method</td>
            <td class="value"><?php echo e($method); ?></td>
        </tr>
    </table>

    <div class="amount">
        <div class="small">Amount Paid</div>
        <div class="big">
            <?php echo e($branding['currency']); ?>

            <?php echo e(number_format((float)$amount, 0)); ?>

        </div>
    </div>

    <div class="thanks">
        <?php echo e($branding['receipt_footer_note'] ?: 'Thank you for your business!'); ?>

    </div>

    <div class="footer">
        <strong><?php echo e($branding['site_name']); ?></strong><br>
        <?php if(!empty($branding['business_phone'])): ?><?php echo e($branding['business_phone']); ?><?php endif; ?>
        <?php if(!empty($branding['business_phone']) && !empty($branding['business_email'])): ?> | <?php endif; ?>
        <?php if(!empty($branding['business_email'])): ?><?php echo e($branding['business_email']); ?><?php endif; ?>
        <?php if(!empty($branding['business_address'])): ?><br><?php echo e($branding['business_address']); ?><?php endif; ?>
    </div>
</body>
</html>
<?php /**PATH /home/kemmytec/nsales/resources/views/receipts/receipt-pdf.blade.php ENDPATH**/ ?>