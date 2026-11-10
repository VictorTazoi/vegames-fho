(function () {
    const base = window.VEGAMES_BASE_URL || '';
    let charts = {};
    let ultimosFiltros = {};

    function destruirGraficos() {
        Object.values(charts).forEach(c => c && c.destroy());
        charts = {};
    }

    function atualizarKpis(resumo) {
        document.getElementById('kpiOee').textContent = resumo.oee + '%';
        document.getElementById('kpiDisponibilidade').textContent = resumo.disponibilidade + '%';
        document.getElementById('kpiPerformance').textContent = resumo.performance + '%';
        document.getElementById('kpiQualidade').textContent = resumo.qualidade + '%';
        document.getElementById('kpiTempoParado').textContent = 'Tempo parado: ' + VegaCharts.formatarDuracao(resumo.tempo_parado_seg);
        document.getElementById('kpiProduzido').textContent = 'Produzido: ' + resumo.total_produzido + ' peças';
        document.getElementById('kpiRefugo').textContent = 'Refugo: ' + resumo.rejeitadas + ' peças';
    }

    function graficoProducao(dados) {
        const ctx = document.getElementById('chartProducao');
        charts.producao = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dados.map(d => d.nome),
                datasets: [
                    { label: 'Produzidas', data: dados.map(d => +d.produzidas), backgroundColor: VegaCharts.palette.navy },
                    { label: 'Rejeitadas', data: dados.map(d => +d.rejeitadas), backgroundColor: VegaCharts.palette.red },
                    { label: 'Meta', data: dados.map(d => +d.meta), backgroundColor: VegaCharts.palette.gold },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { position: 'top' } },
            },
        });
    }

    function graficoMotivos(dados) {
        const ctx = document.getElementById('chartMotivos');
        if (!dados.length) {
            ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);
            return;
        }
        charts.motivos = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: dados.map(d => d.motivo),
                datasets: [{ data: dados.map(d => +d.total_seg), backgroundColor: VegaCharts.seriesColors }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                plugins: {
                    tooltip: { callbacks: { label: (c) => `${c.label}: ${VegaCharts.formatarDuracao(c.parsed)}` } },
                },
            },
        });
    }

    function graficoOeePorMaquina(dados) {
        const ctx = document.getElementById('chartOeeEstacao');
        charts.oeeMaquina = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dados.map(d => d.nome),
                datasets: [
                    { label: 'Disponibilidade', data: dados.map(d => d.disponibilidade), backgroundColor: VegaCharts.palette.navyLight },
                    { label: 'Performance', data: dados.map(d => d.performance), backgroundColor: VegaCharts.palette.green },
                    { label: 'Qualidade', data: dados.map(d => d.qualidade), backgroundColor: VegaCharts.palette.red },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false, animation: false,
                scales: { x: { beginAtZero: true, ticks: { callback: v => v + '%' } } },
                plugins: { legend: { position: 'top' } },
            },
        });
    }

    function graficoTendencia(dados) {
        const ctx = document.getElementById('chartTendencia');
        charts.tendencia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dados.map(d => d.dia),
                datasets: [
                    { label: 'Produzidas', data: dados.map(d => +d.produzidas), borderColor: VegaCharts.palette.navy, backgroundColor: 'transparent', tension: .35 },
                    { label: 'Rejeitadas', data: dados.map(d => +d.rejeitadas), borderColor: VegaCharts.palette.red, backgroundColor: 'transparent', tension: .35 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    async function carregarDashboard(filtros) {
        ultimosFiltros = filtros;
        try {
            const dados = await VegaCharts.fetchJSON(base + '/api/dashboard/kpis.php?' + VegaCharts.queryString(filtros));
            if (!dados) return;
            destruirGraficos();
            atualizarKpis(dados.resumo);
            graficoProducao(dados.producao_por_maquina);
            graficoMotivos(dados.distribuicao_paradas);
            graficoOeePorMaquina(dados.oee_por_maquina);
            graficoTendencia(dados.tendencia);
        } catch (err) {
            console.error(err);
            window.mostrarToast(err.message, 'error');
        }
    }

    VegaFiltros.aoAplicar(carregarDashboard);

    window.VegaLiveEvents && window.VegaLiveEvents.aoReceber(function () {
        carregarDashboard(ultimosFiltros);
    });

    const btnAnalisar = document.getElementById('btnAnalisarIA');
    btnAnalisar.addEventListener('click', async function () {
        const resultado = document.getElementById('llmResultado');
        btnAnalisar.disabled = true;
        btnAnalisar.innerHTML = '<span class="spinner"></span> Analisando...';
        resultado.className = 'llm-empty';
        resultado.textContent = 'A IA está interpretando os indicadores, aguarde...';

        try {
            const resp = await fetch(base + '/api/llm/analisar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(ultimosFiltros),
            });
            const dados = await resp.json();
            if (!resp.ok) throw new Error(dados.erro || 'Não foi possível gerar a análise.');
            resultado.className = 'llm-text';
            resultado.textContent = dados.analise;
        } catch (err) {
            resultado.className = 'llm-empty';
            resultado.textContent = err.message;
        } finally {
            btnAnalisar.disabled = false;
            btnAnalisar.textContent = 'Gerar análise';
        }
    });
})();
