<?php
require 'conexao.php';

$mensagem = "";
$modoManual = isset($_GET['manual']);

// Guardar o anime na base de dados (PT-BR, nota, trailer e link do IMDb)
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
            $mensagem = "Anime cadastrado com sucesso em PT-BR!";
            $modoManual = false;
        } catch (PDOException $e) {
            // Fallback caso as novas colunas ainda não tenham sido criadas na tabela do banco
            try {
                $stmt = $pdo->prepare("INSERT INTO animes (titulo, sinopse, capa_url, ano_lancamento, nota_imdb) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $sinopse, $capa_url, $ano, $nota_imdb]);
                $mensagem = "Anime cadastrado (adicione as colunas trailer_url e imdb_link no banco para salvar todos os links)!";
                $modoManual = false;
            } catch (PDOException $ex) {
                $mensagem = "Erro ao cadastrar: " . $ex->getMessage();
            }
        }
    } else {
        $mensagem = "Por favor, preencha pelo menos o título e a URL da capa.";
    }
}

// Buscar todos os animes registados para mostrar na tabela
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

        <div id="msg-api" class="msg" style="display: none;"></div>

        <!-- Opção de alternar entre Pesquisa e Cadastro Manual -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <a href="painel.php" class="btn-hina" style="text-align: center; background: <?php echo !$modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Pesquisar Catálogo (PT-BR)</a>
            <a href="painel.php?manual=1" class="btn-hina" style="text-align: center; background: <?php echo $modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Cadastrar Manualmente</a>
        </div>

        <?php if (!$modoManual): ?>
            <!-- Pesquisa Inteligente -->
            <div>
                <label style="display:block; margin-bottom:8px;">Pesquisar Anime (Sinopse PT-BR + Nota + Trailer + IMDb):</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="nome_pesquisa" class="input-pesquisa" placeholder="Ex: Naruto, Bleach, One Piece, Attack on Titan..." style="margin-bottom: 0;" onkeypress="if(event.key === 'Enter'){ buscarAnimeAPI(); }">
                    <button type="button" onclick="buscarAnimeAPI()" style="padding: 0 20px; background: #ff0080; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Pesquisar</button>
                </div>
            </div>

            <!-- Antevisão dos Dados Encontrados -->
            <form method="POST" id="form-preview" style="display: none; margin-top: 15px; border-top: 1px solid #3f4254; padding-top: 15px;">
                <h3>Antevisão dos Dados Encontrados</h3>
                
                <div class="preview-box">
                    <img id="prev-img" src="" width="85" style="border-radius: 4px; object-fit: cover;">
                    <div>
                        <h4 id="prev-titulo" style="color: #fff; margin-bottom: 5px;"></h4>
                        <p style="color: #f5c518; font-weight: bold; font-size: 0.9rem; margin-bottom: 6px;">
                            <i class="fa-solid fa-star"></i> Nota: <span id="prev-nota"></span>
                        </p>
                        <p id="prev-sinopse" style="color: #bbb; font-size: 0.9rem;"></p>
                    </div>
                </div>

                <input type="hidden" name="titulo" id="inp-titulo">
                <input type="hidden" name="sinopse" id="inp-sinopse">
                <input type="hidden" name="capa_url" id="inp-capa">
                <input type="hidden" name="ano" id="inp-ano">
                <input type="hidden" name="nota_imdb" id="inp-nota-val">
                <input type="hidden" name="trailer_url" id="inp-trailer">
                <input type="hidden" name="imdb_link" id="inp-imdb">

                <button type="submit" name="guardar_anime" style="width: 100%; padding: 12px; background: #2e7d32; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Confirmar e Guardar na Base de Dados</button>
            </form>
        <?php endif; ?>

        <!-- Formulário de Cadastro Manual -->
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

                <label>Link do Trailer (YouTube Embed - Ex: https://www.youtube.com/embed/XXXXX):</label>
                <input type="text" name="trailer_url" placeholder="https://www.youtube.com/embed/...">

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

    <script>
    // Catálogo otimizado 100% em PT-BR, com notas reais, trailers oficiais e links do IMDb
    const catalogoPTBR = [
        {
            titulo: "Naruto",
            sinopse: "Naruto Uzumaki é um jovem ninja da Vila Oculta da Folha que carrega dentro de si a temível Raposa de Nove Caudas. Rejeitado pelos moradores, ele luta para conquistar o respeito de todos e realizar seu maior sonho: tornar-se Hokage.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx20-YJvLbgJQPCoI.jpg",
            ano: 2002,
            nota: 8.4,
            trailer: "https://www.youtube.com/embed/-G9NyZZbAhg",
            imdb: "https://www.imdb.com/title/tt0409591/"
        },
        {
            titulo: "Naruto Shippuden",
            sinopse: "Após dois anos e meio treinando com Jiraiya, Naruto retorna à Vila da Folha mais forte para resgatar seu amigo Sasuke Uchiha e proteger o mundo ninja da organização criminosa Akatsuki.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx1735-kGfVm0gkGTM3.png",
            ano: 2007,
            nota: 8.7,
            trailer: "https://www.youtube.com/embed/Agx_tjsXv94",
            imdb: "https://www.imdb.com/title/tt0988824/"
        },
        {
            titulo: "Bleach",
            sinopse: "Ichigo Kurosaki é um adolescente capaz de ver espíritos que acidentalmente absorve os poderes de uma Ceifadora de Almas. Agora, ele precisa proteger os humanos de espíritos malignos chamados Hollows.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx269-KxkqTIuQgJ6v.png",
            ano: 2004,
            nota: 8.2,
            trailer: "https://www.youtube.com/embed/e542Fkuyjqg",
            imdb: "https://www.imdb.com/title/tt0433486/"
        },
        {
            titulo: "One Piece",
            sinopse: "Monkey D. Luffy e sua tripulação de piratas navegam pelos mares perigosos da Grand Line em busca do maior tesouro já deixado pelo Rei dos Piratas, o lendário One Piece.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx21-YCDoj1EkAxFn.jpg",
            ano: 1999,
            nota: 9.0,
            trailer: "https://www.youtube.com/embed/MC-Py3B2Gr0",
            imdb: "https://www.imdb.com/title/tt0388629/"
        },
        {
            titulo: "Attack on Titan (Shingeki no Kyojin)",
            sinopse: "Há mais de um século, a humanidade vive cercada por muralhas gigantescas para se proteger dos Titãs, criaturas colossais que devoram seres humanos sem motivo aparente.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx16498-73IhOXpJZiMF.jpg",
            ano: 2013,
            nota: 9.1,
            trailer: "https://www.youtube.com/embed/M_OauHnAFcI",
            imdb: "https://www.imdb.com/title/tt2560140/"
        },
        {
            titulo: "Demon Slayer: Kimetsu no Yaiba",
            sinopse: "Após sua família ser massacrada por um demônio e sua irmã mais nova Nezuko ser transformada em um oni, Tanjiro Kamado inicia uma jornada para encontrar uma cura e vingar sua família.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx101922-PEn1CTc93blC.jpg",
            ano: 2019,
            nota: 8.6,
            trailer: "https://www.youtube.com/embed/VQ6UDu8s_nE",
            imdb: "https://www.imdb.com/title/tt9362722/"
        },
        {
            titulo: "Jujutsu Kaisen",
            sinopse: "Yuji Itadori engole um dedo amaldiçoado para salvar seus amigos e passa a dividir seu corpo com Ryomen Sukuna, o Rei das Maldições, ingressando em uma escola secreta de feiticeiros.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx113415-bbBWj4pEFseh.jpg",
            ano: 2020,
            nota: 8.5,
            trailer: "https://www.youtube.com/embed/pk8aQz_e11o",
            imdb: "https://www.imdb.com/title/tt12343534/"
        },
        {
            titulo: "Death Note",
            sinopse: "Light Yagami, um estudante brilhante, encontra um caderno sobrenatural capaz de matar qualquer pessoa cujo nome seja escrito nele, decidindo criar um novo mundo livre de criminosos.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx1535-lawCwhzhi96X.jpg",
            ano: 2006,
            nota: 8.9,
            trailer: "https://www.youtube.com/embed/NlJZ-Ygsh_c",
            imdb: "https://www.imdb.com/title/tt0877057/"
        },
        {
            titulo: "Dragon Ball Z",
            sinopse: "Goku e os Guerreiros Z defendem a Terra contra ameaças intergalácticas cada vez mais poderosas, incluindo os Saiyajins, o tirano Freeza, o androide Cell e o temível Majin Boo.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx813-B23132506064.jpg",
            ano: 1989,
            nota: 8.8,
            trailer: "https://www.youtube.com/embed/L130b9xKj_8",
            imdb: "https://www.imdb.com/title/tt0214341/"
        },
        {
            titulo: "Fullmetal Alchemist: Brotherhood",
            sinopse: "Os irmãos Edward e Alphonse Elric buscam a lendária Pedra Filosofal para restaurar seus corpos após uma tentativa fracassada de ressuscitar sua mãe usando alquimia proibida.",
            capa: "https://s4.anilist.co/file/anilistcdn/media/anime/cover/large/bx5114-KJTQz9AIm6Wk.jpg",
            ano: 2009,
            nota: 9.1,
            trailer: "https://www.youtube.com/embed/mhVzh3XQ70g",
            imdb: "https://www.imdb.com/title/tt1355644/"
        }
    ];

    function preencherPreview(titulo, sinopse, capa, ano, nota, trailer, imdb) {
        document.getElementById('prev-img').src = capa;
        document.getElementById('prev-titulo').textContent = titulo;
        document.getElementById('prev-nota').textContent = parseFloat(nota).toFixed(1);
        document.getElementById('prev-sinopse').textContent = sinopse.length > 140 ? sinopse.substring(0, 140) + '...' : sinopse;

        document.getElementById('inp-titulo').value = titulo;
        document.getElementById('inp-sinopse').value = sinopse;
        document.getElementById('inp-capa').value = capa;
        document.getElementById('inp-ano').value = ano;
        document.getElementById('inp-nota-val').value = nota;
        document.getElementById('inp-trailer').value = trailer;
        document.getElementById('inp-imdb').value = imdb;

        document.getElementById('msg-api').style.display = 'none';
        document.getElementById('form-preview').style.display = 'block';
    }

    async function buscarAnimeAPI() {
        const termo = document.getElementById('nome_pesquisa').value.trim();
        const msgBox = document.getElementById('msg-api');
        const formPreview = document.getElementById('form-preview');

        if (!termo) return;

        msgBox.style.display = 'block';
        msgBox.textContent = 'A pesquisar anime em PT-BR...';
        formPreview.style.display = 'none';

        const encontradoPT = catalogoPTBR.find(a => a.titulo.toLowerCase().includes(termo.toLowerCase()));
        if (encontradoPT) {
            preencherPreview(encontradoPT.titulo, encontradoPT.sinopse, encontradoPT.capa, encontradoPT.ano, encontradoPT.nota, encontradoPT.trailer, encontradoPT.imdb);
            return;
        }

        msgBox.textContent = 'Anime não encontrado no catálogo rápido. Podes usar o botão "Cadastrar Manualmente" acima para inserir todos os detalhes.';
    }
    </script>

</body>
</html>