<?php
require_once 'conexao.php';

// Garante que a coluna trailer_url exista na tabela animes
try {
    $checkTrailer = $pdo->query("SHOW COLUMNS FROM animes LIKE 'trailer_url'");
    if ($checkTrailer->rowCount() === 0) {
        $pdo->exec("ALTER TABLE animes ADD COLUMN trailer_url VARCHAR(500) NULL AFTER nota_imdb");
    }
} catch (PDOException $e) {
    // Segue sem interromper caso já exista ou não tenha permissão de ALTER
}

// Endpoint JSON interno para cadastro automático via fetch()
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_auto_salvar'])) {
    header('Content-Type: application/json; charset=utf-8');

    $titulo = trim($_POST['titulo'] ?? '');
    $sinopse = trim($_POST['sinopse'] ?? '');
    $capa_url = trim($_POST['capa_url'] ?? '');
    $nota_imdb = filter_var($_POST['nota_imdb'] ?? 0.0, FILTER_VALIDATE_FLOAT);
    $nota_imdb = ($nota_imdb !== false) ? $nota_imdb : 0.0;
    $trailer_url = trim($_POST['trailer_url'] ?? '');
    $link_download = trim($_POST['link_download'] ?? '');
    $episodios_json = $_POST['episodios_api'] ?? '[]';

    if ($titulo === '' || $capa_url === '') {
        echo json_encode(['ok' => false, 'msg' => 'Título ou capa inválidos.']);
        exit;
    }

    try {
        // Evita duplicar exatamente o mesmo título
        $stmtCheck = $pdo->prepare("SELECT id FROM animes WHERE LOWER(titulo) = LOWER(?) LIMIT 1");
        $stmtCheck->execute([$titulo]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            echo json_encode([
                'ok' => true,
                'duplicado' => true,
                'anime_id' => (int)$existente['id'],
                'msg' => "O anime \"{$titulo}\" já estava cadastrado (ID #" . (int)$existente['id'] . ")."
            ]);
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO animes (titulo, sinopse, capa_url, nota_imdb, trailer_url, link_download)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $titulo,
            $sinopse !== '' ? $sinopse : 'Sem sinopse.',
            $capa_url,
            $nota_imdb,
            $trailer_url !== '' ? $trailer_url : null,
            $link_download !== '' ? $link_download : null,
        ]);

        $novoAnimeId = (int)$pdo->lastInsertId();
        $episodiosDecodificados = json_decode($episodios_json, true);
        $totalEps = 0;

        if (is_array($episodiosDecodificados) && !empty($episodiosDecodificados)) {
            $stmtEp = $pdo->prepare("
                INSERT INTO episodios (anime_id, numero_episodio, titulo)
                VALUES (?, ?, ?)
            ");
            foreach ($episodiosDecodificados as $ep) {
                $numEp = isset($ep['mal_id']) ? (int)$ep['mal_id'] : (int)($ep['numero'] ?? 0);
                if ($numEp <= 0) {
                    continue;
                }
                $tituloEp = trim((string)($ep['title'] ?? ('Episódio ' . $numEp)));
                $stmtEp->execute([$novoAnimeId, $numEp, $tituloEp]);
                $totalEps++;
            }
        }

        $pdo->commit();

        echo json_encode([
            'ok' => true,
            'duplicado' => false,
            'anime_id' => $novoAnimeId,
            'episodios' => $totalEps,
            'msg' => "Cadastrado automaticamente: \"{$titulo}\" (ID #{$novoAnimeId})" . ($totalEps > 0 ? " com {$totalEps} episódio(s)." : ".")
        ]);
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Hina - Cadastro Automático de Anime</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <nav class="nav-links">
            <a href="index.php">Início</a>
            <a href="painel.php">Painel Admin</a>
            <a href="adicionar_anime.php">Cadastro Automático</a>
        </nav>
    </header>

    <div class="container" style="max-width: 680px; margin-top: 2rem;">
        <div style="background: #1e1e1e; padding: 2rem; border-radius: 8px;">
            <h2>Cadastro Automático via API</h2>
            <p style="color: #aaa; font-size: 14px; margin-bottom: 1.2rem;">
                Digite o nome do anime
            </p>

            <div style="margin-bottom: 1rem;">
                <label style="color: #ff0080; display: block; margin-bottom: 0.5rem;">Nome(s) do Anime:</label>
                <textarea id="nomes_pesquisa" rows="3"style="width: 100%; padding: 0.7rem; background: #121212; border: 1px solid #333; color: #fff; border-radius: 4px;"></textarea>
            </div>

            <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center; margin-bottom: 1.2rem;">
                <button type="button" onclick="executarCadastroAutomatico()" class="btn-hina">
                    Cadastrar Automaticamente Agora
                </button>

                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #ccc; cursor: pointer;">
                    <input type="checkbox" id="auto_ao_digitar" checked>
                    Salvar automaticamente ao parar de digitar (1 anime)
                </label>

                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #ccc; cursor: pointer;">
                    <input type="checkbox" id="importar_episodios_check" checked>
                    Importar episódios da API
                </label>
            </div>

            <div id="log-auto" style="background: #141519; border: 1px solid #2a2d36; border-radius: 6px; padding: 14px; min-height: 70px; font-size: 14px; color: #ddd;">
                Aguardando nome do anime para cadastrar automaticamente...
            </div>
        </div>
    </div>

    <script>
    let timerDigitacao = null;
    let ultimoSalvoAuto = '';

    document.getElementById('nomes_pesquisa').addEventListener('input', function () {
        if (!document.getElementById('auto_ao_digitar').checked) return;
        clearTimeout(timerDigitacao);

        const bruto = this.value.trim();
        // Só dispara automaticamente ao digitar se for um único termo (sem vírgula ou quebra de linha) com >= 3 letras
        if (bruto.length < 3 || bruto.includes(',') || bruto.includes('\n')) return;

        timerDigitacao = setTimeout(() => {
            if (bruto.toLowerCase() !== ultimoSalvoAuto.toLowerCase()) {
                ultimoSalvoAuto = bruto;
                cadastrarUmAnime(bruto, true);
            }
        }, 800);
    });

    async function executarCadastroAutomatico() {
        const bruto = document.getElementById('nomes_pesquisa').value.trim();
        if (!bruto) return;

        const listaNomes = bruto
            .split(/[\n,]+/)
            .map(item => item.trim())
            .filter(item => item.length > 0);

        const logBox = document.getElementById('log-auto');
        logBox.innerHTML = '';

        for (let i = 0; i < listaNomes.length; i++) {
            await cadastrarUmAnime(listaNomes[i], false);
            // Pequena pausa para respeitar o limite de requisições da API Jikan
            if (i < listaNomes.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 600));
            }
        }
    }

    async function cadastrarUmAnime(termo, substituirLog) {
        const logBox = document.getElementById('log-auto');
        const importarEps = document.getElementById('importar_episodios_check').checked;

        const linhaStatus = document.createElement('div');
        linhaStatus.style.marginBottom = '8px';
        linhaStatus.textContent = `Buscando "${termo}" na API...`;

        if (substituirLog) {
            logBox.innerHTML = '';
        }
        logBox.appendChild(linhaStatus);

        try {
            const resp = await fetch(`https://api.jikan.moe/v4/anime?q=${encodeURIComponent(termo)}&limit=1`);
            const dados = await resp.json();

            if (!dados.data || dados.data.length === 0) {
                linhaStatus.style.color = '#e57373';
                linhaStatus.textContent = `Nenhum anime encontrado para "${termo}".`;
                return;
            }

            const anime = dados.data[0];
            const capa = anime.images?.jpg?.large_image_url || anime.images?.jpg?.image_url || '';
            const nota = anime.score ?? 0.0;
            const sinopse = anime.synopsis ?? 'Sem sinopse.';
            const trailer = anime.trailer?.url || anime.trailer?.embed_url || '';

            let listaEps = [];
            if (importarEps && anime.mal_id) {
                try {
                    const respEps = await fetch(`https://api.jikan.moe/v4/anime/${anime.mal_id}/episodes?page=1`);
                    const dadosEps = await respEps.json();
                    if (Array.isArray(dadosEps.data)) {
                        listaEps = dadosEps.data.map((ep, idx) => ({
                            numero: ep.mal_id || (idx + 1),
                            title: ep.title || `Episódio ${ep.mal_id || (idx + 1)}`
                        }));
                    }
                } catch (e) {
                    // Ignora falha de episódios e prossegue com o cadastro do anime
                }
            }

            const formData = new URLSearchParams();
            formData.append('acao_auto_salvar', '1');
            formData.append('titulo', anime.title || termo);
            formData.append('sinopse', sinopse);
            formData.append('capa_url', capa);
            formData.append('nota_imdb', nota);
            formData.append('trailer_url', trailer);
            formData.append('link_download', '');
            formData.append('episodios_api', JSON.stringify(listaEps));

            const respSalvar = await fetch('adicionar_anime.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });

            const resultado = await respSalvar.json();

            if (resultado.ok) {
                linhaStatus.style.color = '#81c784';
                linhaStatus.innerHTML = `${resultado.msg} <a href="anime.php?id=${resultado.anime_id}" style="color:#ff0080; margin-left:8px;">Abrir página</a>`;
            } else {
                linhaStatus.style.color = '#e57373';
                linhaStatus.textContent = resultado.msg || 'Erro ao cadastrar.';
            }
        } catch (err) {
            linhaStatus.style.color = '#e57373';
            linhaStatus.textContent = `Erro ao processar "${termo}": ${err.message}`;
        }
    }
    </script>
</body>
</html>