const VegaCharts = (function () {
    const palette = {
        navy: '#16305c',
        navyLight: '#3f8fd1',
        gold: '#e0a030',
        green: '#2fa84f',
        red: '#d9483f',
        gray: '#a9b4c2',
    };

    const seriesColors = ['#16305c', '#3f8fd1', '#e0a030', '#2fa84f', '#d9483f', '#8a97a8'];

    async function fetchJSON(url) {
        const resp = await fetch(url, { credentials: 'same-origin' });
        const dados = await resp.json();
        if (!resp.ok) {
            if (resp.status === 401) {
                window.location.href = window.VEGAMES_BASE_URL + '/index.php';
                return null;
            }
            throw new Error(dados.erro || 'Erro ao carregar dados.');
        }
        return dados;
    }

    function queryString(filtros) {
        const params = new URLSearchParams();
        Object.entries(filtros || {}).forEach(([k, v]) => {
            if (v !== null && v !== undefined && v !== '') params.append(k, v);
        });
        return params.toString();
    }

    function formatarDuracao(segundos) {
        segundos = Math.max(0, Math.round(segundos || 0));
        const h = Math.floor(segundos / 3600);
        const m = Math.floor((segundos % 3600) / 60);
        if (h > 0) return `${h}h ${m}min`;
        return `${m}min`;
    }

    Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";
    Chart.defaults.color = '#4b5768';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.animation = false;
    Chart.defaults.transitions.active.animation.duration = 0;

    return { palette, seriesColors, fetchJSON, queryString, formatarDuracao };
})();
