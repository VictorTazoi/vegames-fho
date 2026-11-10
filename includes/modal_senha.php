<?php
?>
<div class="modal-overlay" id="modalConta">
    <div class="modal-box">
        <button type="button" class="modal-close" id="btnFecharConta" aria-label="Fechar"><?= icon('close', 16) ?></button>
        <h3>Minha conta</h3>
        <p class="conta-info">
            <?= htmlspecialchars($usuarioAtual['nome']) ?>
            (<?= htmlspecialchars($usuarioAtual['login']) ?>)
            &middot; <?= $usuarioAtual['perfil'] === 'admin' ? 'Administrador' : 'Operador' ?>
        </p>

        <form id="formSenha" autocomplete="off">
            <div class="form-error" id="senhaErro"></div>

            <label class="form-label" for="senhaAtual">Senha atual</label>
            <input class="form-input" type="password" id="senhaAtual" autocomplete="current-password" required>

            <label class="form-label" for="senhaNova">Nova senha</label>
            <input class="form-input" type="password" id="senhaNova" autocomplete="new-password" minlength="6" required>

            <label class="form-label" for="senhaConfirmar">Repita a nova senha</label>
            <input class="form-input" type="password" id="senhaConfirmar" autocomplete="new-password" required>

            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="btnCancelarConta">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvarSenha">Salvar senha</button>
            </div>
        </form>
    </div>
</div>
