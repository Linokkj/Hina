<?php
require 'conexao.php';

$mensagem = "";
$modoManual = isset($_GET['manual']);
$dadosApi = null;

// Processar a busca na AniList API (GraphQL) via PHP
if (isset($_GET['pesquisar_api'])) {
    $termoBusca = trim($_GET['termo'] ?? '');
    if (!empty($termoBusca)) {
        $query = '
        query ($search: String) {
          Media (search: $search, type: ANIME) {
            id
            title {
              romaji
              english
            }
            description(asHtml: false)
            coverImage {
              large
            }
            seasonYear
            averageScore
            trailer {
              id
              site
            }
          }
        }
        ';

        $variaveis = [
            'search' => $termoBusca
        ];

        $payload = json_encode([
            'query' => $query,
            'variables' => $variaveis
        ]);

        $ch = curl_init('https://graphql.anilist.co');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $respostaJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($respostaJson && $httpCode == 200) {
            $dadosObj = json_decode($respostaJson, true);
            if (!empty($dadosObj['data']['Media'])) {
                $anime = $dadosObj['data']['Media'];
                
                $titulo = $anime['title']['english'] ?? $anime['title']['romaji'] ?? $termoBusca;
                $sinopseIngles = $anime['description'] ? strip_tags($anime['description']) : 'Sem sinopse disponível.';
                $capaUrl = $anime['coverImage']['large'] ?? '';
                $ano = $anime['seasonYear'] ?? date('Y');
                $notaImdb = $anime['averageScore'] ? round($anime['averageScore'] / 10, 1) : 0.0;
                
                $trailerUrl = '';
                if (!empty($anime['trailer']) && $anime['trailer']['site'] === 'youtube') {
                    $trailerUrl = "https://www.youtube.com/watch?v=" . $anime['trailer']['id'];
                }

                $imdbLink = "https://anilist.co/anime/" . $anime['id'];

                // Traduz a sinopse automaticamente para português
                $urlTraducao = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=pt&dt=t&q=" . urlencode($sinopseIngles);
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, $urlTraducao);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                $traducaoJson = curl_exec($ch2);

                $sinopsePt = $sinopseIngles;
                if ($traducaoJson) {
                    $traducaoArr = json_decode($traducaoJson, true);
                    if (isset($traducaoArr[0])) {
                        $sinopsePt = "";
                        foreach ($traducaoArr[0] as $parte) {
                            $sinopsePt .= $parte[0];
                        }
                    }
                }

                $dadosApi = [
                    'titulo' => $titulo,
                    'sinopse' => $sinopsePt,
                    'capa_url' => $capaUrl,
                    'ano' => $ano,
                    'nota_imdb' => $notaImdb,
                    'trailer_url' => $trailerUrl,
                    'imdb_link' => $imdbLink
                ];
            } else {
                $mensagem = "Nenhum anime encontrado com esse nome.";
            }
        } else {
            $mensagem = "Erro ao conectar com a API da AniList.";
        }
    }
}

// Salvar no banco de dados
if (isset($_POST['guardar_anime'])) {
    $titulo = $_POST['titulo'] ?? '';
    $sinopse = $_POST['sinopse'] ?? '';
    $capa_url = $_POST['capa_url'] ?? '';
    $ano = !empty($_POST['ano']) ? $_POST['ano'] : date('Y');
    $nota_imdb = !empty($_POST['nota_imdb']) ? (float)$_POST['nota_imdb'] : 0.0;
    $trailer_url = $_POST['trailer_url'] ?? '';
    $imdb_link = $_POST['imdb_link'] ?? '';

    if (!empty($titulo) && !empty($capa_url)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO animes (titulo, sinopse, capa_url, ano_lancamento, nota_imdb, trailer_url, imdb_link) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $sinopse, $capa_url, $ano, $nota_imdb, $trailer_url, $imdb_link]);
            $mensagem = "Anime cadastrado com sucesso!";
            $modoManual = false;
            $dadosApi = null;
        } catch (PDOException $e) {
            try {
                $stmt = $pdo->prepare("INSERT INTO animes (titulo, sinopse, capa_url, ano_lancamento, nota_imdb) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $sinopse, $capa_url, $ano, $nota_imdb]);
                $mensagem = "Anime cadastrado com sucesso!";
                $modoManual = false;
                $dadosApi = null;
            } catch (PDOException $ex) {
                $mensagem = "Erro ao cadastrar: " . $ex->getMessage();
            }
        }
    } else {
        $mensagem = "Por favor, preencha pelo menos o título e a URL da capa.";
    }
}

