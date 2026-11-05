<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require 'includes/conexao.php';

    $nome = trim($_POST['nome'] ?? '');
    $quantidade = (int) ($_POST['quantidade'] ?? -1);
    $valor = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
    $msg = 'invalido';

    if ($nome !== '' && $quantidade >= 0 && $valor > 0) {
        $stmt = $conn->prepare("INSERT INTO produtos (nome, quantidade, valor) VALUES (?, ?, ?)");
        $stmt->bind_param("sid", $nome, $quantidade, $valor);
        $stmt->execute();
        $msg = 'cadastrado';
    }

    // Redireciona para que o F5 não repita o envio do formulário.
    header("Location: henriquefrontend.php?msg=$msg");
    exit;
}

$avisos = [
    'cadastrado' => ["Produto cadastrado com sucesso!", ""],
    'invalido'   => ["Preencha os campos corretamente.", "erro"],
];
$aviso = $avisos[$_GET['msg'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar Produto</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <header>
        <div>
            <h1>Cadastrar produto</h1>
            <p class="suave">Novo item para a loja</p>
        </div>
        <a class="link" href="index.php">Ver loja</a>
    </header>

    <main>
        <?php if ($aviso): ?>
            <p class="aviso <?= $aviso[1] ?>"><?= $aviso[0] ?></p>
        <?php endif ?>

        <form method="POST" class="card">
            <label>Nome do produto
                <input type="text" name="nome" required>
            </label>
            <label>Quantidade disponível
                <input type="number" name="quantidade" min="0" required>
            </label>
            <label>Valor (R$)
                <input type="number" name="valor" min="0.01" step="0.01" required>
            </label>
            <button type="submit" class="primario">Cadastrar produto</button>
        </form>
    </main>
</body>
</html>
