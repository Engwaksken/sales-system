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

    // Theme colours come from admin Settings; anything that is not a hex colour falls back safely.
    $hexOr = function ($value, string $fallback): string {
        $value = trim((string) $value);
        if (preg_match('/^#([0-9a-f]{3})$/i', $value, $m)) {
            $value = '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
        }

        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $fallback;
    };
    $primary = $hexOr($siteSettings['theme_primary'] ?? null, '#0f766e');
    $secondary = $hexOr($siteSettings['theme_secondary'] ?? null, '#0ea5e9');
    $accent = $hexOr($siteSettings['theme_accent'] ?? null, '#f59e0b');

    // White or near-black text on anything painted in the primary colour (WCAG relative luminance).
    $channel = function (string $hex, int $offset): float {
        $c = hexdec(substr($hex, $offset, 2)) / 255;
        return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    };
    $luminance = 0.2126 * $channel($primary, 1) + 0.7152 * $channel($primary, 3) + 0.0722 * $channel($primary, 5);
    $primaryIsLight = (1.05 / ($luminance + 0.05)) < 4.5;
    $onPrimary = $primaryIsLight ? '#0f172a' : '#ffffff';

    $fontFamily = trim(str_replace(['"', "'", ';', '{', '}', '<', '>', '\\'], '', (string)($siteSettings['font_family'] ?? 'Lato'))) ?: 'Lato';
    $fontSize = max(12, min(20, (int)($siteSettings['font_size'] ?? 14)));
    $googleFonts = ['Lato', 'Inter', 'Poppins', 'Open Sans', 'Roboto', 'Nunito', 'Montserrat', 'Outfit'];
    $loadGoogleFont = in_array($fontFamily, $googleFonts, true);

    $cssVersion = @filemtime(public_path('css/auth.css')) ?: '1';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="{{ $primary }}">

    <title>@yield('title') - {{ $systemName }}</title>

    @if($favicon)
        <link rel="icon" href="{{ $favicon }}">
    @endif

    @if($loadGoogleFont)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontFamily) }}:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ $cssVersion }}">

    <style>
        :root{
            --auth-primary:{{ $primary }};
            --auth-secondary:{{ $secondary }};
            --auth-accent:{{ $accent }};
            --auth-on-primary:{{ $onPrimary }};
            --auth-font:"{{ $fontFamily }}",Lato,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
            --auth-font-size:{{ $fontSize }}px
        }
    </style>
</head>
<body class="{{ $primaryIsLight ? 'auth-primary-light' : '' }}">
<div class="auth-shell">
    {{-- Brand panel: desktop only (960px and wider). --}}
    <aside class="auth-aside" aria-label="About {{ $systemName }}">
        <div class="auth-aside-inner">
            <div class="auth-brand">
                <span class="auth-logo" aria-hidden="true">
                    @if($systemLogo)
                        <img src="{{ $systemLogo }}" alt="">
                    @else
                        <i class="fas fa-chart-line"></i>
                    @endif
                </span>
                <span class="auth-brand-name">{{ $systemName }}</span>
            </div>

            <div class="auth-aside-copy">
                <p class="auth-tagline">@yield('tagline', 'Your trusted sales and business management workspace.')</p>
                <ul class="auth-points">
                    <li><span class="auth-point-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></span><span>Sales, stock and expenses in one place</span></li>
                    <li><span class="auth-point-icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></span><span>Invoices and receipts shared as PDFs</span></li>
                    <li><span class="auth-point-icon" aria-hidden="true"><i class="fas fa-shield-halved"></i></span><span>Every sign-in confirmed with an email code</span></li>
                </ul>
            </div>

            <p class="auth-aside-foot">&copy; {{ date('Y') }} {{ $systemName }}</p>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-main-inner">
            {{-- Compact brand header: below 960px only. --}}
            <div class="auth-mobile-brand">
                <span class="auth-logo" aria-hidden="true">
                    @if($systemLogo)
                        <img src="{{ $systemLogo }}" alt="">
                    @else
                        <i class="fas fa-chart-line"></i>
                    @endif
                </span>
                <span class="auth-brand-name">{{ $systemName }}</span>
            </div>

            <div class="auth-card">
                <header class="auth-header">
                    <h1>@hasSection('heading') @yield('heading') @else @yield('title') @endif</h1>
                    @hasSection('lead')
                        <p>@yield('lead')</p>
                    @endif
                </header>

                @if(session('status'))
                    <div data-flash class="auth-alert auth-alert-success" role="status">
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div data-flash class="auth-alert auth-alert-error" role="alert">
                        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                        <div>
                            @if($errors->count() === 1)
                                <span>{{ $errors->first() }}</span>
                            @else
                                <strong>Please fix the following:</strong>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>

            <p class="auth-footnote">
                <i class="fas fa-lock" aria-hidden="true"></i>
                Protected with your password and an email verification code
            </p>
        </div>
    </main>
</div>

