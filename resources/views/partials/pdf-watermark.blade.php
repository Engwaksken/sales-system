@php
    $branding = $branding ?? \App\Support\DocumentBranding::get();
@endphp

@if(!empty($branding['logo_data_uri']))
    <div class="pdf-logo-watermark">
        <img src="{{ $branding['logo_data_uri'] }}" alt="">
    </div>
@endif
