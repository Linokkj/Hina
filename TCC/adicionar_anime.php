<?php
require_once 'conexao.php';
$mensagem = "";

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

            <p id="status-api" style="display:none; background: #2c2c2c; padding: 10px; border-left: 4px solid #bb86fc; margin: 1rem 0;"></p>

            <div style="margin-bottom: 1rem;">
                <label style="color: #bb86fc; display: block; margin-bottom: 0.5rem;">Nome do Anime (Ex: Naruto, Bleach):</label>
                <input type="text" id="nome_pesquisa" required placeholder="Escreve o nome..." style="width: 100%; padding: 0.7rem; background: #121212; border: 1px solid #333; color: #fff; border-radius: 4px;">
            </div>
            <button type="button" onclick="buscarAnime()" class="btn-hina">Pesquisar na API</button>

            <form method="POST" id="form-salvar" style="display: none; margin-top: 2rem; border-top: 1px solid #333; padding-top: 1.5rem;">
                <h3>Antevisão dos Dados</h3>
                
                <div style="display: flex; gap: 1rem; align-items: center; margin: 1rem 0; background: #252525; padding: 1rem; border-radius: 6px;">
                    <img id="preview-capa" src="" width="90" style="border-radius: 4px;">
                    <div>
                        <h4 id="preview-titulo"></h4>
                        <p style="color: #f5c518; margin-top: 5px;">Nota: ⭐ <span id="preview-nota"></span></p>
                    </div>
                </div>

                <input type="hidden" name="titulo" id="input-titulo">
                <input type="hidden" name="sinopse" id="input-sinopse">
                <input type="hidden" name="capa_url" id="input-capa">
                <input type="hidden" name="nota_imdb" id="input-nota">

                <div style="margin-bottom: 1rem;">
                    <label style="color: #bb86fc; display: block; margin-bottom: 0.5rem;">Link de Download (Opcional):</label>
                    <input type="url" name="link_download" placeholder="https://..." style="width: 100%; padding: 0.7rem; background: #121212; border: 1px solid #333; color: #fff; border-radius: 4px;">
                </div>

                <button type="submit" name="guardar_anime" class="btn-hina" style="background-color: #2e7d32; width: 100%;">Gravar na Base de Dados</button>
            </form>
        </div>
    </div>

    <script>
    async function buscarAnime() {
        const termo = document.getElementById('nome_pesquisa').value.trim();
        const status = document.getElementById('status-api');
        const formSalvar = document.getElementById('form-salvar');

        if (!termo) return;

        status.style.display = 'block';
        status.textContent = 'Buscando anime...';
        formSalvar.style.display = 'none';

        try {
            const resposta = await fetch(`https://api.jikan.moe/v4/anime?q=${encodeURIComponent(termo)}&limit=1`);
            const dados = await resposta.json();

            if (dados.data && dados.data.length > 0) {
                const anime = dados.data[0];
                const capa = anime.images.jpg.large_image_url;
                const nota = anime.score ?? 0.0;
                const sinopse = anime.synopsis ?? 'Sem sinopse.';

                document.getElementById('preview-capa').src = capa;
                document.getElementById('preview-titulo').textContent = anime.title;
                document.getElementById('preview-nota').textContent = nota || 'N/A';

                document.getElementById('input-titulo').value = anime.title;
                document.getElementById('input-sinopse').value = sinopse;
                document.getElementById('input-capa').value = capa;
                document.getElementById('input-nota').value = nota;

                status.style.display = 'none';
                formSalvar.style.display = 'block';
            } else {
                status.textContent = 'Nenhum anime encontrado com esse nome.';
            }
        } catch (erro) {
            status.textContent = 'Erro de conexão com a API: ' + erro.message;
        }
    }
    </script>
</body>
</html>