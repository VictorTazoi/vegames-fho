window.VegaLiveEvents = (function () {
    const base = window.VEGAMES_BASE_URL || '';
    let es = null;
    const listeners = [];

    function conectar() {
        if (es) return;
        es = new EventSource(base + '/api/eventos/stream.php');
        ['producao', 'refugo', 'parada', 'ordem'].forEach(function (tipo) {
            es.addEventListener(tipo, function (e) {
                listeners.forEach(function (cb) { cb(tipo, e.data); });
            });
        });
        es.onerror = function () {
        };
    }

    return {
        aoReceber: function (cb) {
            listeners.push(cb);
            conectar();
        },
    };
})();
