<?php
// Recebe POST com JSON: {"id": 2, "qtd": 5} para adicionar ou {"id": 2, "qtd": -3} para remover.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["erro" => "Use POST"]));
}

require __DIR__ . '/../includes/conexao.php';

$d = json_decode(file_get_contents('php://input'), true);
$id = (int) ($d['id'] ?? 0);
$qtd = (int) ($d['qtd'] ?? 0);

if ($qtd === 0) {
    http_response_code(400);
    exit(json_encode(["erro" => "Quantidade inválida"], JSON_UNESCAPED_UNICODE));
}

// O estoque nunca fica negativo.
$stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ? AND quantidade + ? >= 0");
$stmt->bind_param("iii", $qtd, $id, $qtd);
$stmt->execute();

if (!$stmt->affected_rows) {
    http_response_code(409);
    exit(json_encode(["erro" => "Não há unidades suficientes para remover."], JSON_UNESCAPED_UNICODE));
}

echo json_encode(["ok" => true]);
