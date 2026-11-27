<?php
session_start();
require 'conexao.php';

$fotoPadrao = 'https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg';

if (isset($_GET['sair'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

$estaLogado = !empty($_SESSION['usuario_id']) || !empty($_SESSION['user_id']) || !empty($_SESSION['logado']);

$ehAdmin = (
    (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ||
    (!empty($_SESSION['admin']) && $_SESSION['admin'] === true) ||
    (isset($_SESSION['tipo']) && strtolower((string)$_SESSION['tipo']) === 'admin') ||
    (isset($_SESSION['cargo']) && strtolower((string)$_SESSION['cargo']) === 'admin') ||
    (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin') ||
    (isset($_SESSION['nivel']) && strtolower((string)$_SESSION['nivel']) === 'admin')
);

if (!$estaLogado || !$ehAdmin) {
    header('Location: login.php?erro=acesso_negado');
    exit;
}

$mensagem = "";
$modoManual = isset($_GET['manual']);
$dadosApi = null;

function tamanhoTexto(string $texto): int {
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function recorteTexto(string $texto, int $inicio, int $comprimento): string {
    return function_exists('mb_substr')
        ? mb_substr($texto, $inicio, $comprimento, 'UTF-8')
        : substr($texto, $inicio, $comprimento);
}

function urlExternaValida(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) ? $url : '';
}

$colsAnimes = [];
$colsEp = [];

try {
    $colsAnimes = $pdo->query("SHOW COLUMNS FROM animes")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('trailer_url', $colsAnimes, true)) {
        $pdo->exec("ALTER TABLE animes ADD COLUMN trailer_url VARCHAR(500) NULL");
        $colsAnimes = $pdo->query("SHOW COLUMNS FROM animes")->fetchAll(PDO::FETCH_COLUMN);
    }
    if (!in_array('imdb_link', $colsAnimes, true)) {
        $pdo->exec("ALTER TABLE animes ADD COLUMN imdb_link VARCHAR(500) NULL");
        $colsAnimes = $pdo->query("SHOW COLUMNS FROM animes")->fetchAll(PDO::FETCH_COLUMN);
    }

    $colsEp = $pdo->query("SHOW COLUMNS FROM episodios")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('titulo', $colsEp, true)) {
        $pdo->exec("ALTER TABLE episodios ADD COLUMN titulo VARCHAR(255) NOT NULL DEFAULT ''");
        $colsEp = $pdo->query("SHOW COLUMNS FROM episodios")->fetchAll(PDO::FETCH_COLUMN);
    }
    if (!in_array('temporada', $colsEp, true)) {
        $pdo->exec("ALTER TABLE episodios ADD COLUMN temporada INT NOT NULL DEFAULT 1");
        $colsEp = $pdo->query("SHOW COLUMNS FROM episodios")->fetchAll(PDO::FETCH_COLUMN);
    }
    if (!in_array('link_download', $colsEp, true)) {
        $pdo->exec("ALTER TABLE episodios ADD COLUMN link_download VARCHAR(500) NULL");
        $colsEp = $pdo->query("SHOW COLUMNS FROM episodios")->fetchAll(PDO::FETCH_COLUMN);
    }
    if (!in_array('video_url', $colsEp, true)) {
        $pdo->exec("ALTER TABLE episodios ADD COLUMN video_url VARCHAR(500) NULL");
        $colsEp = $pdo->query("SHOW COLUMNS FROM episodios")->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $mensagem = "Aviso ao verificar estrutura da base de dados: " . $e->getMessage();
}

function salvarAnimeNoBanco(PDO $pdo, array $dados, string $capaFallback = ''): array {
    $titulo = trim((string)($dados['titulo'] ?? ''));
    $sinopse = trim((string)($dados['sinopse'] ?? ''));
    $capa_url = urlExternaValida((string)($dados['capa_url'] ?? ''));
    if ($capa_url === '' && $capaFallback !== '') {
        $capa_url = $capaFallback;
    }
    $ano = !empty($dados['ano']) ? (int)$dados['ano'] : (int)date('Y');
    $nota_imdb = (isset($dados['nota_imdb']) && $dados['nota_imdb'] !== '') ? (float)$dados['nota_imdb'] : 0.0;
    $trailer_url = urlExternaValida((string)($dados['trailer_url'] ?? ''));
    $imdb_link = urlExternaValida((string)($dados['imdb_link'] ?? ''));

    if ($titulo === '') {
        return ['ok' => false, 'msg' => 'Por favor, informe pelo menos o título do anime.'];
    }

    $stmtCheck = $pdo->prepare("SELECT id, titulo FROM animes WHERE LOWER(titulo) = LOWER(?) LIMIT 1");
    $stmtCheck->execute([$titulo]);
    $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($existente) {
        return [
            'ok' => true,
            'duplicado' => true,
            'id' => (int)$existente['id'],
            'msg' => 'O anime "' . $existente['titulo'] . '" já está registado (ID #' . $existente['id'] . ').'
        ];
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO animes (titulo, sinopse, capa_url, ano_lancamento, nota_imdb, trailer_url, imdb_link)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $titulo,
            $sinopse !== '' ? $sinopse : 'Sem sinopse disponível.',
            $capa_url,
            $ano,
            $nota_imdb,
            $trailer_url !== '' ? $trailer_url : null,
            $imdb_link !== '' ? $imdb_link : null,
        ]);

        $novoId = (int)$pdo->lastInsertId();
        return [
            'ok' => true,
            'duplicado' => false,
            'id' => $novoId,
            'msg' => 'Anime "' . $titulo . '" cadastrado com sucesso! (ID #' . $novoId . ')'
        ];
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO animes (titulo, sinopse, capa_url, ano_lancamento, nota_imdb)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $titulo,
                $sinopse !== '' ? $sinopse : 'Sem sinopse disponível.',
                $capa_url,
                $ano,
                $nota_imdb,
            ]);
            $novoId = (int)$pdo->lastInsertId();
            return [
                'ok' => true,
                'duplicado' => false,
                'id' => $novoId,
                'msg' => 'Anime "' . $titulo . '" cadastrado com sucesso! (ID #' . $novoId . ')'
            ];
        } catch (PDOException $ex) {
            return ['ok' => false, 'msg' => 'Erro ao cadastrar: ' . $ex->getMessage()];
        }
    }
}

