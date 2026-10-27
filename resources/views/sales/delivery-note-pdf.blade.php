@php
    $branding = $branding ?? \App\Support\DocumentBranding::get();
    $recipient = $sale->deliveryRecipient();
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
        width: 32%;
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

    .deliver-to {
        border: 1px solid #e2e8f0;
        border-left: 3px solid {{ $branding['theme_primary'] }};
        background: #f8fafc;
        padding: 9px 11px;
        line-height: 1.55;
    }

    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
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

    .items tfoot td {
        border-top: 2px solid {{ $branding['theme_primary'] }};
        border-bottom: 0;
        font-weight: bold;
        color: #111827;
    }

    .right { text-align: right !important; }
    .center { text-align: center !important; }

    .check-box {
        display: inline-block;
        width: 11px;
        height: 11px;
        border: 1px solid #94a3b8;
    }

    .signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 34px;
    }

    .signatures td {
        width: 50%;
        padding: 0 10px 0 0;
        border: 0;
        vertical-align: top;
    }

    .sign-line {
        border-bottom: 1px solid #94a3b8;
        height: 26px;
        margin-bottom: 4px;
    }

    .sign-label {
        color: #64748b;
        font-size: 8px;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .notice {
        margin-top: 18px;
        color: #64748b;
        font-size: 8.5px;
        line-height: 1.5;
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
        'documentTitle' => 'Delivery Note'
    ])

    <table class="meta">
        <tr>
            <td style="width:50%" rowspan="3">
                <div class="label">Deliver To</div>
                <div class="deliver-to">
                    <strong>{{ $recipient['name'] }}</strong>
                    @if(!empty($recipient['phone']))<br>{{ $recipient['phone'] }}@endif
                    @if(!empty($recipient['email']))<br>{{ $recipient['email'] }}@endif
                    @if(!empty($recipient['address']))<br>{{ $recipient['address'] }}@endif
                </div>
            </td>
            <td style="width:25%">
                <div class="label">Delivery Note No.</div>
                <strong>{{ $sale->delivery_note_no }}</strong>
            </td>
            <td style="width:25%">
                <div class="label">Date</div>
                <strong>{{ optional($sale->sale_date)->format('d M Y') ?? $sale->sale_date }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Invoice Ref.</div>
                <strong>{{ $sale->invoice_no ?: '—' }}</strong>
            </td>
            <td>
                <div class="label">Dispatched From</div>
                <strong>{{ $sale->branch->name ?? '—' }}</strong>
            </td>
        </tr>
        @if(!empty($recipient['order_number']))
        <tr>
            <td colspan="2">
                <div class="label">Order Ref.</div>
                <strong>{{ $recipient['order_number'] }}</strong>
            </td>
        </tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:6%">#</th>
                <th>Item Description</th>
                <th style="width:18%">SKU</th>
                <th class="right" style="width:12%">Qty</th>
                <th class="center" style="width:12%">Received</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($sale->sale_items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product->name ?? '—' }}</td>
                    <td>{{ $item->product->sku ?? '—' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="center"><span class="check-box"></span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No items on this sale.</td>
                </tr>
            @endforelse
        </tbody>

        @if($sale->sale_items->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">Total Quantity</td>
                <td class="right">{{ (int) $sale->sale_items->sum('quantity') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="notice">
        Please inspect the goods on delivery. Goods received in good order and condition unless noted below.
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line"></div>
                <div class="sign-label">Delivered By (Name &amp; Signature)</div>
                <div class="sign-line"></div>
                <div class="sign-label">Date</div>
            </td>
            <td>
                <div class="sign-line"></div>
                <div class="sign-label">Received By (Name &amp; Signature)</div>
                <div class="sign-line"></div>
                <div class="sign-label">Date</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        @if(!empty($branding['receipt_footer_note'])){{ $branding['receipt_footer_note'] }}<br>@endif
        <strong>{{ $branding['site_name'] }}</strong><br>
        @if(!empty($branding['business_phone'])){{ $branding['business_phone'] }}@endif
        @if(!empty($branding['business_phone']) && !empty($branding['business_email'])) | @endif
        @if(!empty($branding['business_email'])){{ $branding['business_email'] }}@endif
        @if(!empty($branding['business_address']))<br>{{ $branding['business_address'] }}@endif
    </div>
</body>
</html>
