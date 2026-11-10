<?php

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/Settings.php';
    require_once __DIR__ . '/../../includes/EventoIngestor.php';

    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $chave = $_SERVER['HTTP_X_DEVICE_KEY'] ?? ($_POST['chave'] ?? '');
    
    if (!hash_equals(Settings::obter('device_api_key'), $chave)) {
        http_response_code(401);
        echo json_encode(['erro' => 'Chave de dispositivo inválida.']);
        exit;
    }

    $corpo = json_decode(file_get_contents('php://input'), true);

    if (!is_array($corpo) || empty($corpo['topico']) || !isset($corpo['payload'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Corpo inválido. Esperado {"topico": "...", "payload": {...}}.']);
        exit;
    }

    if (preg_match('#^vegames/maquina/(\d+)/heartbeat$#', $corpo['topico'], $hb)) {
        try {
            $stmt = db()->prepare('UPDATE maquinas SET ultimo_heartbeat = NOW() WHERE id = :id');
            $stmt->execute(['id' => (int) $hb[1]]);
            echo json_encode(['sucesso' => true]);
        } catch (Throwable $e) {
            error_log('VegaMES heartbeat error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno ao registrar o heartbeat.']);
        }
        exit;
    }

    try {
        $ingestor = new EventoIngestor();
        $resultado = $ingestor->processarMensagem($corpo['topico'], $corpo['payload']);

        if (preg_match('#^vegames/maquina/(\d+)/#', $corpo['topico'], $m)) {
            $stmt = db()->prepare('UPDATE maquinas SET ultimo_heartbeat = NOW() WHERE id = :id');
            $stmt->execute(['id' => (int) $m[1]]);
        }

        echo json_encode(['sucesso' => true] + $resultado);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['erro' => $e->getMessage()]);
    } catch (Throwable $e) {
        error_log('VegaMES ingest error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['erro' => 'Erro interno ao processar o evento.']);
    }
