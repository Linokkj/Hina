<?php
session_start();
require 'conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

$usuarioLogado = !empty($_SESSION['logado']) || !empty($_SESSION['usuario_id']) || !empty($_SESSION['user_id']);
$usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 0);
$nomeSessao = trim((string)($_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? $_SESSION['usuario'] ?? ''));

$ehAdmin = (
    (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ||
    (!empty($_SESSION['admin']) && $_SESSION['admin'] === true) ||
    (isset($_SESSION['tipo']) && strtolower((string)$_SESSION['tipo']) === 'admin') ||
    (isset($_SESSION['cargo']) && strtolower((string)$_SESSION['cargo']) === 'admin') ||
    (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin') ||
    (isset($_SESSION['nivel']) && strtolower((string)$_SESSION['nivel']) === 'admin')
);

$ehFavorito = false;
if ($usuarioLogado && $usuarioId > 0) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS favoritos_usuario (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                anime_id INT NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_usuario_anime (usuario_id, anime_id)
            )
        ");

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorito'])) {
            $stmtCheckFav = $pdo->prepare("SELECT id FROM favoritos_usuario WHERE usuario_id = ? AND anime_id = ? LIMIT 1");
            $stmtCheckFav->execute([$usuarioId, $id]);

            if ($stmtCheckFav->fetch()) {
                $pdo->prepare("DELETE FROM favoritos_usuario WHERE usuario_id = ? AND anime_id = ?")->execute([$usuarioId, $id]);
            } else {
                $pdo->prepare("INSERT INTO favoritos_usuario (usuario_id, anime_id) VALUES (?, ?)")->execute([$usuarioId, $id]);
            }
            header("Location: anime.php?id=" . $id);
            exit;
        }

        $stmtIsFav = $pdo->prepare("SELECT id FROM favoritos_usuario WHERE usuario_id = ? AND anime_id = ? LIMIT 1");
        $stmtIsFav->execute([$usuarioId, $id]);
        $ehFavorito = (bool)$stmtIsFav->fetch();
    } catch (PDOException $e) {
        $ehFavorito = false;
    }
}

$mensagem_erro = '';
$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario'])) {
    if (!$usuarioLogado) {
        $mensagem_erro = "É necessário estar logado para publicar uma avaliação.";
    } else {
        $nomeInput = trim($_POST['nome_usuario'] ?? '');
        $nome_usuario = $nomeSessao !== '' ? $nomeSessao : $nomeInput;
        $nota = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_INT);
        $comentario = trim($_POST['comentario'] ?? '');

        if ($nome_usuario && $nota >= 1 && $nota <= 5 && $comentario) {
            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO comentarios_anime (anime_id, nome_usuario, nota, comentario)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtInsert->execute([$id, $nome_usuario, $nota, $comentario]);
                header("Location: anime.php?id=" . $id . "&comentado=1");
                exit;
            } catch (PDOException $e) {
                $mensagem_erro = "Erro ao enviar avaliação: " . $e->getMessage();
            }
        } else {
            $mensagem_erro = "Preencha todos os campos e selecione uma nota de 1 a 5.";
        }
    }
}

if (isset($_GET['comentado'])) {
    $mensagem_sucesso = "Avaliação publicada com sucesso!";
}

