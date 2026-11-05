<?php
$cfg = @include __DIR__ . '/../config.php';

try {
    if (!$cfg) throw new mysqli_sql_exception("config.php não encontrado (copie o config.exemplo.php)");
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['senha'], $cfg['banco']);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit(json_encode(["erro" => "Falha na conexão: " . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}
