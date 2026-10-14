<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectors = [
        '[data-flash-message]',
        '.alert-success',
        '.alert-danger',
        '.alert-error',
        '.alert-warning',
        '[role="alert"]'
    ];

    const messages = Array.from(document.querySelectorAll(selectors.join(',')));

    messages.forEach(function (message) {
        if (message.dataset.flashTimeoutBound === '1') {
            return;
        }

        message.dataset.flashTimeoutBound = '1';
        message.style.transition = 'opacity .5s ease, transform .5s ease';

        window.setTimeout(function () {
            message.style.opacity = '0';
            message.style.transform = 'translateY(-4px)';

            window.setTimeout(function () {
                message.remove();
            }, 500);
        }, 5000);
    });
});
</script>
