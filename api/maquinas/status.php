<?php
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/OeeCalculator.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginApi();

    $filtros = [
        'turno_id' => $_GET['turno_id'] ?? null,
        'ordem_id' => $_GET['ordem_id'] ?? null,
        'data_inicio' => $_GET['data_inicio'] ?? null,
        'data_fim' => $_GET['data_fim'] ?? null,
    ];

    if (empty($filtros['data_inicio']) && empty($filtros['data_fim']) && empty($filtros['ordem_id'])) {
        $hoje = date('Y-m-d');
        $filtros['data_inicio'] = $hoje;
        $filtros['data_fim'] = $hoje;
    }

    $calc = new OeeCalculator();
    $maquinas = $calc->calcularPorMaquina($filtros);

    $pdo = db();
    $stmt = $pdo->query("
        SELECT maquina_id, inicio, motivo
        FROM paradas
        WHERE fim IS NULL
    ");
    $paradasAbertas = [];
    foreach ($stmt->fetchAll() as $row) {
        $paradasAbertas[$row['maquina_id']] = $row;
    }

    $stmt = $pdo->query('SELECT id, TIMESTAMPDIFF(SECOND, ultimo_heartbeat, NOW()) AS segundos_sem_contato FROM maquinas');
    $segundosSemContato = [];
    foreach ($stmt->fetchAll() as $row) {
        $segundosSemContato[$row['id']] = $row['segundos_sem_contato'];
    }

    foreach ($maquinas as &$maquina) {
        $parada = $paradasAbertas[$maquina['maquina_id']] ?? null;
        $maquina['parada_atual'] = $parada ? [
            'desde' => $parada['inicio'],
            'motivo' => $parada['motivo'] ?: 'Não classificado',
        ] : null;

        $segundos = $segundosSemContato[$maquina['maquina_id']] ?? null;
        $maquina['gateway_offline'] = ($segundos === null || $segundos > GATEWAY_TIMEOUT_SEG);
    }
    unset($maquina);

    echo json_encode(['maquinas' => $maquinas]);
