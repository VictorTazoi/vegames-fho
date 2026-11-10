<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/Settings.php';

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
    $clientId = trim((string) ($dados['mqtt_client_id'] ?? ''));
    $mqttUser = trim((string) ($dados['mqtt_user'] ?? ''));
    $mqttPass = (string) ($dados['mqtt_pass'] ?? '');
    $topicFilter = trim((string) ($dados['mqtt_topic_filter'] ?? ''));
    $deviceKey = trim((string) ($dados['device_api_key'] ?? ''));

    $erros = [];
    if ($host === '') {
        $erros[] = 'Informe o host/IP do broker MQTT.';
    }
    if ($port < 1 || $port > 65535) {
        $erros[] = 'A porta MQTT deve estar entre 1 e 65535.';
    }
    if ($clientId === '') {
        $erros[] = 'Informe um Client ID para o bridge MQTT.';
    }
    if ($topicFilter === '') {
        $erros[] = 'Informe o filtro de tópico MQTT (ex.: vegames/maquina/+/+).';
    }
    if ($deviceKey === '' || strlen($deviceKey) < 8) {
        $erros[] = 'A chave de dispositivo deve ter ao menos 8 caracteres.';
    }

    if ($erros) {
        http_response_code(422);
        echo json_encode(['erro' => implode(' ', $erros)]);
        exit;
    }

    Settings::salvar([
        'mqtt_host' => $host,
        'mqtt_port' => (string) $port,
        'mqtt_client_id' => $clientId,
        'mqtt_user' => $mqttUser,
        'mqtt_pass' => $mqttPass,
        'mqtt_topic_filter' => $topicFilter,
        'device_api_key' => $deviceKey,
    ]);

    echo json_encode(['sucesso' => true, 'configuracoes' => Settings::obterTodas()]);
