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
    $codigo = trim((string) ($dados['codigo'] ?? ''));
    $nome = trim((string) ($dados['nome'] ?? ''));
    $tempoCiclo = (float) ($dados['tempo_ciclo_seg'] ?? 0);

    $erros = [];
    if ($id <= 0) {
        $erros[] = 'Item inválido.';
    }
    if ($codigo === '') {
        $erros[] = 'Informe o código do item.';
    }
    if ($nome === '') {
        $erros[] = 'Informe o nome do item.';
    }
    if ($tempoCiclo <= 0) {
        $erros[] = 'O tempo de ciclo deve ser maior que zero.';
    }

    if ($erros) {
        http_response_code(422);
        echo json_encode(['erro' => implode(' ', $erros)]);
        exit;
    }

    $pdo = db();

    $stmtExiste = $pdo->prepare('SELECT id FROM itens WHERE id = :id');
    $stmtExiste->execute(['id' => $id]);
    if (!$stmtExiste->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['erro' => 'Item não encontrado.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE itens SET codigo = :codigo, nome = :nome, tempo_ciclo_seg = :tempo_ciclo_seg WHERE id = :id'
        );
        $stmt->execute([
            'codigo' => $codigo,
            'nome' => $nome,
            'tempo_ciclo_seg' => $tempoCiclo,
            'id' => $id,
        ]);
        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(422);
            echo json_encode(['erro' => 'Já existe outro item com este código.']);
            exit;
        }
        throw $e;
    }
