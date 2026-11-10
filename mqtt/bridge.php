<?php

    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/Settings.php';
    require_once __DIR__ . '/../includes/MqttClient.php';

    $host = Settings::obter('mqtt_host');
    $port = (int) Settings::obter('mqtt_port');
    $clientId = Settings::obter('mqtt_client_id');
    $mqttUser = Settings::obter('mqtt_user');
    $mqttPass = Settings::obter('mqtt_pass');
    $topicFilter = Settings::obter('mqtt_topic_filter');
    $deviceKey = Settings::obter('device_api_key');

    function encaminharParaIngest(string $topico, string $payloadRaw, string $deviceKey): void
    {
        $payload = json_decode($payloadRaw, true);
        if (!is_array($payload)) {
            fwrite(STDERR, "[bridge] Payload não é JSON válido, ignorado: {$payloadRaw}\n");
            return;
        }

        $corpo = json_encode(['topico' => $topico, 'payload' => $payload]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Device-Key: " . $deviceKey . "\r\n",
                'content' => $corpo,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $resposta = @file_get_contents(MQTT_INGEST_URL, false, $ctx);
        echo "[bridge] {$topico} -> {$resposta}\n";
    }

    echo "[bridge] Conectando ao broker MQTT {$host}:{$port} ...\n";
    $socket = mqttConnect($host, $port, $clientId, 60, 10, $mqttUser, $mqttPass);
    stream_set_timeout($socket, 30);
    mqttSubscribe($socket, $topicFilter);
    echo "[bridge] Inscrito em {$topicFilter}. Aguardando eventos...\n";

    $ultimoPing = time();

    while (true) {
        $read = [$socket];
        $write = null;
        $except = null;
        $changed = stream_select($read, $write, $except, 15);

        if ($changed > 0) {
            $headerByte = fread($socket, 1);
            if ($headerByte === '' || $headerByte === false) {
                break;
            }
            $header = ord($headerByte);
            $type = $header >> 4;
            $len = mqttDecodeRemainingLength($socket);
            $body = $len > 0 ? mqttReadBytes($socket, $len) : '';

            if ($type === 3) {
                $topicLen = unpack('n', substr($body, 0, 2))[1];
                $topic = substr($body, 2, $topicLen);
                $offset = 2 + $topicLen;
                $qos = ($header >> 1) & 0x03;
                if ($qos > 0) {
                    $offset += 2;
                }
                $payload = substr($body, $offset);
                encaminharParaIngest($topic, $payload, $deviceKey);
            } elseif ($type === 13) {
            }
        }

        if (time() - $ultimoPing >= 30) {
            mqttPing($socket);
            $ultimoPing = time();
        }
    }

    fclose($socket);
