<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginApi();

    $pdo = db();

    $maquinaId = $_GET['maquina_id'] ?? null;
    $ordemId = $_GET['ordem_id'] ?? null;
    $dataInicio = $_GET['data_inicio'] ?? null;
    $dataFim = $_GET['data_fim'] ?? null;

    $where = [];
    $params = [];

    if ($maquinaId) {
        $where[] = 'p.maquina_id = :maquina_id';
        $params['maquina_id'] = $maquinaId;
    }
    if ($dataInicio) {
        $where[] = 'p.inicio >= :data_inicio';
        $params['data_inicio'] = $dataInicio . ' 00:00:00';
    }
    if ($dataFim) {
        $where[] = 'p.inicio <= :data_fim';
        $params['data_fim'] = $dataFim . ' 23:59:59';
    }
    if ($ordemId) {
        $stmt = $pdo->prepare('SELECT maquina_id, inicio, fim FROM ordens_producao WHERE id = :id');
        $stmt->execute(['id' => $ordemId]);
        $ordem = $stmt->fetch();
        if ($ordem) {
            $where[] = 'p.maquina_id = :ordem_maquina';
            $params['ordem_maquina'] = $ordem['maquina_id'];
            $where[] = 'p.inicio >= :ordem_inicio';
            $params['ordem_inicio'] = $ordem['inicio'];
            if ($ordem['fim']) {
                $where[] = 'p.inicio <= :ordem_fim';
                $params['ordem_fim'] = $ordem['fim'];
            }
        }
    }

    $sql = "
        SELECT COALESCE(NULLIF(p.motivo, ''), 'Não classificado') AS causa,
            COUNT(*) AS frequencia,
            SUM(TIMESTAMPDIFF(SECOND, p.inicio, COALESCE(p.fim, NOW()))) AS tempo_total_seg
        FROM paradas p
    ";
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' GROUP BY causa ORDER BY frequencia DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linhas = $stmt->fetchAll();

    $totalFrequencia = array_sum(array_column($linhas, 'frequencia'));
    $acumulado = 0;
    $resultado = [];
    foreach ($linhas as $linha) {
        $freq = (int) $linha['frequencia'];
        $acumulado += $freq;
        $resultado[] = [
            'causa' => $linha['causa'],
            'frequencia' => $freq,
            'tempo_total_seg' => (int) $linha['tempo_total_seg'],
            'percentual_acumulado' => $totalFrequencia > 0 ? round(($acumulado / $totalFrequencia) * 100, 1) : 0,
        ];
    }

    echo json_encode(['pareto' => $resultado]);
