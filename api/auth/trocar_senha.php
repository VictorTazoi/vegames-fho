<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    $usuario = exigirLoginApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $atual = (string) ($dados['senha_atual'] ?? '');
    $nova = (string) ($dados['nova_senha'] ?? '');
    $confirmar = (string) ($dados['confirmar'] ?? '');

    if ($atual === '' || $nova === '' || $confirmar === '') {
        http_response_code(400);
        echo json_encode(['erro' => 'Preencha todos os campos.']);
        exit;
    }
    if (strlen($nova) < 6) {
        http_response_code(400);
        echo json_encode(['erro' => 'A nova senha precisa ter pelo menos 6 caracteres.']);
        exit;
    }
    if ($nova !== $confirmar) {
        http_response_code(400);
        echo json_encode(['erro' => 'A confirmação não é igual à nova senha.']);
        exit;
    }
    if ($nova === $atual) {
        http_response_code(400);
        echo json_encode(['erro' => 'A nova senha deve ser diferente da atual.']);
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT senha FROM usuarios WHERE id = :id AND ativo = 1');
    $stmt->execute(['id' => $usuario['id']]);
    $hashAtual = $stmt->fetchColumn();

    if (!$hashAtual || !password_verify($atual, $hashAtual)) {
        http_response_code(401);
        echo json_encode(['erro' => 'Senha atual incorreta.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id');
    $stmt->execute([
        'senha' => password_hash($nova, PASSWORD_DEFAULT),
        'id' => $usuario['id'],
    ]);

    echo json_encode(['sucesso' => true]);
