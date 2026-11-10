(function () {
    const base = window.VEGAMES_BASE_URL || '';
    let itensCache = [];
    let maquinasCache = [];
    let turnosCache = [];

    function mostrarResultado(elId, sucesso, mensagem) {
        const el = document.getElementById(elId);
        el.style.display = 'block';
        el.className = 'config-test-result ' + (sucesso ? 'ok' : 'erro');
        el.textContent = mensagem;
    }

    function svgIcon(inner) {
        return '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + inner + '</svg>';
    }
    const ICON_EYE = svgIcon('<path d="M2 12s3.7-6.5 10-6.5S22 12 22 12s-3.7 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.6"/>');
    const ICON_EDIT = svgIcon('<path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16v4z"/><line x1="13.5" y1="7" x2="17" y2="10.5"/>');
    const ICON_TRASH = svgIcon('<polyline points="4 7 20 7"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>');

    const STATUS_LABEL = {
        planejada: 'Planejada',
        em_andamento: 'Em andamento',
        finalizada: 'Finalizada',
        cancelada: 'Cancelada',
    };

    function formatarTempoCiclo(seg) {
        return Number(seg).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + ' s/peça';
    }

    function formatarDataHora(valor) {
        if (!valor) return '—';
        return new Date(valor.replace(' ', 'T')).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    }

    function paraInputDatetime(valor) {
        if (!valor) return '';
        return valor.replace(' ', 'T').slice(0, 16);
    }

    function paraBancoDatetime(valor) {
        if (!valor) return '';
        return valor.replace('T', ' ') + ':00';
    }

    function formatarMinutos(segundos) {
        const min = segundos / 60;
        return min.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' min';
    }

    function atualizarTempoPlanejado(prefixo) {
        const itemId = parseInt(document.getElementById(prefixo + 'Item').value, 10);
        const meta = parseInt(document.getElementById(prefixo + 'Meta').value, 10) || 0;
        const item = itensCache.find(i => i.id === itemId);
        const campo = document.getElementById(prefixo + 'TempoPlanejado');
        if (!item || meta <= 0) {
            campo.value = '';
            return;
        }
        campo.value = formatarMinutos(meta * Number(item.tempo_ciclo_seg));
    }

    document.getElementById('ordemItem').addEventListener('change', () => atualizarTempoPlanejado('ordem'));
    document.getElementById('ordemMeta').addEventListener('input', () => atualizarTempoPlanejado('ordem'));
    document.getElementById('editItem').addEventListener('change', () => atualizarTempoPlanejado('edit'));
    document.getElementById('editMeta').addEventListener('input', () => atualizarTempoPlanejado('edit'));

    function preencherSelect(select, lista, formatar) {
        select.innerHTML = '<option value="">Selecione...</option>'
            + lista.map(item => `<option value="${item.id}">${formatar(item)}</option>`).join('');
    }

    async function carregarItens() {
        const resp = await fetch(base + '/api/itens/listar.php');
        const dados = await resp.json();
        itensCache = dados.itens || [];

        const tbody = document.querySelector('#tabelaItens tbody');
        tbody.innerHTML = itensCache.length
            ? itensCache.map(i => `<tr>
                <td>${i.codigo}</td>
                <td>${i.nome}</td>
                <td>${formatarTempoCiclo(i.tempo_ciclo_seg)}</td>
                <td>
                    <div class="table-actions">
                        <button type="button" class="btn-icon-sm" title="Editar" data-acao="editar-item" data-id="${i.id}">${ICON_EDIT}</button>
                        <button type="button" class="btn-icon-sm danger" title="Excluir" data-acao="excluir-item" data-id="${i.id}">${ICON_TRASH}</button>
                    </div>
                </td>
            </tr>`).join('')
            : '<tr><td colspan="4" style="color:var(--gray-500);">Nenhum item cadastrado ainda.</td></tr>';

        preencherSelect(document.getElementById('ordemItem'), itensCache, i => `${i.codigo} — ${i.nome}`);
        preencherSelect(document.getElementById('editItem'), itensCache, i => `${i.codigo} — ${i.nome}`);
    }

    async function carregarOpcoes() {
        const resp = await fetch(base + '/api/filtros/opcoes.php');
        const dados = await resp.json();
        maquinasCache = dados.maquinas || [];
        turnosCache = dados.turnos || [];

        preencherSelect(document.getElementById('ordemMaquina'), maquinasCache, m => m.nome);
        preencherSelect(document.getElementById('editMaquina'), maquinasCache, m => m.nome);
        preencherSelect(document.getElementById('ordemTurno'), turnosCache, t => t.nome);
        preencherSelect(document.getElementById('editTurno'), turnosCache, t => t.nome);
    }

    async function carregarOrdens() {
        const resp = await fetch(base + '/api/ordens/listar.php');
        const dados = await resp.json();
        const tbody = document.querySelector('#tabelaOrdens tbody');
        tbody.innerHTML = (dados.ordens || []).length
            ? dados.ordens.map(o => `<tr>
                <td>${o.numero}</td>
                <td>${o.item_codigo ? o.item_codigo + ' — ' + o.item_nome : '—'}</td>
                <td>${o.maquina_nome}</td>
                <td>${o.turno_nome}</td>
                <td>${o.produzidas} / ${o.meta_producao}</td>
                <td>${STATUS_LABEL[o.status] || o.status}</td>
                <td>${o.oee_oee !== null ? Number(o.oee_oee).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%' : '—'}</td>
                <td>${formatarDataHora(o.inicio)}</td>
                <td>
                    <div class="table-actions">
                        <button type="button" class="btn-icon-sm" title="Ver" data-acao="ver" data-id="${o.id}">${ICON_EYE}</button>
                        <button type="button" class="btn-icon-sm" title="Editar" data-acao="editar" data-id="${o.id}">${ICON_EDIT}</button>
                        <button type="button" class="btn-icon-sm danger" title="Excluir" data-acao="excluir" data-id="${o.id}">${ICON_TRASH}</button>
                    </div>
                </td>
            </tr>`).join('')
            : '<tr><td colspan="9" style="color:var(--gray-500);">Nenhuma ordem cadastrada ainda.</td></tr>';
    }

    document.getElementById('formItem').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const resp = await fetch(base + '/api/itens/salvar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    codigo: document.getElementById('itemCodigo').value.trim(),
                    nome: document.getElementById('itemNome').value.trim(),
                    tempo_ciclo_seg: parseFloat(document.getElementById('itemTempoCiclo').value) || 0,
                }),
            });
            const resultado = await resp.json();
            if (!resp.ok) throw new Error(resultado.erro || 'Não foi possível cadastrar o item.');
            mostrarResultado('itemResultado', true, 'Item cadastrado com sucesso.');
            this.reset();
            await carregarItens();
        } catch (err) {
            mostrarResultado('itemResultado', false, err.message);
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('formOrdem').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const inicioLocal = document.getElementById('ordemInicio').value;
            const resp = await fetch(base + '/api/ordens/salvar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    numero: document.getElementById('ordemNumero').value.trim(),
                    item_id: parseInt(document.getElementById('ordemItem').value, 10) || 0,
                    maquina_id: parseInt(document.getElementById('ordemMaquina').value, 10) || 0,
                    turno_id: parseInt(document.getElementById('ordemTurno').value, 10) || 0,
                    meta_producao: parseInt(document.getElementById('ordemMeta').value, 10) || 0,
                    inicio: paraBancoDatetime(inicioLocal),
                }),
            });
            const resultado = await resp.json();
            if (!resp.ok) throw new Error(resultado.erro || 'Não foi possível cadastrar a ordem.');
            mostrarResultado('ordemResultado', true, 'Ordem cadastrada com sucesso.');
            this.reset();
            await carregarOrdens();
        } catch (err) {
            mostrarResultado('ordemResultado', false, err.message);
        } finally {
            btn.disabled = false;
        }
    });

    const modalEditarItem = document.getElementById('modalEditarItem');
    function fecharEditarItem() { modalEditarItem.classList.remove('open'); }
    document.getElementById('btnFecharEditarItem').addEventListener('click', fecharEditarItem);
    document.getElementById('btnCancelarEditarItem').addEventListener('click', fecharEditarItem);
    modalEditarItem.addEventListener('click', e => { if (e.target === modalEditarItem) fecharEditarItem(); });

    function abrirEditarItem(id) {
        const item = itensCache.find(i => i.id === id);
        if (!item) return;
        document.getElementById('editItemId').value = item.id;
        document.getElementById('editItemCodigo').value = item.codigo;
        document.getElementById('editItemNome').value = item.nome;
        document.getElementById('editItemTempoCiclo').value = item.tempo_ciclo_seg;
        document.getElementById('editItemResultado').style.display = 'none';
        modalEditarItem.classList.add('open');
    }

    document.getElementById('formEditarItem').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const resp = await fetch(base + '/api/itens/atualizar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: parseInt(document.getElementById('editItemId').value, 10),
                    codigo: document.getElementById('editItemCodigo').value.trim(),
                    nome: document.getElementById('editItemNome').value.trim(),
                    tempo_ciclo_seg: parseFloat(document.getElementById('editItemTempoCiclo').value) || 0,
                }),
            });
            const resultado = await resp.json();
            if (!resp.ok) throw new Error(resultado.erro || 'Não foi possível salvar as alterações.');
            window.mostrarToast('Item atualizado com sucesso.');
            fecharEditarItem();
            await carregarItens();
        } catch (err) {
            mostrarResultado('editItemResultado', false, err.message);
        } finally {
            btn.disabled = false;
        }
    });

    async function excluirItem(id) {
        if (!confirm('Excluir este item? Esta ação não pode ser desfeita.')) return;
        try {
            const resp = await fetch(base + '/api/itens/excluir.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            const resultado = await resp.json();
            if (!resp.ok) throw new Error(resultado.erro || 'Não foi possível excluir o item.');
            window.mostrarToast('Item excluído.');
            await carregarItens();
        } catch (err) {
            window.mostrarToast(err.message, 'error');
        }
    }

    document.getElementById('tabelaItens').addEventListener('click', function (e) {
        const btn = e.target.closest('[data-acao]');
        if (!btn) return;
        const id = parseInt(btn.dataset.id, 10);
        if (btn.dataset.acao === 'editar-item') abrirEditarItem(id);
        else if (btn.dataset.acao === 'excluir-item') excluirItem(id);
    });

    const modalVer = document.getElementById('modalVer');
    function fecharVer() { modalVer.classList.remove('open'); }
    document.getElementById('btnFecharVer').addEventListener('click', fecharVer);
    document.getElementById('btnFecharVer2').addEventListener('click', fecharVer);
    modalVer.addEventListener('click', e => { if (e.target === modalVer) fecharVer(); });

    async function abrirVer(id) {
        const resp = await fetch(base + '/api/ordens/obter.php?id=' + id);
        const dados = await resp.json();
        if (!resp.ok) { window.mostrarToast(dados.erro || 'Erro ao carregar ordem.', 'error'); return; }
        const o = dados.ordem;
        const linhas = [
            ['Número', o.numero],
            ['Item', o.item_codigo ? `${o.item_codigo} — ${o.item_nome}` : '—'],
            ['Máquina / Estação', o.maquina_nome],
            ['Turno', o.turno_nome],
            ['Produzido / Meta', `${o.produzidas} / ${o.meta_producao}`],
            ['Tempo planejado', formatarMinutos(o.tempo_planejado_seg)],
            ['Tempo de ciclo ideal', formatarTempoCiclo(o.tempo_ciclo_ideal_seg)],
            ['Status', STATUS_LABEL[o.status] || o.status],
            ['Início', formatarDataHora(o.inicio)],
            ['Fim', formatarDataHora(o.fim)],
        ];
        if (o.oee_oee !== null) {
            linhas.push(
                ['OEE (desta ordem)', Number(o.oee_oee).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%'],
                ['— Disponibilidade', Number(o.oee_disponibilidade).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%'],
                ['— Performance', Number(o.oee_performance).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%'],
                ['— Qualidade', Number(o.oee_qualidade).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%'],
            );
        }
        document.getElementById('verConteudo').innerHTML = linhas.map(([label, valor]) =>
            `<div class="modal-detail-row"><span class="label">${label}</span><span class="value">${valor}</span></div>`
        ).join('');
        modalVer.classList.add('open');
    }

    const modalEditar = document.getElementById('modalEditar');
    function fecharEditar() { modalEditar.classList.remove('open'); }
    document.getElementById('btnFecharEditar').addEventListener('click', fecharEditar);
    document.getElementById('btnCancelarEditar').addEventListener('click', fecharEditar);
    modalEditar.addEventListener('click', e => { if (e.target === modalEditar) fecharEditar(); });

    async function abrirEditar(id) {
        const resp = await fetch(base + '/api/ordens/obter.php?id=' + id);
        const dados = await resp.json();
        if (!resp.ok) { window.mostrarToast(dados.erro || 'Erro ao carregar ordem.', 'error'); return; }
        const o = dados.ordem;

        document.getElementById('editId').value = o.id;
        document.getElementById('editNumero').value = o.numero;
        document.getElementById('editItem').value = o.item_id || '';
        document.getElementById('editMaquina').value = o.maquina_id;
        document.getElementById('editTurno').value = o.turno_id;
        document.getElementById('editMeta').value = o.meta_producao;
        document.getElementById('editTempoPlanejado').value = formatarMinutos(o.tempo_planejado_seg);
        document.getElementById('editInicio').value = paraInputDatetime(o.inicio);
        document.getElementById('editFim').value = paraInputDatetime(o.fim);
        document.getElementById('editStatus').value = o.status;
        document.getElementById('editResultado').style.display = 'none';

        modalEditar.classList.add('open');
    }

    document.getElementById('formEditarOrdem').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const resp = await fetch(base + '/api/ordens/atualizar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: parseInt(document.getElementById('editId').value, 10),
                    numero: document.getElementById('editNumero').value.trim(),
                    item_id: parseInt(document.getElementById('editItem').value, 10) || 0,
                    maquina_id: parseInt(document.getElementById('editMaquina').value, 10) || 0,
                    turno_id: parseInt(document.getElementById('editTurno').value, 10) || 0,
                    meta_producao: parseInt(document.getElementById('editMeta').value, 10) || 0,
                    inicio: paraBancoDatetime(document.getElementById('editInicio').value),
                    fim: paraBancoDatetime(document.getElementById('editFim').value),
                    status: document.getElementById('editStatus').value,
                }),
            });
            const resultado = await resp.json();
            if (!resp.ok) throw new Error(resultado.erro || 'Não foi possível salvar as alterações.');
            window.mostrarToast('Ordem atualizada com sucesso.');
            fecharEditar();
            await carregarOrdens();
        } catch (err) {
            mostrarResultado('editResultado', false, err.message);
        } finally {
            btn.disabled = false;
        }
    });

    async function excluir(id) {
        if (!confirm('Excluir esta ordem de produção? Esta ação não pode ser desfeita.')) return;
        try {
            const resp = await fetch(base + '/api/ordens/excluir.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            const resultado = await resp.json();
            if (!resp.ok) throw new Error(resultado.erro || 'Não foi possível excluir a ordem.');
            window.mostrarToast('Ordem excluída.');
            await carregarOrdens();
        } catch (err) {
            window.mostrarToast(err.message, 'error');
        }
    }

    document.getElementById('tabelaOrdens').addEventListener('click', function (e) {
        const btn = e.target.closest('[data-acao]');
        if (!btn) return;
        const id = parseInt(btn.dataset.id, 10);
        if (btn.dataset.acao === 'ver') abrirVer(id);
        else if (btn.dataset.acao === 'editar') abrirEditar(id);
        else if (btn.dataset.acao === 'excluir') excluir(id);
    });

    window.VegaLiveEvents && window.VegaLiveEvents.aoReceber(function () {
        carregarOrdens();
    });

    carregarItens();
    carregarOpcoes();
    carregarOrdens();
})();
