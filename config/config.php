<?php

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'vegames');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');

    define('BASE_URL', '/VegaMES');

    if (file_exists(__DIR__ . '/config.local.php')) {
        require __DIR__ . '/config.local.php';
    }
    defined('DEVICE_API_KEY') || define('DEVICE_API_KEY', 'trocar-esta-chave');
    defined('MQTT_USER') || define('MQTT_USER', '');
    defined('MQTT_PASS') || define('MQTT_PASS', '');

    define('OLLAMA_HOST', getenv('VEGAMES_OLLAMA_HOST') ?: 'http://localhost:11434');
    define('OLLAMA_MODEL', getenv('VEGAMES_OLLAMA_MODEL') ?: 'phi4-mini');

    define('MQTT_HOST', '127.0.0.1');
    define('MQTT_PORT', 1883);
    define('MQTT_CLIENT_ID', 'vegames_bridge');
    define('MQTT_TOPIC_FILTER', 'vegames/maquina/+/+');
    define('MQTT_INGEST_URL', 'http://localhost' . BASE_URL . '/api/eventos/ingest.php');

    define('SESSION_NAME', 'vegames_session');
    define('SESSION_LIFETIME_MIN', 120);

    define('GATEWAY_TIMEOUT_SEG', 60);

    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    date_default_timezone_set('America/Sao_Paulo');

    function asset_url(string $caminhoRelativo): string
    {
        $caminhoRelativo = ltrim($caminhoRelativo, '/');
        $caminhoNoDisco = __DIR__ . '/../' . $caminhoRelativo;
        $versao = file_exists($caminhoNoDisco) ? filemtime($caminhoNoDisco) : time();
        return BASE_URL . '/' . $caminhoRelativo . '?v=' . $versao;
    }
