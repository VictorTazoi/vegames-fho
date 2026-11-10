window.VegaFiltros = (function () {
    const base = window.VEGAMES_BASE_URL || '';
    let opcoes = { maquinas: [], turnos: [], ordens: [] };
    const callbacks = [];

    function preencherDatalist(id, itens, mapa) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = '';
        itens.forEach(([label, valor]) => {
            const opt = document.createElement('option');
            opt.value = label;
            el.appendChild(opt);
            mapa[label] = valor;
        });
    }

    const mapaMaquinas = {};
    const mapaTurnos = {};
    const mapaOrdens = {};

    async function carregarOpcoes() {
        const resp = await fetch(base + '/api/filtros/opcoes.php');
        if (!resp.ok) throw new Error('Falha ao carregar filtros');
        opcoes = await resp.json();

        preencherDatalist('listaMaquinas', opcoes.maquinas.map(m => [m.nome, m.id]), mapaMaquinas);
        preencherDatalist('listaTurnos', opcoes.turnos.map(t => [t.nome, t.id]), mapaTurnos);
        preencherDatalist('listaOrdens', opcoes.ordens.map(o => [o.numero, o.id]), mapaOrdens);
    }

    function coletar() {
        const ordemTexto = (document.getElementById('filtroOrdem') || {}).value || '';
        const turnoTexto = (document.getElementById('filtroTurno') || {}).value || '';
        const maquinaTexto = (document.getElementById('filtroMaquina') || {}).value || '';
        const dataInicio = (document.getElementById('filtroDataInicio') || {}).value || '';
        const dataFim = (document.getElementById('filtroDataFim') || {}).value || '';

        return {
            ordem_id: mapaOrdens[ordemTexto] || null,
            turno_id: mapaTurnos[turnoTexto] || null,
            maquina_id: mapaMaquinas[maquinaTexto] || null,
            data_inicio: dataInicio || null,
            data_fim: dataFim || null,
        };
    }

    function aoAplicar(fn) {
        callbacks.push(fn);
    }

    function init() {
        carregarOpcoes()
            .then(() => callbacks.forEach(fn => fn(coletar())))
            .catch(err => {
                console.error(err);
                window.mostrarToast && window.mostrarToast('Não foi possível carregar as opções de filtro.', 'error');
            });

        const btn = document.getElementById('btnAplicarFiltros');
        btn && btn.addEventListener('click', () => {
            const filtros = coletar();
            callbacks.forEach(fn => fn(filtros));
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    return { coletar, aoAplicar };
})();
