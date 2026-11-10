(function () {
    const base = window.VEGAMES_BASE_URL || '';

    const STATUS = {
        normal: ['Normal', VegaCharts.palette.green],
        anomalia: ['ANOMALIA', VegaCharts.palette.red],
        coletando: ['Coletando', VegaCharts.palette.gold],
        sem_sinal: ['Sem sinal', VegaCharts.palette.gray],
        sem_dados: ['Sem dados', VegaCharts.palette.gray],
    };

    async function atualizar() {
        try {
            const dados = await VegaCharts.fetchJSON(base + '/api/vibracao/dados.php?resumo=1&minutos=5');
            if (!dados) return;

            const resumo = dados.resumo;
            const [texto, cor] = STATUS[resumo.status] || STATUS.sem_dados;
            const valor = document.getElementById('kpiVibracao');
            valor.textContent = texto;
            valor.style.color = cor;

            const sub = document.getElementById('kpiVibracaoSub');
            sub.textContent = resumo.ultimo_rms !== null
                ? 'RMS ' + Number(resumo.ultimo_rms).toFixed(3) + ' g - ver detalhes'
                : 'Ver detalhes';
        } catch (erro) {
            console.error(erro);
        }
    }

    atualizar();
    setInterval(atualizar, 5000);
})();
