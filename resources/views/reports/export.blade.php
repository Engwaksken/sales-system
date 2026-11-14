@php
    $branding = \App\Support\DocumentBranding::get();
    $currency = $branding['currency'] ?? 'UGX';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { margin: 24px 24px 30px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1e293b; position: relative; margin: 0; }
    .pdf-logo-watermark { position: fixed; top: 34%; left: 31%; width: 38%; opacity: .055; z-index: -10; text-align: center; }
    .pdf-logo-watermark img { width: 100%; max-height: 290px; object-fit: contain; }
    .pdf-brand-header { width: 100%; border-collapse: collapse; margin-bottom: 10px; border-bottom: 3px solid {{ $branding['theme_primary'] }}; }
    .pdf-brand-header td { border: 0; padding: 0 0 9px; vertical-align: middle; }
    .pdf-brand-logo-cell { width: 82px; }
    .pdf-brand-logo { max-width: 70px; max-height: 52px; }
    .pdf-brand-company { line-height: 1.45; color: #64748b; font-size: 8.5px; }
    .pdf-brand-name { color: #0f172a; font-weight: bold; font-size: 15px; }
    .pdf-brand-doc-title { width: 30%; text-align: right; color: {{ $branding['theme_primary'] }}; font-size: 14px; font-weight: bold; text-transform: uppercase; }
    .filters { margin-bottom: 4px; font-size: 8.5px; color: #475569; }
    .filters span { margin-right: 14px; }
    .filters strong { color: #0f172a; }
    .generated { color: #94a3b8; font-size: 8px; margin-bottom: 8px; }
    table.data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .data-table th, .data-table td { border-bottom: 1px solid #e2e8f0; padding: 5px 7px; text-align: left; }
    .data-table th { background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 8px; text-transform: uppercase; color: #64748b; }
    .data-table td.money { text-align: right; white-space: nowrap; }
    .footer { margin-top: 12px; padding-top: 7px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 7.5px; }
    .print-bar { display: none; }
    @if($forPrint)
        body { font-size: 11px; padding: 24px; }
        .data-table th { font-size: 10px; }
        .print-bar { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 16px; }
        .print-bar button { font: inherit; padding: 6px 14px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; }
        .print-bar button.primary { background: {{ $branding['theme_primary'] }}; border-color: {{ $branding['theme_primary'] }}; color: #fff; }
        @media print { .print-bar { display: none; } body { padding: 0; } }
    @endif
</style>
</head>
<body>
    @if($forPrint)
        <div class="print-bar">
            <button type="button" onclick="window.close()">Close</button>
            <button type="button" class="primary" onclick="window.print()">Print</button>
        </div>
    @endif

    @include('partials.pdf-watermark', ['branding' => $branding])
    @include('partials.pdf-brand-header', ['branding' => $branding, 'documentTitle' => $title])

    <div class="filters">
        @foreach($filters as $label => $value)
            <span>{{ $label }}: <strong>{{ $value }}</strong></span>
        @endforeach
    </div>
    <div class="generated">
        Generated {{ now()->format('d M Y H:i') }} by {{ $generatedBy }}, {{ number_format($rows->count()) }} row(s)
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $key => $value)
                        @if(in_array($key, $moneyColumns, true))
                            <td class="money">{{ $currency }} {{ number_format((float) $value, 2) }}</td>
                        @elseif(is_bool($value))
                            <td>{{ $value ? 'Yes' : 'No' }}</td>
                        @elseif(is_scalar($value) || $value === null)
                            <td>{{ $value ?? '' }}</td>
                        @else
                            <td>{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(count($headers), 1) }}">No data found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $branding['site_name'] }}
        @if(!empty($branding['business_phone'])) | {{ $branding['business_phone'] }} @endif
        @if(!empty($branding['business_email'])) | {{ $branding['business_email'] }} @endif
    </div>

    @if($forPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
