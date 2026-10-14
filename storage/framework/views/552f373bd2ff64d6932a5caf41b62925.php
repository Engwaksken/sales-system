<?php
    $branding = \App\Support\DocumentBranding::get();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 24px 24px 30px; }

    * { box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9px;
        color: #1e293b;
        position: relative;
    }

    .pdf-logo-watermark {
        position: fixed;
        top: 34%;
        left: 31%;
        width: 38%;
        opacity: .055;
        z-index: -10;
        text-align: center;
    }

    .pdf-logo-watermark img {
        width: 100%;
        max-height: 290px;
        object-fit: contain;
    }

    .pdf-brand-header {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
        border-bottom: 3px solid <?php echo e($branding['theme_primary']); ?>;
    }

    .pdf-brand-header td {
        border: 0;
        padding: 0 0 9px;
        vertical-align: middle;
    }

    .pdf-brand-logo-cell { width: 82px; }
    .pdf-brand-logo { max-width: 70px; max-height: 52px; }

    .pdf-brand-company {
        line-height: 1.45;
        color: #64748b;
        font-size: 8.5px;
    }

    .pdf-brand-name {
        color: #0f172a;
        font-weight: bold;
        font-size: 15px;
    }

    .pdf-brand-doc-title {
        width: 30%;
        text-align: right;
        color: <?php echo e($branding['theme_primary']); ?>;
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .generated {
        color: #94a3b8;
        font-size: 8px;
        margin-bottom: 8px;
    }

    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    .data-table th,
    .data-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: 5px 7px;
        text-align: left;
    }

    .data-table th {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        font-size: 8px;
        text-transform: uppercase;
        color: #64748b;
    }

    .footer {
        margin-top: 12px;
        padding-top: 7px;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        color: #94a3b8;
        font-size: 7.5px;
    }
</style>
</head>
<body>
    <?php echo $__env->make('partials.pdf-watermark', ['branding' => $branding], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.pdf-brand-header', [
        'branding' => $branding,
        'documentTitle' => 'Stock Report'
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="generated">
        Generated <?php echo e(now()->format('d M Y H:i')); ?>

        by <?php echo e($generatedBy); ?>

    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Supplier</th>
                <th>Quantity</th>
                <th>Cost Price</th>
                <th>Selling Price</th>
                <th>Expiry Date</th>
            </tr>
        </thead>

        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($row->product->name ?? '—'); ?></td>
                    <td><?php echo e($row->supplier->name ?? '—'); ?></td>
                    <td><?php echo e($row->quantity); ?></td>
                    <td><?php echo e($branding['currency']); ?> <?php echo e(number_format((float)$row->cost_price, 2)); ?></td>
                    <td><?php echo e($branding['currency']); ?> <?php echo e(number_format((float)$row->selling_price, 2)); ?></td>
                    <td><?php echo e($row->expiry_date); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6">No stock records.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <?php echo e($branding['site_name']); ?>

        <?php if(!empty($branding['business_phone'])): ?> | <?php echo e($branding['business_phone']); ?> <?php endif; ?>
        <?php if(!empty($branding['business_email'])): ?> | <?php echo e($branding['business_email']); ?> <?php endif; ?>
    </div>
</body>
</html>
<?php /**PATH /home/kemmytec/nsales/resources/views/stock/report-pdf.blade.php ENDPATH**/ ?>