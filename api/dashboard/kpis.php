<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/OeeCalculator.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginApi();

    $pdo = db();

    $filtros = [
        'maquina_id' => $_GET['maquina_id'] ?? null,
        'turno_id' => $_GET['turno_id'] ?? null,
        'ordem_id' => $_GET['ordem_id'] ?? null,
        'data_inicio' => $_GET['data_inicio'] ?? null,
        'data_fim' => $_GET['data_fim'] ?? null,
    ];

    $calc = new OeeCalculator();

    $resumo = $calc->calcular($filtros);

    $condMaquina = [];
    $paramsMaquina = [];
    
    if (!empty($filtros['maquina_id'])) {
        $condMaquina[] = 'm.id = :maquina_id';
        $paramsMaquina['maquina_id'] = $filtros['maquina_id'];
    }

    $sqlProducaoPorMaquina = "
        SELECT m.id, m.nome,
            COALESCE((SELECT SUM(pecas_contadas) FROM contagem_producao cp WHERE cp.maquina_id = m.id), 0) AS produzidas,
            COALESCE((SELECT SUM(quantidade) FROM refugo r WHERE r.maquina_id = m.id), 0) AS rejeitadas,
            COALESCE((SELECT SUM(meta_producao) FROM ordens_producao op WHERE op.maquina_id = m.id), 0) AS meta
        FROM maquinas m
    ";

    if ($condMaquina) {
        $sqlProducaoPorMaquina .= ' WHERE ' . implode(' AND ', $condMaquina);
    }
    $sqlProducaoPorMaquina .= ' ORDER BY m.nome';
    $stmt = $pdo->prepare($sqlProducaoPorMaquina);
    $stmt->execute($paramsMaquina);
    $producaoPorMaquina = $stmt->fetchAll();

    $condParadas = [];
    $paramsParadas = [];

    if (!empty($filtros['maquina_id'])) {
        $condParadas[] = 'maquina_id = :maquina_id';
        $paramsParadas['maquina_id'] = $filtros['maquina_id'];
    }

    if (!empty($filtros['data_inicio'])) {
        $condParadas[] = 'inicio >= :data_inicio';
        $paramsParadas['data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
    }

    if (!empty($filtros['data_fim'])) {
        $condParadas[] = 'inicio <= :data_fim';
        $paramsParadas['data_fim'] = $filtros['data_fim'] . ' 23:59:59';
    }
    $sqlMotivos = "
        SELECT COALESCE(NULLIF(motivo, ''), 'Não classificado') AS motivo,
            SUM(TIMESTAMPDIFF(SECOND, inicio, COALESCE(fim, NOW()))) AS total_seg
        FROM paradas
    ";

    if ($condParadas) {
        $sqlMotivos .= ' WHERE ' . implode(' AND ', $condParadas);
    }
    $sqlMotivos .= ' GROUP BY motivo ORDER BY total_seg DESC LIMIT 5';
    $stmt = $pdo->prepare($sqlMotivos);
    $stmt->execute($paramsParadas);
    $distribuicaoParadas = $stmt->fetchAll();

    $oeePorMaquina = $calc->calcularPorMaquina($filtros);

    $condTendProd = [];
    $paramsTendProd = [];

    if (!empty($filtros['maquina_id'])) {
        $condTendProd[] = 'maquina_id = :maquina_id';
        $paramsTendProd['maquina_id'] = $filtros['maquina_id'];
    }
    $dataInicio = $filtros['data_inicio'] ?: date('Y-m-d', strtotime('-6 days'));
    $dataFim = $filtros['data_fim'] ?: date('Y-m-d');
    $condTendProd[] = 'registrado_em >= :ti';
    $condTendProd[] = 'registrado_em <= :tf';
    $paramsTendProd['ti'] = $dataInicio . ' 00:00:00';
    $paramsTendProd['tf'] = $dataFim . ' 23:59:59';

    $sqlTendProduzidas = "
        SELECT DATE(registrado_em) AS dia, SUM(pecas_contadas) AS produzidas
        FROM contagem_producao
        WHERE " . implode(' AND ', $condTendProd) . "
        GROUP BY DATE(registrado_em) ORDER BY dia
    ";
    $stmt = $pdo->prepare($sqlTendProduzidas);
    $stmt->execute($paramsTendProd);
    $tendProduzidas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $sqlTendRejeitadas = "
        SELECT DATE(registrado_em) AS dia, SUM(quantidade) AS rejeitadas
        FROM refugo
        WHERE " . implode(' AND ', $condTendProd) . "
        GROUP BY DATE(registrado_em) ORDER BY dia
    ";
    $stmt = $pdo->prepare($sqlTendRejeitadas);
    $stmt->execute($paramsTendProd);
    $tendRejeitadas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $dias = array_unique(array_merge(array_keys($tendProduzidas), array_keys($tendRejeitadas)));
    sort($dias);
    $tendencia = array_map(fn($dia) => [
        'dia' => $dia,
        'produzidas' => (int) ($tendProduzidas[$dia] ?? 0),
        'rejeitadas' => (int) ($tendRejeitadas[$dia] ?? 0),
    ], $dias);

    echo json_encode([
        'resumo' => $resumo,
        'producao_por_maquina' => $producaoPorMaquina,
        'distribuicao_paradas' => $distribuicaoParadas,
        'oee_por_maquina' => $oeePorMaquina,
        'tendencia' => $tendencia,
    ]);
