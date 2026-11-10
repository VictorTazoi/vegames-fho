<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
exigirLoginApi();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Informe o id do item.']);
    exit;
}

$stmt = db()->prepare('SELECT id, codigo, nome, tempo_ciclo_seg FROM itens WHERE id = :id');
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    echo json_encode(['erro' => 'Item não encontrado.']);
    exit;
}

echo json_encode(['item' => $item]);
