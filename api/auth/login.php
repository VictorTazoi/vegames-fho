<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    iniciarSessao();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $login = trim((string) ($dados['login'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');

    if ($login === '' || $senha === '') {
        http_response_code(400);
        echo json_encode(['erro' => 'Informe login e senha.']);
        exit;
    }

    $stmt = db()->prepare('SELECT id, nome, login, senha, perfil FROM usuarios WHERE login = :login AND ativo = 1 LIMIT 1');
    $stmt->execute(['login' => $login]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        http_response_code(401);
        echo json_encode(['erro' => 'Login ou senha inválidos.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int) $usuario['id'],
        'nome' => $usuario['nome'],
        'login' => $usuario['login'],
        'perfil' => $usuario['perfil'],
    ];

    echo json_encode(['sucesso' => true, 'usuario' => $_SESSION['usuario']]);
