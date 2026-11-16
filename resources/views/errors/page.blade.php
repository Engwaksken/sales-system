{{--
    Branded error page. Self-contained (no CDN styles or scripts) so it still
    renders when the database, the CDN or the network is down.

    Variables: $code, $title, $message, $icon (lock|search|clock|alert|wrench|wifi|key),
    $actions (array of: back, home, retry, login).
--}}
@php
    $siteSettings = $siteSettings ?? [];
    $systemName = $siteSettings['site_name'] ?? config('app.name', 'NSales');
    $primary = preg_match('/^#[0-9a-fA-F]{6}$/', $siteSettings['theme_primary'] ?? '') ? $siteSettings['theme_primary'] : '#0f766e';
    $logo = trim((string) ($siteSettings['system_logo'] ?? ''));
    $logoUrl = $logo !== '' ? asset(ltrim($logo, '/')) : null;
    $favicon = trim((string) ($siteSettings['system_favicon'] ?? ''));
    $faviconUrl = $favicon !== '' ? asset(ltrim($favicon, '/')) : null;
    $actions = $actions ?? ['back', 'home'];
    $code = $code ?? '';
    $icon = $icon ?? 'alert';

    try {
        $signedIn = auth()->check();
    } catch (\Throwable) {
        $signedIn = false;
    }

    $icons = [
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M9 9l4 4m0-4-4 4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'wrench' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
        'wifi' => '<path d="M2 2l20 20"/><path d="M8.5 16.4a5 5 0 0 1 7 0"/><path d="M5 12.9a10 10 0 0 1 5.2-2.7"/><path d="M19 12.9a10 10 0 0 0-2.2-1.6"/><path d="M1.4 9a15 15 0 0 1 4.3-2.8"/><path d="M22.6 9A15 15 0 0 0 10.7 5"/><path d="M12 20h.01"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3"/><path d="m16 7 3 3"/><path d="m19 4 2 2"/>',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} - {{ $systemName }}</title>
    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <style>
        :root { --brand: {{ $primary }}; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --bg: #f8fafc; --card: #ffffff; }
        @media (prefers-color-scheme: dark) {
            :root { --ink: #e2e8f0; --muted: #94a3b8; --line: #1e293b; --bg: #0b1120; --card: #111827; }
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 24px 16px; background: var(--bg); color: var(--ink);
            font: 15px/1.55 "Lato", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background-image: radial-gradient(circle at 50% -10%, color-mix(in srgb, var(--brand) 14%, transparent), transparent 60%);
        }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; color: var(--ink); text-decoration: none; font-weight: 800; font-size: 17px; }
        .brand img { height: 40px; width: auto; max-width: 140px; object-fit: contain; }
        .card {
            width: 100%; max-width: 460px; text-align: center; background: var(--card); border: 1px solid var(--line);
            border-radius: 20px; padding: 36px 28px 30px; box-shadow: 0 20px 45px -25px rgba(15, 23, 42, .35);
        }
        .icon {
            width: 72px; height: 72px; margin: 0 auto 18px; border-radius: 50%; display: grid; place-items: center;
            color: var(--brand); background: color-mix(in srgb, var(--brand) 12%, transparent);
        }
        .icon svg { width: 34px; height: 34px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .code { font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--brand); margin: 0 0 6px; }
        h1 { margin: 0 0 10px; font-size: 24px; line-height: 1.25; }
        p.message { margin: 0 auto 26px; color: var(--muted); max-width: 360px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 20px;
            border-radius: 12px; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer;
            border: 1px solid var(--line); background: transparent; color: var(--ink);
        }
        .btn.primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn:hover { filter: brightness(.95); }
        .btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--brand) 45%, transparent); outline-offset: 2px; }
        .status { margin-top: 18px; font-size: 13px; color: var(--muted); min-height: 1.2em; }
        footer { margin-top: 22px; font-size: 12px; color: var(--muted); }
        @media (max-width: 420px) { .actions .btn { flex: 1 1 100%; } .card { padding: 30px 20px 24px; } }
    </style>
</head>
<body>
    <a class="brand" href="{{ url('/') }}">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="" onerror="this.remove()">
        @endif
        <span>{{ $systemName }}</span>
    </a>

    <main class="card" role="main">
        <div class="icon" aria-hidden="true"><svg viewBox="0 0 24 24">{!! $icons[$icon] ?? $icons['alert'] !!}</svg></div>
        @if($code !== '')
            <p class="code">Error {{ $code }}</p>
        @endif
        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>

        <div class="actions">
            @foreach($actions as $i => $action)
                @php $class = $i === 0 ? 'btn primary' : 'btn'; @endphp
                @if($action === 'retry')
                    <button type="button" class="{{ $class }}" onclick="location.reload()">Try again</button>
                @elseif($action === 'back')
                    <button type="button" class="{{ $class }}" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Go back</button>
                @elseif($action === 'home')
                    <a class="{{ $class }}" href="{{ url('/') }}">{{ $signedIn ? 'Go to dashboard' : 'Go to home page' }}</a>
                @elseif($action === 'login')
                    <a class="{{ $class }}" href="{{ route('login') }}">Sign in</a>
                @endif
            @endforeach
        </div>

        @if(!empty($autoRetry))
            <p class="status" id="net-status" aria-live="polite"></p>
            <script>
                (function () {
                    var status = document.getElementById('net-status');
                    function update() {
                        status.textContent = navigator.onLine ? 'Connection is back. Reloading...' : 'Waiting for your connection. This page will reload by itself.';
                        if (navigator.onLine) setTimeout(function () { location.reload(); }, 800);
                    }
                    window.addEventListener('online', update);
                    window.addEventListener('offline', update);
                    if (!navigator.onLine) update();
                })();
            </script>
        @endif
    </main>

    <footer>&copy; {{ date('Y') }} {{ $systemName }}</footer>
</body>
</html>
