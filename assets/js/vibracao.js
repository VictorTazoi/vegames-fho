(function () {
    const base = window.VEGAMES_BASE_URL || '';
    let grafico = null;
    let timer = null;

    const STATUS = {
        normal: ['Normal', VegaCharts.palette.green],
        anomalia: ['ANOMALIA', VegaCharts.palette.red],
        coletando: ['Coletando', VegaCharts.palette.gold],
        sem_sinal: ['Sem sinal', VegaCharts.palette.gray],
        sem_dados: ['Sem dados', VegaCharts.palette.gray],
    };

    function formatarG(valor) {
        if (valor === null || valor === undefined) return '--';
        return Number(valor).toFixed(3) + ' g';
    }

    function atualizarCards(resumo) {
        const [texto, cor] = STATUS[resumo.status] || STATUS.sem_dados;
        const status = document.getElementById('vibStatus');
        status.textContent = texto;
        status.style.color = cor;

        const hora = resumo.ultima_leitura ? resumo.ultima_leitura.substr(11) : '--';
        document.getElementById('vibUltima').textContent = 'Última leitura: ' + hora;
        document.getElementById('vibRms').textContent = formatarG(resumo.ultimo_rms);
        document.getElementById('vibPico').textContent = formatarG(resumo.ultimo_pico);
        document.getElementById('vibAnomalias').textContent = resumo.avaliadas ? resumo.anomalias : '--';
        document.getElementById('vibTotal').textContent =
            'Leituras: ' + resumo.total + ' (avaliadas: ' + resumo.avaliadas + ')';
    }

    function desenharGrafico(leituras) {
        const horas = leituras.map(l => l.horario.substr(11));
        const ehAnomalia = l => l.anomalia === 1;

        const tamanhoPonto = leituras.map(l => ehAnomalia(l) ? 5 : 0);
        const corPonto = leituras.map(l => ehAnomalia(l) ? VegaCharts.palette.red : VegaCharts.palette.navy);

        if (grafico) grafico.destroy();
        grafico = new Chart(document.getElementById('chartVibracao'), {
            type: 'line',
            data: {
                labels: horas,
                datasets: [
                    {
                        label: 'RMS (g)',
                        data: leituras.map(l => l.rms),
                        borderColor: VegaCharts.palette.navy,
                        backgroundColor: 'transparent',
                        tension: 0.25,
                        pointRadius: tamanhoPonto,
                        pointBackgroundColor: corPonto,
                        pointBorderColor: corPonto,
                    },
                    {
                        label: 'Pico (g)',
                        data: leituras.map(l => l.pico),
                        borderColor: VegaCharts.palette.gold,
                        backgroundColor: 'transparent',
                        tension: 0.25,
                        borderWidth: 1.5,
                        pointRadius: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { title: { display: true, text: 'Horário' }, ticks: { maxTicksLimit: 12, maxRotation: 0 } },
                    y: { title: { display: true, text: 'Aceleração (g)' } },
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            afterBody: itens => {
                                const l = leituras[itens[0].dataIndex];
                                if (l.anomalia === 1) return 'Anomalia';
                                if (l.anomalia === 0) return 'Normal';
                                return 'Ainda não avaliada';
                            },
                        },
                    },
                },
            },
        });
    }

    function mostrarAviso(texto) {
        const aviso = document.getElementById('vibAviso');
        aviso.textContent = texto || '';
        aviso.style.display = texto ? '' : 'none';
    }

    async function carregar() {
        const filtros = {
            maquina_id: document.getElementById('vibMaquina').value,
            minutos: document.getElementById('vibMinutos').value,
        };
        try {
            const dados = await VegaCharts.fetchJSON(base + '/api/vibracao/dados.php?' + VegaCharts.queryString(filtros));
            if (!dados) return;

            atualizarCards(dados.resumo);
            desenharGrafico(dados.leituras);

            if (dados.aviso) {
                mostrarAviso(dados.aviso);
            } else if (dados.leituras.length === 0) {
                mostrarAviso('Nenhuma leitura neste período. Veja se o ESP32 está ligado e se o bridge MQTT está rodando.');
            } else {
                mostrarAviso('');
            }
        } catch (erro) {
            console.error(erro);
            window.mostrarToast(erro.message, 'error');
        }
    }

    function agendarAtualizacao() {
        if (timer) clearInterval(timer);
        const ms = parseInt(document.getElementById('vibAuto').value, 10);
        if (ms > 0) timer = setInterval(carregar, ms);
    }

    document.getElementById('vibAtualizar').addEventListener('click', carregar);
    document.getElementById('vibMaquina').addEventListener('change', carregar);
    document.getElementById('vibMinutos').addEventListener('change', carregar);
    document.getElementById('vibAuto').addEventListener('change', agendarAtualizacao);

    carregar();
    agendarAtualizacao();
})();
