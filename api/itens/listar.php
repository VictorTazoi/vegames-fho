<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
exigirLoginApi();

$itens = db()->query(
    'SELECT id, codigo, nome, tempo_ciclo_seg FROM itens ORDER BY codigo'
)->fetchAll();

echo json_encode(['itens' => $itens]);
