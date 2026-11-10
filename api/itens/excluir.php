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

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe o id do item.']);
        exit;
    }

    $pdo = db();

    try {
        $stmt = $pdo->prepare('DELETE FROM itens WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['erro' => 'Item não encontrado.']);
            exit;
        }

        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(422);
            echo json_encode(['erro' => 'Este item não pode ser excluído: existem ordens de produção cadastradas com ele.']);
            exit;
        }
        throw $e;
    }
