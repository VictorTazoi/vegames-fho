<?php
    require_once __DIR__ . '/../config/config.php';

    function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME_MIN * 60,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    function usuarioLogado(): ?array
    {
        iniciarSessao();
        return $_SESSION['usuario'] ?? null;
    }

    function exigirLogin(): array
    {
        $usuario = usuarioLogado();
        if (!$usuario) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
        return $usuario;
    }

    function exigirLoginApi(): array
    {
        $usuario = usuarioLogado();
        if (!$usuario) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'Sessão expirada. Faça login novamente.']);
            exit;
        }
        return $usuario;
    }

    function exigirAdmin(): array
    {
        $usuario = exigirLogin();
        if (($usuario['perfil'] ?? '') !== 'admin') {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Acesso restrito ao administrador.';
            exit;
        }
        return $usuario;
    }

    function exigirAdminApi(): array
    {
        $usuario = exigirLoginApi();
        if (($usuario['perfil'] ?? '') !== 'admin') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => 'Acesso restrito ao administrador.']);
            exit;
        }
        return $usuario;
    }

    function exigirGestao(): array
    {
        $usuario = exigirLogin();
        if (($usuario['perfil'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . '/ihm/painel.php');
            exit;
        }
        return $usuario;
    }

    function exigirLoginIhm(): array
    {
        $usuario = usuarioLogado();
        if (!$usuario) {
            header('Location: ' . BASE_URL . '/ihm/index.php');
            exit;
        }
        return $usuario;
    }

    function exigirLoginIhmApi(): array
    {
        return exigirLoginApi();
    }
