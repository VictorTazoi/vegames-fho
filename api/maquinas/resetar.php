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
    $confirmar = (bool) ($dados['confirmar'] ?? false);

    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Máquina inválida.']);
        exit;
    }
    if (!$confirmar) {
        http_response_code(422);
        echo json_encode(['erro' => 'Confirmação obrigatória para resetar o histórico.']);
        exit;
    }

    $pdo = db();

    $check = $pdo->prepare('SELECT id, nome FROM maquinas WHERE id = :id');
    $check->execute(['id' => $id]);
    $maquina = $check->fetch();
    if (!$maquina) {
        http_response_code(404);
        echo json_encode(['erro' => 'Máquina não encontrada.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        foreach (['contagem_producao', 'refugo', 'paradas', 'leituras_vibracao', 'oee_resultados', 'ordens_producao'] as $tabela) {
            $stmt = $pdo->prepare("DELETE FROM {$tabela} WHERE maquina_id = :id");
            $stmt->execute(['id' => $id]);
        }

        $stmt = $pdo->prepare("UPDATE maquinas SET status = 'operando', ultimo_heartbeat = NULL WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        error_log('VegaMES resetar máquina falhou: ' . $e->getMessage());
        echo json_encode(['erro' => 'Falha ao resetar o histórico da máquina.']);
        exit;
    }

    echo json_encode(['sucesso' => true]);