function requisicaoCurl(string $url, ?array $postData = null, int $timeout = 10): string {
    if (!function_exists('curl_init')) {
        return '';
    }

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => $timeout,
    ];

    if ($postData !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($postData);
    }

    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);

    return is_string($resp) ? $resp : '';
}

function traduzirBlocoParaPt(string $bloco): string {
    $bloco = trim($bloco);
    if ($bloco === '') {
        return '';
    }

    $respGoogle = requisicaoCurl(
        'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=pt&dt=t',
        ['q' => $bloco],
        10
    );

    if ($respGoogle !== '') {
        $json = json_decode($respGoogle, true);
        if (isset($json[0]) && is_array($json[0])) {
            $out = '';
            foreach ($json[0] as $item) {
                if (isset($item[0]) && is_string($item[0])) {
                    $out .= $item[0];
                }
            }
            $out = trim($out);
            if ($out !== '' && strcasecmp($out, $bloco) !== 0) {
                return $out;
            }
        }
    }

    $urlMemory = "https://api.mymemory.translated.net/get?langpair=en|pt&q=" . urlencode($bloco);
    $respMemory = requisicaoCurl($urlMemory, null, 10);
    if ($respMemory !== '') {
        $json2 = json_decode($respMemory, true);
        $trad2 = trim((string)($json2['responseData']['translatedText'] ?? ''));
        if ($trad2 !== '' && stripos($trad2, 'MYMEMORY WARNING') === false && strcasecmp($trad2, $bloco) !== 0) {
            return html_entity_decode($trad2, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    return $bloco;
}

function limparETraduzirSinopse(string $descricaoRaw): string {
    $texto = html_entity_decode(strip_tags($descricaoRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $texto = preg_replace('/\(\s*(?:Source|Product|Products|Studio|Studios|Producer|Producers|Note|Written by)\s*:[^)]*\)/iu', '', $texto);
    $texto = preg_replace('/\b(?:Source|Product|Products|Studio|Studios|Producer|Producers)\s*:\s*[^\r\n]+/iu', '', $texto);
    $texto = trim(preg_replace('/\n{3,}/', "\n\n", $texto));

    if ($texto === '') {
        return 'Sem sinopse disponível.';
    }

    $sentencas = preg_split('/(?<=[.!?])\s+|\n+/', $texto) ?: [$texto];
    $blocos = [];
    $atual = '';

    foreach ($sentencas as $s) {
        $s = trim($s);
        if ($s === '') {
            continue;
        }
        $candidato = $atual === '' ? $s : ($atual . ' ' . $s);
        if (tamanhoTexto($candidato) > 450 && $atual !== '') {
            $blocos[] = $atual;
            $atual = $s;
        } else {
            $atual = $candidato;
        }
    }
    if ($atual !== '') {
        $blocos[] = $atual;
    }

    $traduzidas = [];
    foreach ($blocos as $bloco) {
        $traduzidas[] = traduzirBlocoParaPt($bloco);
    }

    $resultado = trim(implode(' ', $traduzidas));
    return $resultado !== '' ? $resultado : $texto;
}

$autoSalvar = isset($_GET['auto_salvar']) && $_GET['auto_salvar'] === '1';

if (isset($_GET['pesquisar_api'])) {
    $termoBusca = trim((string)($_GET['termo'] ?? ''));
    if ($termoBusca !== '') {
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
            studios {
              nodes {
                name
                isAnimationStudio
              }
            }
          }
        }
        ';

        $payload = json_encode([
            'query' => $query,
            'variables' => ['search' => $termoBusca]
        ]);

        $ch = curl_init('https://graphql.anilist.co');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_USERAGENT      => 'HinaApp/1.0',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $respostaJson = curl_exec($ch);
        $curlErrno   = curl_errno($ch);
        $curlError   = curl_error($ch);
        $httpCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($curlErrno !== 0) {
            $mensagem = "Erro de conexão cURL ({$curlErrno}): {$curlError}";
        } elseif ($httpCode === 429) {
            $mensagem = "Limite de requisições da AniList atingido (HTTP 429). Aguarde alguns segundos e tente novamente.";
        } elseif ($respostaJson && $httpCode === 200) {
            $dadosObj = json_decode($respostaJson, true);
            if (!empty($dadosObj['data']['Media']) && is_array($dadosObj['data']['Media'])) {
                $anime = $dadosObj['data']['Media'];

                $tituloEng = trim((string)($anime['title']['english'] ?? ''));
                $tituloRom = trim((string)($anime['title']['romaji'] ?? ''));
                $titulo = $tituloEng !== '' ? $tituloEng : ($tituloRom !== '' ? $tituloRom : $termoBusca);

                $descRaw = (string)($anime['description'] ?? '');
                $sinopsePt = $descRaw !== '' ? limparETraduzirSinopse($descRaw) : 'Sem sinopse disponível.';

                $studiosLista = [];
                $producersLista = [];
                if (!empty($anime['studios']['nodes']) && is_array($anime['studios']['nodes'])) {
                    foreach ($anime['studios']['nodes'] as $stNode) {
                        $nomeSt = trim((string)($stNode['name'] ?? ''));
                        if ($nomeSt === '') {
                            continue;
                        }
                        if (!empty($stNode['isAnimationStudio'])) {
                            $studiosLista[] = $nomeSt;
                        } else {
                            $producersLista[] = $nomeSt;
                        }
                    }
                }

                $capaUrl = urlExternaValida((string)($anime['coverImage']['large'] ?? ''));
                $ano = !empty($anime['seasonYear']) ? (int)$anime['seasonYear'] : (int)date('Y');
                $notaImdb = !empty($anime['averageScore']) ? round(((float)$anime['averageScore']) / 10, 1) : 0.0;

                $trailerId = trim((string)($anime['trailer']['id'] ?? ''));
                $trailerSite = strtolower(trim((string)($anime['trailer']['site'] ?? '')));
                $trailerUrl = ($trailerId !== '' && $trailerSite === 'youtube')
                    ? urlExternaValida("https://www.youtube.com/watch?v=" . $trailerId)
                    : '';

                $imdbLink = !empty($anime['id'])
                    ? urlExternaValida("https://anilist.co/anime/" . (int)$anime['id'])
                    : '';

                $dadosApi = [
                    'titulo'      => $titulo,
                    'sinopse'     => $sinopsePt,
                    'capa_url'    => $capaUrl,
                    'ano'         => $ano,
                    'nota_imdb'   => $notaImdb,
                    'trailer_url' => $trailerUrl,
                    'imdb_link'   => $imdbLink,
                    'studios'     => implode(', ', $studiosLista),
                    'producers'   => implode(', ', $producersLista),
                ];

                if ($autoSalvar) {
                    $resAuto = salvarAnimeNoBanco($pdo, $dadosApi, $fotoPadrao);
                    $mensagem = $resAuto['msg'];
                    $dadosApi = null;
                }
            } else {
                $mensagem = "Nenhum anime encontrado com esse nome.";
            }
        } else {
            $mensagem = "Erro ao conectar com a API da AniList (HTTP {$httpCode}).";
        }
    }
}

if (isset($_POST['guardar_anime'])) {
    $resSave = salvarAnimeNoBanco($pdo, $_POST, $fotoPadrao);
    $mensagem = $resSave['msg'];
    if ($resSave['ok']) {
        $modoManual = false;
        $dadosApi = null;
    }
}

$ultimoAnimeSelecionado = 0;
$ultimaTempSelecionada = 1;

if (isset($_POST['guardar_episodio'])) {
    $animeIdEp = filter_input(INPUT_POST, 'anime_id', FILTER_VALIDATE_INT);
    $temporadaEp = filter_input(INPUT_POST, 'temporada', FILTER_VALIDATE_INT);
    $numEp = filter_input(INPUT_POST, 'numero_episodio', FILTER_VALIDATE_INT);
    $tituloEp = trim((string)($_POST['titulo_episodio'] ?? ''));
    $videoUrlEp = trim((string)($_POST['video_url'] ?? ''));
    $downloadEp = trim((string)($_POST['link_download'] ?? ''));

    $temporadaEp = ($temporadaEp && $temporadaEp >= 1) ? $temporadaEp : 1;

    if ($animeIdEp && $animeIdEp >= 1) {
        $ultimoAnimeSelecionado = $animeIdEp;
        $ultimaTempSelecionada = $temporadaEp;

        // Se o número não foi preenchido, pega automaticamente o próximo número para aquele anime/temporada
        if (!$numEp || $numEp < 1) {
            try {
                if (in_array('temporada', $colsEp, true)) {
                    $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(numero_episodio), 0) + 1 FROM episodios WHERE anime_id = ? AND temporada = ?");
                    $stmtMax->execute([$animeIdEp, $temporadaEp]);
                } else {
                    $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(numero_episodio), 0) + 1 FROM episodios WHERE anime_id = ?");
                    $stmtMax->execute([$animeIdEp]);
                }
                $numEp = max(1, (int)$stmtMax->fetchColumn());
            } catch (PDOException $e) {
                $numEp = 1;
            }
        }

        // Se o título não foi preenchido, gera automaticamente "Episódio XX"
        if ($tituloEp === '') {
            $tituloEp = 'Episódio ' . str_pad((string)$numEp, 2, '0', STR_PAD_LEFT);
        }

        try {
            $camposEp = [];
            $placeholdersEp = [];
            $valoresEp = [];

            if (in_array('anime_id', $colsEp, true)) {
                $camposEp[] = 'anime_id';
                $placeholdersEp[] = '?';
                $valoresEp[] = $animeIdEp;
            }
            if (in_array('temporada', $colsEp, true)) {
                $camposEp[] = 'temporada';
                $placeholdersEp[] = '?';
                $valoresEp[] = $temporadaEp;
            }
            if (in_array('numero_episodio', $colsEp, true)) {
                $camposEp[] = 'numero_episodio';
                $placeholdersEp[] = '?';
                $valoresEp[] = $numEp;
            }
            if (in_array('titulo', $colsEp, true)) {
                $camposEp[] = 'titulo';
                $placeholdersEp[] = '?';
                $valoresEp[] = $tituloEp;
            } elseif (in_array('nome', $colsEp, true)) {
                $camposEp[] = 'nome';
                $placeholdersEp[] = '?';
                $valoresEp[] = $tituloEp;
            }
            if (in_array('video_url', $colsEp, true)) {
                $camposEp[] = 'video_url';
                $placeholdersEp[] = '?';
                $valoresEp[] = $videoUrlEp !== '' ? $videoUrlEp : null;
            }
            if (in_array('link_download', $colsEp, true)) {
                $camposEp[] = 'link_download';
                $placeholdersEp[] = '?';
                $valoresEp[] = $downloadEp !== '' ? $downloadEp : null;
            }

            $sqlEp = "INSERT INTO episodios (" . implode(', ', $camposEp) . ") VALUES (" . implode(', ', $placeholdersEp) . ")";
            $stmtEp = $pdo->prepare($sqlEp);
            $stmtEp->execute($valoresEp);
            $mensagem = "Episódio {$numEp} ({$tituloEp} - Temporada {$temporadaEp}) cadastrado com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar episódio: " . $e->getMessage();
        }
    } else {
        $mensagem = "Selecione um anime para adicionar o episódio.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apagar_episodio'])) {
    $epId = filter_input(INPUT_POST, 'episodio_id', FILTER_VALIDATE_INT);

    if (!$epId || $epId <= 0) {
        $mensagem = "ID de episódio inválido.";
    } else {
        try {
            $stmtDelEp = $pdo->prepare("DELETE FROM episodios WHERE id = ? LIMIT 1");
            $stmtDelEp->execute([$epId]);

            if ($stmtDelEp->rowCount() > 0) {
                $mensagem = "Episódio apagado com sucesso!";
            } else {
                $mensagem = "Episódio não encontrado.";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao apagar episódio: " . $e->getMessage();
        }
    }
}

$stmtList = $pdo->query("SELECT * FROM animes ORDER BY id DESC");
$animes = $stmtList->fetchAll(PDO::FETCH_ASSOC);

$episodiosCadastrados = [];
$proximosPorAnime = [];
try {
    $episodiosCadastrados = $pdo->query("
        SELECT e.*, a.titulo AS anime_titulo
        FROM episodios e
        LEFT JOIN animes a ON a.id = e.anime_id
        ORDER BY e.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($episodiosCadastrados as $epRow) {
        $aId = (int)($epRow['anime_id'] ?? 0);
        $tNum = (int)($epRow['temporada'] ?? 1);
        $eNum = (int)($epRow['numero_episodio'] ?? 0);
        if (!isset($proximosPorAnime[$aId][$tNum]) || ($eNum + 1) > $proximosPorAnime[$aId][$tNum]) {
            $proximosPorAnime[$aId][$tNum] = $eNum + 1;
        }
    }
} catch (PDOException $e) {
    $episodiosCadastrados = [];
}

if ($ultimoAnimeSelecionado === 0 && !empty($animes)) {
    $ultimoAnimeSelecionado = (int)$animes[0]['id'];
}
$proximoEpInicial = $proximosPorAnime[$ultimoAnimeSelecionado][$ultimaTempSelecionada] ?? 1;

$nomeAdmin = (string)($_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? $_SESSION['usuario'] ?? 'Administrador');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo - Hina</title>
    <link rel="icon" type="image/jpeg" href="<?php echo htmlspecialchars($fotoPadrao); ?>">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container { max-width: 900px; margin: 30px auto; padding: 20px; background: #1a1b22; border-radius: 8px; border: 1px solid #23252b; color: #fff; }
        form input, form textarea, form select, .input-pesquisa { width: 100%; padding: 12px; margin-bottom: 15px; background: #23252b; border: 1px solid #3f4254; color: #fff; border-radius: 4px; box-sizing: border-box; outline: none; }
        form input:focus, form textarea:focus, form select:focus, .input-pesquisa:focus { border-color: #ff0080; }
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
        .admin-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,0,128,0.15); border: 1px solid #ff0080; color: #ff0080; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .ep-item-painel { background: #23252b; border: 1px solid #2f3240; border-radius: 6px; padding: 12px 14px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn-apagar-ep { background: #b91c1c; color: #fff; border: none; padding: 7px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-apagar-ep:hover { background: #991b1b; }
        .dica-campo { font-size: 0.78rem; color: #9aa0a6; font-weight: normal; }
    </style>
</head>
<body>

    <header class="site-header">
        <a href="index.php" class="logo" style="text-decoration:none; display:flex; align-items:center; gap:10px;">
            <img src="<?php echo htmlspecialchars($fotoPadrao); ?>" class="logo-img" alt="Logo Hina" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
            <h1 style="color:#fff; font-size:1.2rem;">Hina - Painel</h1>
        </a>
        <nav class="nav-links">
            <a href="index.php">Ver Site</a>
            <a href="painel.php">Gerir Animes</a>
            <a href="painel.php?sair=1" style="color: #ff6b6b;">Sair</a>
        </nav>
    </header>

    <div class="admin-container">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
            <h2 style="margin: 0;">Painel de Gestão de Animes</h2>
            <span class="admin-badge"><i class="fa-solid fa-shield-halved"></i> <?php echo htmlspecialchars($nomeAdmin); ?></span>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="msg"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <a href="painel.php" class="btn-hina" style="text-align: center; background: <?php echo !$modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Pesquisar via AniList API</a>
            <a href="painel.php?manual=1" class="btn-hina" style="text-align: center; background: <?php echo $modoManual ? '#ff0080' : '#3f4254'; ?>; color: #fff; text-decoration:none; padding: 10px; border-radius:4px; flex:1;">Cadastrar Manualmente</a>
        </div>

        <?php if (!$modoManual): ?>
            <form method="GET" action="painel.php">
                <label style="display:block; margin-bottom:8px;">Pesquisar Anime Global na AniList</label>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="text" name="termo" class="input-pesquisa" placeholder="Digite o nome do anime..." value="<?php echo htmlspecialchars($_GET['termo'] ?? ''); ?>" style="margin-bottom: 0; flex: 1; min-width: 220px;" required>
                    <input type="hidden" name="pesquisar_api" value="1">
                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; cursor: pointer;">
                        <input type="checkbox" name="auto_salvar" value="1" <?php echo $autoSalvar ? 'checked' : ''; ?> style="width: auto; margin-bottom: 0;">
                        Salvar automático
                    </label>
                    <button type="submit" style="padding: 12px 20px; background: #ff0080; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Pesquisar</button>
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
                                <i class="fa-solid fa-star"></i> Nota: <?php echo number_format((float)$dadosApi['nota_imdb'], 1); ?>
                            </p>
                            <?php if (!empty($dadosApi['studios'])): ?>
                                <p style="color: #ddd; font-size: 0.85rem; margin-bottom: 4px;">
                                    <strong>Studios:</strong> <?php echo htmlspecialchars($dadosApi['studios']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($dadosApi['producers'])): ?>
                                <p style="color: #bbb; font-size: 0.85rem; margin-bottom: 6px;">
                                    <strong>Producers:</strong> <?php echo htmlspecialchars($dadosApi['producers']); ?>
                                </p>
                            <?php endif; ?>
                            <p style="color: #bbb; font-size: 0.9rem;">
                                <?php
                                $sinPreview = (string)$dadosApi['sinopse'];
                                echo htmlspecialchars(tamanhoTexto($sinPreview) > 140 ? recorteTexto($sinPreview, 0, 140) . '...' : $sinPreview);
                                ?>
                            </p>
                        </div>
                    </div>

                    <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($dadosApi['titulo']); ?>">
                    <input type="hidden" name="sinopse" value="<?php echo htmlspecialchars($dadosApi['sinopse']); ?>">
                    <input type="hidden" name="capa_url" value="<?php echo htmlspecialchars($dadosApi['capa_url']); ?>">
                    <input type="hidden" name="ano" value="<?php echo htmlspecialchars((string)$dadosApi['ano']); ?>">
                    <input type="hidden" name="nota_imdb" value="<?php echo htmlspecialchars((string)$dadosApi['nota_imdb']); ?>">
                    <input type="hidden" name="trailer_url" value="<?php echo htmlspecialchars($dadosApi['trailer_url']); ?>">
                    <input type="hidden" name="imdb_link" value="<?php echo htmlspecialchars($dadosApi['imdb_link']); ?>">

                    <button type="submit" name="guardar_anime" style="width: 100%; padding: 12px; background: #2e7d32; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Confirmar e Guardar na Base de Dados</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($modoManual): ?>
            <form method="POST" style="margin-top: 15px; border-top: 1px solid #3f4254; padding-top: 15px;">
                <h3>Cadastro Manual Rápido <span class="dica-campo">(só o título é obrigatório)</span></h3>

                <label>Título do Anime:</label>
                <input type="text" name="titulo" required placeholder="Ex: Naruto">

                <label>URL da Imagem de Capa <span class="dica-campo">(opcional — usa foto padrão se vazio)</span>:</label>
                <input type="text" name="capa_url" placeholder="https://exemplo.com/capa.jpg">

                <label>Sinopse <span class="dica-campo">(opcional)</span>:</label>
                <textarea name="sinopse" rows="3" placeholder="Escreva a sinopse em português..."></textarea>

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Ano <span class="dica-campo">(opcional)</span>:</label>
                        <input type="number" name="ano" placeholder="<?php echo date('Y'); ?>">
                    </div>
                    <div style="flex: 1;">
                        <label>Nota IMDb <span class="dica-campo">(opcional)</span>:</label>
                        <input type="number" step="0.1" min="0" max="10" name="nota_imdb" placeholder="8.4">
                    </div>
                </div>

                <label>Link do Trailer no YouTube <span class="dica-campo">(opcional)</span>:</label>
                <input type="url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=...">

                <label>Link oficial do IMDb <span class="dica-campo">(opcional)</span>:</label>
                <input type="url" name="imdb_link" placeholder="https://www.imdb.com/title/tt...">

                <button type="submit" name="guardar_anime" style="width: 100%; padding: 12px; background: #2e7d32; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Guardar na Base de Dados</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($animes)): ?>
            <form method="POST" style="margin-top: 30px; border-top: 1px solid #3f4254; padding-top: 20px;">
                <h3>Adicionar Episódio Rápido <span class="dica-campo">(número e título preenchem sozinhos)</span></h3>

                <label>Anime:</label>
                <select name="anime_id" id="selectAnimeEp" required>
                    <?php foreach ($animes as $itemAnime): ?>
                        <?php $aIdItem = (int)$itemAnime['id']; ?>
                        <option value="<?php echo $aIdItem; ?>" <?php echo $aIdItem === $ultimoAnimeSelecionado ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($itemAnime['titulo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <div style="width: 130px;">
                        <label>Temporada:</label>
                        <input type="number" name="temporada" id="inputTempEp" min="1" value="<?php echo $ultimaTempSelecionada; ?>">
                    </div>
                    <div style="width: 130px;">
                        <label>Nº Episódio:</label>
                        <input type="number" name="numero_episodio" id="inputNumEp" min="1" value="<?php echo $proximoEpInicial; ?>" placeholder="Auto">
                    </div>
                    <div style="flex: 1; min-width: 180px;">
                        <label>Título <span class="dica-campo">(opcional)</span>:</label>
                        <input type="text" name="titulo_episodio" id="inputTituloEp" placeholder="Auto: Episódio <?php echo str_pad((string)$proximoEpInicial, 2, '0', STR_PAD_LEFT); ?>">
                    </div>
                </div>

                <label>URL do Vídeo <span class="dica-campo">(opcional)</span>:</label>
                <input type="text" name="video_url" id="inputVideoEp" placeholder="videos/ep01.mp4 ou https://...">

                <label>Link de Download ou Magnet <span class="dica-campo">(opcional)</span>:</label>
                <input type="text" name="link_download" id="inputDownloadEp" placeholder="https://... ou magnet:?xt=...">

                <button type="submit" name="guardar_episodio" style="width: 100%; padding: 12px; background: #ff0080; border: none; color: #fff; font-weight: bold; border-radius: 4px; cursor: pointer;">Salvar Episódio</button>
            </form>
        <?php endif; ?>

        <h2 style="margin-top: 35px;">Episódios Registados (<?php echo count($episodiosCadastrados); ?>)</h2>
        <?php if (!empty($episodiosCadastrados)): ?>
            <div style="margin-top: 14px;">
                <?php foreach ($episodiosCadastrados as $epItem): ?>
                    <?php
                    $nomeAnimeEp = (string)($epItem['anime_titulo'] ?? ('Anime #' . (int)($epItem['anime_id'] ?? 0)));
                    $tituloEpItem = (string)($epItem['titulo'] ?? $epItem['nome'] ?? 'Sem título');
                    $tempEpItem = (int)($epItem['temporada'] ?? $epItem['season'] ?? 1);
                    $numEpItem = (int)($epItem['numero_episodio'] ?? 0);
                    ?>
                    <div class="ep-item-painel">
                        <div>
                            <strong><?php echo htmlspecialchars($nomeAnimeEp); ?></strong>
                            <span style="color: #a0a0a0;"> • T<?php echo $tempEpItem; ?> • EP <?php echo str_pad($numEpItem, 2, '0', STR_PAD_LEFT); ?> — </span>
                            <span><?php echo htmlspecialchars($tituloEpItem); ?></span>
                        </div>
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja apagar este episódio?');">
                            <input type="hidden" name="episodio_id" value="<?php echo (int)$epItem['id']; ?>" style="margin: 0;">
                            <button type="submit" name="apagar_episodio" class="btn-apagar-ep">
                                <i class="fa-solid fa-trash"></i> Apagar EP
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #777; margin-top: 12px;">Nenhum episódio registado.</p>
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
                            <td><?php echo (int)$anime['id']; ?></td>
                            <td><?php echo htmlspecialchars($anime['titulo']); ?></td>
                            <td style="color: #f5c518; font-weight: bold;">⭐ <?php echo number_format((float)($anime['nota_imdb'] ?? 0), 1); ?></td>
                            <td><?php echo htmlspecialchars((string)($anime['ano_lancamento'] ?? '-')); ?></td>
                            <td>
                                <a href="anime.php?id=<?php echo (int)$anime['id']; ?>" class="btn-acao btn-editar">Ver</a>
                                <a href="editar.php?id=<?php echo (int)$anime['id']; ?>" class="btn-acao btn-editar">Editar</a>
                                <a href="excluir.php?id=<?php echo (int)$anime['id']; ?>" class="btn-acao btn-excluir" onclick="return confirm('Tens a certeza que queres apagar o anime <?php echo htmlspecialchars((string)$anime['titulo'], ENT_QUOTES); ?>?');">Excluir</a>
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
        (function () {
            const proximos = <?php echo json_encode($proximosPorAnime, JSON_UNESCAPED_UNICODE); ?>;
            const selectAnime = document.getElementById('selectAnimeEp');
            const inputTemp = document.getElementById('inputTempEp');
            const inputNum = document.getElementById('inputNumEp');
            const inputTitulo = document.getElementById('inputTituloEp');
            const inputVideo = document.getElementById('inputVideoEp');
            const inputDownload = document.getElementById('inputDownloadEp');

            if (!selectAnime || !inputTemp || !inputNum || !inputTitulo) return;

            function atualizarSugestao() {
                const animeId = String(selectAnime.value || '');
                const temp = String(parseInt(inputTemp.value, 10) || 1);
                const prox = (proximos[animeId] && proximos[animeId][temp]) ? proximos[animeId][temp] : 1;
                inputNum.value = prox;
                inputTitulo.placeholder = 'Auto: Episódio ' + String(prox).padStart(2, '0');
            }

            function detectarNumeroNoLink(valor) {
                const texto = decodeURIComponent(String(valor || ''));
                const match = texto.match(/(?:ep(?:is[oó]dio)?|e|\s-\s)\s*0*(\d{1,3})\b/i);
                if (match && match[1]) {
                    const numDetectado = parseInt(match[1], 10);
                    if (numDetectado > 0) {
                        inputNum.value = numDetectado;
                        inputTitulo.placeholder = 'Auto: Episódio ' + String(numDetectado).padStart(2, '0');
                    }
                }
            }

            selectAnime.addEventListener('change', atualizarSugestao);
            inputTemp.addEventListener('input', atualizarSugestao);

            if (inputVideo) {
                inputVideo.addEventListener('input', () => detectarNumeroNoLink(inputVideo.value));
            }
            if (inputDownload) {
                inputDownload.addEventListener('input', () => detectarNumeroNoLink(inputDownload.value));
            }
        })();
    </script>

</body>
</html>