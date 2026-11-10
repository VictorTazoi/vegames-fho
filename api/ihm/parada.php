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
    $acao = (string) ($dados['acao'] ?? '');

    if ($maquinaId <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe a máquina.']);
        exit;
    }

    if (!in_array($acao, ['inicio', 'fim'], true)) {
        http_response_code(422);
        echo json_encode(['erro' => 'Ação inválida.']);
        exit;
    }
    
    if ($acao === 'inicio' && trim((string) ($dados['motivo'] ?? '')) === '') {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe o motivo da parada.']);
        exit;
    }

    try {
        $ingestor = new EventoIngestor();
        $resultado = $ingestor->registrarParada($maquinaId, [
            'acao' => $acao,
            'tipo' => $dados['tipo'] ?? 'nao_programada',
            'motivo' => $dados['motivo'] ?? null,
        ]);
        echo json_encode(['sucesso' => true] + $resultado);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['erro' => $e->getMessage()]);
    } catch (Throwable $e) {
        error_log('VegaMES IHM parada error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['erro' => 'Erro interno ao registrar a parada.']);
    }
