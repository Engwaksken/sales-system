@php
    $siteSettings = $siteSettings ?? [];
    $systemName = $siteSettings['site_name'] ?? config('app.name', 'NSales');

    $configuredLogo = trim((string)($siteSettings['system_logo'] ?? ''));
    $fallbackLogo = file_exists(public_path('assets/icons/logo.png'))
        ? 'assets/icons/logo.png'
        : null;

    $logoPath = $configuredLogo !== '' ? $configuredLogo : $fallbackLogo;
    $systemLogo = $logoPath ? asset(ltrim($logoPath, '/')) : null;

    $favicon = !empty($siteSettings['system_favicon'])
        ? asset(ltrim($siteSettings['system_favicon'], '/'))
        : null;

    $primary = $siteSettings['theme_primary'] ?? '#0f766e';
    $secondary = $siteSettings['theme_secondary'] ?? '#0ea5e9';
    $accent = $siteSettings['theme_accent'] ?? '#f59e0b';
    $fontFamily = $siteSettings['font_family'] ?? 'Lato';
    $fontSize = max(12, min(20, (int)($siteSettings['font_size'] ?? 14)));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ $systemName }}</title>

    @if($favicon)
        <link rel="icon" href="{{ $favicon }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    <style>
        :root{
            --auth-primary:{{ $primary }};
            --auth-secondary:{{ $secondary }};
            --auth-accent:{{ $accent }};
            --auth-font:"{{ $fontFamily }}",Lato,Arial,sans-serif;
            --auth-font-size:{{ $fontSize }}px
        }
    </style>
</head>
<body>
<div class="auth-page">
    {{-- Decorative corner circles intentionally removed. --}}

    <main class="auth-card">
        <div class="auth-brand">
            <div class="auth-logo">
                @if($systemLogo)
                    <img src="{{ $systemLogo }}" alt="{{ $systemName }} logo">
                @else
                    <i class="fas fa-chart-line"></i>
                @endif
            </div>

            <div>
                <h1>{{ $systemName }}</h1>
                <p>@yield('subtitle', 'Secure sales management')</p>
            </div>
        </div>

        @if(session('status'))
            <div data-flash class="auth-alert success">
                <i class="fas fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div data-flash class="auth-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')

        <div class="auth-footer">
            <i class="fas fa-shield-halved"></i>
            Password + email OTP protected access
        </div>
    </main>
</div>

<script>
function togglePassword(button) {
    const wrap = button.closest('.password-control');
    const input = wrap.querySelector('input');
    const icon = button.querySelector('i');
    const showing = input.type === 'text';

    input.type = showing ? 'password' : 'text';
    icon.className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
    button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
}
</script>

@stack('scripts')
@include('partials.flash-autohide')
</body>
</html>
