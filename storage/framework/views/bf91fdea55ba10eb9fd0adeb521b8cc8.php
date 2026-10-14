<?php
    $customer = $payment->customer ?? $payment->sale?->customer;
    $sourceOrder = $payment->sale?->orders?->first();
    $currency = $branding['currency'] ?? 'UGX';
    $paperWidth = match($paper) {
        '58mm' => '58mm',
        '80mm' => '80mm',
        'a5' => '148mm',
        default => '210mm',
    };
    $pageSize = match($paper) {
        '58mm' => '58mm auto',
        '80mm' => '80mm auto',
        'a5' => 'A5',
        default => 'A4',
    };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt <?php echo e($payment->receipt_no); ?></title>
<style>
@page{size:<?php echo e($pageSize); ?>;margin:<?php echo e(in_array($paper,['58mm','80mm'],true) ? '2mm' : '10mm'); ?>}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:#fff;color:#000}
body{width:<?php echo e($paperWidth); ?>;max-width:100%;margin:0 auto;font-family:Arial,"DejaVu Sans",sans-serif;font-size:<?php echo e($paper==='58mm'?'9px':($paper==='80mm'?'10.5px':'12px')); ?>;line-height:1.35}
.receipt{padding:<?php echo e(in_array($paper,['58mm','80mm'],true) ? '1mm' : '0'); ?>}
.header{text-align:center;border-bottom:1px dashed #000;padding-bottom:7px}
.logo{max-width:<?php echo e($paper==='58mm'?'32mm':'40mm'); ?>;max-height:<?php echo e($paper==='58mm'?'13mm':'16mm'); ?>;object-fit:contain;margin:0 auto 4px}
.name{font-weight:700;font-size:<?php echo e($paper==='58mm'?'13px':'15px'); ?>}
.title{text-align:center;font-weight:700;margin:8px 0;font-size:<?php echo e($paper==='58mm'?'11px':'13px'); ?>}
.section{padding:7px 0;border-bottom:1px dashed #000}
.row{display:flex;justify-content:space-between;gap:7px;padding:2px 0}.row b{font-weight:700}
.amount{text-align:center;padding:10px 0;border-bottom:1px dashed #000}.amount small{display:block;text-transform:uppercase}.amount strong{display:block;margin-top:3px;font-size:<?php echo e($paper==='58mm'?'16px':'20px'); ?>}
.footer{text-align:center;padding-top:8px;font-size:9px}
.no-print{position:fixed;right:14px;top:14px;display:flex;gap:8px;z-index:20}.no-print button{border:0;padding:9px 13px;border-radius:7px;cursor:pointer;font-weight:700}.print-btn{background:#111827;color:white}.close-btn{background:#e5e7eb}
@media print{.no-print{display:none!important}html,body{width:<?php echo e($paperWidth); ?>;max-width:<?php echo e($paperWidth); ?>}}
</style>
</head>
<body>
<div class="no-print">
    <button class="print-btn" onclick="window.print()">Print</button>
    <button class="close-btn" onclick="window.close()">Close</button>
</div>

<div class="receipt">
    <div class="header">
        <?php if(!empty($branding['logo_data_uri'])): ?>
            <img src="<?php echo e($branding['logo_data_uri']); ?>" class="logo" alt="Logo">
        <?php endif; ?>
        <div class="name"><?php echo e($branding['site_name']); ?></div>
        <?php if(!empty($branding['business_address'])): ?><div><?php echo e($branding['business_address']); ?></div><?php endif; ?>
        <?php if(!empty($branding['business_phone'])): ?><div><?php echo e($branding['business_phone']); ?></div><?php endif; ?>
    </div>

    <div class="title">PAYMENT RECEIPT</div>

    <div class="section">
        <div class="row"><b>Receipt</b><span><?php echo e($payment->receipt_no); ?></span></div>
        <div class="row"><b>Invoice</b><span><?php echo e($payment->invoice_no ?: '—'); ?></span></div>
        <?php if($sourceOrder): ?>
            <div class="row"><b>Order</b><span><?php echo e($sourceOrder->order_number ?: $sourceOrder->order_id); ?></span></div>
        <?php endif; ?>
        <div class="row"><b>Date</b><span><?php echo e(optional($payment->created_at)->format('d M Y H:i')); ?></span></div>
    </div>

    <div class="section">
        <div class="row"><b>Customer</b><span><?php echo e($customer?->name ?? $sourceOrder?->customer_name ?? 'Walk-in Customer'); ?></span></div>
        <div class="row"><b>Method</b><span><?php echo e($payment->method); ?></span></div>
        <div class="row"><b>Status</b><span><?php echo e(str($payment->status)->title()); ?></span></div>
    </div>

    <div class="amount">
        <small>Amount Paid</small>
        <strong><?php echo e($currency); ?> <?php echo e(number_format((float)$payment->amount,0)); ?></strong>
    </div>

    <div class="footer">
        <?php echo e($branding['receipt_footer_note'] ?? 'Thank you for your business!'); ?><br>
        <?php if(!empty($branding['business_email'])): ?><?php echo e($branding['business_email']); ?><br><?php endif; ?>
        <?php echo e($branding['site_name']); ?>

    </div>
</div>
</body>
</html>
<?php /**PATH /home/kemmytec/nsales/resources/views/payments/receipt-print.blade.php ENDPATH**/ ?>