<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginIhmApi();

    $maquinaId = (int) ($_GET['maquina_id'] ?? 0);
    if ($maquinaId <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe a máquina.']);
        exit;
    }

    $pdo = db();
    $limite = 8;

    $stmt = $pdo->prepare(
        "(SELECT 'producao' AS tipo, registrado_em AS quando, pecas_contadas AS quantidade, NULL AS motivo
        FROM contagem_producao WHERE maquina_id = :m1 ORDER BY registrado_em DESC LIMIT {$limite})
        UNION ALL
        (SELECT 'refugo', registrado_em, quantidade, motivo
        FROM refugo WHERE maquina_id = :m2 ORDER BY registrado_em DESC LIMIT {$limite})
        UNION ALL
        (SELECT CASE WHEN fim IS NULL THEN 'parada_inicio' ELSE 'parada_fim' END, COALESCE(fim, inicio), NULL, motivo
        FROM paradas WHERE maquina_id = :m3 ORDER BY inicio DESC LIMIT {$limite})
        ORDER BY quando DESC
        LIMIT {$limite}"
    );
    $stmt->execute(['m1' => $maquinaId, 'm2' => $maquinaId, 'm3' => $maquinaId]);

    echo json_encode(['eventos' => $stmt->fetchAll()]);
