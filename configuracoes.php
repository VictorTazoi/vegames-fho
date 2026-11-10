<?php
$pageTitle = 'Configurações';
$activeNav = 'configuracoes';
require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="page-title"><span><?= icon('settings') ?></span> Configurações de Conexão (ESP32 / MQTT)</div>

<div class="panel" style="margin-bottom:16px;">

    <form id="formConfig">
        <div class="config-grid">
            <div class="filter-group">
                <label>IP broker MQTT</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="mqttHost" placeholder="192.168.1.50" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group">
                <label>Porta MQTT</label>
                <div class="field" style="padding-left:0;">
                    <input type="number" id="mqttPort" min="1" max="65535" placeholder="1883" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group">
                <label>Client ID do bridge</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="mqttClientId" placeholder="vegames_bridge" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group">
                <label>Usuário do broker MQTT</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="mqttUser" placeholder="deixe em branco se o broker não usa autenticação" style="padding-left:14px;" autocomplete="off">
                </div>
            </div>
            <div class="filter-group">
                <label>Senha do broker MQTT</label>
                <div class="field" style="padding-left:0;">
                    <input type="password" id="mqttPass" placeholder="deixe em branco se o broker não usa autenticação" style="padding-left:14px;padding-right:40px;" autocomplete="off">
                    <button type="button" class="field-toggle" id="btnMostrarMqttPass" title="Mostrar/ocultar"><?= icon('eye', 16) ?></button>
                </div>
            </div>
            <div class="filter-group">
                <label>Tópico MQTT</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="mqttTopicFilter" placeholder="vegames/maquina/+/+" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group" style="grid-column: 1 / -1;">
                <label>Chave</label>
                <div class="field" style="padding-left:0;">
                    <input type="password" id="deviceApiKey" placeholder="chave secreta usada pelo ESP32/bridge" style="padding-left:14px;padding-right:40px;" required>
                    <button type="button" class="field-toggle" id="btnMostrarChave" title="Mostrar/ocultar"><?= icon('eye', 16) ?></button>
                </div>
            </div>
        </div>

        <div id="testeResultado" class="config-test-result" style="display:none;"></div>

        <div class="config-actions">
            <button type="button" class="btn btn-outline" id="btnTestar">Testar</button>
            <button type="submit" class="btn btn-primary" id="btnSalvar">Salvar</button>
        </div>
        <div id="salvarResultado" class="config-test-result" style="display:none;"></div>
    </form>
</div>

<div class="panel" style="margin-bottom:16px;">
    <div class="chart-title">Gestão de Máquinas</div>
    <div id="listaMaquinasCadastro" class="machine-manage-list">
        <div class="empty-state">Carregando máquinas...</div>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
<script src="<?= asset_url('assets/js/configuracoes.js') ?>"></script>
<script src="<?= asset_url('assets/js/maquinas-config.js') ?>"></script>
