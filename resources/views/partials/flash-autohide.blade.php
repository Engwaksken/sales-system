{{-- Success/error messages marked data-flash stay for 5 seconds, then fade out.
     Hovering or focusing a message pauses the timer so longer errors can be read. --}}
<style>
    [data-flash] { transition: opacity .5s ease, max-height .5s ease, margin .5s ease, padding .5s ease; overflow: hidden; }
    [data-flash].flash-hiding { opacity: 0; max-height: 0 !important; margin-top: 0 !important; margin-bottom: 0 !important; padding-top: 0 !important; padding-bottom: 0 !important; border-width: 0 !important; }
    @media (prefers-reduced-motion: reduce) { [data-flash] { transition: none; } }
</style>
<script>
(function () {
    var VISIBLE_MS = 5000;

    function arm(el) {
        if (el.dataset.flashArmed) return;
        el.dataset.flashArmed = '1';
        if (!el.hasAttribute('role')) el.setAttribute('role', /red|rose|error|danger/i.test(el.className) ? 'alert' : 'status');

        var remaining = VISIBLE_MS, started = Date.now(), timer = null;
        function hide() {
            el.style.maxHeight = el.scrollHeight + 'px';
            el.offsetHeight; // commit the start height so the collapse animates
            el.classList.add('flash-hiding');
            setTimeout(function () { el.remove(); }, 550);
        }
        function start() { started = Date.now(); timer = setTimeout(hide, remaining); }
        function pause() { if (timer) { clearTimeout(timer); timer = null; remaining = Math.max(1000, remaining - (Date.now() - started)); } }

        el.addEventListener('mouseenter', pause);
        el.addEventListener('mouseleave', start);
        el.addEventListener('focusin', pause);
        el.addEventListener('focusout', start);
        start();
    }

    function scan(root) {
        if (root.matches && root.matches('[data-flash]')) arm(root);
        if (root.querySelectorAll) root.querySelectorAll('[data-flash]').forEach(arm);
    }

    document.addEventListener('DOMContentLoaded', function () {
        scan(document);
        new MutationObserver(function (records) {
            records.forEach(function (r) { r.addedNodes.forEach(function (n) { if (n.nodeType === 1) scan(n); }); });
        }).observe(document.body, { childList: true, subtree: true });
    });
})();
</script>
