<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginIhmApi();

    $pdo = db();

    $maquinas = $pdo->query('SELECT id, nome, localizacao, status FROM maquinas ORDER BY nome')->fetchAll();

    $stmtParada = $pdo->prepare(
        "SELECT inicio, tipo, motivo FROM paradas WHERE maquina_id = :id AND fim IS NULL ORDER BY inicio DESC LIMIT 1"
    );
    $stmtOrdem = $pdo->prepare(
        "SELECT op.numero, op.meta_producao, op.tempo_ciclo_ideal_seg, op.inicio,
                COALESCE((SELECT SUM(pecas_contadas) FROM contagem_producao cp
                        WHERE cp.maquina_id = op.maquina_id AND cp.registrado_em >= op.inicio), 0) AS produzidas
        FROM ordens_producao op
        WHERE op.maquina_id = :id AND op.status = 'em_andamento' ORDER BY op.inicio DESC LIMIT 1"
    );
    $stmtProximaOrdem = $pdo->prepare(
        "SELECT numero FROM ordens_producao
        WHERE maquina_id = :id AND status = 'planejada' ORDER BY inicio ASC, id ASC LIMIT 1"
    );
    $stmtProduzidas = $pdo->prepare(
        "SELECT COALESCE(SUM(pecas_contadas),0) FROM contagem_producao WHERE maquina_id = :id AND DATE(registrado_em) = CURDATE()"
    );
    $stmtRejeitadas = $pdo->prepare(
        "SELECT COALESCE(SUM(quantidade),0) FROM refugo WHERE maquina_id = :id AND DATE(registrado_em) = CURDATE()"
    );
    $stmtParadoHoje = $pdo->prepare(
        "SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, inicio, COALESCE(fim, NOW()))),0)
        FROM paradas WHERE maquina_id = :id AND DATE(inicio) = CURDATE()"
    );

    foreach ($maquinas as &$maquina) {
        $id = $maquina['id'];

        $stmtParada->execute(['id' => $id]);
        $parada = $stmtParada->fetch();
        $maquina['parada_atual'] = $parada ? [
            'desde' => $parada['inicio'],
            'tipo' => $parada['tipo'],
            'motivo' => $parada['motivo'] ?: 'Não classificado',
        ] : null;

        $stmtOrdem->execute(['id' => $id]);
        $ordem = $stmtOrdem->fetch();
        $maquina['ordem_ativa'] = $ordem ?: null;

        $stmtProximaOrdem->execute(['id' => $id]);
        $proximaOrdem = $stmtProximaOrdem->fetch();
        $maquina['ordem_proxima'] = $proximaOrdem ?: null;

        $stmtProduzidas->execute(['id' => $id]);
        $maquina['produzidas_hoje'] = (int) $stmtProduzidas->fetchColumn();

        $stmtRejeitadas->execute(['id' => $id]);
        $maquina['rejeitadas_hoje'] = (int) $stmtRejeitadas->fetchColumn();

        $tempoPlanejadoSeg = 8 * 3600;

        $stmtParadoHoje->execute(['id' => $id]);
        $tempoParadoSeg = (int) $stmtParadoHoje->fetchColumn();
        $tempoOperandoSeg = max(0, $tempoPlanejadoSeg - $tempoParadoSeg);

        $cicloIdealSeg = $ordem ? $ordem['tempo_ciclo_ideal_seg'] : 5;
        $totalProduzido = $maquina['produzidas_hoje'] + $maquina['rejeitadas_hoje'];

        $disponibilidade = $tempoOperandoSeg / $tempoPlanejadoSeg;
        $performance = $tempoOperandoSeg > 0 ? min(1, ($totalProduzido * $cicloIdealSeg) / $tempoOperandoSeg) : 0;
        $qualidade = $totalProduzido > 0 ? $maquina['produzidas_hoje'] / $totalProduzido : 0;

        $maquina['oee'] = round($disponibilidade * $performance * $qualidade * 100, 1);
    }
    unset($maquina);

    echo json_encode(['maquinas' => $maquinas]);
