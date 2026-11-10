<?php

$pageTitle = 'Vibração';
$activeNav = 'vibracao';
require_once __DIR__ . '/includes/layout_top.php';
require_once __DIR__ . '/config/database.php';

$maquinas = [];
try {
    $maquinas = db()->query('SELECT id, nome FROM maquinas ORDER BY nome')->fetchAll();
} catch (PDOException $e) {
    error_log('VegaMES vibracao.php: ' . $e->getMessage());
}

?>

<div class="page-title"><span><?= icon('alert') ?></span> Vibração do Motor</div>

<div class="filters-bar">
    <div class="filter-group">
        <label>Estação</label>
        <div class="field" style="padding-left:0;">
            <select id="vibMaquina" style="padding-left:14px;">
                <option value="">Padrão</option>
                <?php foreach ($maquinas as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="filter-group">
        <label>Período</label>
        <div class="field" style="padding-left:0;">
            <select id="vibMinutos" style="padding-left:14px;">
                <option value="5">Últimos 5 minutos</option>
                <option value="10" selected>Últimos 10 minutos</option>
                <option value="30">Últimos 30 minutos</option>
                <option value="60">Última hora</option>
                <option value="360">Últimas 6 horas</option>
                <option value="1440">Últimas 24 horas</option>
            </select>
        </div>
    </div>
    <div class="filter-group">
        <label>Atualização</label>
        <div class="field" style="padding-left:0;">
            <select id="vibAuto" style="padding-left:14px;">
                <option value="3000" selected>Tempo real</option>
                <option value="0">Manual</option>
            </select>
        </div>
    </div>
    <button class="filter-apply" id="vibAtualizar" title="Atualizar agora"><?= icon('filter', 18) ?></button>
</div>

<div class="kpi-row">
    <div class="kpi-card">
        <div class="label">Status</div>
        <div class="value" id="vibStatus">--</div>
        <div class="sub" id="vibUltima">Última leitura: --</div>
    </div>
    <div class="kpi-card">
        <div class="label">RMS atual</div>
        <div class="value" id="vibRms">-- g</div>
        <div class="sub">Média da janela</div>
    </div>
    <div class="kpi-card">
        <div class="label">Pico atual</div>
        <div class="value" id="vibPico">-- g</div>
        <div class="sub">Maior valor em 2 s</div>
    </div>
    <div class="kpi-card">
        <div class="label">Anomalias</div>
        <div class="value" id="vibAnomalias">--</div>
        <div class="sub" id="vibTotal">Leituras: --</div>
    </div>
</div>

<div class="panel">
    <div class="chart-title">RMS e Pico em funcao do tempo</div>
    <div class="chart-wrap tall"><canvas id="chartVibracao"></canvas></div>
    <div id="vibAviso" class="llm-empty" style="margin-top:10px; display:none;"></div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>

<script src="<?= asset_url('assets/js/charts-common.js') ?>"></script>
<script src="<?= asset_url('assets/js/vibracao.js') ?>"></script>
