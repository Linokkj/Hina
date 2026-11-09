<?php
require 'conexao.php';

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $sinopse = $_POST['sinopse'] ?? '';
    $capa_url = $_POST['capa_url'] ?? '';
    $ano = $_POST['ano'] ?? '';

    if (!empty($titulo) && !empty($capa_url)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO animes (titulo, sinopse, capa_url, ano_lancamento) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $sinopse, $capa_url, $ano]);
            $mensagem = "Anime cadastrado com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar: " . $e->getMessage();
        }
    } else {
        $mensagem = "Por favor, preencha pelo menos o título e a URL da capa.";
    }
}

$stmt = $pdo->query("SELECT * FROM animes ORDER BY id DESC");
$animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo - Hina</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { max-width: 900px; margin: 30px auto; padding: 20px; background: #1a1b22; border-radius: 8px; border: 1px solid #23252b; }
        form input, form textarea { width: 100%; padding: 12px; margin-bottom: 15px; background: #23252b; border: 1px solid #3f4254; color: #fff; border-radius: 4px; box-sizing: border-box; outline: none; }
        form input:focus, form textarea:focus { border-color: #ff0080; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #2a2c37; }
        th { background-color: #23252b; color: #ff0080; }
        .msg { padding: 10px; background: rgba(255,0,128,0.1); border: 1px solid #ff0080; color: #ff0080; margin-bottom: 15px; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo">
            <img src="https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg" class="logo-img" alt="Logo Hina">
            <h1>Hina- Painel</h1>
        </a>
        <nav class="nav-links">
            <a href="index.php">Ver Site</a>
            <a href="login.php">Entrar</a>
            <a href="painel.php">Gerir Animes</a>
        </nav>
    </header>

    <div class="admin-container">
        <h2>Cadastrar Novo Anime</h2>

        <?php if (!empty($mensagem)): ?>
            <div class="msg"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form action="painel.php" method="POST">
            <label>Título do Anime:</label>
            <input type="text" name="titulo" required>

            <label>Sinopse:</label>
            <textarea name="sinopse" rows="4"></textarea>

            <label>URL da Imagem de Capa:</label>
            <input type="text" name="capa_url" placeholder="https://exemplo.com/capa.jpg" required>

            <label>Ano de Lançamento:</label>
            <input type="number" name="ano">

            <button type="submit" class="btn-hina" style="width: 100%;">Cadastrar Anime</button>
        </form>

        <h2 style="margin-top: 40px;">Animes Registados no Sistema</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Ano</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($animes)): ?>
                    <?php foreach ($animes as $anime): ?>
                        <tr>
                            <td><?php echo $anime['id']; ?></td>
                            <td><?php echo htmlspecialchars($anime['titulo']); ?></td>
                            <td><?php echo $anime['ano_lancamento']; ?></td>
                            <td>
                                <a href="#" style="color: #ff0080; text-decoration: none; font-weight: bold;">Editar</a> | 
                                <a href="#" style="color: #ff4757; text-decoration: none; font-weight: bold;">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #777;">Nenhum anime registado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>