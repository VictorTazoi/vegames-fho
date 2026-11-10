(function () {
    const base = window.VEGAMES_BASE_URL || '';
    const gauges = {};
    let ultimosFiltros = {};

    const STATUS_LABEL = { operando: 'Produzindo', parada: 'Parada', manutencao: 'Em manutenção' };
    const STATUS_CLASSE = { operando: 'produzindo', parada: 'parada', manutencao: 'manutencao' };

    function corOee(valor) {
        if (valor >= 85) return VegaCharts.palette.green;
        if (valor >= 60) return VegaCharts.palette.gold;
        return VegaCharts.palette.red;
    }

    function criarCard(maquina) {
        const div = document.createElement('div');
        div.className = 'machine-card';

        let statusClasse, statusTexto;
        if (maquina.gateway_offline) {
            statusClasse = 'offline';
            statusTexto = 'Gateway Offline';
        } else {
            const statusChave = maquina.status_maquina || 'operando';
            statusClasse = STATUS_CLASSE[statusChave] || 'produzindo';
            statusTexto = STATUS_LABEL[statusChave] || statusChave;
        }
        const motivo = (!maquina.gateway_offline && maquina.parada_atual) ? ` - ${maquina.parada_atual.motivo}` : '';

        div.innerHTML = `
            <h3>${maquina.nome}</h3>
            <span class="machine-status ${statusClasse}"><span class="dot"></span>${statusTexto}${motivo}</span>
            <div class="gauge-wrap"><canvas id="gauge-${maquina.maquina_id}"></canvas></div>
            <div class="gauge-label">OEE</div>
            <div class="mini-bars">
                <div class="mini-bar disp">
                    <div class="bar-track"><div class="bar-fill" style="height:${maquina.disponibilidade}%"></div></div>
                    <div class="bar-value">${maquina.disponibilidade}%</div>
                    <div class="bar-label">DISPONIB.</div>
                </div>
                <div class="mini-bar perf">
                    <div class="bar-track"><div class="bar-fill" style="height:${maquina.performance}%"></div></div>
                    <div class="bar-value">${maquina.performance}%</div>
                    <div class="bar-label">DESEMPENHO</div>
                </div>
                <div class="mini-bar qual">
                    <div class="bar-track"><div class="bar-fill" style="height:${maquina.qualidade}%"></div></div>
                    <div class="bar-value">${maquina.qualidade}%</div>
                    <div class="bar-label">QUALIDADE</div>
                </div>
            </div>
            <div class="machine-meta">${maquina.produzidas} produzidas · ${maquina.rejeitadas} refugadas</div>
        `;
        return div;
    }

    function desenharGauge(maquina) {
        const ctx = document.getElementById('gauge-' + maquina.maquina_id);
        if (!ctx) return;
        const valor = maquina.oee;
        const cor = corOee(valor);
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [valor, 100 - valor],
                    backgroundColor: [cor, '#e4e8ee'],
                    borderWidth: 0,
                }],
            },
            options: {
                circumference: 180,
                rotation: -90,
                cutout: '72%',
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
            },
            plugins: [{
                id: 'gaugeLabel',
                afterDraw(chart) {
                    const { ctx, chartArea } = chart;
                    ctx.save();
                    ctx.font = '700 22px Segoe UI';
                    ctx.fillStyle = '#16305c';
                    ctx.textAlign = 'center';
                    const cx = (chartArea.left + chartArea.right) / 2;
                    const cy = chartArea.bottom - 6;
                    ctx.fillText(valor + '%', cx, cy);
                    ctx.restore();
                },
            }],
        });
        gauges[maquina.maquina_id] = chart;
    }

    async function carregar(filtros) {
        ultimosFiltros = filtros;
        const grid = document.getElementById('machineGrid');
        try {
            const dados = await VegaCharts.fetchJSON(base + '/api/maquinas/status.php?' + VegaCharts.queryString(filtros));
            if (!dados) return;

            Object.values(gauges).forEach(g => g.destroy());
            grid.innerHTML = '';

            if (!dados.maquinas.length) {
                grid.innerHTML = '<div class="empty-state">Nenhuma máquina encontrada para os filtros selecionados.</div>';
                return;
            }

            dados.maquinas.forEach(maquina => grid.appendChild(criarCard(maquina)));
            dados.maquinas.forEach(desenharGauge);
        } catch (err) {
            console.error(err);
            window.mostrarToast(err.message, 'error');
        }
    }

    VegaFiltros.aoAplicar(carregar);

    window.VegaLiveEvents && window.VegaLiveEvents.aoReceber(function () {
        carregar(ultimosFiltros);
    });
})();
