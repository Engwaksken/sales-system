@php
    $branding = \App\Support\DocumentBranding::get();
    $documentTitle = 'Invoice';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 30px 34px; }

    * { box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #1e293b;
        position: relative;
    }

    .pdf-logo-watermark {
        position: fixed;
        top: 31%;
        left: 30%;
        width: 40%;
        opacity: .055;
        z-index: -10;
        text-align: center;
    }

    .pdf-logo-watermark img {
        width: 100%;
        max-height: 300px;
        object-fit: contain;
    }

    .pdf-brand-header {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        border-bottom: 3px solid {{ $branding['theme_primary'] }};
    }

    .pdf-brand-header td {
        border: 0;
        padding: 0 0 10px;
        vertical-align: middle;
    }

    .pdf-brand-logo-cell { width: 95px; }
    .pdf-brand-logo { max-width: 82px; max-height: 58px; }

    .pdf-brand-company {
        line-height: 1.45;
        color: #64748b;
        font-size: 9px;
    }

    .pdf-brand-name {
        font-size: 17px;
        font-weight: bold;
        color: #111827;
        margin-bottom: 2px;
    }

    .pdf-brand-doc-title {
        width: 28%;
        text-align: right;
        font-size: 19px;
        color: {{ $branding['theme_primary'] }};
        font-weight: bold;
        text-transform: uppercase;
    }

    .meta {
        width: 100%;
        border-collapse: collapse;
        margin: 5px 0 14px;
    }

    .meta td {
        padding: 4px 6px;
        border: 0;
        vertical-align: top;
    }

    .meta .label {
        color: #94a3b8;
        font-size: 8px;
        text-transform: uppercase;
    }

    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    .items th,
    .items td {
        border-bottom: 1px solid #e2e8f0;
        padding: 7px 8px;
        text-align: left;
    }

    .items th {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        font-size: 8px;
        text-transform: uppercase;
        color: #64748b;
    }

    .right { text-align: right !important; }

    .totals {
        width: 285px;
        margin-left: auto;
        margin-top: 14px;
        border-collapse: collapse;
    }

    .totals td {
        padding: 5px 7px;
        border: 0;
    }

    .grand td {
        border-top: 2px solid {{ $branding['theme_primary'] }};
        padding-top: 8px;
        color: #111827;
        font-size: 13px;
        font-weight: bold;
    }

    .footer {
        margin-top: 23px;
        padding-top: 9px;
        border-top: 1px dashed #cbd5e1;
        color: #94a3b8;
        text-align: center;
        font-size: 8px;
        line-height: 1.5;
    }
</style>
</head>
<body>
    @include('partials.pdf-watermark', ['branding' => $branding])
    @include('partials.pdf-brand-header', [
        'branding' => $branding,
        'documentTitle' => 'Invoice'
    ])

    <table class="meta">
        <tr>
            <td style="width:50%">
                <div class="label">Invoice Number</div>
                <strong>{{ $sale->invoice_no }}</strong>
            </td>
            <td style="width:50%">
                <div class="label">Invoice Date</div>
                <strong>{{ optional($sale->sale_date)->format('d M Y') ?? $sale->sale_date }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Customer</div>
                <strong>{{ $sale->customer->name ?? 'Walk-in Customer' }}</strong>
            </td>
            <td>
                <div class="label">Branch</div>
                <strong>{{ $sale->branch->name ?? '—' }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Payment Method</div>
                <strong>{{ $sale->payment_method }}</strong>
            </td>
            <td>
                <div class="label">Payment Status</div>
                <strong>{{ $sale->payment_status }}</strong>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">VAT</th>
                <th class="right">Total</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($sale->sale_items as $item)
                <tr>
                    <td>{{ $item->product->name ?? '—' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format((float)$item->price, 2) }}</td>
                    <td class="right">{{ number_format((float)$item->vat_amount, 2) }}</td>
                    <td class="right">{{ number_format((float)$item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No invoice items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Discount</td>
            <td class="right">{{ $branding['currency'] }} {{ number_format((float)$sale->discount, 2) }}</td>
        </tr>
        <tr>
            <td>VAT</td>
            <td class="right">{{ $branding['currency'] }} {{ number_format((float)$sale->vat, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Total</td>
            <td class="right">{{ $branding['currency'] }} {{ number_format((float)$sale->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        <strong>{{ $branding['site_name'] }}</strong><br>
        @if(!empty($branding['business_phone'])){{ $branding['business_phone'] }}@endif
        @if(!empty($branding['business_phone']) && !empty($branding['business_email'])) | @endif
        @if(!empty($branding['business_email'])){{ $branding['business_email'] }}@endif
        @if(!empty($branding['business_address']))<br>{{ $branding['business_address'] }}@endif
    </div>
</body>
</html>
