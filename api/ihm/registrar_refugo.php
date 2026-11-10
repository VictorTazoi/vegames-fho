<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/EventoIngestor.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginIhmApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $maquinaId = (int) ($dados['maquina_id'] ?? 0);
    $quantidade = max(1, (int) ($dados['quantidade'] ?? 1));
    $motivo = trim((string) ($dados['motivo'] ?? ''));

    if ($maquinaId <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe a máquina.']);
        exit;
    }
    
    if ($motivo === '') {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe o motivo do refugo.']);
        exit;
    }

    try {
        $ingestor = new EventoIngestor();
        $resultado = $ingestor->registrarRefugo($maquinaId, ['quantidade' => $quantidade, 'motivo' => $motivo]);
        echo json_encode(['sucesso' => true] + $resultado);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['erro' => $e->getMessage()]);
    } catch (Throwable $e) {
        error_log('VegaMES IHM registrar_refugo error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['erro' => 'Erro interno ao registrar o refugo.']);
    }
