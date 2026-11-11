<?php
require_once 'conexao.php';

$animeEncontrado = null;
$mensagem = "";

if (isset($_POST['pesquisar'])) {
    $termoPesquisa = urlencode($_POST['nome_pesquisa']);
    $url = "https://api.jikan.moe/v4/anime?q={$termoPesquisa}&limit=1";

    $opts = ["http" => ["header" => "User-Agent: HinaTCCApp/1.0\r\n"]];
    $contexto = stream_context_create($opts);
    $resposta = @file_get_contents($url, false, $contexto);

    if ($resposta) {
        $dados = json_decode($resposta, true);
        if (!empty($dados['data'])) {
            $animeEncontrado = $dados['data'][0];
        } else {
            $mensagem = "Nenhum anime encontrado com esse nome.";
        }
    } else {
        $mensagem = "Erro ao contactar a API do MyAnimeList.";
    }
}

if (isset($_POST['guardar_anime'])) {
    $titulo = $_POST['titulo'];
    $sinopse = $_POST['sinopse'];
    $capa_url = $_POST['capa_url'];
    $nota_imdb = $_POST['nota_imdb'] ?? 0.0;
    $link_download = $_POST['link_download'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO animes (titulo, sinopse, capa_url, nota_imdb, link_download) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $sinopse, $capa_url, $nota_imdb, $link_download]);
        $mensagem = "Anime guardado com sucesso na base de dados!";
    } catch (PDOException $e) {
        $mensagem = "Erro ao guardar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Hina - Adicionar Anime via API</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <nav class="nav-links">
            <a href="index.php">Início</a>
            <a href="adicionar_anime.php">Adicionar Anime</a>
        </nav>
    </header>

    <div class="container" style="max-width: 600px; margin-top: 2rem;">
        <div style="background: #1e1e1e; padding: 2rem; border-radius: 8px;">
            <h2>Pesquisar e Registar via API</h2>
            
            <?php if (!empty($mensagem)): ?>
                <p style="background: #2c2c2c; padding: 10px; border-left: 4px solid #bb86fc; margin: 1rem 0;"><?php echo htmlspecialchars($mensagem); ?></p>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="color: #bb86fc; display: block; margin-bottom: 0.5rem;">Nome do Anime (Ex: Naruto, Bleach):</label>
                    <input type="text" name="nome_pesquisa" required placeholder="Escreve o nome..." style="width: 100%; padding: 0.7rem; background: #121212; border: 1px solid #333; color: #fff; border-radius: 4px;">
                </div>
                <button type="submit" name="pesquisar" class="btn-hina">Pesquisar na API</button>
            </form>

            <?php if ($animeEncontrado): ?>
                <form method="POST" style="margin-top: 2rem; border-top: 1px solid #333; padding-top: 1.5rem;">
                    <h3>Antevisão dos Dados</h3>
                    
                    <div style="display: flex; gap: 1rem; align-items: center; margin: 1rem 0; background: #252525; padding: 1rem; border-radius: 6px;">
                        <img src="<?php echo htmlspecialchars($animeEncontrado['images']['jpg']['large_image_url']); ?>" width="90" style="border-radius: 4px;">
                        <div>
                            <h4><?php echo htmlspecialchars($animeEncontrado['title']); ?></h4>
                            <p style="color: #f5c518; margin-top: 5px;">Nota: ⭐ <?php echo htmlspecialchars($animeEncontrado['score'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($animeEncontrado['title']); ?>">
                    <input type="hidden" name="sinopse" value="<?php echo htmlspecialchars($animeEncontrado['synopsis'] ?? 'Sem sinopse.'); ?>">
                    <input type="hidden" name="capa_url" value="<?php echo htmlspecialchars($animeEncontrado['images']['jpg']['large_image_url']); ?>">
                    <input type="hidden" name="nota_imdb" value="<?php echo htmlspecialchars($animeEncontrado['score'] ?? 0.0); ?>">

                    <div style="margin-bottom: 1rem;">
                        <label style="color: #bb86fc; display: block; margin-bottom: 0.5rem;">Link de Download (Opcional):</label>
                        <input type="url" name="link_download" placeholder="https://..." style="width: 100%; padding: 0.7rem; background: #121212; border: 1px solid #333; color: #fff; border-radius: 4px;">
                    </div>

                    <button type="submit" name="guardar_anime" class="btn-hina" style="background-color: #2e7d32; width: 100%;">Gravar na Base de Dados</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>