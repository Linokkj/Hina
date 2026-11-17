<?php
require_once 'conexao.php';
$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM animes WHERE id = ?");
    $stmt->execute([$id]);
    $anime = $stmt->fetch();

    if (!$anime) {
        die("Anime não encontrado.");
    }

    $stmtEp = $pdo->prepare("SELECT * FROM episodios WHERE anime_id = ? ORDER BY numero_episodio ASC");
    $stmtEp->execute([$id]);
    $episodios = $stmtEp->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($anime['titulo']); ?> - Hina</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .anime-container { max-width: 1000px; margin: 30px auto; padding: 20px; color: #fff; }
        .anime-detalhes-box { display: flex; gap: 2rem; background: #1e1e1e; padding: 2rem; border-radius: 8px; flex-wrap: wrap; border: 1px solid #2a2c37; }
        .anime-capa { width: 220px; border-radius: 6px; object-fit: cover; height: 320px; }
        .anime-info { flex: 1; }
        .anime-info h1 { font-size: 2rem; margin-bottom: 10px; }
        .anime-nota { color: #f5c518; margin: 0.5rem 0; font-weight: bold; font-size: 1.1rem; }
        .anime-sinopse { color: #b0b0b0; margin-top: 1rem; line-height: 1.6; font-size: 1rem; }
        .botoes-acao { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 1.5rem; }
        .btn-acao-custom { display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.9rem; }
        .btn-download-geral { background: #ff0080; color: #fff; }
        .btn-imdb { background: #f5c518; color: #000; }
        .trailer-box { margin-top: 2rem; background: #1e1e1e; padding: 1.5rem; border-radius: 8px; border: 1px solid #2a2c37; }
        .trailer-titulo { font-size: 1.3rem; border-left: 4px solid #ff0080; padding-left: 10px; margin-bottom: 1rem; color: #fff; }
        .video-container { position: relative; width: 100%; padding-bottom: 56.25%; height: 0; background: #000; border-radius: 6px; overflow: hidden; }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .episodios-box { background: #1e1e1e; border-radius: 8px; padding: 1rem; border: 1px solid #2a2c37; margin-top: 1rem; }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="nav-links">
            <a href="index.php">Início</a>
            <a href="painel.php">Painel Admin</a>
        </nav>
    </header>

    <main class="anime-container">
        <!-- Detalhes Principais do Anime -->
        <div class="anime-detalhes-box">
            <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="Capa" class="anime-capa">
            <div class="anime-info">
                <h1><?php echo htmlspecialchars($anime['titulo']); ?></h1>
                <p class="anime-nota">
                    <i class="fa-solid fa-star"></i> Classificação: 
                    <?php 
                    $notaFinal = $anime['nota_imdb'] ?? 0;
                    if ($notaFinal <= 0) { $notaFinal = 8.4; }
                    echo number_format((float)$notaFinal, 1);
                    ?>
                </p>
                <p class="anime-sinopse"><?php echo nl2br(htmlspecialchars($anime['sinopse'])); ?></p>
                
                <div class="botoes-acao">
                    <?php if (!empty($anime['link_download'])): ?>
                        <a href="<?php echo htmlspecialchars($anime['link_download']); ?>" class="btn-acao-custom btn-download-geral" target="_blank">
                            <i class="fa-solid fa-download"></i> Download Geral
                        </a>
                    <?php endif; ?>

                    <!-- Botão do IMDb -->
                    <?php if (!empty($anime['imdb_link'])): ?>
                        <a href="<?php echo htmlspecialchars($anime['imdb_link']); ?>" class="btn-acao-custom btn-imdb" target="_blank">
                            <i class="fa-brands fa-imdb" style="font-size: 1.1rem;"></i> Ver no IMDb
                        </a>
                    <?php else: ?>
                        <a href="https://www.imdb.com/find?q=<?php echo urlencode($anime['titulo']); ?>" class="btn-acao-custom btn-imdb" target="_blank">
                            <i class="fa-brands fa-imdb" style="font-size: 1.1rem;"></i> Pesquisar no IMDb
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bloco do Trailer via Iframe do YouTube -->
        <?php if (!empty($anime['trailer_url'])): ?>
            <div class="trailer-box">
                <h3 class="trailer-titulo">Trailer Oficial</h3>
                <div class="video-container">
                    <iframe src="<?php echo htmlspecialchars($anime['trailer_url']); ?>" allowfullscreen></iframe>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lista de Episódios -->
        <h2 style="margin-top: 2rem;">Lista de Episódios</h2>
        <div class="episodios-box">
            <?php if (!empty($episodios)): ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($episodios as $ep): ?>
                        <li style="padding: 12px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center;">
                            <span>Episódio <?php echo $ep['numero_episodio']; ?>: <?php echo htmlspecialchars($ep['titulo']); ?></span>
                            <?php if (!empty($ep['link_download'])): ?>
                                <a href="<?php echo htmlspecialchars($ep['link_download']); ?>" class="btn-download" target="_blank" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; background: #2e7d32; color: #fff; border-radius: 4px; text-decoration: none;">
                                    <i class="fa-solid fa-download"></i> Baixar Ep.
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #888; margin: 0;">Nenhum episódio registado para este anime de momento.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>