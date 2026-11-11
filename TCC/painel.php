<?php
require 'conexao.php';

$animeEncontrado = null;
$mensagem = "";
$modoManual = false;

// 1. Processar a pesquisa na Jikan API com limite de tempo curto (timeout)
if (isset($_POST['pesquisar_api'])) {
    $termoPesquisa = urlencode(trim($_POST['nome_pesquisa']));
    $url = "https://api.jikan.moe/v4/anime?q={$termoPesquisa}&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HinaTCCApp/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos para não prender a página
    
    $resposta = curl_exec($ch);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($resposta) {
        $dados = json_decode($resposta, true);
        if (!empty($dados['data'])) {
            $animeEncontrado = $dados['data'][0];
        } else {
            $mensagem = "Nenhum anime encontrado na API. Podes cadastrar manualmente abaixo.";
            $modoManual = true;
        }
    } else {
        $mensagem = "Aviso: A rede bloqueou a API (" . $erroCurl . "). Podes cadastrar manualmente abaixo.";
        $modoManual = true;
    }
}

// Ativar modo manual se o utilizador clicar no botão correspondente
if (isset($_POST['ativar_manual']) || isset($_GET['manual'])) {
    $modoManual = true;
}

// 2. Guardar o anime na base de dados (seja vindo da API ou do formulário manual)
if (isset($_POST['guardar_anime'])) {
    $titulo = $_POST['titulo'] ?? '';
    $sinopse = $_POST['sinopse'] ?? '';
    $capa_url = $_POST['capa_url'] ?? '';
    $ano = $_POST['ano'] ?? date('Y');

    if (!empty($titulo) && !empty($capa_url)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO animes (titulo, sinopse, capa_url, ano_lancamento) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $sinopse, $capa_url, $ano]);
            $mensagem = "Anime cadastrado com sucesso!";
            $animeEncontrado = null;
            $modoManual = false;
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar: " . $e->getMessage();
        }
    } else {
        $mensagem = "Por favor, preencha pelo menos o título e a URL da capa.";
    }
}

// 3. Buscar todos os animes registados para mostrar na tabela
$stmtList = $pdo->query("SELECT * FROM animes ORDER BY id DESC");
$animes = $stmtList->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo - Hina</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container { max-width: 900px; margin: 30px auto; padding: 20px; background: #1a1b22; border-radius: 8px; border: 1px solid #23252b; color: #fff; }
        form input, form textarea { width: 100%; padding: 12px; margin-bottom: 15px; background: #23252b; border: 1px solid #3f4254; color: #fff; border-radius: 4px; box-sizing: border-box; outline: none; }
        form input:focus, form textarea:focus { border-color: #ff0080; }
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

        <!-- Opção de alternar entre Pesquisa API e Cadastro Manual -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <a href="painel.php" class="btn-hina" style="text-align: center; background: <?php echo !$modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Pesquisar via API</a>
            <a href="painel.php?manual=1" class="btn-hina" style="text-align: center; background: <?php echo $modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Cadastrar Manualmente</a>
        </div>

        <?php if (!$modoManual && !$animeEncontrado): ?>
            <!-- Formulário de Pesquisa na API -->
            <form method="POST">
                <label>Pesquisar Anime na API (MyAnimeList):</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="nome_pesquisa" required placeholder="Ex: Naruto, Bleach..." style="margin-bottom: 0;">
                    <button type="submit" name="pesquisar_api" style="padding: 0 20px; background: #ff0080; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Pesquisar</button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Antevisão dos Dados Encontrados via API -->
        <?php if ($animeEncontrado): ?>
            <form method="POST" style="margin-top: 15px; border-top: 1px solid #3f4254; padding-top: 15px;">
                <h3>Antevisão dos Dados Encontrados</h3>
                
                <div class="preview-box">
                    <img src="<?php echo htmlspecialchars($animeEncontrado['images']['jpg']['large_image_url']); ?>" width="80" style="border-radius: 4px; object-fit: cover;">
                    <div>
                        <h4 style="color: #fff; margin-bottom: 5px;"><?php echo htmlspecialchars($animeEncontrado['title']); ?></h4>
                        <p style="color: #bbb; font-size: 0.9rem;"><?php echo htmlspecialchars(mb_strimwidth($animeEncontrado['synopsis'] ?? 'Sem sinopse.', 0, 120, "...")); ?></p>
                    </div>
                </div>

                <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($animeEncontrado['title']); ?>">
                <input type="hidden" name="sinopse" value="<?php echo htmlspecialchars($animeEncontrado['synopsis'] ?? 'Sem sinopse.'); ?>">
                <input type="hidden" name="capa_url" value="<?php echo htmlspecialchars($animeEncontrado['images']['jpg']['large_image_url']); ?>">
                <input type="hidden" name="ano" value="<?php echo htmlspecialchars($animeEncontrado['year'] ?? date('Y')); ?>">

                <button type="submit" name="guardar_anime" style="width: 100%; padding: 12px; background: #2e7d32; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Confirmar e Guardar na Base de Dados</button>
            </form>
        <?php endif; ?>

        <!-- Formulário de Cadastro Manual (Caso a API falhe ou o utilizador queira) -->
        <?php if ($modoManual): ?>
            <form method="POST" style="margin-top: 15px; border-top: 1px solid #3f4254; padding-top: 15px;">
                <h3>Cadastro Manual de Anime</h3>

                <label>Título do Anime:</label>
                <input type="text" name="titulo" required placeholder="Ex: Naruto">

                <label>Sinopse:</label>
                <textarea name="sinopse" rows="4" placeholder="Escreve a sinopse..."></textarea>

                <label>URL da Imagem de Capa:</label>
                <input type="text" name="capa_url" required placeholder="https://exemplo.com/capa.jpg">

                <label>Ano de Lançamento:</label>
                <input type="number" name="ano" placeholder="2002">

                <button type="submit" name="guardar_anime" style="width: 100%; padding: 12px; background: #2e7d32; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Guardar na Base de Dados</button>
            </form>
        <?php endif; ?>

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
                            <td><?php echo htmlspecialchars($anime['ano_lancamento'] ?? '-'); ?></td>
                            <td>
                                <a href="editar.php?id=<?php echo $anime['id']; ?>" class="btn-acao btn-editar">Editar</a> 
                                <a href="excluir.php?id=<?php echo $anime['id']; ?>" class="btn-acao btn-excluir" onclick="return confirm('Tens a certeza que queres apagar o anime <?php echo htmlspecialchars($anime['titulo'], ENT_QUOTES); ?>?');">Excluir</a>
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