<script>
(function () {
    // Password show/hide.
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-password-toggle]');
        if (!button) return;
        var input = button.closest('.auth-control').querySelector('input');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        var icon = button.querySelector('i');
        if (icon) icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    });

    // Loading state: once a form really submits, disable its buttons and show a spinner (no double posts).
    // Bubble phase, so forms that cancel their own submit (and the offline guard) are respected.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (event.defaultPrevented || form.dataset.submitting) return;
        form.dataset.submitting = '1';
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type="submit"]').forEach(function (button) {
            button.disabled = true;
            button.classList.add('is-loading');
            var label = button.querySelector('[data-label]') || button;
            if (button.dataset.loadingText) {
                label.dataset.original = label.textContent;
                label.textContent = button.dataset.loadingText;
            }
        });
    });

    // Restore buttons if the browser brings the page back from its back/forward cache.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        document.querySelectorAll('form[data-submitting]').forEach(function (form) {
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('button.is-loading').forEach(function (button) {
                button.disabled = false;
                button.classList.remove('is-loading');
                var label = button.querySelector('[data-label]') || button;
                if (label.dataset.original) label.textContent = label.dataset.original;
            });
        });
    });

    // Password strength checklist and meter.
    document.querySelectorAll('[data-password-strength]').forEach(function (box) {
        var input = document.getElementById(box.dataset.passwordStrength);
        if (!input) return;
        var rules = {
            length: function (v) { return v.length >= 8; },
            upper: function (v) { return /[A-Z]/.test(v); },
            lower: function (v) { return /[a-z]/.test(v); },
            number: function (v) { return /\d/.test(v); }
        };
        var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        var meter = box.querySelector('.auth-meter');
        var meterLabel = box.querySelector('[data-strength-label]');

        function update() {
            var value = input.value;
            var met = 0;
            Object.keys(rules).forEach(function (key) {
                var ok = rules[key](value);
                if (ok) met++;
                var item = box.querySelector('[data-rule="' + key + '"]');
                if (!item) return;
                item.classList.toggle('is-met', ok);
                var state = item.querySelector('.visually-hidden');
                if (state) state.textContent = ok ? ' (done)' : '';
            });
            var score = 0;
            if (value.length) score = met <= 2 ? 1 : (met === 3 ? 2 : (value.length >= 12 ? 4 : 3));
            meter.dataset.score = String(score);
            meterLabel.textContent = score ? labels[score] : '';
        }
        input.addEventListener('input', update);
        update();
    });

    // "Passwords match" hint on confirmation fields.
    document.querySelectorAll('[data-password-match]').forEach(function (hint) {
        var original = document.getElementById(hint.dataset.passwordMatch);
        var confirm = document.getElementById(hint.dataset.confirm);
        if (!original || !confirm) return;
        function update() {
            if (!confirm.value) { hint.hidden = true; return; }
            var same = confirm.value === original.value;
            hint.hidden = false;
            hint.classList.toggle('is-met', same);
            hint.querySelector('span').textContent = same ? 'Passwords match' : 'Passwords do not match yet';
            hint.querySelector('i').className = same ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
        }
        original.addEventListener('input', update);
        confirm.addEventListener('input', update);
        update();
    });

    // Six-box one-time code. Supports typing, paste, one-time-code autofill, arrows and backspace.
    document.querySelectorAll('[data-otp]').forEach(function (group) {
        var boxes = Array.prototype.slice.call(group.querySelectorAll('[data-otp-box]'));
        var form = group.closest('form');
        var hidden = form.querySelector('input[name="otp"]');
        var clientError = form.querySelector('[data-otp-client-error]');

        function sync() {
            hidden.value = boxes.map(function (b) { return b.value; }).join('');
        }
        function fill(from, digits) {
            digits.split('').forEach(function (d, i) { if (boxes[from + i]) boxes[from + i].value = d; });
            sync();
            var next = boxes[Math.min(from + digits.length, boxes.length - 1)];
            next.focus();
        }

        boxes.forEach(function (box, index) {
            box.addEventListener('input', function () {
                var digits = box.value.replace(/\D/g, '');
                if (digits.length > 1) { box.value = ''; fill(index, digits.slice(0, boxes.length - index)); return; }
                box.value = digits;
                sync();
                if (digits && index < boxes.length - 1) boxes[index + 1].focus();
            });
            box.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && !box.value && index > 0) {
                    boxes[index - 1].value = '';
                    boxes[index - 1].focus();
                    sync();
                    event.preventDefault();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    boxes[index - 1].focus(); event.preventDefault();
                } else if (event.key === 'ArrowRight' && index < boxes.length - 1) {
                    boxes[index + 1].focus(); event.preventDefault();
                }
            });
            box.addEventListener('focus', function () { box.select(); });
            box.addEventListener('paste', function (event) {
                var text = (event.clipboardData || window.clipboardData).getData('text') || '';
                var digits = text.replace(/\D/g, '').slice(0, boxes.length);
                if (!digits) return;
                event.preventDefault();
                boxes.forEach(function (b) { b.value = ''; });
                fill(0, digits);
            });
        });

        form.addEventListener('submit', function (event) {
            sync();
            if (hidden.value.length === boxes.length) return;
            event.preventDefault();
            if (clientError) clientError.hidden = false;
            group.classList.add('is-invalid');
            var empty = boxes.filter(function (b) { return !b.value; })[0];
            if (empty) empty.focus();
        });
        group.addEventListener('input', function () {
            if (clientError) clientError.hidden = true;
            group.classList.remove('is-invalid');
        });

        boxes[0].focus();
    });

    // Put the cursor in the first field the server rejected.
    var invalid = document.querySelector('input[aria-invalid="true"]:not([data-otp-box]), textarea[aria-invalid="true"]');
    if (invalid) invalid.focus();
})();
</script>

@include('partials.network-status')
@stack('scripts')
@include('partials.flash-autohide')
</body>
</html>
