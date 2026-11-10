(function () {
    const base = window.VEGAMES_BASE_URL || '';

    const PARADA_MOTIVOS = {
        nao_programada: ['Falta de matéria-prima', 'Falta de operador', 'Quebra de equipamento', 'Falta de energia', 'Ajuste de máquina', 'Outros'],
        programada: ['Troca de ferramenta', 'Limpeza programada', 'Setup de produção'],
        manutencao: ['Manutenção corretiva', 'Manutenção preventiva'],
    };
    const REFUGO_MOTIVOS = ['Peça fora do padrão dimensional', 'Falha de montagem', 'Defeito visual', 'Contaminação', 'Outros'];

    const ICONS = {
        producao: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 12 9 17 20 6"/></svg>',
        refugo: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9.5 17H2.5L12 3z"/><line x1="12" y1="10" x2="12" y2="14.5"/></svg>',
        parada_inicio: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>',
        parada_fim: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 12 9 17 20 6"/></svg>',
    };
    const FEED_LABEL = { producao: 'Peça produzida', refugo: 'Refugo registrado', parada_inicio: 'Parada iniciada', parada_fim: 'Parada finalizada' };

    let maquinaAtual = null;
    let pollTimer = null;
    let tipoParadaSelecionado = 'nao_programada';
    let motivoParadaSelecionado = '';
    let motivoRefugoSelecionado = '';
    let refugoQtd = 1;

    function mostrarToast(msg, tipo) {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.className = 'toast show' + (tipo === 'error' ? ' error' : '');
        setTimeout(() => toast.classList.remove('show'), 3200);
    }

    async function api(url, opts) {
        const resp = await fetch(base + url, opts);
        const dados = await resp.json();
        if (resp.status === 401) { window.location.href = base + '/ihm/index.php'; return null; }
        if (!resp.ok) throw new Error(dados.erro || 'Falha na operação.');
        return dados;
    }

    async function carregarMaquinas() {
        const grid = document.getElementById('machineGrid');
        try {
            const dados = await api('/api/ihm/status.php');
            if (!dados) return;
            if (!dados.maquinas.length) {
                grid.innerHTML = '<div class="empty-state">Nenhuma máquina cadastrada.</div>';
                return;
            }
            grid.innerHTML = '';
            dados.maquinas.forEach(m => {
                const div = document.createElement('div');
                div.className = 'ihm-machine-select-card';
                const statusTxt = m.status === 'parada' ? 'Parada' : (m.status === 'manutencao' ? 'Em manutenção' : 'Produzindo');
                div.innerHTML = `<h3>${m.nome}</h3><div class="loc">${m.localizacao || ''}</div><span class="machine-status ${m.status === 'operando' ? 'produzindo' : m.status}"><span class="dot"></span>${statusTxt}</span>`;
                div.addEventListener('click', () => abrirPainel(m.id));
                grid.appendChild(div);
            });
        } catch (err) {
            grid.innerHTML = '<div class="empty-state">' + err.message + '</div>';
        }
    }

    function abrirPainel(maquinaId) {
        maquinaAtual = maquinaId;

        history.replaceState(null, '', '?maquina_id=' + maquinaId);

        document.getElementById('viewSelecao').style.display = 'none';
        document.getElementById('viewPainel').style.display = 'block';
        atualizarPainel();
        carregarFeed();
        clearInterval(pollTimer);
        pollTimer = setInterval(() => { atualizarPainel(); carregarFeed(); }, 8000);
    }

    document.getElementById('btnVoltar').addEventListener('click', () => {
        clearInterval(pollTimer);
        maquinaAtual = null;
        history.replaceState(null, '', window.location.pathname);
        document.getElementById('viewPainel').style.display = 'none';
        document.getElementById('viewSelecao').style.display = 'block';
        carregarMaquinas();
    });

    async function atualizarPainel() {
        if (!maquinaAtual) return;
        try {
            const dados = await api('/api/ihm/status.php');
            if (!dados) return;
            const m = dados.maquinas.find(x => x.id === maquinaAtual);
            if (!m) return;

            document.getElementById('maquinaNome').textContent = m.nome;
            document.getElementById('produzidasHoje').textContent = m.produzidas_hoje;
            document.getElementById('rejeitadasHoje').textContent = m.rejeitadas_hoje;

            if (m.ordem_ativa) {
                document.getElementById('ordemAtiva').textContent = `Ordem ${m.ordem_ativa.numero} · meta ${m.ordem_ativa.meta_producao} peças`;
            } else if (m.ordem_proxima) {
                document.getElementById('ordemAtiva').textContent = `Ordem ${m.ordem_proxima.numero} pronta para iniciar`;
            } else {
                document.getElementById('ordemAtiva').textContent = 'Nenhuma ordem cadastrada';
            }

            const progresso = document.getElementById('ordemProgress');
            if (m.ordem_ativa) {
                progresso.style.display = 'block';
                const produzidas = m.ordem_ativa.produzidas;
                const meta = m.ordem_ativa.meta_producao;
                const pct = meta > 0 ? Math.round((produzidas / meta) * 100) : 0;
                document.getElementById('ordemProgressLabel').textContent = `Ordem ${m.ordem_ativa.numero}: ${produzidas} / ${meta} peças`;
                document.getElementById('ordemProgressPct').textContent = pct + '%';
                document.getElementById('ordemProgressBar').style.width = Math.min(100, pct) + '%';
            } else {
                progresso.style.display = 'none';
            }

            const badge = document.getElementById('statusBadge');
            const parada = m.status === 'parada';
            badge.className = 'machine-status ' + (m.status === 'operando' ? 'produzindo' : m.status);
            badge.innerHTML = '<span class="dot"></span>' + (parada ? 'Parada' : (m.status === 'manutencao' ? 'Em manutenção' : 'Produzindo'))
                + (m.parada_atual ? ' - ' + m.parada_atual.motivo : '');

            document.getElementById('btnIniciarParada').style.display = parada ? 'none' : 'flex';
            document.getElementById('btnFinalizarParada').style.display = parada ? 'flex' : 'none';
            document.getElementById('btnProduzir').disabled = parada;
            document.getElementById('btnRefugo').disabled = parada;

            document.getElementById('btnIniciarOrdem').style.display = (!m.ordem_ativa && m.ordem_proxima) ? 'flex' : 'none';
            document.getElementById('btnConcluirOrdem').style.display = m.ordem_ativa ? 'flex' : 'none';

            const fill = document.getElementById('ihmOeeFill');
            fill.style.width = m.oee + '%';
            fill.style.background = m.oee >= 85 ? '#2fa84f' : (m.oee >= 60 ? '#e0a030' : '#d9483f');
            document.getElementById('ihmOeeVal').textContent = m.oee + '%';
        } catch (err) {
            mostrarToast(err.message, 'error');
        }
    }

    async function carregarFeed() {
        if (!maquinaAtual) return;
        try {
            const dados = await api('/api/ihm/eventos_recentes.php?maquina_id=' + maquinaAtual);
            if (!dados) return;
            const el = document.getElementById('feedEventos');
            if (!dados.eventos.length) {
                el.innerHTML = '<div class="empty-state">Sem eventos ainda.</div>';
                return;
            }
            el.innerHTML = dados.eventos.map(ev => {
                const hora = new Date(ev.quando.replace(' ', 'T')).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                let texto = FEED_LABEL[ev.tipo];
                if (ev.tipo === 'producao') texto += ` (+${ev.quantidade})`;
                if (ev.tipo === 'refugo') texto += ev.motivo ? ` — ${ev.motivo} (${ev.quantidade})` : ` (${ev.quantidade})`;
                if ((ev.tipo === 'parada_inicio' || ev.tipo === 'parada_fim') && ev.motivo) texto += ` — ${ev.motivo}`;
                return `<div class="ihm-feed-item"><span class="ihm-feed-icon ${ev.tipo}">${ICONS[ev.tipo]}</span>
                    <div class="ihm-feed-text"><strong>${texto}</strong><span>${hora}</span></div></div>`;
            }).join('');
        } catch (err) {  }
    }

    document.getElementById('btnProduzir').addEventListener('click', async function () {
        this.disabled = true;
        try {
            await api('/api/ihm/registrar_producao.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maquina_id: maquinaAtual, quantidade: 1 }),
            });
            mostrarToast('Peça produzida registrada.');
            atualizarPainel();
            carregarFeed();
        } catch (err) {
            mostrarToast(err.message, 'error');
        } finally {
            this.disabled = false;
        }
    });

    function montarChips(containerId, lista, aoClicar) {
        const el = document.getElementById(containerId);
        el.innerHTML = '';
        lista.forEach(m => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'ihm-chip';
            chip.textContent = m;
            chip.addEventListener('click', () => aoClicar(m, chip));
            el.appendChild(chip);
        });
    }

    function marcarChipSelecionado(containerId, chipSelecionado) {
        document.querySelectorAll('#' + containerId + ' .ihm-chip').forEach(c => {
            c.style.background = c === chipSelecionado ? 'var(--navy-800)' : '';
            c.style.color = c === chipSelecionado ? '#fff' : '';
            c.style.borderColor = c === chipSelecionado ? 'var(--navy-800)' : '';
        });
    }

    document.getElementById('btnRefugo').addEventListener('click', function () {
        refugoQtd = 1;
        motivoRefugoSelecionado = '';
        document.getElementById('refugoQtd').textContent = refugoQtd;
        document.getElementById('motivoRefugoOutro').value = '';
        montarChips('chipsRefugo', REFUGO_MOTIVOS, (m, chip) => {
            motivoRefugoSelecionado = m;
            document.getElementById('motivoRefugoOutro').value = '';
            marcarChipSelecionado('chipsRefugo', chip);
        });
        document.getElementById('overlayRefugo').classList.add('open');
    });
    document.getElementById('btnCancelarRefugo').addEventListener('click', () => document.getElementById('overlayRefugo').classList.remove('open'));
    document.getElementById('btnRefugoMenos').addEventListener('click', () => { refugoQtd = Math.max(1, refugoQtd - 1); document.getElementById('refugoQtd').textContent = refugoQtd; });
    document.getElementById('btnRefugoMais').addEventListener('click', () => { refugoQtd++; document.getElementById('refugoQtd').textContent = refugoQtd; });

    document.getElementById('btnConfirmarRefugo').addEventListener('click', async function () {
        const outro = document.getElementById('motivoRefugoOutro').value.trim();
        const motivo = outro || motivoRefugoSelecionado;
        if (!motivo) { mostrarToast('Selecione ou informe um motivo.', 'error'); return; }

        this.disabled = true;
        try {
            await api('/api/ihm/registrar_refugo.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maquina_id: maquinaAtual, quantidade: refugoQtd, motivo }),
            });
            mostrarToast('Refugo registrado.');
            document.getElementById('overlayRefugo').classList.remove('open');
            atualizarPainel();
            carregarFeed();
        } catch (err) {
            mostrarToast(err.message, 'error');
        } finally {
            this.disabled = false;
        }
    });

    function renderChipsParada() {
        motivoParadaSelecionado = '';
        montarChips('chipsParada', PARADA_MOTIVOS[tipoParadaSelecionado], (m, chip) => {
            motivoParadaSelecionado = m;
            document.getElementById('motivoParadaOutro').value = '';
            marcarChipSelecionado('chipsParada', chip);
        });
    }

    document.querySelectorAll('#tabsTipoParada .ihm-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('#tabsTipoParada .ihm-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            tipoParadaSelecionado = this.dataset.tipo;
            renderChipsParada();
        });
    });

    document.getElementById('btnIniciarParada').addEventListener('click', function () {
        tipoParadaSelecionado = 'nao_programada';
        document.querySelectorAll('#tabsTipoParada .ihm-tab').forEach(t => t.classList.toggle('active', t.dataset.tipo === 'nao_programada'));
        document.getElementById('motivoParadaOutro').value = '';
        renderChipsParada();
        document.getElementById('overlayParada').classList.add('open');
    });
    document.getElementById('btnCancelarParada').addEventListener('click', () => document.getElementById('overlayParada').classList.remove('open'));

    document.getElementById('btnConfirmarParada').addEventListener('click', async function () {
        const outro = document.getElementById('motivoParadaOutro').value.trim();
        const motivo = outro || motivoParadaSelecionado;
        if (!motivo) { mostrarToast('Selecione ou informe um motivo.', 'error'); return; }

        this.disabled = true;
        try {
            await api('/api/ihm/parada.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maquina_id: maquinaAtual, acao: 'inicio', tipo: tipoParadaSelecionado, motivo }),
            });
            mostrarToast('Parada iniciada.');
            document.getElementById('overlayParada').classList.remove('open');
            atualizarPainel();
            carregarFeed();
        } catch (err) {
            mostrarToast(err.message, 'error');
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('btnFinalizarParada').addEventListener('click', async function () {
        this.disabled = true;
        try {
            await api('/api/ihm/parada.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maquina_id: maquinaAtual, acao: 'fim' }),
            });
            mostrarToast('Parada finalizada.');
            atualizarPainel();
            carregarFeed();
        } catch (err) {
            mostrarToast(err.message, 'error');
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('btnIniciarOrdem').addEventListener('click', async function () {
        this.disabled = true;
        try {
            await api('/api/ihm/ordem_iniciar.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maquina_id: maquinaAtual }),
            });
            mostrarToast('Ordem iniciada.');
            atualizarPainel();
        } catch (err) {
            mostrarToast(err.message, 'error');
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('btnConcluirOrdem').addEventListener('click', async function () {
        this.disabled = true;
        try {
            await api('/api/ihm/ordem_concluir.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maquina_id: maquinaAtual }),
            });
            mostrarToast('Ordem concluída.');
            atualizarPainel();
        } catch (err) {
            mostrarToast(err.message, 'error');
        } finally {
            this.disabled = false;
        }
    });

    function atualizarRelogio() {
        const el = document.getElementById('ihmRelogio');
        if (el) el.textContent = new Date().toLocaleTimeString('pt-BR');
    }
    setInterval(atualizarRelogio, 1000);
    atualizarRelogio();

    document.getElementById('btnSairIhm').addEventListener('click', async function () {
        try { await fetch(base + '/api/auth/logout.php', { method: 'POST' }); } catch (e) {  }
        window.location.href = base + '/ihm/index.php';
    });

    window.VegaLiveEvents && window.VegaLiveEvents.aoReceber(function () {
        if (!maquinaAtual) {
            carregarMaquinas();
            return;
        }
        atualizarPainel();
        carregarFeed();
    });

    const maquinaIdNaUrl = new URLSearchParams(window.location.search).get('maquina_id');
    if (maquinaIdNaUrl) {
        abrirPainel(parseInt(maquinaIdNaUrl, 10));
    } else {
        carregarMaquinas();
    }
})();
