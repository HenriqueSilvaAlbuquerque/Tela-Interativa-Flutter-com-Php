<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../includes/conexao.php';

$result = $conn->query("SELECT id, nome, quantidade, valor FROM produtos ORDER BY nome");

$produtos = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['quantidade'] = (int) $row['quantidade'];
    $row['valor'] = (float) $row['valor'];
    $produtos[] = $row;
}

echo json_encode($produtos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
