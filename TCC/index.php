<?php
require 'conexao.php';

try {
    $stmt = $pdo->query("SELECT * FROM animes ORDER BY id DESC");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao carregar os animes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hina - Catálogo de Animes</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    <section class="hero-banner">
        <div class="hero-content">
            <h2>Os melhores animes para assistir</h2>
            <p>Explore o nosso catálogo interativo desenvolvido para o Projeto Integrador.</p>
        </div>
    </section>

    <main class="container">
        <h2>Catálogo de Animes</h2>

        <div class="anime-grid">
            <?php if (!empty($animes)): ?>
                <?php foreach ($animes as $anime): ?>
                    <div class="anime-card">
                        <div class="image-container">
                            <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="Capa do Anime">
                            <?php if (!empty($anime['nota_imdb']) && $anime['nota_imdb'] > 0): ?>
                                <div class="imdb-badge">
                                    <i class="fa-solid fa-star"></i> <?php echo number_format((float)$anime['nota_imdb'], 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="anime-info">
                            <div>
                                <h3><?php echo htmlspecialchars($anime['titulo']); ?></h3>
                                <p>
                                    <?php 
                                    $sinopse = $anime['sinopse'] ?? '';
                                    echo htmlspecialchars(strlen($sinopse) > 80 ? substr($sinopse, 0, 80) . "..." : $sinopse); 
                                    ?>
                                </p>
                            </div>
                            <a href="anime.php?id=<?php echo $anime['id']; ?>" class="btn-hina">Ver Episódios</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #888;">Nenhum anime registado de momento. Adicione através do painel administrativo.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Hina - Trabalho de Conclusão de Curso.</p>
    </footer>

</body>
</html>