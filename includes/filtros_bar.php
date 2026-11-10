<?php require_once __DIR__ . '/icons.php'; ?>
<div class="filters-bar">
    <div class="filter-group">
        <label>Selecionar Ordem de Produção</label>
        <div class="field">
            <span class="icon"><?= icon('search', 16) ?></span>
            <input type="text" id="filtroOrdem" list="listaOrdens" placeholder="Todas" autocomplete="off">
        </div>
        <datalist id="listaOrdens"></datalist>
    </div>
    <div class="filter-group">
        <label>Selecionar Turno</label>
        <div class="field">
            <span class="icon"><?= icon('search', 16) ?></span>
            <input type="text" id="filtroTurno" list="listaTurnos" placeholder="Todos" autocomplete="off">
        </div>
        <datalist id="listaTurnos"></datalist>
    </div>
    <div class="filter-group">
        <label>Selecionar Estação</label>
        <div class="field">
            <span class="icon"><?= icon('search', 16) ?></span>
            <input type="text" id="filtroMaquina" list="listaMaquinas" placeholder="Todas" autocomplete="off">
        </div>
        <datalist id="listaMaquinas"></datalist>
    </div>
    <div class="filter-group">
        <label>Data de Início</label>
        <div class="field" style="padding-left:0;">
            <input type="date" id="filtroDataInicio" style="padding-left:14px;">
        </div>
    </div>
    <div class="filter-group">
        <label>Data Final</label>
        <div class="field" style="padding-left:0;">
            <input type="date" id="filtroDataFim" style="padding-left:14px;">
        </div>
    </div>
    <button class="filter-apply" id="btnAplicarFiltros" title="Aplicar filtros"><?= icon('filter', 18) ?></button>
</div>
