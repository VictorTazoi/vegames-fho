(function () {
    const base = window.VEGAMES_BASE_URL || '';
    const lista = document.getElementById('listaMaquinasCadastro');
    if (!lista) return;

    const STATUS_LABEL = { operando: 'Operando', parada: 'Parada', manutencao: 'Em manutenção' };

    let maquinas = [];
    let confirmandoId = null;

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function render() {
        if (!maquinas.length) {
            lista.innerHTML = '<div class="empty-state">Nenhuma máquina cadastrada.</div>';
            return;
        }

        lista.innerHTML = maquinas.map(m => `
            <div class="machine-manage-row" data-id="${m.id}">
                <div class="mm-fields">
                    <input type="text" class="mm-nome" value="${escapeHtml(m.nome)}" placeholder="Nome da máquina">
                    <input type="text" class="mm-localizacao" value="${escapeHtml(m.localizacao || '')}" placeholder="Localização (opcional)">
                </div>
                <span class="mm-status">${STATUS_LABEL[m.status] || m.status}</span>
                <div class="mm-actions">
                    <button type="button" class="btn btn-outline btn-sm mm-salvar">Salvar</button>
                    <button type="button" class="btn btn-danger btn-sm mm-resetar">Resetar histórico</button>
                </div>
                ${confirmandoId === m.id ? `
                <div class="machine-manage-confirm">
                    <span>Apaga toda a produção, refugo, paradas e ordens desta máquina. Não pode ser desfeito. Confirma?</span>
                    <span style="display:flex;gap:8px;">
                        <button type="button" class="btn btn-danger btn-sm mm-resetar-confirmar">Sim, resetar</button>
                        <button type="button" class="btn btn-outline btn-sm mm-resetar-cancelar">Cancelar</button>
                    </span>
                </div>` : ''}
            </div>
        `).join('');
    }

    async function carregar() {
        try {
            const resp = await fetch(base + '/api/maquinas/listar.php');
            const dados = await resp.json();
            if (!resp.ok) throw new Error(dados.erro || 'Falha ao carregar máquinas.');
            maquinas = dados.maquinas;
            render();
        } catch (err) {
            lista.innerHTML = '<div class="empty-state">Erro ao carregar máquinas.</div>';
            console.error(err);
        }
    }

    async function salvar(id, nome, localizacao) {
        const resp = await fetch(base + '/api/maquinas/atualizar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nome, localizacao }),
        });
        const dados = await resp.json();
        if (!resp.ok) throw new Error(dados.erro || 'Falha ao salvar máquina.');
        return dados;
    }

    async function resetar(id) {
        const resp = await fetch(base + '/api/maquinas/resetar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, confirmar: true }),
        });
        const dados = await resp.json();
        if (!resp.ok) throw new Error(dados.erro || 'Falha ao resetar histórico.');
        return dados;
    }

    lista.addEventListener('click', async (ev) => {
        const row = ev.target.closest('.machine-manage-row');
        if (!row) return;
        const id = Number(row.dataset.id);

        if (ev.target.classList.contains('mm-salvar')) {
            const nome = row.querySelector('.mm-nome').value.trim();
            const localizacao = row.querySelector('.mm-localizacao').value.trim();
            if (!nome) {
                window.mostrarToast('Informe o nome da máquina.', 'error');
                return;
            }
            try {
                await salvar(id, nome, localizacao);
                const m = maquinas.find(mm => mm.id === id);
                if (m) { m.nome = nome; m.localizacao = localizacao; }
                window.mostrarToast('Máquina atualizada.', 'success');
            } catch (err) {
                window.mostrarToast(err.message, 'error');
            }
            return;
        }

        if (ev.target.classList.contains('mm-resetar')) {
            confirmandoId = id;
            render();
            return;
        }

        if (ev.target.classList.contains('mm-resetar-cancelar')) {
            confirmandoId = null;
            render();
            return;
        }

        if (ev.target.classList.contains('mm-resetar-confirmar')) {
            try {
                await resetar(id);
                confirmandoId = null;
                window.mostrarToast('Histórico resetado.', 'success');
                await carregar();
            } catch (err) {
                window.mostrarToast(err.message, 'error');
            }
            return;
        }
    });

    carregar();
})();
