<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirAdminApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];

    $id = (int) ($dados['id'] ?? 0);
    $nome = trim((string) ($dados['nome'] ?? ''));
    $localizacao = trim((string) ($dados['localizacao'] ?? ''));

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Máquina inválida.']);
        exit;
    }
    if ($nome === '') {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe o nome da máquina.']);
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare('UPDATE maquinas SET nome = :nome, localizacao = :localizacao WHERE id = :id');
    $stmt->execute([
        'nome' => $nome,
        'localizacao' => $localizacao !== '' ? $localizacao : null,
        'id' => $id,
    ]);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare('SELECT id FROM maquinas WHERE id = :id');
        $check->execute(['id' => $id]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['erro' => 'Máquina não encontrada.']);
            exit;
        }
    }

    echo json_encode(['sucesso' => true]);
