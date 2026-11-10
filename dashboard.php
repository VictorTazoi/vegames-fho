<?php
$pageTitle = 'Principais Indicadores';
$activeNav = 'indicadores';
require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="page-title">Principais Indicadores</div>

<?php require __DIR__ . '/includes/filtros_bar.php'; ?>

<div class="kpi-row" id="kpiRow">
    <div class="kpi-card">
        <div class="label">OEE</div>
        <div class="value gold" id="kpiOee">--%</div>
        <div class="sub">Eficiência Global do Equipamento</div>
    </div>
    <div class="kpi-card">
        <div class="label">Disponibilidade</div>
        <div class="value" id="kpiDisponibilidade">--%</div>
        <div class="sub" id="kpiTempoParado">Tempo parado: --</div>
    </div>
    <div class="kpi-card">
        <div class="label">Performance</div>
        <div class="value" id="kpiPerformance">--%</div>
        <div class="sub" id="kpiProduzido">Produzido: --</div>
    </div>
    <div class="kpi-card">
        <div class="label">Qualidade</div>
        <div class="value" id="kpiQualidade">--%</div>
        <div class="sub" id="kpiRefugo">Refugo: --</div>
    </div>
    <a class="kpi-card" href="<?= BASE_URL ?>/vibracao.php" style="text-decoration:none; display:block;">
        <div class="label">Vibração</div>
        <div class="value" id="kpiVibracao">--</div>
        <div class="sub" id="kpiVibracaoSub">Ver detalhes</div>
    </a>
</div>

<div class="charts-grid">
    <div class="panel">
        <div class="chart-title">Produção por Estação</div>
        <div class="chart-wrap"><canvas id="chartProducao"></canvas></div>
    </div>
    <div class="panel">
        <div class="chart-title">Tempo de Parada por Motivo</div>
        <div class="chart-wrap"><canvas id="chartMotivos"></canvas></div>
    </div>
    <div class="panel">
        <div class="chart-title">OEE por Estação</div>
        <div class="chart-wrap"><canvas id="chartOeeEstacao"></canvas></div>
    </div>
    <div class="panel">
        <div class="chart-title">Tendência de Produção</div>
        <div class="chart-wrap"><canvas id="chartTendencia"></canvas></div>
    </div>
</div>

<div class="panel llm-panel">
    <div class="llm-header">
        <div class="chart-title" style="margin:0;">Análise</div>
        <button class="btn btn-gold" id="btnAnalisarIA">Gerar</button>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
<script src="<?= asset_url('assets/js/charts-common.js') ?>"></script>
<script src="<?= asset_url('assets/js/dashboard.js') ?>"></script>
<script src="<?= asset_url('assets/js/vibracao-card.js') ?>"></script>
