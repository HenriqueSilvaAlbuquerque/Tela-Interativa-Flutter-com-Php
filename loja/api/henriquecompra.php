<?php
// Recebe POST com JSON: [{"id": 2, "qtd": 3}, ...] e desconta do estoque.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["erro" => "Use POST"]));
}

require __DIR__ . '/../includes/conexao.php';

$itens = json_decode(file_get_contents('php://input'), true);
if (!is_array($itens) || !$itens) {
    http_response_code(400);
    exit(json_encode(["erro" => "Carrinho vazio ou inválido"]));
}

$conn->begin_transaction();
$stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ? AND quantidade >= ?");

foreach ($itens as $item) {
    $id = (int) $item['id'];
    $qtd = (int) $item['qtd'];
    $stmt->bind_param("iii", $qtd, $id, $qtd);
    $stmt->execute();

    if ($qtd <= 0 || $stmt->affected_rows === 0) {
        $conn->rollback();
        http_response_code(409);
        exit(json_encode(["erro" => "Estoque insuficiente para o produto $id"]));
    }
}

$conn->commit();
echo json_encode(["ok" => true]);