try {
    $stmtAnime = $pdo->prepare("SELECT * FROM animes WHERE id = ?");
    $stmtAnime->execute([$id]);
    $anime = $stmtAnime->fetch(PDO::FETCH_ASSOC);

    if (!$anime) {
        header('Location: index.php');
        exit;
    }

    $colunasEp = [];
    try {
        $stmtCols = $pdo->query("SHOW COLUMNS FROM episodios");
        foreach ($stmtCols->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $colunasEp[] = strtolower((string)$col['Field']);
        }
    } catch (PDOException $e) {
        $colunasEp = [];
    }

    $temColunaTemporada = in_array('temporada', $colunasEp, true);
    $temColunaSeason = in_array('season', $colunasEp, true);

    $orderEp = "numero_episodio ASC";
    if ($temColunaTemporada) {
        $orderEp = "temporada ASC, numero_episodio ASC";
    } elseif ($temColunaSeason) {
        $orderEp = "season ASC, numero_episodio ASC";
    }

    $stmtEp = $pdo->prepare("SELECT * FROM episodios WHERE anime_id = ? ORDER BY {$orderEp}");
    $stmtEp->execute([$id]);
    $rawEpisodios = $stmtEp->fetchAll(PDO::FETCH_ASSOC);

    $episodios = [];
    $temporadas = [];
    foreach ($rawEpisodios as $ep) {
        $numTemp = 1;
        if ($temColunaTemporada && !empty($ep['temporada'])) {
            $numTemp = max(1, (int)$ep['temporada']);
        } elseif ($temColunaSeason && !empty($ep['season'])) {
            $numTemp = max(1, (int)$ep['season']);
        } elseif (preg_match('/(?:temporada|season|t)\s*0*(\d+)/i', (string)($ep['titulo'] ?? ''), $m)) {
            $numTemp = max(1, (int)$m[1]);
        }
        $ep['_temporada'] = $numTemp;
        $episodios[] = $ep;
        $temporadas[$numTemp] = $numTemp;
    }

    if (empty($temporadas)) {
        $temporadas[1] = 1;
    }
    ksort($temporadas);

    $tempParam = filter_input(INPUT_GET, 'temp', FILTER_VALIDATE_INT);
    $temporadaAtual = ($tempParam && isset($temporadas[$tempParam])) ? (int)$tempParam : (int)reset($temporadas);

    $stmtCom = $pdo->prepare("SELECT * FROM comentarios_anime WHERE anime_id = ? ORDER BY data_criacao DESC");
    $stmtCom->execute([$id]);
    $comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar detalhes do anime: " . htmlspecialchars($e->getMessage()));
}

function formatarYoutubeEmbed($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return $url;
}

function renderizarPlayerVideo($url, $titulo) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }

    $caminhoSemQuery = strtolower(parse_url($url, PHP_URL_PATH) ?? $url);
    $ehArquivoVideo = (bool)preg_match('/\.(mp4|webm|ogg|ogv)$/i', $caminhoSemQuery);

    if ($ehArquivoVideo) {
        $ext = pathinfo($caminhoSemQuery, PATHINFO_EXTENSION);
        $mime = match ($ext) {
            'webm' => 'video/webm',
            'ogg', 'ogv' => 'video/ogg',
            default => 'video/mp4',
        };

        return '<div class="video-player-box">'
             . '<video controls preload="metadata" playsinline>'
             . '<source src="' . htmlspecialchars($url) . '" type="' . $mime . '">'
             . 'O seu navegador não suporta reprodução de vídeo HTML5.'
             . '</video>'
             . '</div>';
    }

    $embedUrl = formatarYoutubeEmbed($url);
    return '<div class="video-frame">'
         . '<iframe src="' . htmlspecialchars($embedUrl) . '" '
         . 'title="' . htmlspecialchars($titulo) . '" '
         . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
         . 'referrerpolicy="strict-origin-when-cross-origin" '
         . 'allowfullscreen></iframe>'
         . '</div>';
}

$trailerOriginal = trim((string)($anime['trailer_url'] ?? ''));
$trailerEmbed = formatarYoutubeEmbed($trailerOriginal);

