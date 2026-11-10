<?php

    function mqttEncodeString(string $str): string
    {
        return pack('n', strlen($str)) . $str;
    }

    function mqttEncodeRemainingLength(int $len): string
    {
        $bytes = '';
        do {
            $byte = $len % 128;
            $len = intdiv($len, 128);
            if ($len > 0) {
                $byte |= 0x80;
            }
            $bytes .= chr($byte);
        } while ($len > 0);
        return $bytes;
    }

    function mqttReadBytes($socket, int $n): string
    {
        $data = '';
        while (strlen($data) < $n) {
            $chunk = fread($socket, $n - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('Conexão MQTT encerrada inesperadamente.');
            }
            $data .= $chunk;
        }
        return $data;
    }

    function mqttDecodeRemainingLength($socket): int
    {
        $multiplier = 1;
        $value = 0;
        do {
            $byte = ord(mqttReadBytes($socket, 1));
            $value += ($byte & 0x7F) * $multiplier;
            $multiplier *= 128;
        } while ($byte & 0x80);
        return $value;
    }

    function mqttConnect(string $host, int $port, string $clientId, int $keepAlive = 60, int $timeoutSeg = 10, string $username = '', string $password = '')
    {
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeoutSeg);
        if (!$socket) {
            throw new RuntimeException("Não foi possível conectar ao broker MQTT {$host}:{$port} - {$errstr}");
        }
        stream_set_timeout($socket, $timeoutSeg);

        $connectFlags = 0x02;
        if ($username !== '') {
            $connectFlags |= 0x80;
        }
        if ($password !== '') {
            $connectFlags |= 0x40;
        }

        $protocolName = mqttEncodeString('MQTT');
        $variableHeader = $protocolName . chr(4) . chr($connectFlags) . pack('n', $keepAlive);
        $payload = mqttEncodeString($clientId);
        if ($username !== '') {
            $payload .= mqttEncodeString($username);
        }
        if ($password !== '') {
            $payload .= mqttEncodeString($password);
        }
        $remaining = $variableHeader . $payload;

        $packet = chr(0x10) . mqttEncodeRemainingLength(strlen($remaining)) . $remaining;
        fwrite($socket, $packet);

        $header = ord(mqttReadBytes($socket, 1));
        $len = mqttDecodeRemainingLength($socket);
        $body = mqttReadBytes($socket, $len);
        if (($header >> 4) !== 2 || ord($body[1]) !== 0) {
            $codigo = ord($body[1] ?? "\xFF");
            throw new RuntimeException('Broker recusou a conexão (CONNACK código ' . $codigo . ').');
        }

        return $socket;
    }

    function mqttSubscribe($socket, string $topicFilter, int $qos = 0): void
    {
        $packetId = random_int(1, 65535);
        $remaining = pack('n', $packetId) . mqttEncodeString($topicFilter) . chr($qos);
        $packet = chr(0x82) . mqttEncodeRemainingLength(strlen($remaining)) . $remaining;
        fwrite($socket, $packet);

        $header = ord(mqttReadBytes($socket, 1));
        $len = mqttDecodeRemainingLength($socket);
        mqttReadBytes($socket, $len);
    }

    function mqttPing($socket): void
    {
        fwrite($socket, chr(0xC0) . chr(0x00));
    }

    function mqttTestarConexao(string $host, int $port, string $clientId, int $timeoutSeg = 5, string $username = '', string $password = ''): array
    {
        try {
            $socket = mqttConnect($host, $port, $clientId . '-teste-' . random_int(1000, 9999), 30, $timeoutSeg, $username, $password);
            fclose($socket);
            return ['sucesso' => true, 'mensagem' => "Conexão estabelecida com sucesso em {$host}:{$port}."];
        } catch (Throwable $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }
