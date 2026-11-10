<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirLoginApi();

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['erro' => 'Informe o id da ordem.']);
        exit;
    }

    $stmt = db()->prepare(
        "SELECT op.id, op.numero, op.item_id, i.codigo AS item_codigo, i.nome AS item_nome,
                op.maquina_id, m.nome AS maquina_nome, op.turno_id, t.nome AS turno_nome,
                op.meta_producao, op.tempo_planejado_seg, op.tempo_ciclo_ideal_seg,
                op.inicio, op.fim, op.status,
                COALESCE((SELECT SUM(pecas_contadas) FROM contagem_producao cp
                        WHERE cp.maquina_id = op.maquina_id AND cp.registrado_em >= op.inicio
                            AND (op.fim IS NULL OR cp.registrado_em <= op.fim)), 0) AS produzidas,
                oee.disponibilidade AS oee_disponibilidade, oee.performance AS oee_performance,
                oee.qualidade AS oee_qualidade, oee.oee AS oee_oee, oee.calculado_em AS oee_calculado_em
        FROM ordens_producao op
        JOIN maquinas m ON m.id = op.maquina_id
        JOIN turnos t ON t.id = op.turno_id
        LEFT JOIN itens i ON i.id = op.item_id
        LEFT JOIN oee_resultados oee ON oee.ordem_id = op.id
        WHERE op.id = :id"
    );
    $stmt->execute(['id' => $id]);
    $ordem = $stmt->fetch();

    if (!$ordem) {
        http_response_code(404);
        echo json_encode(['erro' => 'Ordem não encontrada.']);
        exit;
    }

    echo json_encode(['ordem' => $ordem]);
