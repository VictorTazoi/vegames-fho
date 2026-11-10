<?php
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    iniciarSessao();

    $_SESSION = [];
    session_destroy();

    echo json_encode(['sucesso' => true]);