$fundoHeroUrl = trim((string)($anime['banner_url'] ?? ''));
if ($fundoHeroUrl === '') {
    $fundoHeroUrl = trim((string)($anime['capa_url'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($anime['titulo']); ?> - Hina</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .anime-hero {
            position: relative;
            background-color: #090a0d;
            padding: 50px 4%;
            border-bottom: 1px solid #23252b;
            overflow: hidden;
        }
        <?php if ($fundoHeroUrl !== ''): ?>
        .anime-hero::before {
            content: "";
            position: absolute;
            inset: -20px;
            background: url('<?php echo htmlspecialchars($fundoHeroUrl); ?>') center center / cover no-repeat;
            filter: blur(18px) brightness(0.35) saturate(1.2);
            z-index: 0;
        }
        <?php endif; ?>
        .anime-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(9, 10, 13, 0.92) 0%, rgba(9, 10, 13, 0.70) 55%, rgba(9, 10, 13, 0.90) 100%);
            z-index: 0;
        }
        .anime-detail-wrapper {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            gap: 35px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .anime-poster {
            width: 260px;
            aspect-ratio: 2 / 3;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.85);
            border: 1px solid #2c2f38;
        }
        .anime-meta {
            flex: 1;
            min-width: 280px;
        }
        .anime-meta h1 {
            font-size: 34px;
            margin-bottom: 12px;
            color: #ffffff;
        }
        .meta-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
        }
        .meta-pill {
            background-color: rgba(45, 48, 59, 0.88);
            border: 1px solid #3f4352;
            color: #f3f4f6;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 13.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
        }
        .meta-pill-nota {
            color: #facc15;
        }
        .meta-pill-ref {
            color: #ff0080;
        }
        .anime-synopsis {
            color: #cccccc;
            font-size: 15px;
            line-height: 1.65;
            margin-bottom: 25px;
        }
        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-block {
            background-color: #141519;
            border: 1px solid #23252b;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .section-block h2 {
            font-size: 20px;
            border-left: 4px solid #ff0080;
            padding-left: 12px;
            margin-bottom: 20px;
        }
        .episodes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .episodes-header h2 {
            margin-bottom: 0;
        }
        .season-select {
            background-color: #23252b;
            color: #ffffff;
            border: 1px solid #3f4254;
            border-radius: 4px;
            padding: 8px 14px;
            font-family: inherit;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        .season-select:focus {
            outline: none;
            border-color: #ff0080;
        }
        .video-frame {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            background: #000;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 10px;
        }
        .video-frame iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .video-player-box {
            margin-top: 10px;
            background: #000;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #2c2f38;
        }
        .video-player-box video {
            width: 100%;
            max-height: 460px;
            display: block;
            background: #000;
        }
        .episode-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .episode-item {
            background-color: #1c1e24;
            border: 1px solid #2a2d36;
            border-radius: 6px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: border-color 0.2s;
        }
        .episode-item:hover {
            border-color: #ff0080;
        }
        .episode-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .episode-number {
            color: #ff0080;
            font-weight: 800;
            margin-right: 10px;
        }
        .comment-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
        }
        .comment-form input,
        .comment-form select,
        .comment-form textarea {
            background-color: #23252b;
            border: 1px solid #3f4254;
            border-radius: 4px;
            padding: 12px;
            color: #ffffff;
            font-family: inherit;
            font-size: 14px;
        }
        .comment-form input:focus,
        .comment-form select:focus,
        .comment-form textarea:focus {
            outline: none;
            border-color: #ff0080;
        }
        .login-required-box {
            background-color: #1c1e24;
            border: 1px dashed #ff0080;
            border-radius: 6px;
            padding: 18px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .comment-card {
            background-color: #1c1e24;
            border-left: 3px solid #ff0080;
            padding: 14px 16px;
            border-radius: 4px;
            margin-bottom: 12px;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
        }
        .comment-stars {
            color: #f5c518;
        }
        .alert-msg {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-success { background: rgba(46, 125, 50, 0.2); border: 1px solid #2e7d32; color: #81c784; }
        .alert-error { background: rgba(211, 47, 47, 0.2); border: 1px solid #d32f2f; color: #e57373; }
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
            <?php if ($usuarioLogado): ?>
                <a href="perfil.php">Meu Perfil</a>
                <?php if ($ehAdmin): ?>
                    <a href="painel.php">Painel Admin</a>
                <?php endif; ?>
                <a href="painel.php?sair=1">Sair</a>
            <?php else: ?>
                <a href="login.php">Entrar</a>
                <a href="cadastro.php">Criar conta</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="anime-hero">
        <div class="anime-detail-wrapper">
            <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="<?php echo htmlspecialchars($anime['titulo']); ?>" class="anime-poster">

            <div class="anime-meta">
                <h1><?php echo htmlspecialchars($anime['titulo']); ?></h1>

                <div class="meta-badges">
                    <?php if (!empty($anime['nota_imdb']) && $anime['nota_imdb'] > 0): ?>
                        <span class="meta-pill meta-pill-nota">
                            <i class="fa-solid fa-star"></i> <?php echo number_format((float)$anime['nota_imdb'], 1); ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($anime['ano_lancamento'])): ?>
                        <span class="meta-pill">
                            <i class="fa-regular fa-calendar"></i> <?php echo (int)$anime['ano_lancamento']; ?>
                        </span>
                    <?php endif; ?>

                    <span class="meta-pill">
                        <i class="fa-solid fa-film"></i> <?php echo count($episodios); ?> episódio(s)
                    </span>

                    <?php if (!empty($anime['imdb_link'])): ?>
                        <a href="<?php echo htmlspecialchars($anime['imdb_link']); ?>" target="_blank" rel="noopener" class="meta-pill meta-pill-ref">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Referência / AniList
                        </a>
                    <?php endif; ?>
                </div>

                <p class="anime-synopsis">
                    <?php echo nl2br(htmlspecialchars($anime['sinopse'] ?: 'Sem sinopse disponível para este título.')); ?>
                </p>

                <div class="action-row">
                    <a href="#episodios" class="btn-hina"><i class="fa-solid fa-play"></i> Ver Episódios</a>

                    <?php if ($usuarioLogado): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="toggle_favorito" class="btn-hina" style="background-color: <?php echo $ehFavorito ? '#ff0080' : '#23252b'; ?>; border: 1px solid #ff0080;">
                                <i class="<?php echo $ehFavorito ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                                <?php echo $ehFavorito ? 'Favoritado' : 'Favoritar'; ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($trailerEmbed !== ''): ?>
                        <a href="#trailer" class="btn-hina" style="background-color: #23252b; border: 1px solid #ff0080;">
                            <i class="fa-brands fa-youtube"></i> Ver Trailer
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($anime['link_download'])): ?>
                        <a href="<?php echo htmlspecialchars($anime['link_download']); ?>" target="_blank" rel="noopener" class="btn-download">
                            <i class="fa-solid fa-download"></i> Baixar Temporada
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <main class="container">

        <?php if ($trailerEmbed !== ''): ?>
            <section class="section-block" id="trailer">
                <h2><i class="fa-brands fa-youtube" style="color: #ff0080;"></i> Trailer Oficial</h2>
                <div class="video-frame">
                    <iframe src="<?php echo htmlspecialchars($trailerEmbed); ?>"
                            title="Trailer de <?php echo htmlspecialchars($anime['titulo']); ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </div>
                <?php if ($trailerOriginal !== ''): ?>
                    <p style="color: #9aa0a6; font-size: 12px; margin-top: 10px;">
                        Caso o vídeo não abra aqui por restrição do YouTube,
                        <a href="<?php echo htmlspecialchars($trailerOriginal); ?>" target="_blank" rel="noopener" style="color: #ff0080;">assista diretamente no YouTube</a>.
                    </p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="section-block" id="episodios">
            <div class="episodes-header">
                <h2>Episódios Disponíveis</h2>

                <select id="seletorTemporada" class="season-select" aria-label="Selecionar temporada">
                    <?php foreach ($temporadas as $numTemp): ?>
                        <option value="<?php echo $numTemp; ?>" <?php echo $numTemp === $temporadaAtual ? 'selected' : ''; ?>>
                            Temporada <?php echo $numTemp; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($episodios)): ?>
                <div class="episode-list" id="listaEpisodios">
                    <?php foreach ($episodios as $ep): ?>
                        <?php $epTemp = (int)$ep['_temporada']; ?>
                        <div class="episode-item" data-temporada="<?php echo $epTemp; ?>" style="<?php echo $epTemp === $temporadaAtual ? '' : 'display: none;'; ?>">
                            <div class="episode-top">
                                <div>
                                    <span class="episode-number">EP <?php echo str_pad((int)$ep['numero_episodio'], 2, '0', STR_PAD_LEFT); ?></span>
                                    <strong><?php echo htmlspecialchars($ep['titulo']); ?></strong>
                                </div>

                                <?php if (!empty($ep['link_download'])): ?>
                                    <a href="<?php echo htmlspecialchars($ep['link_download']); ?>" target="_blank" rel="noopener" class="btn-download">
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php
                            $playerEp = renderizarPlayerVideo($ep['video_url'] ?? '', $ep['titulo']);
                            if ($playerEp !== '') {
                                echo $playerEp;
                            } else {
                                echo '<p style="color: #9aa0a6; font-size: 13px;">Nenhum link ou ficheiro de vídeo associado a este episódio.</p>';
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p id="semEpisodiosTemporada" style="color: #888; display: none;">Nenhum episódio registado para esta temporada.</p>
            <?php else: ?>
                <p style="color: #888;">Ainda não existem episódios registados na base de dados para este anime.</p>
            <?php endif; ?>
        </section>

        <section class="section-block">
            <h2>Avaliações da Comunidade (<?php echo count($comentarios); ?>)</h2>

            <?php if ($mensagem_sucesso): ?>
                <div class="alert-msg alert-success"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
            <?php endif; ?>

            <?php if ($mensagem_erro): ?>
                <div class="alert-msg alert-error"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>

            <?php if ($usuarioLogado): ?>
                <form method="POST" class="comment-form">
                    <input type="text" name="nome_usuario" placeholder="O seu nome ou alcunha" value="<?php echo htmlspecialchars($nomeSessao); ?>" <?php echo $nomeSessao !== '' ? 'readonly' : 'required'; ?> maxlength="100">
                    <select name="nota" required>
                        <option value="5">★★★★★ (5 - Obra-prima)</option>
                        <option value="4">★★★★☆ (4 - Muito bom)</option>
                        <option value="3">★★★☆☆ (3 - Bom)</option>
                        <option value="2">★★☆☆☆ (2 - Regular)</option>
                        <option value="1">★☆☆☆☆ (1 - Fraco)</option>
                    </select>
                    <textarea name="comentario" rows="3" placeholder="Escreva o que achou deste anime..." required></textarea>
                    <button type="submit" name="enviar_comentario" class="btn-hina" style="align-self: flex-start;">Publicar Avaliação</button>
                </form>
            <?php else: ?>
                <div class="login-required-box">
                    <p style="color: #cccccc; font-size: 14px; margin: 0;">
                        <i class="fa-solid fa-lock" style="color: #ff0080;"></i>
                        Entre na sua conta para avaliar e deixar um comentário neste anime.
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="login.php" class="btn-hina">Entrar</a>
                        <a href="cadastro.php" class="meta-pill" style="padding: 10px 14px;">Criar conta</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($comentarios)): ?>
                <?php foreach ($comentarios as $com): ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <strong><?php echo htmlspecialchars($com['nome_usuario']); ?></strong>
                            <span class="comment-stars">
                                <?php echo str_repeat('★', (int)$com['nota']) . str_repeat('☆', 5 - (int)$com['nota']); ?>
                            </span>
                        </div>
                        <p style="color: #ddd; font-size: 14px; margin-bottom: 6px;">
                            <?php echo nl2br(htmlspecialchars($com['comentario'])); ?>
                        </p>
                        <small style="color: #777; font-size: 12px;">
                            <?php echo date('d/m/Y H:i', strtotime($com['data_criacao'])); ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #888;">Seja o primeiro a comentar e avaliar este anime!</p>
            <?php endif; ?>
        </section>

    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Hina - Trabalho de Conclusão de Curso.</p>
    </footer>

    <script>
        (function () {
            const seletor = document.getElementById('seletorTemporada');
            const itens = Array.from(document.querySelectorAll('#listaEpisodios .episode-item'));
            const msgVazio = document.getElementById('semEpisodiosTemporada');

            if (!seletor || itens.length === 0) return;

            function filtrarTemporada(tempValor) {
                const temp = parseInt(tempValor, 10) || 1;
                let visiveis = 0;

                itens.forEach((item) => {
                    const itemTemp = parseInt(item.getAttribute('data-temporada') || '1', 10);
                    const mostrar = itemTemp === temp;
                    item.style.display = mostrar ? '' : 'none';
                    if (mostrar) visiveis++;
                });

                if (msgVazio) {
                    msgVazio.style.display = visiveis === 0 ? 'block' : 'none';
                }
            }

            seletor.addEventListener('change', () => {
                filtrarTemporada(seletor.value);
            });

            filtrarTemporada(seletor.value);
        })();
    </script>

</body>
</html>