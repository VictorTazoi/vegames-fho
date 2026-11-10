<?php
$pageTitle = 'Análise de Paradas';
$activeNav = 'paradas';
require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="page-title"><span><?= icon('pause') ?></span> Análise de Paradas</div>

<?php require __DIR__ . '/includes/filtros_bar.php'; ?>

<div class="panel">
    <div class="chart-title">Diagrama de Pareto</div>
    <div class="pareto-wrap"><canvas id="chartPareto"></canvas></div>

    <table class="pareto-table" id="tabelaPareto">
        <thead>
            <tr>
                <th>Causa</th>
                <th>Frequência</th>
                <th>Tempo total parado</th>
                <th>% Acumulado</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
<script src="<?= asset_url('assets/js/charts-common.js') ?>"></script>
<script src="<?= asset_url('assets/js/paradas.js') ?>"></script>
