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
$codigo = trim((string) ($dados['codigo'] ?? ''));
$nome = trim((string) ($dados['nome'] ?? ''));
$tempoCiclo = (float) ($dados['tempo_ciclo_seg'] ?? 0);

$erros = [];
if ($codigo === '') {
    $erros[] = 'Informe o código do item.';
}
if ($nome === '') {
    $erros[] = 'Informe o nome do item.';
}
if ($tempoCiclo <= 0) {
    $erros[] = 'O tempo de ciclo deve ser maior que zero.';
}

if ($erros) {
    http_response_code(422);
    echo json_encode(['erro' => implode(' ', $erros)]);
    exit;
}

$pdo = db();

try {
    $stmt = $pdo->prepare(
        'INSERT INTO itens (codigo, nome, tempo_ciclo_seg) VALUES (:codigo, :nome, :tempo_ciclo_seg)'
    );
    $stmt->execute([
        'codigo' => $codigo,
        'nome' => $nome,
        'tempo_ciclo_seg' => $tempoCiclo,
    ]);
    echo json_encode(['sucesso' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(422);
        echo json_encode(['erro' => 'Já existe um item com este código.']);
        exit;
    }
    throw $e;
}
