<?php
session_start();
require 'conexao.php';

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hina - Catálogo de Animes</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .header-avatar-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            text-decoration: none;
        }
        .header-avatar-img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff0080;
            display: block;
        }
        .header-avatar-placeholder {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #23252b;
            border: 2px solid #ff0080;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
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

    <section class="hero-banner">
        <div class="hero-content">
            <h2>Os melhores animes para assistir</h2>
            <p>Explore o nosso catálogo interativo desenvolvido para o Projeto Integrador.</p>
        </div>
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
                                    $resumoSinopse = mb_strlen($sinopse, 'UTF-8') > 80
                                        ? mb_substr($sinopse, 0, 80, 'UTF-8') . "..."
                                        : $sinopse;
                                    echo htmlspecialchars($resumoSinopse);
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

</body>
</html>