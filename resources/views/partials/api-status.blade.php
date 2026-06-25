<script>
(function () {
    const STATUS_URL = '{{ route('api.ispring.status') }}';
    const DOT_CLASS  = 'online-dot';

    function updateDot(online) {
        document.querySelectorAll('.' + DOT_CLASS).forEach(function (el) {
            el.classList.toggle('offline', !online);
        });
        var wrap = document.getElementById('apiStatusWrap');
        if (wrap) {
            wrap.dataset.status = online
                ? 'ISpring API: Connected'
                : 'ISpring API: Disconnected — data may be outdated';
        }
    }

    function checkStatus() {
        fetch(STATUS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (!r.ok || r.redirected) {
                    console.warn('[API Status] Unexpected response:', r.status, r.url);
                    updateDot(false);
                    return null;
                }
                return r.json();
            })
            .then(function (data) {
                if (data !== null && data !== undefined) {
                    updateDot(data.online === true);
                }
            })
            .catch(function (err) {
                console.warn('[API Status] Fetch error:', err);
                updateDot(false);
            });
    }

    checkStatus();
    setInterval(checkStatus, 30000);
})();
</script>
