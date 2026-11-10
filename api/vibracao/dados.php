<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginApi();

    $pdo = db();
    $minutos = max(1, min(1440, (int) ($_GET['minutos'] ?? 10)));
    $soResumo = !empty($_GET['resumo']);

    try {
        $maquinaId = (int) ($_GET['maquina_id'] ?? 0);

        if ($maquinaId <= 0) {
            $maquinaId = (int) $pdo->query(
                'SELECT maquina_id FROM leituras_vibracao ORDER BY registrado_em DESC LIMIT 1'
            )->fetchColumn();
        }

        if ($maquinaId <= 0) {
            $maquinaId = (int) $pdo->query('SELECT id FROM maquinas ORDER BY id LIMIT 1')->fetchColumn();
        }

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(registrado_em, '%Y-%m-%d %H:%i:%s') AS horario,
                    TIMESTAMPDIFF(SECOND, registrado_em, NOW()) AS segundos_atras,
                    rms_g, pico_g, anomalia
            FROM leituras_vibracao
            WHERE maquina_id = :maquina AND registrado_em >= NOW() - INTERVAL {$minutos} MINUTE
            ORDER BY registrado_em DESC LIMIT 3000"
        );

        $stmt->execute(['maquina' => $maquinaId]);
        $leituras = array_reverse($stmt->fetchAll());

    } catch (PDOException $e) {
        error_log('VegaMES vibracao: ' . $e->getMessage());
        echo json_encode([
            'leituras' => [],
            'resumo' => ['status' => 'sem_dados', 'total' => 0, 'avaliadas' => 0, 'anomalias' => 0],
            'aviso' => 'Tabela de vibração não encontrada. Importe o arquivo sql/vegames_completo.sql.',
        ]);

        exit;
    }

    $total = count($leituras);
    $avaliadas = 0;
    $anomalias = 0;

    foreach ($leituras as $l) {
        if ($l['anomalia'] !== null) {
            $avaliadas++;
            if ((int) $l['anomalia'] === 1) {
                $anomalias++;
            }
        }
    }

    $ultima = $total > 0 ? $leituras[$total - 1] : null;
    $status = 'sem_dados';
    if ($ultima !== null) {

        if ((int) $ultima['segundos_atras'] > 30) {
            $status = 'sem_sinal';
        } else {
            $status = 'coletando';

            foreach (array_slice($leituras, -5) as $l) {

                if ($l['anomalia'] === null) {
                    continue;
                }
                
                $status = 'normal';

                if ((int) $l['anomalia'] === 1) {
                    $status = 'anomalia';
                    break;
                }
            }
        }
    }

    $resumo = [
        'status' => $status,
        'total' => $total,
        'avaliadas' => $avaliadas,
        'anomalias' => $anomalias,
        'ultimo_rms' => $ultima ? (float) $ultima['rms_g'] : null,
        'ultimo_pico' => $ultima ? (float) $ultima['pico_g'] : null,
        'ultima_leitura' => $ultima ? $ultima['horario'] : null,
    ];

    if ($soResumo) {
        echo json_encode(['maquina_id' => $maquinaId, 'resumo' => $resumo]);
        exit;
    }

    $passo = max(1, (int) ceil($total / 1200));
    $lista = [];

    foreach ($leituras as $i => $l) {
        $ehAnomalia = $l['anomalia'] !== null && (int) $l['anomalia'] === 1;

        if ($i % $passo !== 0 && !$ehAnomalia && $i !== $total - 1) {
            continue;
        }

        $lista[] = [
            'horario' => $l['horario'],
            'rms' => (float) $l['rms_g'],
            'pico' => (float) $l['pico_g'],
            'anomalia' => $l['anomalia'] === null ? null : (int) $l['anomalia'],
        ];
    }

    echo json_encode(['maquina_id' => $maquinaId, 'leituras' => $lista, 'resumo' => $resumo]);
