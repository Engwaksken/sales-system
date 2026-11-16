{{--
    Salesperson signature block for invoices and receipts (DomPDF and browser print).
    $signer: ['name', 'role', 'data_uri'] from App\Support\DocumentSignature, or null.
    $label: caption under the line, e.g. "Sold by" / "Received by".
    $compact: true for narrow thermal paper.
--}}
@if(!empty($signer))
    @php $compact = $compact ?? false; @endphp
    <table style="width:100%; border-collapse:collapse; margin-top:{{ $compact ? '10px' : '26px' }}; page-break-inside:avoid;">
        <tr>
            <td style="width:{{ $compact ? '100%' : '50%' }}; {{ $compact ? 'text-align:center;' : '' }} vertical-align:bottom; padding:0;">
                <div style="height:{{ $compact ? '42px' : '56px' }}; {{ $compact ? 'text-align:center;' : '' }}">
                    @if(!empty($signer['data_uri']))
                        <img src="{{ $signer['data_uri'] }}" alt="Signature" style="max-height:{{ $compact ? '42px' : '56px' }}; max-width:{{ $compact ? '150px' : '200px' }};">
                    @endif
                </div>
                <div style="border-top:1px solid #64748b; width:{{ $compact ? '75%' : '220px' }}; {{ $compact ? 'margin:0 auto;' : '' }} padding-top:4px; font-size:{{ $compact ? '9px' : '10px' }}; color:#334155;">
                    <strong>{{ $signer['name'] }}</strong>
                    @if(!empty($signer['role']))<span style="color:#64748b;"> &middot; {{ $signer['role'] }}</span>@endif
                    <div style="color:#64748b; font-size:{{ $compact ? '8px' : '9px' }};">{{ $signer['label'] ?? ($label ?? 'Sold by') }}</div>
                </div>
            </td>
            @unless($compact)
                <td style="width:50%;"></td>
            @endunless
        </tr>
    </table>
@endif
