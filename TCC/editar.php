<?php
require 'conexao.php';

$id = $_GET['id'] ?? 0;
$mensagem = "";

// Buscar os dados atuais do anime
$stmt = $pdo->prepare("SELECT * FROM animes WHERE id = ?");
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    die("Anime não encontrado.");
}

// Processar a atualização quando o formulário for submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $sinopse = $_POST['sinopse'] ?? '';
    $capa_url = $_POST['capa_url'] ?? '';
    $ano = $_POST['ano'] ?? '';

    if (!empty($titulo) && !empty($capa_url)) {
        try {
            $stmtUp = $pdo->prepare("UPDATE animes SET titulo = ?, sinopse = ?, capa_url = ?, ano_lancamento = ? WHERE id = ?");
            $stmtUp->execute([$titulo, $sinopse, $capa_url, $ano, $id]);
            $mensagem = "Anime atualizado com sucesso!";
            
            // Atualizar os dados locais para refletirem no formulário
            $anime['titulo'] = $titulo;
            $anime['sinopse'] = $sinopse;
            $anime['capa_url'] = $capa_url;
            $anime['ano_lancamento'] = $ano;
        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar: " . $e->getMessage();
        }
    } else {
        $mensagem = "Preencha pelo menos o título e a capa.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Anime - Hina</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { max-width: 600px; margin: 30px auto; padding: 20px; background: #1a1b22; border-radius: 8px; border: 1px solid #23252b; color: #fff; }
        form input, form textarea { width: 100%; padding: 12px; margin-bottom: 15px; background: #23252b; border: 1px solid #3f4254; color: #fff; border-radius: 4px; box-sizing: border-box; outline: none; }
        form input:focus, form textarea:focus { border-color: #ff0080; }
        .msg { padding: 10px; background: rgba(255,0,128,0.1); border: 1px solid #ff0080; color: #ff0080; margin-bottom: 15px; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2>Editar Anime: <?php echo htmlspecialchars($anime['titulo']); ?></h2>

        <?php if (!empty($mensagem)): ?>
            <div class="msg"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form action="editar.php?id=<?php echo $id; ?>" method="POST">
            <label>Título do Anime:</label>
            <input type="text" name="titulo" value="<?php echo htmlspecialchars($anime['titulo']); ?>" required>

            <label>Sinopse:</label>
            <textarea name="sinopse" rows="4"><?php echo htmlspecialchars($anime['sinopse']); ?></textarea>

            <label>URL da Imagem de Capa:</label>
            <input type="text" name="capa_url" value="<?php echo htmlspecialchars($anime['capa_url']); ?>" required>

            <label>Ano de Lançamento:</label>
            <input type="number" name="ano" value="<?php echo htmlspecialchars($anime['ano_lancamento']); ?>">

            <button type="submit" style="width: 100%; padding: 12px; background: #ff0080; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Guardar Alterações</button>
        </form>

        <p style="margin-top: 20px; text-align: center;"><a href="painel.php" style="color: #ff0080; text-decoration: none;">&larr; Voltar ao Painel</a></p>
    </div>
</body>
</html>