$stmtList = $pdo->query("SELECT * FROM animes ORDER BY id DESC");
$animes = $stmtList->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo - Hina</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container { max-width: 900px; margin: 30px auto; padding: 20px; background: #1a1b22; border-radius: 8px; border: 1px solid #23252b; color: #fff; }
        form input, form textarea, .input-pesquisa { width: 100%; padding: 12px; margin-bottom: 15px; background: #23252b; border: 1px solid #3f4254; color: #fff; border-radius: 4px; box-sizing: border-box; outline: none; }
        form input:focus, form textarea:focus, .input-pesquisa:focus { border-color: #ff0080; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #2a2c37; }
        th { background-color: #23252b; color: #ff0080; }
        .msg { padding: 10px; background: rgba(255,0,128,0.1); border: 1px solid #ff0080; color: #ff0080; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .btn-acao { text-decoration: none; font-weight: bold; padding: 5px 10px; border-radius: 4px; display: inline-block; font-size: 0.85rem; }
        .btn-editar { color: #ff0080; background: rgba(255, 0, 128, 0.1); }
        .btn-excluir { color: #ff4757; background: rgba(255, 71, 87, 0.1); }
        .btn-editar:hover { background: rgba(255, 0, 128, 0.2); }
        .btn-excluir:hover { background: rgba(255, 71, 87, 0.2); }
        .preview-box { display: flex; gap: 15px; align-items: center; background: #23252b; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo" style="text-decoration:none; display:flex; align-items:center; gap:10px;">
            <img src="https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg" class="logo-img" alt="Logo Hina" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
            <h1 style="color:#fff; font-size:1.2rem;">Hina - Painel</h1>
        </a>
        <nav class="nav-links">
            <a href="index.php">Ver Site</a>
            <a href="painel.php">Gerir Animes</a>
        </nav>
    </header>

    <div class="admin-container">
        <h2>Painel de Gestão de Animes</h2>

        <?php if (!empty($mensagem)): ?>
            <div class="msg"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <a href="painel.php" class="btn-hina" style="text-align: center; background: <?php echo !$modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Pesquisar via AniList API</a>
            <a href="painel.php?manual=1" class="btn-hina" style="text-align: center; background: <?php echo $modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Cadastrar Manualmente</a>
        </div>

        <?php if (!$modoManual): ?>
            <form method="GET" action="painel.php">
                <label style="display:block; margin-bottom:8px;">Pesquisar Anime Global na AniList (Ex: Solo Leveling, Baki, Naruto):</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="termo" class="input-pesquisa" placeholder="Digite o nome do anime..." value="<?php echo htmlspecialchars($_GET['termo'] ?? ''); ?>" style="margin-bottom: 0;" required>
                    <input type="hidden" name="pesquisar_api" value="1">
                    <button type="submit" style="padding: 0 20px; background: #ff0080; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Pesquisar</button>
                </div>
            </form>

            <?php if ($dadosApi): ?>
                <form method="POST" style="margin-top: 15px; border-top: 1px solid #3f4254; padding-top: 15px;">
                    <h3>Antevisão dos Dados Encontrados</h3>
                    
                    <div class="preview-box">
                        <img src="<?php echo htmlspecialchars($dadosApi['capa_url']); ?>" width="85" style="border-radius: 4px; object-fit: cover;">
                        <div>
                            <h4 style="color: #fff; margin-bottom: 5px;"><?php echo htmlspecialchars($dadosApi['titulo']); ?></h4>
                            <p style="color: #f5c518; font-weight: bold; font-size: 0.9rem; margin-bottom: 6px;">
                                <i class="fa-solid fa-star"></i> Nota: <?php echo number_format($dadosApi['nota_imdb'], 1); ?>
                            </p>
                            <p style="color: #bbb; font-size: 0.9rem;"><?php echo htmlspecialchars(strlen($dadosApi['sinopse']) > 140 ? substr($dadosApi['sinopse'], 0, 140) . '...' : $dadosApi['sinopse']); ?></p>
                        </div>
                    </div>

                    <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($dadosApi['titulo']); ?>">
                    <input type="hidden" name="sinopse" value="<?php echo htmlspecialchars($dadosApi['sinopse']); ?>">
                    <input type="hidden" name="capa_url" value="<?php echo htmlspecialchars($dadosApi['capa_url']); ?>">
                    <input type="hidden" name="ano" value="<?php echo htmlspecialchars($dadosApi['ano']); ?>">
                    <input type="hidden" name="nota_imdb" value="<?php echo htmlspecialchars($dadosApi['nota_imdb']); ?>">
                    <input type="hidden" name="trailer_url" value="<?php echo htmlspecialchars($dadosApi['trailer_url']); ?>">
                    <input type="hidden" name="imdb_link" value="<?php echo htmlspecialchars($dadosApi['imdb_link']); ?>">

                    <button type="submit" name="guardar_anime" style="width: 100%; padding: 12px; background: #2e7d32; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Confirmar e Guardar na Base de Dados</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($modoManual): ?>
            <form method="POST" style="margin-top: 15px; border-top: 1px solid #3f4254; padding-top: 15px;">
                <h3>Cadastro Manual de Anime</h3>

                <label>Título do Anime:</label>
                <input type="text" name="titulo" required placeholder="Ex: Naruto">

                <label>Sinopse (PT-BR):</label>
                <textarea name="sinopse" rows="4" placeholder="Escreva a sinopse em português..."></textarea>

                <label>URL da Imagem de Capa:</label>
                <input type="text" name="capa_url" required placeholder="https://exemplo.com/capa.jpg">

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Ano de Lançamento:</label>
                        <input type="number" name="ano" placeholder="2002">
                    </div>
                    <div style="flex: 1;">
                        <label>Nota IMDb (0 a 10):</label>
                        <input type="number" step="0.1" min="0" max="10" name="nota_imdb" placeholder="8.4">
                    </div>
                </div>

                <label>Link do Trailer no YouTube:</label>
                <input type="url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=...">

                <label>Link oficial do IMDb (Opcional):</label>
                <input type="url" name="imdb_link" placeholder="https://www.imdb.com/title/tt...">

                <button type="submit" name="guardar_anime" style="width: 100%; padding: 12px; background: #2e7d32; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Guardar na Base de Dados</button>
            </form>
        <?php endif; ?>

        <h2 style="margin-top: 40px;">Animes Registados no Sistema</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Nota</th>
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
                            <td style="color: #f5c518; font-weight: bold;">⭐ <?php echo number_format((float)($anime['nota_imdb'] ?? 0), 1); ?></td>
                            <td><?php echo htmlspecialchars($anime['ano_lancamento'] ?? '-'); ?></td>
                            <td>
                                <a href="editar.php?id=<?php echo $anime['id']; ?>" class="btn-acao btn-editar">Editar</a> 
                                <a href="excluir.php?id=<?php echo $anime['id']; ?>" class="btn-acao btn-excluir" onclick="return confirm('Tens a certeza que queres apagar o anime <?php echo htmlspecialchars($anime['titulo'], ENT_QUOTES); ?>?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: #777;">Nenhum anime registado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>