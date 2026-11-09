<?php
require 'conexao.php';

$id_anime = $_GET['id'] ?? null;

if (!$id_anime) {
    header("Location: index.php");
    exit;
}

try {
    $stmtAnime = $pdo->prepare("SELECT * FROM animes WHERE id = ?");
    $stmtAnime->execute([$id_anime]);
    $anime = $stmtAnime->fetch(PDO::FETCH_ASSOC);

    if (!$anime) {
        echo "Anime não encontrado.";
        exit;
    }

    $stmtEp = $pdo->prepare("SELECT * FROM episodios WHERE anime_id = ? ORDER BY numero_episodio ASC");
    $stmtEp->execute([$id_anime]);
    $episodios = $stmtEp->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($anime['titulo']); ?> - Hina</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .anime-detalhe-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; display: flex; gap: 40px; flex-wrap: wrap; }
        .anime-detalhe-capa img { width: 300px; height: 430px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .anime-detalhe-info { flex: 1; min-width: 280px; }
        .anime-detalhe-info h2 { font-size: 32px; margin-top: 0; color: #fff; }
        .anime-detalhe-info .ano { color: #ff0080; font-weight: bold; margin-bottom: 15px; display: inline-block; }
        .anime-detalhe-info p { color: #ccc; line-height: 1.6; }
        
        .episodios-section { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .episodio-card { background: #23252b; padding: 15px 20px; border-radius: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; transition: background 0.2s; }
        .episodio-card:hover { background: #2a2c37; }
        .episodio-titulo { font-weight: bold; color: #fff; }
    </style>
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo">
            <img src="https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg" class="logo-img" alt="Logo Hina">
            <h1>Hina<span>.</span></h1>
        </a>
        <nav class="nav-links">
            <a href="index.php">Início</a>
            <a href="login.php">Entrar</a>
            <a href="painel.php">Painel Admin</a>
        </nav>
    </header>

    <div class="anime-detalhe-container">
        <div class="anime-detalhe-capa">
            <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="Capa de <?php echo htmlspecialchars($anime['titulo']); ?>">
        </div>
        <div class="anime-detalhe-info">
            <h2><?php echo htmlspecialchars($anime['titulo']); ?></h2>
            <span class="ano">Ano de Lançamento: <?php echo htmlspecialchars($anime['ano_lancamento']); ?></span>
            <h3>Sinopse</h3>
            <p><?php echo nl2br(htmlspecialchars($anime['sinopse'])); ?></p>
        </div>
    </div>

    <div class="episodios-section">
        <h2>Episódios Disponíveis</h2>
        
        <?php if (!empty($episodios)): ?>
            <?php foreach ($episodios as $ep): ?>
                <div class="episodio-card">
                    <span class="episodio-titulo">
                        Episódio <?php echo $ep['numero_episodio']; ?>: <?php echo htmlspecialchars($ep['titulo_episodio']); ?>
                    </span>
                    <a href="<?php echo htmlspecialchars($ep['link_download']); ?>" target="_blank" class="btn-hina" style="padding: 6px 14px; font-size: 14px;">
                        📥 Baixar / Assistir
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #777;">Ainda não existem episódios cadastrados para este anime.</p>
        <?php endif; ?>
    </div>

    <footer class="site-footer">
        <p>&copy; 2026 Hina - Trabalho de Conclusão de Curso.</p>
    </footer>

</body>
</html>