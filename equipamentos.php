<?php
$pageTitle = 'Análise Individual de Equipamento';
$activeNav = 'equipamentos';
require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="page-title"><span><?= icon('gauge') ?></span> Status atual das estações</div>

<?php require __DIR__ . '/includes/filtros_bar.php'; ?>

<div class="machine-grid" id="machineGrid">
    <div class="empty-state">Carregando estações...</div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
<script src="<?= asset_url('assets/js/charts-common.js') ?>"></script>
<script src="<?= asset_url('assets/js/equipamentos.js') ?>"></script>
