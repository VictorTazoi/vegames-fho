<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirAdminApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $numero = trim((string) ($dados['numero'] ?? ''));
    $itemId = (int) ($dados['item_id'] ?? 0);
    $maquinaId = (int) ($dados['maquina_id'] ?? 0);
    $turnoId = (int) ($dados['turno_id'] ?? 0);
    $meta = (int) ($dados['meta_producao'] ?? 0);
    $inicio = trim((string) ($dados['inicio'] ?? ''));

    $erros = [];
    if ($numero === '') {
        $erros[] = 'Informe o número da ordem.';
    }
    if ($itemId <= 0) {
        $erros[] = 'Selecione o item a ser produzido.';
    }
    if ($maquinaId <= 0) {
        $erros[] = 'Selecione a máquina/estação.';
    }
    if ($turnoId <= 0) {
        $erros[] = 'Selecione o turno.';
    }
    if ($meta <= 0) {
        $erros[] = 'A meta de produção deve ser maior que zero.';
    } elseif ($meta > 1000000) {
        $erros[] = 'A meta de produção não pode passar de 1.000.000 peças.';
    }
    if ($inicio === '') {
        $erros[] = 'Informe a data/hora de início planejada.';
    }

    if ($erros) {
        http_response_code(422);
        echo json_encode(['erro' => implode(' ', $erros)]);
        exit;
    }

    $pdo = db();

    $stmtItem = $pdo->prepare('SELECT tempo_ciclo_seg FROM itens WHERE id = :id');
    $stmtItem->execute(['id' => $itemId]);
    $item = $stmtItem->fetch();
    if (!$item) {
        http_response_code(422);
        echo json_encode(['erro' => 'Item não encontrado.']);
        exit;
    }

    $tempoPlanejadoSeg = (int) round($meta * (float) $item['tempo_ciclo_seg']);
    if ($tempoPlanejadoSeg > 315360000) {
        http_response_code(422);
        echo json_encode(['erro' => 'Meta × tempo de ciclo do item resulta num tempo planejado absurdamente alto. Revise os valores.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO ordens_producao
                (numero, item_id, maquina_id, turno_id, meta_producao, tempo_planejado_seg, tempo_ciclo_ideal_seg, inicio, status)
            VALUES
                (:numero, :item_id, :maquina_id, :turno_id, :meta_producao, :tempo_planejado_seg, :tempo_ciclo_ideal_seg, :inicio, "planejada")'
        );
        $stmt->execute([
            'numero' => $numero,
            'item_id' => $itemId,
            'maquina_id' => $maquinaId,
            'turno_id' => $turnoId,
            'meta_producao' => $meta,
            'tempo_planejado_seg' => $tempoPlanejadoSeg,
            'tempo_ciclo_ideal_seg' => $item['tempo_ciclo_seg'],
            'inicio' => $inicio,
        ]);
        echo json_encode(['sucesso' => true, 'id' => (int) $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(422);
            echo json_encode(['erro' => 'Já existe uma ordem com este número.']);
            exit;
        }
        throw $e;
    }
