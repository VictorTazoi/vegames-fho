(function () {
    const base = window.VEGAMES_BASE_URL || '';
    let chartPareto = null;

    function preencherTabela(dados) {
        const tbody = document.querySelector('#tabelaPareto tbody');
        tbody.innerHTML = '';
        if (!dados.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="empty-state">Nenhuma parada registrada para os filtros selecionados.</td></tr>';
            return;
        }
        dados.forEach(d => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${d.causa}</td>
                <td>${d.frequencia}</td>
                <td>${VegaCharts.formatarDuracao(d.tempo_total_seg)}</td>
                <td>${d.percentual_acumulado}%</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function desenharPareto(dados) {
        const ctx = document.getElementById('chartPareto');
        if (chartPareto) chartPareto.destroy();

        chartPareto = new Chart(ctx, {
            data: {
                labels: dados.map(d => d.causa),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Frequência',
                        data: dados.map(d => d.frequencia),
                        backgroundColor: VegaCharts.palette.navyLight,
                        order: 2,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Acumulado (%)',
                        data: dados.map(d => d.percentual_acumulado),
                        borderColor: VegaCharts.palette.red,
                        backgroundColor: VegaCharts.palette.red,
                        tension: 0.2,
                        yAxisID: 'y1',
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Frequência' } },
                    y1: {
                        beginAtZero: true, max: 100, position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { callback: v => v + '%' },
                        title: { display: true, text: '% Acumulado' },
                    },
                },
                plugins: { legend: { position: 'top' } },
            },
        });
    }

    async function carregar(filtros) {
        try {
            const dados = await VegaCharts.fetchJSON(base + '/api/paradas/pareto.php?' + VegaCharts.queryString(filtros));
            if (!dados) return;
            desenharPareto(dados.pareto);
            preencherTabela(dados.pareto);
        } catch (err) {
            console.error(err);
            window.mostrarToast(err.message, 'error');
        }
    }

    VegaFiltros.aoAplicar(carregar);
})();
