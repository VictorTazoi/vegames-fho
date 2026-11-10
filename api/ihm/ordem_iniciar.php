<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginIhmApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $maquinaId = (int) ($dados['maquina_id'] ?? 0);
    if ($maquinaId <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe a máquina.']);
        exit;
    }

    $pdo = db();

    $stmt = $pdo->prepare("SELECT id FROM ordens_producao WHERE maquina_id = :id AND status = 'em_andamento' LIMIT 1");
    $stmt->execute(['id' => $maquinaId]);
    if ($stmt->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['erro' => 'Já existe uma ordem em andamento para esta máquina.']);
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT id FROM ordens_producao WHERE maquina_id = :id AND status = 'planejada'
        ORDER BY inicio ASC, id ASC LIMIT 1"
    );
    $stmt->execute(['id' => $maquinaId]);
    $ordemId = $stmt->fetchColumn();

    if (!$ordemId) {
        http_response_code(422);
        echo json_encode(['erro' => 'Não há ordem planejada para iniciar nesta máquina.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE ordens_producao SET status = 'em_andamento', inicio = NOW() WHERE id = :id");
    $stmt->execute(['id' => $ordemId]);

    echo json_encode(['sucesso' => true, 'ordem_id' => (int) $ordemId]);
