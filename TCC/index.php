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

$animes = [];
try {
    $stmt = $pdo->query("SELECT * FROM animes ORDER BY id DESC");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao carregar os animes: " . htmlspecialchars($e->getMessage());
}

$slidesBanner = [
    [
        'imagem' => 'https://s4.anilist.co/file/anilistcdn/media/anime/banner/21-wf37VakJmZqs.jpg',
        'link'   => 'anime.php?id=3',
        'titulo' => 'One Piece',
    ],
    [
        'imagem' => 'https://s4.anilist.co/file/anilistcdn/media/anime/banner/154587-ivXNJ23SM1xB.jpg',
        'link'   => 'anime.php?id=8',
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

    <main class="container">
        <h2>Catálogo de Animes</h2>

        <div class="anime-grid">
            <?php if (!empty($animes)): ?>
                <?php foreach ($animes as $anime): ?>
                    <div class="anime-card">
                        <div class="image-container">
                            <img src="<?php echo htmlspecialchars($anime['capa_url']); ?>" alt="Capa do Anime">
                            <?php if (!empty($anime['nota_imdb']) && $anime['nota_imdb'] > 0): ?>
                                <div class="imdb-badge">
                                    <i class="fa-solid fa-star"></i> <?php echo number_format((float)$anime['nota_imdb'], 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="anime-info">
                            <div>
                                <h3><?php echo htmlspecialchars($anime['titulo']); ?></h3>
                                <p>
                                    <?php
                                    $sinopse = (string)($anime['sinopse'] ?? '');
                                    echo htmlspecialchars(cortarTextoSeguro($sinopse, 80, '...'));
                                    ?>
                                </p>
                            </div>
                            <a href="anime.php?id=<?php echo (int)$anime['id']; ?>" class="btn-hina">Ver Episódios</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #888;">Nenhum anime registado de momento. Adicione através do painel administrativo.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Hina - Trabalho de Conclusão de Curso.</p>
    </footer>

    <script>
        (function () {
            const header = document.getElementById('siteHeader');
            if (header) {
                let ultimoScrollY = Math.max(0, window.scrollY || window.pageYOffset || 0);
                let framePendente = false;
                const tolerancia = 16;

                function atualizarHeader() {
                    const atualScrollY = Math.max(0, window.scrollY || window.pageYOffset || 0);
                    const delta = atualScrollY - ultimoScrollY;

                    if (atualScrollY <= 60) {
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
                        // Arrastou para baixo: some
                        header.classList.add('header-escondido');
                    } else if (delta < 0) {
                        // Arrastou para cima: aparece
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

            if (!track || slides.length <= 1) return;

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
        })();
    </script>

</body>
</html>