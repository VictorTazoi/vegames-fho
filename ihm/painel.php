<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
$usuarioAtual = exigirLoginIhm();
$iniciais = mb_strtoupper(mb_substr($usuarioAtual['nome'], 0, 1));
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <title>VegaMES IHM</title>
        <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
        <link rel="stylesheet" href="<?= asset_url('assets/css/ihm.css') ?>">
        <script>window.VEGAMES_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
    </head>
    <body>
        <div class="ihm-app">
            <header class="ihm-topbar">
                <span class="brand"><span class="vega">Vega</span><span class="mes">MES</span> IHM</span>
                <div class="ihm-topbar-right">
                    <button type="button" class="ihm-user-btn" data-abrir-conta title="Minha conta">
                        <?= icon('user', 16) ?> <?= htmlspecialchars($usuarioAtual['nome']) ?>
                    </button>
                    <button class="ihm-btn-icon btn-tela-cheia" title="Tela cheia" aria-label="Tela cheia"><?= icon('expand', 18) ?></button>
                    <button class="ihm-btn-sair" id="btnSairIhm"><?= icon('logout', 16) ?> Sair</button>
                </div>
            </header>

            <main class="ihm-content">
                <div id="viewSelecao">
                    <div class="page-title" style="margin-bottom:16px;"><span><?= icon('gauge') ?></span> Selecione a máquina</div>
                    <div class="ihm-machine-grid" id="machineGrid">
                        <div class="empty-state">Carregando máquinas...</div>
                    </div>
                </div>

                <div id="viewPainel" style="display:none;">
                    <div class="ihm-corner">
                        <div class="ihm-corner-left">
                            <div class="ihm-clock" id="ihmRelogio">--:--:--</div>
                            <div class="ihm-oee-mini">
                                <span class="ihm-oee-mini-label">OEE hoje</span>
                                <div class="ihm-oee-mini-track"><div class="ihm-oee-mini-fill" id="ihmOeeFill"></div></div>
                                <span class="ihm-oee-mini-val" id="ihmOeeVal">-</span>
                            </div>
                        </div>
                        <button class="ihm-back" id="btnVoltar"><?= icon('close', 14) ?> Trocar de máquina</button>
                    </div>

                    <div class="ihm-status-panel">
                        <span class="machine-status" id="statusBadge"></span>
                        <h2 id="maquinaNome">-</h2>
                        <div id="ordemAtiva" style="font-size:12px;color:var(--gray-500);"></div>

                        <div id="ordemProgress" style="display:none;margin:10px 0;">
                            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-700);margin-bottom:4px;">
                                <span id="ordemProgressLabel"></span>
                                <span id="ordemProgressPct"></span>
                            </div>
                            <div style="background:var(--gray-200);border-radius:6px;height:8px;overflow:hidden;">
                                <div id="ordemProgressBar" style="background:var(--navy-800);height:100%;width:0%;"></div>
                            </div>
                        </div>

                        <div class="ihm-status-row">
                            <div class="ihm-status-item">
                                <div class="label">Produzidas hoje</div>
                                <div class="value" id="produzidasHoje">-</div>
                            </div>
                            <div class="ihm-status-item">
                                <div class="label">Refugadas hoje</div>
                                <div class="value" id="rejeitadasHoje">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="ihm-actions">
                        <button class="ihm-action-btn ihm-action-produzir" id="btnProduzir">
                            <?= icon('check', 26) ?> +1 Peça Produzida
                        </button>
                        <button class="ihm-action-btn ihm-action-refugo" id="btnRefugo">
                            <?= icon('alert', 22) ?> Refugo
                        </button>
                        <button class="ihm-action-btn ihm-action-ordem-iniciar" id="btnIniciarOrdem" style="display:none;">
                            <?= icon('check', 22) ?> Iniciar
                        </button>
                        <button class="ihm-action-btn ihm-action-ordem-concluir" id="btnConcluirOrdem" style="display:none;">
                            <?= icon('check', 22) ?> Concluir OP
                        </button>
                        <button class="ihm-action-btn ihm-action-parada-iniciar" id="btnIniciarParada">
                            <?= icon('pause', 22) ?> Parar
                        </button>
                        <button class="ihm-action-btn ihm-action-parada-finalizar" id="btnFinalizarParada" style="display:none;">
                            <?= icon('check', 22) ?> Finalizar
                        </button>
                    </div>

                    <div class="ihm-feed">
                        <div class="chart-title">Últimos eventos</div>
                        <div id="feedEventos"><div class="empty-state">Sem eventos ainda.</div></div>
                    </div>
                </div>
            </main>
        </div>

        <div class="ihm-overlay" id="overlayParada">
            <div class="ihm-sheet">
                <h3>Motivo da parada</h3>
                <div class="ihm-tabs" id="tabsTipoParada">
                    <button type="button" class="ihm-tab active" data-tipo="nao_programada">Não programada</button>
                    <button type="button" class="ihm-tab" data-tipo="programada">Programada</button>
                    <button type="button" class="ihm-tab" data-tipo="manutencao">Manutenção</button>
                </div>
                <div class="ihm-chip-grid" id="chipsParada"></div>
                <div class="field" style="margin-bottom:14px;">
                    <input type="text" id="motivoParadaOutro" placeholder="Outro motivo (opcional)">
                </div>
                <div class="ihm-sheet-actions">
                    <button type="button" class="btn btn-outline" id="btnCancelarParada">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarParada">Confirmar</button>
                </div>
            </div>
        </div>

        <div class="ihm-overlay" id="overlayRefugo">
            <div class="ihm-sheet">
                <h3>Registrar refugo</h3>
                <div class="ihm-stepper">
                    <button type="button" id="btnRefugoMenos">-</button>
                    <span class="qtd" id="refugoQtd">1</span>
                    <button type="button" id="btnRefugoMais">+</button>
                </div>
                <div class="ihm-chip-grid" id="chipsRefugo"></div>
                <div class="field" style="margin-bottom:14px;">
                    <input type="text" id="motivoRefugoOutro" placeholder="Outro motivo (opcional)">
                </div>
                <div class="ihm-sheet-actions">
                    <button type="button" class="btn btn-outline" id="btnCancelarRefugo">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarRefugo">Confirmar</button>
                </div>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/modal_senha.php'; ?>

        <div class="toast" id="toast"></div>
        <script src="<?= asset_url('assets/js/fullscreen.js') ?>"></script>
        <script src="<?= asset_url('assets/js/trocar-senha.js') ?>"></script>
        <script src="<?= asset_url('assets/js/live-events.js') ?>"></script>
        <script src="<?= asset_url('assets/js/ihm.js') ?>"></script>
    </body>
</html>
