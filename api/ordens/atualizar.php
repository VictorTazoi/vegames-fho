<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/OeeCalculator.php';

    header('Content-Type: application/json; charset=utf-8');
    exigirAdminApi();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido.']);
        exit;
    }

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int) ($dados['id'] ?? 0);
    $numero = trim((string) ($dados['numero'] ?? ''));
    $itemId = (int) ($dados['item_id'] ?? 0);
    $maquinaId = (int) ($dados['maquina_id'] ?? 0);
    $turnoId = (int) ($dados['turno_id'] ?? 0);
    $meta = (int) ($dados['meta_producao'] ?? 0);
    $inicio = trim((string) ($dados['inicio'] ?? ''));
    $fim = trim((string) ($dados['fim'] ?? ''));
    $status = trim((string) ($dados['status'] ?? ''));

    $statusValidos = ['planejada', 'em_andamento', 'finalizada', 'cancelada'];

    $erros = [];
    if ($id <= 0) {
        $erros[] = 'Ordem inválida.';
    }
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
        $erros[] = 'Informe a data/hora de início.';
    }
    if (!in_array($status, $statusValidos, true)) {
        $erros[] = 'Status inválido.';
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

    $stmtExiste = $pdo->prepare('SELECT id FROM ordens_producao WHERE id = :id');
    $stmtExiste->execute(['id' => $id]);
    if (!$stmtExiste->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['erro' => 'Ordem não encontrada.']);
        exit;
    }

    $tempoPlanejadoSeg = (int) round($meta * (float) $item['tempo_ciclo_seg']);
    if ($tempoPlanejadoSeg > 315360000) {
        http_response_code(422);
        echo json_encode(['erro' => 'Meta × tempo de ciclo do item resulta num tempo planejado absurdamente alto. Revise os valores.']);
        exit;
    }

    if ($status === 'finalizada' && $fim === '') {
        $fim = date('Y-m-d H:i:s');
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE ordens_producao SET
                numero = :numero,
                item_id = :item_id,
                maquina_id = :maquina_id,
                turno_id = :turno_id,
                meta_producao = :meta_producao,
                tempo_planejado_seg = :tempo_planejado_seg,
                tempo_ciclo_ideal_seg = :tempo_ciclo_ideal_seg,
                inicio = :inicio,
                fim = :fim,
                status = :status
            WHERE id = :id'
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
            'fim' => $fim !== '' ? $fim : null,
            'status' => $status,
            'id' => $id,
        ]);

        if ($status === 'finalizada') {
            (new OeeCalculator())->salvarSnapshotOrdem($id);
        }

        echo json_encode(['sucesso' => true]);
    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            http_response_code(422);
            echo json_encode(['erro' => 'Já existe outra ordem com este número.']);
            
            exit;
        }

        throw $e;
    }
