<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/Settings.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirAdminApi();

    echo json_encode([
        'configuracoes' => Settings::obterTodas(),
        'endpoint_ingestao' => 'http://' . $_SERVER['SERVER_NAME'] . BASE_URL . '/api/eventos/ingest.php',
    ]);
