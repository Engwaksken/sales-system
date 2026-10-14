@php
    $branding = $branding ?? \App\Support\DocumentBranding::get();
@endphp

<table class="pdf-brand-header">
    <tr>
        <td class="pdf-brand-logo-cell">
            @if(!empty($branding['logo_data_uri']))
                <img class="pdf-brand-logo" src="{{ $branding['logo_data_uri'] }}" alt="Logo">
            @endif
        </td>

        <td class="pdf-brand-company">
            <div class="pdf-brand-name">{{ $branding['site_name'] }}</div>

            @if(!empty($branding['business_phone']))
                <div>{{ $branding['business_phone'] }}</div>
            @endif

            @if(!empty($branding['business_email']))
                <div>{{ $branding['business_email'] }}</div>
            @endif

            @if(!empty($branding['business_address']))
                <div>{{ $branding['business_address'] }}</div>
            @endif
        </td>

        <td class="pdf-brand-doc-title">
            {{ $documentTitle ?? ($title ?? '') }}
        </td>
    </tr>
</table>
