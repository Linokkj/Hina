<?php
session_start();
require 'conexao.php';

function cortarTextoSeguro(string $texto, int $limite = 80, string $sufixo = '...'): string {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($texto, 'UTF-8') > $limite
            ? mb_substr($texto, 0, $limite, 'UTF-8') . $sufixo
            : $texto;
    }

    if (preg_match_all('/./u', $texto, $matches) && count($matches[0]) > $limite) {
        return implode('', array_slice($matches[0], 0, $limite)) . $sufixo;
    }

    return strlen($texto) > $limite
        ? substr($texto, 0, $limite) . $sufixo
        : $texto;
}

$usuarioLogado = !empty($_SESSION['logado']) || !empty($_SESSION['usuario_id']) || !empty($_SESSION['user_id']);
$usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 0);
$emailSessao = trim((string)($_SESSION['usuario_email'] ?? $_SESSION['email'] ?? ''));
$fotoPerfilUsuario = trim((string)($_SESSION['foto_perfil'] ?? ''));

$ehAdmin = (
    (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ||
    (!empty($_SESSION['admin']) && $_SESSION['admin'] === true) ||
    (isset($_SESSION['tipo']) && strtolower((string)$_SESSION['tipo']) === 'admin') ||
    (isset($_SESSION['cargo']) && strtolower((string)$_SESSION['cargo']) === 'admin') ||
    (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin') ||
    (isset($_SESSION['nivel']) && strtolower((string)$_SESSION['nivel']) === 'admin')
);

if ($usuarioLogado) {
    try {
        if ($usuarioId > 0) {
            $stmtFoto = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ? LIMIT 1");
            $stmtFoto->execute([$usuarioId]);
            $rowUser = $stmtFoto->fetch(PDO::FETCH_ASSOC);
        } elseif ($emailSessao !== '') {
            $stmtFoto = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmtFoto->execute([$emailSessao]);
            $rowUser = $stmtFoto->fetch(PDO::FETCH_ASSOC);
        } else {
            $rowUser = null;
        }

        if ($rowUser !== false) {
            $fotoPerfilUsuario = trim((string)($rowUser['foto_perfil'] ?? ''));
            $_SESSION['foto_perfil'] = $fotoPerfilUsuario;
        }
    } catch (PDOException $e) {
        // Mantém o valor da sessão em caso de falha transitória
    }
}

$termoBusca = trim((string)($_GET['busca'] ?? ''));

$animes = [];
try {
    if ($termoBusca !== '') {
        $stmt = $pdo->prepare("SELECT * FROM animes WHERE titulo LIKE ? OR sinopse LIKE ? ORDER BY id DESC");
        $like = '%' . $termoBusca . '%';
        $stmt->execute([$like, $like]);
        $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("SELECT * FROM animes ORDER BY id DESC");
        $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Erro ao carregar os animes: " . htmlspecialchars($e->getMessage());
}

$slidesBanner = [
    [
        'imagem' => 'https://s4.anilist.co/file/anilistcdn/media/anime/banner/21-wf37VakJmZqs.jpg',
        'link'   => 'index.php',
        'titulo' => 'One Piece',
    ],
    [
        'imagem' => 'https://s4.anilist.co/file/anilistcdn/media/anime/banner/154587-ivXNJ23SM1xB.jpg',
        'link'   => 'index.php',
        'titulo' => 'Frieren: Beyond Journey\'s End',
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hina - Catálogo de Animes</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header class="site-header" id="siteHeader">
        <a href="index.php" class="logo">
            <img src="https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg" class="logo-img" alt="Logo Hina">
            <h1>Hina<span>.</span></h1>
        </a>

        <div class="header-right">
            <nav class="nav-links">
                <a href="index.php">Início</a>
                <?php if ($usuarioLogado): ?>
                    <?php if ($ehAdmin): ?>
                        <a href="painel.php">Painel Admin</a>
                    <?php endif; ?>
                    <a href="perfil.php" class="header-avatar-link" title="Meu Perfil">
                        <?php if ($fotoPerfilUsuario !== ''): ?>
                            <img src="<?php echo htmlspecialchars($fotoPerfilUsuario); ?>" alt="Foto de Perfil" class="header-avatar-img">
                        <?php else: ?>
                            <span class="header-avatar-placeholder"><i class="fa-solid fa-user"></i></span>
                        <?php endif; ?>
                    </a>
                    <a href="painel.php?sair=1">Sair</a>
                <?php else: ?>
                    <a href="login.php">Entrar</a>
                    <a href="cadastro.php">Criar conta</a>
                <?php endif; ?>
            </nav>

            <div class="header-search-box <?php echo $termoBusca !== '' ? 'aberta' : ''; ?>" id="barraBuscaTopo">
                <form method="GET" action="index.php" class="header-search-form" id="formBuscaAnime" role="search">
                    <input
                        type="search"
                        name="busca"
                        id="inputBuscaAnime"
                        class="header-search-input"
                        placeholder="Buscar anime..."
                        value="<?php echo htmlspecialchars($termoBusca); ?>"
                        autocomplete="off"
                        aria-label="Buscar anime"
                    >
                </form>

                <button
                    type="button"
                    class="header-search-btn"
                    id="btnAbrirBusca"
                    aria-label="Abrir busca"
                    aria-expanded="<?php echo $termoBusca !== '' ? 'true' : 'false'; ?>"
                    title="Buscar anime"
                >
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>
    </header>

    <section class="hero-carousel" aria-label="Destaques em carrossel">
        <?php if (count($slidesBanner) > 1): ?>
            <button type="button" class="carousel-btn prev" id="btnSlideAnterior" aria-label="Slide anterior">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button type="button" class="carousel-btn next" id="btnSlideProximo" aria-label="Próximo slide">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        <?php endif; ?>

        <div class="carousel-track" id="carouselTrack">
            <?php foreach ($slidesBanner as $slide): ?>
                <a href="<?php echo htmlspecialchars($slide['link']); ?>" class="carousel-slide" aria-label="<?php echo htmlspecialchars($slide['titulo']); ?>">
                    <img src="<?php echo htmlspecialchars($slide['imagem']); ?>" alt="<?php echo htmlspecialchars($slide['titulo']); ?>" class="carousel-banner-img">
                    <?php if (!empty($slide['titulo'])): ?>
                        <span class="carousel-anime-title"><?php echo htmlspecialchars($slide['titulo']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (count($slidesBanner) > 1): ?>
            <div class="carousel-dots">
                <?php foreach ($slidesBanner as $indice => $slide): ?>
                    <button type="button" class="carousel-dot <?php echo $indice === 0 ? 'ativo' : ''; ?>" data-slide="<?php echo $indice; ?>" aria-label="Ir para o slide <?php echo $indice + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <main class="container" id="catalogo">
        <h2>Catálogo de Animes</h2>
        <p class="search-status" id="statusBuscaAnime" aria-live="polite"></p>

        <div class="anime-grid" id="animeGrid">
            <?php if (!empty($animes)): ?>
                <?php foreach ($animes as $anime): ?>
                    <?php
                    $tituloAnime = (string)($anime['titulo'] ?? '');
                    $sinopseAnime = (string)($anime['sinopse'] ?? '');
                    $textoBusca = trim($tituloAnime . ' ' . $sinopseAnime);
                    ?>
                    <div class="anime-card" data-busca="<?php echo htmlspecialchars($textoBusca); ?>">
                        <div class="image-container">
                            <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="<?php echo htmlspecialchars($tituloAnime); ?>">
                            <?php if (!empty($anime['nota_imdb']) && $anime['nota_imdb'] > 0): ?>
                                <div class="imdb-badge">
                                    <i class="fa-solid fa-star"></i> <?php echo number_format((float)$anime['nota_imdb'], 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="anime-info">
                            <div>
                                <h3><?php echo htmlspecialchars($tituloAnime); ?></h3>
                                <p><?php echo htmlspecialchars(cortarTextoSeguro($sinopseAnime, 80, '...')); ?></p>
                            </div>
                            <a href="anime.php?id=<?php echo (int)$anime['id']; ?>" class="btn-hina">Ver Episódios</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #888;">Nenhum anime registrado no momento. Adicione através do painel administrativo.</p>
            <?php endif; ?>
        </div>

        <p class="search-empty-msg" id="mensagemSemResultados" style="display: none;">
            Nenhum anime encontrado para a sua busca.
        </p>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Hina - Trabalho de Conclusão de Curso.</p>
    </footer>

    <script>
        (function () {
            const header = document.getElementById('siteHeader');
            const barraBusca = document.getElementById('barraBuscaTopo');
            const btnAbrirBusca = document.getElementById('btnAbrirBusca');
            const formBusca = document.getElementById('formBuscaAnime');
            const inputBusca = document.getElementById('inputBuscaAnime');
            const catalogo = document.getElementById('catalogo');
            const statusBusca = document.getElementById('statusBuscaAnime');
            const msgSemResultados = document.getElementById('mensagemSemResultados');
            const cards = Array.from(document.querySelectorAll('#animeGrid .anime-card'));

            function buscaAberta() {
                return !!(barraBusca && barraBusca.classList.contains('aberta'));
            }

            if (header) {
                let ultimoScrollY = Math.max(0, window.scrollY || window.pageYOffset || 0);
                let framePendente = false;
                const tolerancia = 16;

                function atualizarHeader() {
                    const atualScrollY = Math.max(0, window.scrollY || window.pageYOffset || 0);
                    const delta = atualScrollY - ultimoScrollY;

                    if (buscaAberta() || atualScrollY <= 60) {
                        header.classList.remove('header-escondido');
                        ultimoScrollY = atualScrollY;
                        framePendente = false;
                        return;
                    }

                    if (Math.abs(delta) < tolerancia) {
                        framePendente = false;
                        return;
                    }

                    if (delta > 0 && atualScrollY > 100) {
                        header.classList.add('header-escondido');
                    } else if (delta < 0) {
                        header.classList.remove('header-escondido');
                    }

                    ultimoScrollY = atualScrollY;
                    framePendente = false;
                }

                window.addEventListener('scroll', () => {
                    if (!framePendente) {
                        framePendente = true;
                        window.requestAnimationFrame(atualizarHeader);
                    }
                }, { passive: true });
            }

            const track = document.getElementById('carouselTrack');
            const slides = track ? track.querySelectorAll('.carousel-slide') : [];
            const dots = document.querySelectorAll('.carousel-dot');
            const btnPrev = document.getElementById('btnSlideAnterior');
            const btnNext = document.getElementById('btnSlideProximo');
            const carousel = document.querySelector('.hero-carousel');

            if (track && slides.length > 1) {
                let atual = 0;
                let intervalo;

                function irParaSlide(index) {
                    atual = (index + slides.length) % slides.length;
                    track.style.transform = `translate3d(-${atual * 100}%, 0, 0)`;
                    dots.forEach((dot, i) => dot.classList.toggle('ativo', i === atual));
                }

                function iniciarAutoPlay() {
                    pararAutoPlay();
                    intervalo = setInterval(() => irParaSlide(atual + 1), 5000);
                }

                function pararAutoPlay() {
                    if (intervalo) clearInterval(intervalo);
                }

                if (btnPrev) {
                    btnPrev.addEventListener('click', () => {
                        irParaSlide(atual - 1);
                        iniciarAutoPlay();
                    });
                }

                if (btnNext) {
                    btnNext.addEventListener('click', () => {
                        irParaSlide(atual + 1);
                        iniciarAutoPlay();
                    });
                }

                dots.forEach((dot, i) => {
                    dot.addEventListener('click', () => {
                        irParaSlide(i);
                        iniciarAutoPlay();
                    });
                });

                if (carousel) {
                    carousel.addEventListener('mouseenter', pararAutoPlay);
                    carousel.addEventListener('mouseleave', iniciarAutoPlay);
                }

                iniciarAutoPlay();
            }

            function normalizarTexto(str) {
                return (str || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();
            }

            function rolarParaCatalogoSeNecessario() {
                if (!catalogo) return;
                const topoCatalogo = catalogo.getBoundingClientRect().top + window.scrollY - 90;
                if (window.scrollY < topoCatalogo) {
                    window.scrollTo({ top: Math.max(0, topoCatalogo), behavior: 'smooth' });
                }
            }

            function aplicarFiltro(rolar = false) {
                if (!inputBusca || cards.length === 0) return;

                const termoOriginal = inputBusca.value.trim();
                const termoLimpo = normalizarTexto(termoOriginal);
                let visiveis = 0;

                cards.forEach((card) => {
                    const conteudo = normalizarTexto(card.getAttribute('data-busca') || card.textContent);
                    const corresponde = termoLimpo === '' || conteudo.includes(termoLimpo);
                    card.style.display = corresponde ? '' : 'none';
                    if (corresponde) visiveis++;
                });

                if (msgSemResultados) {
                    msgSemResultados.style.display = (termoLimpo !== '' && visiveis === 0) ? 'block' : 'none';
                }

                if (statusBusca) {
                    if (termoLimpo === '') {
                        statusBusca.textContent = '';
                    } else if (visiveis === 1) {
                        statusBusca.textContent = `1 anime encontrado para "${termoOriginal}".`;
                    } else {
                        statusBusca.textContent = `${visiveis} animes encontrados para "${termoOriginal}".`;
                    }
                }

                if (rolar && termoLimpo !== '') {
                    rolarParaCatalogoSeNecessario();
                }
            }

            function abrirBusca() {
                if (!barraBusca) return;
                if (header) header.classList.remove('header-escondido');
                barraBusca.classList.add('aberta');
                if (btnAbrirBusca) btnAbrirBusca.setAttribute('aria-expanded', 'true');
                setTimeout(() => {
                    if (inputBusca) inputBusca.focus();
                }, 40);
            }

            function fecharBusca() {
                if (!barraBusca) return;
                barraBusca.classList.remove('aberta');
                if (btnAbrirBusca) btnAbrirBusca.setAttribute('aria-expanded', 'false');
                if (inputBusca && inputBusca.value !== '') {
                    inputBusca.value = '';
                    aplicarFiltro(false);
                    if (window.location.search.includes('busca=')) {
                        window.history.replaceState({}, '', window.location.pathname);
                    }
                }
            }

            if (btnAbrirBusca) {
                btnAbrirBusca.addEventListener('click', () => {
                    if (!buscaAberta()) {
                        abrirBusca();
                    } else if (inputBusca && inputBusca.value.trim() !== '') {
                        if (formBusca) formBusca.submit();
                    } else {
                        fecharBusca();
                    }
                });
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && buscaAberta()) {
                    fecharBusca();
                }
            });

            if (inputBusca) {
                inputBusca.addEventListener('input', () => aplicarFiltro(true));
                inputBusca.addEventListener('search', () => {
                    aplicarFiltro(false);
                    if (inputBusca.value.trim() === '' && window.location.search.includes('busca=')) {
                        window.history.replaceState({}, '', window.location.pathname);
                    }
                });
            }

            if (formBusca) {
                formBusca.addEventListener('submit', (e) => {
                    if (inputBusca && inputBusca.value.trim() === '' && !window.location.search.includes('busca=')) {
                        e.preventDefault();
                        aplicarFiltro(false);
                    }
                });
            }

            aplicarFiltro(false);
        })();
    </script>

</body>
</html>