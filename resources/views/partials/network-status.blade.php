{{--
    Friendly handling of lost connections, included by every full-page layout:
    - an "offline / back online" banner,
    - forms are not submitted while offline (so the browser's error page never replaces the user's work),
    - fetch() failures carry a readable message instead of "Failed to fetch",
    - registers public/sw.js, which shows the branded /offline page when a page can't load.
--}}
<div id="net-banner" role="status" aria-live="polite" hidden>
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 2l20 20"/><path d="M8.5 16.4a5 5 0 0 1 7 0"/><path d="M5 12.9a10 10 0 0 1 5.2-2.7"/><path d="M19 12.9a10 10 0 0 0-2.2-1.6"/><path d="M12 20h.01"/></svg>
    <span id="net-banner-text"></span>
</div>
<style>
    #net-banner {
        position: fixed; left: 50%; bottom: 18px; transform: translateX(-50%); z-index: 2147483000;
        display: flex; align-items: center; gap: 10px; max-width: calc(100vw - 32px);
        padding: 11px 18px; border-radius: 999px; background: #1e293b; color: #fff;
        font: 600 14px/1.35 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        box-shadow: 0 12px 30px -10px rgba(15, 23, 42, .55);
    }
    #net-banner[hidden] { display: none; }
    #net-banner.is-online { background: #047857; }
    #net-banner.is-online svg { display: none; }
    #net-banner.shake { animation: net-shake .4s; }
    #net-banner svg { width: 18px; height: 18px; flex: none; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; }
    @keyframes net-shake { 0%, 100% { transform: translateX(-50%); } 25% { transform: translateX(calc(-50% - 6px)); } 75% { transform: translateX(calc(-50% + 6px)); } }
</style>
<script>
(function () {
    if (window.__netStatusInstalled) return;
    window.__netStatusInstalled = true;

    var OFFLINE_TEXT = "You're offline. Check your Wi-Fi or mobile data.";
    var banner = document.getElementById('net-banner');
    var text = document.getElementById('net-banner-text');
    var hideTimer = null;

    function show(message, online) {
        clearTimeout(hideTimer);
        text.textContent = message;
        banner.classList.toggle('is-online', !!online);
        banner.hidden = false;
        if (online) hideTimer = setTimeout(function () { banner.hidden = true; }, 3500);
    }

    function nudge(message) {
        show(message, false);
        banner.classList.remove('shake');
        void banner.offsetWidth;
        banner.classList.add('shake');
    }

    /** A readable message for any error from fetch() or elsewhere. */
    window.networkErrorMessage = function (error, fallback) {
        if (!navigator.onLine) return "You're offline. Check your internet connection and try again.";
        if (error && (error.isNetworkError || error instanceof TypeError)) {
            return 'We could not reach the server. Check your internet connection and try again.';
        }
        return (error && error.message) || fallback || 'Something went wrong. Please try again.';
    };

    // fetch() rejects with a bare TypeError ("Failed to fetch", "Load failed") when the network is down.
    if (window.fetch) {
        var nativeFetch = window.fetch.bind(window);
        window.fetch = function () {
            return nativeFetch.apply(null, arguments).catch(function (error) {
                if (error && error.name === 'AbortError') throw error;
                var friendly = new Error(window.networkErrorMessage(error));
                friendly.name = 'NetworkError';
                friendly.isNetworkError = true;
                friendly.cause = error;
                if (!navigator.onLine) show(OFFLINE_TEXT, false);
                throw friendly;
            });
        };
    }

    // Keep the form on screen instead of letting the browser replace it with its offline page.
    window.addEventListener('submit', function (event) {
        if (navigator.onLine) return;
        var form = event.target;
        if (form && (form.method || '').toLowerCase() === 'dialog') return;
        event.preventDefault();
        event.stopImmediatePropagation();
        nudge("You're offline, so this wasn't sent. Reconnect and try again.");
    }, true);

    window.addEventListener('offline', function () { show(OFFLINE_TEXT, false); });
    window.addEventListener('online', function () { show('Back online.', true); });
    if (!navigator.onLine) show(OFFLINE_TEXT, false);

    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').then(function (registration) {
                var key = 'net.offlinePageRefreshedAt';
                var last = 0;
                try { last = Number(localStorage.getItem(key)) || 0; } catch (_) {}
                if (registration.active && Date.now() - last > 86400000) {
                    registration.active.postMessage('refresh-offline-page');
                    try { localStorage.setItem(key, String(Date.now())); } catch (_) {}
                }
            }).catch(function () {});
        });
    }
})();
</script>
