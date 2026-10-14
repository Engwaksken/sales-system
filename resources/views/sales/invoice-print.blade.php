@php
    $currency = $branding['currency'] ?? 'UGX';
    $paperWidth = match($paper) {
        '80mm' => '80mm',
        'a5' => '148mm',
        default => '210mm',
    };
    $pageSize = match($paper) {
        '80mm' => '80mm auto',
        'a5' => 'A5',
        default => 'A4',
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice {{ $sale->invoice_no }}</title>
<style>
@page { size: {{ $pageSize }}; margin: {{ $paper === '80mm' ? '2mm' : '10mm' }}; }
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:#fff;color:#000}
body{
    width:{{ $paperWidth }};
    max-width:100%;
    margin:0 auto;
    font-family:Arial,"DejaVu Sans",sans-serif;
    font-size:{{ $paper === '80mm' ? '10.5px' : '12px' }};
    line-height:1.35
}
.wrap{padding:{{ $paper === '80mm' ? '1mm' : '0' }}}
.header{text-align:center;padding-bottom:8px;border-bottom:1px dashed #000}
.logo{max-width:{{ $paper === '80mm' ? '40mm' : '55mm' }};max-height:{{ $paper === '80mm' ? '16mm' : '22mm' }};object-fit:contain;margin:0 auto 4px}
.name{font-weight:700;font-size:{{ $paper === '80mm' ? '15px' : '20px' }}}
.small{font-size:9px}
.title{text-align:center;font-weight:700;margin:8px 0;font-size:{{ $paper === '80mm' ? '13px' : '17px' }}}
.sep{border-bottom:1px dashed #000;padding-bottom:7px;margin-bottom:7px}
.row{display:flex;justify-content:space-between;gap:8px;padding:2px 0}
.row b{font-weight:700}
.items{width:100%;border-collapse:collapse;margin-top:4px}
.items th,.items td{padding:{{ $paper === '80mm' ? '4px 2px' : '7px 5px' }};border-bottom:1px dotted #999;vertical-align:top}
.items th{text-align:left;text-transform:uppercase;font-size:9px}
.items .r{text-align:right;white-space:nowrap}
.items .c{text-align:center}
.item-name{font-weight:700}
.totals{margin-top:8px;margin-left:auto;width:{{ $paper === '80mm' ? '100%' : '55%' }}}
.grand{border-top:1px solid #000;margin-top:4px;padding-top:5px;font-size:{{ $paper === '80mm' ? '13px' : '16px' }};font-weight:700}
.footer{text-align:center;margin-top:10px;padding-top:7px;border-top:1px dashed #000;font-size:9px}
.no-print{position:fixed;right:14px;top:14px;display:flex;gap:8px;z-index:20}
.no-print button{border:0;padding:9px 13px;border-radius:7px;cursor:pointer;font-weight:700}
.print-btn{background:#111827;color:#fff}.close-btn{background:#e5e7eb;color:#111827}
@media print{
    .no-print{display:none!important}
    html,body{width:{{ $paperWidth }};max-width:{{ $paperWidth }}}
}
</style>
</head>
<body>
<div class="no-print">
    <button class="print-btn" onclick="window.print()">Print</button>
    <button class="close-btn" onclick="window.close()">Close</button>
</div>

<div class="wrap">
    <div class="header">
        @if(!empty($branding['logo_data_uri']))
            <img src="{{ $branding['logo_data_uri'] }}" class="logo" alt="Logo">
        @endif
        <div class="name">{{ $branding['site_name'] }}</div>
        @if(!empty($branding['business_address']))<div class="small">{{ $branding['business_address'] }}</div>@endif
        @if(!empty($branding['business_phone']))<div class="small">{{ $branding['business_phone'] }}</div>@endif
        @if(!empty($branding['business_email']))<div class="small">{{ $branding['business_email'] }}</div>@endif
    </div>

    <div class="title">SALES INVOICE</div>

    <div class="sep">
        <div class="row"><b>Invoice</b><span>{{ $sale->invoice_no }}</span></div>
        <div class="row"><b>Date</b><span>{{ optional($sale->sale_date)->format('d M Y') ?? $sale->sale_date }}</span></div>
        <div class="row"><b>Status</b><span>{{ str($sale->payment_status)->replace('_',' ')->title() }}</span></div>
        @if($sale->orders->isNotEmpty())
            <div class="row"><b>Order</b><span>{{ $sale->orders->first()->order_number ?? $sale->orders->first()->order_id }}</span></div>
        @endif
    </div>

    <div class="sep">
        <b>Customer</b><br>
        {{ $sale->customer?->name ?? 'Walk-in Customer' }}
        @if($sale->customer?->phone)<br>{{ $sale->customer->phone }}@endif
        @if($sale->customer?->email)<br>{{ $sale->customer->email }}@endif
    </div>

    <table class="items">
        <thead>
        <tr><th>Item</th><th class="c">Qty</th><th class="r">Price</th><th class="r">Total</th></tr>
        </thead>
        <tbody>
        @foreach($sale->sale_items as $item)
            <tr>
                <td><div class="item-name">{{ $item->product?->name ?? 'Product' }}</div></td>
                <td class="c">{{ number_format((float)$item->quantity,0) }}</td>
                <td class="r">{{ number_format((float)$item->price,0) }}</td>
                <td class="r">{{ number_format((float)$item->total,0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Discount</span><span>{{ $currency }} {{ number_format((float)$sale->discount,0) }}</span></div>
        <div class="row"><span>VAT</span><span>{{ $currency }} {{ number_format((float)$sale->vat,0) }}</span></div>
        <div class="row grand"><span>Total</span><span>{{ $currency }} {{ number_format((float)$sale->total_amount,0) }}</span></div>
        <div class="row"><span>Paid</span><span>{{ $currency }} {{ number_format((float)$sale->partial_amount,0) }}</span></div>
        <div class="row"><span>Balance</span><span>{{ $currency }} {{ number_format((float)$sale->balance_amount,0) }}</span></div>
    </div>

    <div class="footer">
        {{ $branding['receipt_footer_note'] ?? 'Thank you for your business!' }}
    </div>
</div>
</body>
</html>
