<?php

    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/OeeCalculator.php';

    header('Content-Type: application/json; charset=utf-8');
    $usuario = exigirLoginApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $filtros = [
        'maquina_id' => $dados['maquina_id'] ?? null,
        'turno_id' => $dados['turno_id'] ?? null,
        'ordem_id' => $dados['ordem_id'] ?? null,
        'data_inicio' => $dados['data_inicio'] ?? null,
        'data_fim' => $dados['data_fim'] ?? null,
    ];

    $calc = new OeeCalculator();
    $resumo = $calc->calcular($filtros);
    $resumo['classificacao_oee'] = OeeCalculator::classificarOee($resumo['oee']);

    $porMaquina = $calc->calcularPorMaquina($filtros);
    foreach ($porMaquina as &$maquinaOee) {
        $maquinaOee['classificacao_oee'] = OeeCalculator::classificarOee($maquinaOee['oee']);
    }
    unset($maquinaOee);

    $pdo = db();
    $stmtParadas = $pdo->prepare(
        "SELECT COALESCE(NULLIF(motivo, ''), 'Não classificado') AS causa, COUNT(*) AS frequencia,
                SUM(TIMESTAMPDIFF(SECOND, inicio, COALESCE(fim, NOW()))) AS tempo_total_seg
        FROM paradas
        GROUP BY causa ORDER BY frequencia DESC LIMIT 5"
    );
    $stmtParadas->execute();
    $principaisCausas = $stmtParadas->fetchAll();
    foreach ($principaisCausas as &$causa) {
        $causa['tempo_total_min'] = round(((int) $causa['tempo_total_seg']) / 60, 1);
        unset($causa['tempo_total_seg']);
    }
    unset($causa);

    $contexto = [
        'resumo_geral' => $resumo,
        'oee_por_maquina' => $porMaquina,
        'principais_causas_de_parada' => $principaisCausas,
        'filtros_aplicados' => $filtros,
    ];

    $prompt = "Você é um analista de produção interpretando indicadores de um sistema MES (VegaMES). "
        . "Os valores de OEE, Disponibilidade, Performance, Qualidade, as causas de parada e a classificação de desempenho "
        . "(campo \"classificacao_oee\", nas faixas Crítico < 40%, Baixo 40-59%, Aceitável 60-84%, Classe mundial >= 85%) "
        . "já foram calculados pela aplicação (não recalcule nada e não invente sua própria classificação de bom/ruim — "
        . "use exatamente o texto de classificacao_oee fornecido). Com base nos dados JSON abaixo, escreva uma análise curta "
        . "e objetiva em português, em até 6 frases, destacando: (1) o desempenho geral (OEE e sua classificação), "
        . "(2) a máquina com pior desempenho e possível causa, e (3) uma recomendação prática. \"Máquina com pior desempenho\" "
        . "refere-se ao menor OEE em oee_por_maquina. Dados:\n\n"
        . json_encode($contexto, JSON_UNESCAPED_UNICODE);

    $corpo = json_encode([
        'model' => OLLAMA_MODEL,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
        'stream' => false,
        'keep_alive' => '30m',
        'options' => [
            'num_predict' => 350,
        ],
    ]);

    $ch = curl_init(rtrim(OLLAMA_HOST, '/') . '/api/chat');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $corpo,
        CURLOPT_TIMEOUT => 120,
    ]);
    $respostaBruta = curl_exec($ch);
    $erroCurl = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($respostaBruta === false) {
        error_log('VegaMES Ollama error: ' . $erroCurl);
        http_response_code(503);
        echo json_encode([
            'erro' => 'Não foi possível conectar ao Ollama em ' . OLLAMA_HOST . '. Verifique se o serviço está em execução (comando "ollama serve" ou o app do Ollama aberto).',
        ]);
        exit;
    }

    if ($statusCode >= 400) {
        error_log('VegaMES Ollama error: status ' . $statusCode . ' | ' . $respostaBruta);
        http_response_code(502);
        echo json_encode(['erro' => 'O Ollama retornou um erro. Confirme se o modelo "' . OLLAMA_MODEL . '" foi baixado (ollama pull ' . OLLAMA_MODEL . ').']);
        exit;
    }

    $respostaJson = json_decode($respostaBruta, true);
    $texto = $respostaJson['message']['content'] ?? null;

    if (!$texto) {
        http_response_code(502);
        echo json_encode(['erro' => 'Resposta inesperada do Ollama.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO analises_llm (usuario_id, filtros_json, resposta) VALUES (:usuario_id, :filtros_json, :resposta)'
    );
    $stmt->execute([
        'usuario_id' => $usuario['id'],
        'filtros_json' => json_encode($filtros),
        'resposta' => $texto,
    ]);

    echo json_encode(['sucesso' => true, 'analise' => $texto]);
