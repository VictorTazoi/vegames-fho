<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginApi();

    $pdo = db();

    $maquinas = $pdo->query('SELECT id, nome, localizacao FROM maquinas ORDER BY nome')->fetchAll();
    $turnos = $pdo->query('SELECT id, nome FROM turnos ORDER BY id')->fetchAll();
    $ordens = $pdo->query('SELECT id, numero, maquina_id FROM ordens_producao ORDER BY inicio DESC LIMIT 200')->fetchAll();

    echo json_encode([
        'maquinas' => $maquinas,
        'turnos' => $turnos,
        'ordens' => $ordens,
    ]);
