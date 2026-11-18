    <?php
    require_once 'conexao.php';
    $id = $_GET['id'] ?? 0;

    $mensagemErro = '';
    $mensagemSucesso = '';

    // Processar o envio do formulário de comentário
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario'])) {
        $nome = trim($_POST['nome_usuario'] ?? '');
        $nota = intval($_POST['nota'] ?? 5);
        $comentario = trim($_POST['comentario'] ?? '');

        if (!empty($nome) && !empty($comentario)) {
            try {
                $stmtInsert = $pdo->prepare("INSERT INTO comentarios_anime (anime_id, nome_usuario, nota, comentario) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$id, $nome, $nota, $comentario]);
                
                // REDIRECIONAMENTO (Evita o reenvio do formulário ao atualizar com F5)
                header("Location: anime.php?id=" . $id . "&sucesso=1");
                exit;
            } catch (PDOException $e) {
                $mensagemErro = "Erro ao enviar comentário: " . $e->getMessage();
            }
        } else {
            $mensagemErro = "Por favor, preencha todos os campos obrigatórios.";
        }
    }

    // Verifica se veio parâmetro de sucesso pela URL após o redirecionamento
    if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1) {
        $mensagemSucesso = "Comentário enviado com sucesso!";
    }

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

        // Buscar comentários do anime
        $stmtComentarios = $pdo->prepare("SELECT * FROM comentarios_anime WHERE anime_id = ? ORDER BY data_criacao DESC");
        $stmtComentarios->execute([$id]);
        $comentarios = $stmtComentarios->fetchAll();

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
            .trailer-box, .episodios-box, .comentarios-box { background: #1e1e1e; border-radius: 8px; padding: 1.5rem; border: 1px solid #2a2c37; margin-top: 1.5rem; }
            .trailer-titulo, .secao-titulo { font-size: 1.3rem; border-left: 4px solid #ff0080; padding-left: 10px; margin-bottom: 1rem; color: #fff; }
            .video-container { position: relative; width: 100%; padding-bottom: 56.25%; height: 0; background: #000; border-radius: 6px; overflow: hidden; }
            .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
            
            /* Estilos do Formulário e Comentários */
            .form-comentario input, .form-comentario select, .form-comentario textarea { width: 100%; padding: 10px; margin-bottom: 10px; background: #2a2c37; border: 1px solid #444; color: #fff; border-radius: 4px; }
            .form-comentario button { background: #ff0080; color: #fff; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer; }
            .comentario-item { border-bottom: 1px solid #333; padding: 12px 0; }
            .comentario-item:last-child { border-bottom: none; }
            .comentario-header { display: flex; justify-content: space-between; font-size: 0.9rem; color: #aaa; margin-bottom: 5px; }
            .comentario-autor { font-weight: bold; color: #fff; }
            .alerta-sucesso { background: #2e7d32; color: #fff; padding: 10px; border-radius: 4px; margin-bottom: 1rem; }
            .alerta-erro { background: #c62828; color: #fff; padding: 10px; border-radius: 4px; margin-bottom: 1rem; }
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

            <!-- Bloco do Trailer (Só exibe se houver link válido do YouTube) -->
            <?php 
                $urlTrailer = trim($anime['trailer_url'] ?? '');
                $embedUrl = '';
                $watchUrl = '';
                $temTrailerValido = false;

                if (!empty($urlTrailer) && preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $urlTrailer, $match)) {
                    $videoId = $match[1];
                    $embedUrl = "https://www.youtube.com/embed/" . $videoId;
                    $watchUrl = "https://www.youtube.com/watch?v=" . $videoId;
                    $temTrailerValido = true;
                }
            ?>

            <?php if ($temTrailerValido): ?>
                <div class="trailer-box">
                    <h3 class="trailer-titulo">Trailer Oficial</h3>
                    <div class="video-container">
                        <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" 
                                title="Trailer do Anime" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen></iframe>
                    </div>
                    <div style="text-align: center; margin-top: 10px;">
                        <a href="<?php echo htmlspecialchars($watchUrl); ?>" target="_blank" rel="noopener noreferrer" style="color: #ff0080; text-decoration: none; font-size: 0.9rem; font-weight: bold;">
                            <i class="fa-brands fa-youtube"></i> Assistir diretamente no YouTube (Caso o player esteja bloqueado)
                        </a>
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

            <!-- Seção de Avaliações e Comentários -->
            <div class="comentarios-box">
                <h3 class="secao-titulo">Avaliações e Comentários</h3>

                <?php if (!empty($mensagemSucesso)): ?>
                    <div class="alerta-sucesso"><?php echo $mensagemSucesso; ?></div>
                <?php endif; ?>
                <?php if (!empty($mensagemErro)): ?>
                    <div class="alerta-erro"><?php echo $mensagemErro; ?></div>
                <?php endif; ?>

                <!-- Formulário -->
                <form action="anime.php?id=<?php echo $id; ?>" method="POST" class="form-comentario">
                    <input type="text" name="nome_usuario" placeholder="Seu Nome" required>
                    <select name="nota" required>
                        <option value="5">⭐⭐⭐⭐⭐ (Excelente)</option>
                        <option value="4">⭐⭐⭐⭐ (Muito Bom)</option>
                        <option value="3">⭐⭐⭐ (Bom)</option>
                        <option value="2">⭐⭐ (Regular)</option>
                        <option value="1">⭐ (Ruim)</option>
                    </select>
                    <textarea name="comentario" rows="4" placeholder="Escreva seu comentário sobre o anime..." required></textarea>
                    <button type="submit" name="enviar_comentario">Enviar Comentário</button>
                </form>

                <hr style="border: 0; border-top: 1px solid #333; margin: 2rem 0;">

                <!-- Listagem de Comentários -->
                <div class="lista-comentarios">
                    <?php if (!empty($comentarios)): ?>
                        <?php foreach ($comentarios as $c): ?>
                            <div class="comentario-item">
                                <div class="comentario-header">
                                    <span class="comentario-autor"><?php echo htmlspecialchars($c['nome_usuario']); ?></span>
                                    <span>
                                        <?php echo str_repeat('⭐', $c['nota']); ?> | 
                                        <?php echo date('d/m/Y H:i', strtotime($c['data_criacao'])); ?>
                                    </span>
                                </div>
                                <p style="color: #ccc; margin: 5px 0 0 0;"><?php echo nl2br(htmlspecialchars($c['comentario'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #888;">Seja o primeiro a deixar um comentário e avaliação para este anime!</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </body>
    </html>