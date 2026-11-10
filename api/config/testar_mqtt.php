<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/MqttClient.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirAdminApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $host = trim((string) ($dados['mqtt_host'] ?? ''));
    $port = (int) ($dados['mqtt_port'] ?? 0);
    $clientId = trim((string) ($dados['mqtt_client_id'] ?? 'vegames_bridge'));
    $username = trim((string) ($dados['mqtt_user'] ?? ''));
    $password = (string) ($dados['mqtt_pass'] ?? '');

    if ($host === '' || $port < 1 || $port > 65535) {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe host e porta válidos antes de testar.']);
        exit;
    }

    $resultado = mqttTestarConexao($host, $port, $clientId ?: 'vegames_bridge', 5, $username, $password);
    echo json_encode($resultado);
