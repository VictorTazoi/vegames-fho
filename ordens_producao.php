<?php
$pageTitle = 'Ordens de Produção';
$activeNav = 'ordens';
require_once __DIR__ . '/includes/layout_top.php';
?>
<div class="page-title"><span><?= icon('clipboard') ?></span> Ordens de Produção</div>

<div class="panel" style="margin-bottom:16px;">
    <div class="chart-title">Itens</div>
    <p style="margin:0 0 16px;font-size:13px;color:var(--gray-700);line-height:1.6;">
        Cadastre os itens (produtos) que a linha produz, cada um com o tempo de ciclo ideal
        (tempo esperado para produzir 1 peça) — usado no cálculo de Performance do OEE.
    </p>

    <form id="formItem">
        <div class="config-grid">
            <div class="filter-group">
                <label>Código</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="itemCodigo" placeholder="ITEM-001" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group">
                <label>Nome</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="itemNome" placeholder="Peça padrão" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group">
                <label>Tempo de ciclo (segundos/peça)</label>
                <div class="field" style="padding-left:0;">
                    <input type="number" id="itemTempoCiclo" min="0.1" step="0.1" placeholder="5.0" style="padding-left:14px;" required>
                </div>
            </div>
        </div>
        <div class="config-actions">
            <button type="submit" class="btn btn-primary">Cadastrar Item</button>
        </div>
        <div id="itemResultado" class="config-test-result" style="display:none;"></div>
    </form>

    <table class="pareto-table" id="tabelaItens">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Tempo de ciclo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="panel">
    <div class="chart-title">Ordens de Produção</div>
    <p style="margin:0 0 16px;font-size:13px;color:var(--gray-700);line-height:1.6;">
        Cadastre a ordem com a quantidade (meta) a produzir. Na IHM, o operador digita o número
        desta ordem para começar a produzi-la; a ordem é finalizada automaticamente quando a
        contagem de peças atinge a meta.
    </p>

    <form id="formOrdem">
        <div class="config-grid">
            <div class="filter-group">
                <label>Número da ordem</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="ordemNumero" placeholder="OP-0001" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group">
                <label>Item</label>
                <div class="field" style="padding-left:0;">
                    <select id="ordemItem" style="padding-left:14px;" required></select>
                </div>
            </div>
            <div class="filter-group">
                <label>Máquina / Estação</label>
                <div class="field" style="padding-left:0;">
                    <select id="ordemMaquina" style="padding-left:14px;" required></select>
                </div>
            </div>
            <div class="filter-group">
                <label>Turno</label>
                <div class="field" style="padding-left:0;">
                    <select id="ordemTurno" style="padding-left:14px;" required></select>
                </div>
            </div>
            <div class="filter-group">
                <label>Meta de produção (peças)</label>
                <div class="field" style="padding-left:0;">
                    <input type="number" id="ordemMeta" min="1" max="1000000" step="1" placeholder="400" style="padding-left:14px;" required>
                </div>
            </div>
            <div class="filter-group">
                <label>Tempo planejado (calculado: meta × ciclo do item)</label>
                <div class="field" style="padding-left:0;">
                    <input type="text" id="ordemTempoPlanejado" style="padding-left:14px;background:var(--gray-100);" readonly>
                </div>
            </div>
            <div class="filter-group">
                <label>Início planejado</label>
                <div class="field" style="padding-left:0;">
                    <input type="datetime-local" id="ordemInicio" style="padding-left:14px;" required>
                </div>
            </div>
        </div>
        <div class="config-actions">
            <button type="submit" class="btn btn-primary">Cadastrar Ordem</button>
        </div>
        <div id="ordemResultado" class="config-test-result" style="display:none;"></div>
    </form>

    <table class="pareto-table" id="tabelaOrdens">
        <thead>
            <tr>
                <th>Número</th>
                <th>Item</th>
                <th>Máquina</th>
                <th>Turno</th>
                <th>Produzido / Meta</th>
                <th>Status</th>
                <th>OEE</th>
                <th>Início</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="modal-overlay" id="modalEditarItem">
    <div class="modal-box">
        <button type="button" class="modal-close" id="btnFecharEditarItem"><?= icon('close', 18) ?></button>
        <h3>Editar item</h3>
        <form id="formEditarItem">
            <input type="hidden" id="editItemId">
            <div class="config-grid">
                <div class="filter-group">
                    <label>Código</label>
                    <div class="field" style="padding-left:0;">
                        <input type="text" id="editItemCodigo" style="padding-left:14px;" required>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Nome</label>
                    <div class="field" style="padding-left:0;">
                        <input type="text" id="editItemNome" style="padding-left:14px;" required>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Tempo de ciclo (segundos/peça)</label>
                    <div class="field" style="padding-left:0;">
                        <input type="number" id="editItemTempoCiclo" min="0.1" step="0.1" style="padding-left:14px;" required>
                    </div>
                </div>
            </div>
            <div id="editItemResultado" class="config-test-result" style="display:none;"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="btnCancelarEditarItem">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalVer">
    <div class="modal-box">
        <button type="button" class="modal-close" id="btnFecharVer"><?= icon('close', 18) ?></button>
        <h3>Detalhes da ordem</h3>
        <div id="verConteudo"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" id="btnFecharVer2">Fechar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalEditar">
    <div class="modal-box">
        <button type="button" class="modal-close" id="btnFecharEditar"><?= icon('close', 18) ?></button>
        <h3>Editar ordem</h3>
        <form id="formEditarOrdem">
            <input type="hidden" id="editId">
            <div class="config-grid">
                <div class="filter-group">
                    <label>Número da ordem</label>
                    <div class="field" style="padding-left:0;">
                        <input type="text" id="editNumero" style="padding-left:14px;" required>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Item</label>
                    <div class="field" style="padding-left:0;">
                        <select id="editItem" style="padding-left:14px;" required></select>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Máquina / Estação</label>
                    <div class="field" style="padding-left:0;">
                        <select id="editMaquina" style="padding-left:14px;" required></select>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Turno</label>
                    <div class="field" style="padding-left:0;">
                        <select id="editTurno" style="padding-left:14px;" required></select>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Meta de produção (peças)</label>
                    <div class="field" style="padding-left:0;">
                        <input type="number" id="editMeta" min="1" max="1000000" step="1" style="padding-left:14px;" required>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Tempo planejado (calculado: meta × ciclo do item)</label>
                    <div class="field" style="padding-left:0;">
                        <input type="text" id="editTempoPlanejado" style="padding-left:14px;background:var(--gray-100);" readonly>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Início</label>
                    <div class="field" style="padding-left:0;">
                        <input type="datetime-local" id="editInicio" style="padding-left:14px;" required>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Fim (opcional)</label>
                    <div class="field" style="padding-left:0;">
                        <input type="datetime-local" id="editFim" style="padding-left:14px;">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <div class="field" style="padding-left:0;">
                        <select id="editStatus" style="padding-left:14px;" required>
                            <option value="planejada">Planejada</option>
                            <option value="em_andamento">Em andamento</option>
                            <option value="finalizada">Finalizada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
            </div>
            <div id="editResultado" class="config-test-result" style="display:none;"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="btnCancelarEditar">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/ordens_producao.js"></script>
