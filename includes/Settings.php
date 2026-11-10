<?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/config.php';

    class Settings
    {
        private const PADROES = [
            'mqtt_host' => MQTT_HOST,
            'mqtt_port' => MQTT_PORT,
            'mqtt_client_id' => MQTT_CLIENT_ID,
            'mqtt_user' => MQTT_USER,
            'mqtt_pass' => MQTT_PASS,
            'mqtt_topic_filter' => MQTT_TOPIC_FILTER,
            'device_api_key' => DEVICE_API_KEY,
        ];

        public static function obterTodas(): array
        {
            $pdo = db();
            $salvas = [];
            foreach ($pdo->query('SELECT chave, valor FROM configuracoes') as $row) {
                $salvas[$row['chave']] = $row['valor'];
            }

            $resultado = [];
            foreach (self::PADROES as $chave => $padrao) {
                $resultado[$chave] = array_key_exists($chave, $salvas) && $salvas[$chave] !== ''
                    ? $salvas[$chave]
                    : (string) $padrao;
            }
            return $resultado;
        }

        public static function obter(string $chave): string
        {
            $todas = self::obterTodas();
            return $todas[$chave] ?? (string) (self::PADROES[$chave] ?? '');
        }

        public static function salvar(array $dados): void
        {
            $pdo = db();
            $stmt = $pdo->prepare(
                'INSERT INTO configuracoes (chave, valor) VALUES (:chave, :valor)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
            );
            foreach (self::PADROES as $chave => $padrao) {
                if (!array_key_exists($chave, $dados)) {
                    continue;
                }
                $stmt->execute(['chave' => $chave, 'valor' => (string) $dados[$chave]]);
            }
        }
    }
