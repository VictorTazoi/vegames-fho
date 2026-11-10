<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';
$usuarioAtual = exigirGestao();
$activeNav = $activeNav ?? '';
$pageTitle = $pageTitle ?? '';
$iniciais = mb_strtoupper(mb_substr($usuarioAtual['nome'], 0, 1));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VegaMES<?= $pageTitle ? ' - ' . htmlspecialchars($pageTitle) : '' ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
    <script src="<?= asset_url('assets/vendor/chart.umd.min.js') ?>"></script>
    <script>window.VEGAMES_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
</head>
<body>
    <div class="app">
        <header class="topbar">
            <button class="icon-btn" id="btnAbrirMenu" title="Menu" aria-label="Abrir menu"><?= icon('menu') ?></button>
            <div class="topbar-user">
                <button class="icon-btn btn-tela-cheia" title="Tela cheia" aria-label="Tela cheia"><?= icon('expand', 18) ?></button>
                <button type="button" class="user-btn" data-abrir-conta title="Minha conta">
                    <span class="avatar"><?= htmlspecialchars($iniciais) ?></span>
                    <span class="user-nome"><?= htmlspecialchars($usuarioAtual['nome']) ?></span>
                </button>
                <button class="icon-btn" id="btnSair" title="Sair" aria-label="Sair"><?= icon('logout', 18) ?></button>
            </div>
        </header>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand"><span class="vega">Vega</span><span class="mes">MES</span></span>
                <button class="sidebar-close" id="btnFecharMenu" aria-label="Fechar menu"><?= icon('close', 16) ?></button>
            </div>
            <a class="nav-link <?= $activeNav === 'indicadores' ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php">
                <span class="nav-icon"><?= icon('target') ?></span> Principais Indicadores
            </a>
            <a class="nav-link <?= $activeNav === 'equipamentos' ? 'active' : '' ?>" href="<?= BASE_URL ?>/equipamentos.php">
                <span class="nav-icon"><?= icon('gauge') ?></span> Análise Individual de Equipamento
            </a>
            <a class="nav-link <?= $activeNav === 'paradas' ? 'active' : '' ?>" href="<?= BASE_URL ?>/paradas.php">
                <span class="nav-icon"><?= icon('pause') ?></span> Análise de Paradas
            </a>
            <a class="nav-link <?= $activeNav === 'vibracao' ? 'active' : '' ?>" href="<?= BASE_URL ?>/vibracao.php">
                <span class="nav-icon"><?= icon('alert') ?></span> Vibração
            </a>
            <a class="nav-link <?= $activeNav === 'ordens' ? 'active' : '' ?>" href="<?= BASE_URL ?>/ordens_producao.php">
                <span class="nav-icon"><?= icon('clipboard') ?></span> Ordens de Produção
            </a>
            <a class="nav-link <?= $activeNav === 'configuracoes' ? 'active' : '' ?>" href="<?= BASE_URL ?>/configuracoes.php">
                <span class="nav-icon"><?= icon('settings') ?></span> Configurações
            </a>
            <a class="nav-link nav-link-secondary" href="<?= BASE_URL ?>/ihm/painel.php" target="_blank" rel="noopener">
                <span class="nav-icon"><?= icon('gauge') ?></span> Abrir IHM (operador)
            </a>
            <div class="sidebar-footer">
                &copy; <?= date('Y') ?> VegaMES.<br>Todos os direitos reservados.
            </div>
        </nav>

        <?php require __DIR__ . '/modal_senha.php'; ?>

        <main class="content">
