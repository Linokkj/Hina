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

    // Consulta corrigida utilizando 'numero_episodio'
    $stmtEp = $pdo->prepare("SELECT * FROM episodios WHERE anime_id = ? ORDER BY numero_episodio ASC");
    $stmtEp->execute([$id]);
    $episodios = $stmtEp->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($anime['titulo']); ?> - Hina</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <nav class="nav-links">
            <a href="index.php">Início</a>
        </nav>
    </header>

    <main class="container">
        <div style="display: flex; gap: 2rem; background: #1e1e1e; padding: 2rem; border-radius: 8px; margin-top: 2rem; flex-wrap: wrap;">
            <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="Capa" style="width: 220px; border-radius: 6px; object-fit: cover;">
            <div style="flex: 1;">
                <h1><?php echo htmlspecialchars($anime['titulo']); ?></h1>
                <p style="color: #f5c518; margin: 0.5rem 0; font-weight: bold;">
                    <i class="fa-solid fa-star"></i> Classificação: <?php echo number_format($anime['nota_imdb'], 1); ?>
                </p>
                <p style="color: #b0b0b0; margin-top: 1rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($anime['sinopse'])); ?></p>
                
                <?php if (!empty($anime['link_download'])): ?>
                    <a href="<?php echo htmlspecialchars($anime['link_download']); ?>" class="btn-download" target="_blank" style="margin-top: 1.5rem; display: inline-flex;">
                        <i class="fa-solid fa-download"></i> Download Geral
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <h2 style="margin-top: 2rem;">Lista de Episódios</h2>
        <div style="background: #1e1e1e; border-radius: 8px; padding: 1rem;">
            <?php if (!empty($episodios)): ?>
                <ul style="list-style: none;">
                    <?php foreach ($episodios as $ep): ?>
                        <li style="padding: 12px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center;">
                            <span>Episódio <?php echo $ep['numero_episodio']; ?>: <?php echo htmlspecialchars($ep['titulo']); ?></span>
                            <?php if (!empty($ep['link_download'])): ?>
                                <a href="<?php echo htmlspecialchars($ep['link_download']); ?>" class="btn-download" target="_blank" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">
                                    <i class="fa-solid fa-download"></i> Baixar Ep.
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #888;">Nenhum episódio registado para este anime de momento.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>