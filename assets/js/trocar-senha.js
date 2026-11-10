(function () {
    const base = window.VEGAMES_BASE_URL || '';
    const modal = document.getElementById('modalConta');
    if (!modal) return;

    const form = document.getElementById('formSenha');
    const erro = document.getElementById('senhaErro');
    const botao = document.getElementById('btnSalvarSenha');

    function abrir() {
        form.reset();
        erro.classList.remove('show');
        modal.classList.add('open');
        document.getElementById('senhaAtual').focus();
    }
    function fechar() {
        modal.classList.remove('open');
    }

    document.querySelectorAll('[data-abrir-conta]').forEach(el => el.addEventListener('click', abrir));
    document.getElementById('btnFecharConta').addEventListener('click', fechar);
    document.getElementById('btnCancelarConta').addEventListener('click', fechar);
    modal.addEventListener('click', e => { if (e.target === modal) fechar(); });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        erro.classList.remove('show');

        const nova = document.getElementById('senhaNova').value;
        if (nova !== document.getElementById('senhaConfirmar').value) {
            erro.textContent = 'A confirmação não é igual à nova senha.';
            erro.classList.add('show');
            return;
        }

        botao.disabled = true;
        try {
            const resp = await fetch(base + '/api/auth/trocar_senha.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    senha_atual: document.getElementById('senhaAtual').value,
                    nova_senha: nova,
                    confirmar: document.getElementById('senhaConfirmar').value,
                }),
            });
            const dados = await resp.json();
            if (!resp.ok) throw new Error(dados.erro || 'Não foi possível trocar a senha.');

            fechar();
            mostrarAviso('Senha alterada.');
        } catch (err) {
            erro.textContent = err.message;
            erro.classList.add('show');
        } finally {
            botao.disabled = false;
        }
    });

    function mostrarAviso(msg) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.className = 'toast show';
        setTimeout(() => toast.classList.remove('show'), 3200);
    }
})();
