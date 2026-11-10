<?php
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/icons.php';

    $usuario = usuarioLogado();
    if ($usuario) {
        $destino = ($usuario['perfil'] ?? '') === 'admin' ? '/dashboard.php' : '/ihm/painel.php';
        header('Location: ' . BASE_URL . $destino);
        exit;
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>VegaMES - Login</title>

        <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
        <script>window.VEGAMES_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
    </head>
        <body class="login-page">
            <div class="login-card">
                <div class="login-logo">
                    <span class="brand"><span class="vega">Vega</span><span class="mes">MES</span></span>
                    <span class="tagline">Gestão e Análise Preditiva</span>
                </div>

                <div class="form-error" id="loginErro"></div>

                <form id="formLogin">
                    <div class="field">
                        <span class="icon"><?= icon('user', 16) ?></span>
                        <input type="text" id="login" name="login" placeholder="Login" autocomplete="username" required>
                    </div>
                    <div class="field">
                        <span class="icon"><?= icon('lock', 16) ?></span>
                        <input type="password" id="senha" name="senha" placeholder="Senha" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" id="btnLogin">Login</button>
                </form>
                <div class="login-footer">
                    <a type="button" href="<?= BASE_URL ?>/ihm/index.php" class="btn btn-outline-light login-alt-link">Sou operador &rarr; acessar a IHM</a>
                </div>
            </div>



        <script>
            document.getElementById('formLogin').addEventListener('submit', async function (e) {
                e.preventDefault();
                const erroBox = document.getElementById('loginErro');
                const btn = document.getElementById('btnLogin');
                erroBox.classList.remove('show');
                btn.disabled = true;
                btn.textContent = 'Entrando...';

                try {
                    const resp = await fetch(window.VEGAMES_BASE_URL + '/api/auth/login.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            login: document.getElementById('login').value.trim(),
                            senha: document.getElementById('senha').value,
                        }),
                    });
                    const dados = await resp.json();
                    if (!resp.ok) {
                        throw new Error(dados.erro || 'Não foi possível efetuar o login.');
                    }
                    const destino = dados.usuario.perfil === 'admin' ? '/dashboard.php' : '/ihm/painel.php';
                    window.location.href = window.VEGAMES_BASE_URL + destino;
                } catch (err) {
                    erroBox.textContent = err.message;
                    erroBox.classList.add('show');
                    btn.disabled = false;
                    btn.textContent = 'Login';
                }
            });
        </script>
    </body>
</html>
