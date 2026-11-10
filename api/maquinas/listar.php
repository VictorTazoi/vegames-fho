<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirAdminApi();

    $pdo = db();
    $maquinas = $pdo->query('SELECT id, nome, localizacao, status FROM maquinas ORDER BY nome')->fetchAll();

    echo json_encode(['maquinas' => $maquinas]);
