{{-- Faint logo watermark for browser print views (A4/A5). Thermal paper skips it: grey prints as smudges there. --}}
@if(!empty($branding['logo_data_uri']) && !in_array($paper ?? 'a4', ['58mm', '80mm'], true))
    <div aria-hidden="true" style="position:fixed; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none; z-index:0;">
        <img src="{{ $branding['logo_data_uri'] }}" alt="" style="width:55%; max-width:360px; max-height:45vh; object-fit:contain; opacity:.07; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
    </div>
@endif
