@php
    $branding = \App\Support\DocumentBranding::get();
@endphp
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
        border-bottom: 3px solid {{ $branding['theme_primary'] }};
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
        color: {{ $branding['theme_primary'] }};
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
    @include('partials.pdf-watermark', ['branding' => $branding])
    @include('partials.pdf-brand-header', [
        'branding' => $branding,
        'documentTitle' => 'Stock Report'
    ])

    <div class="generated">
        Generated {{ now()->format('d M Y H:i') }}
        by {{ $generatedBy }}
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
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->product->name ?? '—' }}</td>
                    <td>{{ $row->supplier->name ?? '—' }}</td>
                    <td>{{ $row->quantity }}</td>
                    <td>{{ $branding['currency'] }} {{ number_format((float)$row->cost_price, 2) }}</td>
                    <td>{{ $branding['currency'] }} {{ number_format((float)$row->selling_price, 2) }}</td>
                    <td>{{ $row->expiry_date }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No stock records.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $branding['site_name'] }}
        @if(!empty($branding['business_phone'])) | {{ $branding['business_phone'] }} @endif
        @if(!empty($branding['business_email'])) | {{ $branding['business_email'] }} @endif
    </div>
</body>
</html>
