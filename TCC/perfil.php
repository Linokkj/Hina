<?php
session_start();
require 'conexao.php';

$usuarioLogado = !empty($_SESSION['logado']) || !empty($_SESSION['usuario_id']) || !empty($_SESSION['user_id']);
if (!$usuarioLogado) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 0);
$emailSessao = trim((string)($_SESSION['usuario_email'] ?? $_SESSION['email'] ?? ''));

$ehAdmin = (
    (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ||
    (!empty($_SESSION['admin']) && $_SESSION['admin'] === true) ||
    (isset($_SESSION['tipo']) && strtolower((string)$_SESSION['tipo']) === 'admin') ||
    (isset($_SESSION['cargo']) && strtolower((string)$_SESSION['cargo']) === 'admin') ||
    (isset($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin') ||
    (isset($_SESSION['nivel']) && strtolower((string)$_SESSION['nivel']) === 'admin')
);

$mensagem = "";
$sucesso = false;
$avatarPadrao = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTVsLOLcUZ8ehQbEsjNAgMzFY3eV-xeZpO8pKppKC_bww&s=10";

try {
    $colsUsuarios = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('foto_perfil', $colsUsuarios, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL");
        $colsUsuarios = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favoritos_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            anime_id INT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_usuario_anime (usuario_id, anime_id)
        )
    ");
} catch (PDOException $e) {
    $mensagem = "Aviso na estrutura da base de dados: " . $e->getMessage();
}

try {
    if ($usuarioId > 0) {
        $stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
        $stmtUser->execute([$usuarioId]);
        $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
    } elseif ($emailSessao !== '') {
        $stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmtUser->execute([$emailSessao]);
        $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            $usuarioId = (int)$usuario['id'];
            $_SESSION['usuario_id'] = $usuarioId;
        }
    } else {
        $usuario = null;
    }

    if (!$usuario) {
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao carregar perfil: " . htmlspecialchars($e->getMessage()));
}

if (isset($_GET['remover_fav'])) {
    $animeRemover = filter_input(INPUT_GET, 'remover_fav', FILTER_VALIDATE_INT);
    if ($animeRemover) {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM favoritos_usuario WHERE usuario_id = ? AND anime_id = ?");
            $stmtDel->execute([$usuarioId, $animeRemover]);
            header("Location: perfil.php?fav_removido=1");
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro ao remover favorito: " . $e->getMessage();
        }
    }
}

if (isset($_GET['fav_removido'])) {
    $sucesso = true;
    $mensagem = "Anime removido dos favoritos.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_perfil'])) {
    $novoNome = trim($_POST['nome'] ?? '');
    $fotoUrl = trim($_POST['foto_url'] ?? '');
    $fotoFinal = trim((string)($usuario['foto_perfil'] ?? ''));

    if ($novoNome === '') {
        $mensagem = "O nome não pode ficar em branco.";
    } else {
        if (!empty($_FILES['foto_arquivo']['name']) && (int)$_FILES['foto_arquivo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto_arquivo']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($ext, $permitidas, true)) {
                $mensagem = "Formato de imagem inválido. Use JPG, PNG, WEBP ou GIF.";
            } elseif ((int)$_FILES['foto_arquivo']['size'] > 3 * 1024 * 1024) {
                $mensagem = "A imagem deve ter no máximo 3 MB.";
            } else {
                $pastaUploads = __DIR__ . '/uploads/avatars';
                if (!is_dir($pastaUploads)) {
                    mkdir($pastaUploads, 0775, true);
                }
                $nomeArquivo = 'user_' . $usuarioId . '_' . time() . '.' . $ext;
                $destino = $pastaUploads . '/' . $nomeArquivo;

                if (move_uploaded_file($_FILES['foto_arquivo']['tmp_name'], $destino)) {
                    $fotoFinal = 'uploads/avatars/' . $nomeArquivo;
                } else {
                    $mensagem = "Não foi possível guardar a foto enviada.";
                }
            }
        } elseif ($fotoUrl !== '') {
            if (filter_var($fotoUrl, FILTER_VALIDATE_URL)) {
                $fotoFinal = $fotoUrl;
            } else {
                $mensagem = "Informe um link (URL) válido para a foto de perfil.";
            }
        }

        if ($mensagem === '') {
            try {
                $sets = [];
                $params = [];

                if (in_array('nome', $colsUsuarios, true)) {
                    $sets[] = "nome = ?";
                    $params[] = $novoNome;
                }
                if (in_array('foto_perfil', $colsUsuarios, true)) {
                    $sets[] = "foto_perfil = ?";
                    $params[] = ($fotoFinal !== '' ? $fotoFinal : null);
                }

                if (!empty($sets)) {
                    $params[] = $usuarioId;
                    $pdo->prepare("UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                }

                $_SESSION['usuario_nome'] = $novoNome;
                $usuario['nome'] = $novoNome;
                $usuario['foto_perfil'] = $fotoFinal;

                $sucesso = true;
                $mensagem = "Perfil atualizado com sucesso!";
            } catch (PDOException $e) {
                $mensagem = "Erro ao atualizar perfil: " . $e->getMessage();
            }
        }
    }
}

$favoritos = [];
try {
    $stmtFav = $pdo->prepare("
        SELECT a.*
        FROM favoritos_usuario f
        INNER JOIN animes a ON a.id = f.anime_id
        WHERE f.usuario_id = ?
        ORDER BY f.id DESC
    ");
    $stmtFav->execute([$usuarioId]);
    $favoritos = $stmtFav->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $favoritos = [];
}

$fotoAtual = trim((string)($usuario['foto_perfil'] ?? ''));
if ($fotoAtual === '') {
    $fotoAtual = $avatarPadrao;
}
$nomeAtual = (string)($usuario['nome'] ?? 'Utilizador');
$emailAtual = (string)($usuario['email'] ?? '');
$tipoAtual = strtolower((string)($usuario['tipo'] ?? ($ehAdmin ? 'admin' : 'usuario')));
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Hina</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .perfil-wrapper {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 4%;
        }
        .perfil-card {
            background-color: #141519;
            border: 1px solid #23252b;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
            align-items: flex-start;
        }
        .avatar-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            min-width: 180px;
        }
        .avatar-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ff0080;
            background: #23252b;
        }
        .badge-role {
            background: #23252b;
            color: #ff0080;
            border: 1px solid #ff0080;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .perfil-form {
            flex: 1;
            min-width: 260px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .perfil-form label {
            font-size: 13px;
            color: #cccccc;
        }
        .perfil-form input {
            background-color: #23252b;
            border: 1px solid #3f4254;
            border-radius: 4px;
            padding: 10px 12px;
            color: #ffffff;
            font-family: inherit;
            font-size: 14px;
        }
        .perfil-form input:focus {
            outline: none;
            border-color: #ff0080;
        }
        .fav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 18px;
        }
        .fav-card {
            background: #1c1e24;
            border: 1px solid #2a2d36;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .fav-card img {
            width: 100%;
            aspect-ratio: 2 / 3;
            object-fit: cover;
        }
        .fav-body {
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            justify-content: space-between;
        }
        .fav-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-remover-fav {
            background: rgba(211, 47, 47, 0.2);
            border: 1px solid #d32f2f;
            color: #e57373;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
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
            <a href="perfil.php">Meu Perfil</a>
            <?php if ($ehAdmin): ?>
                <a href="painel.php">Painel Admin</a>
            <?php endif; ?>
            <a href="painel.php?sair=1">Sair</a>
        </nav>
    </header>

    <main class="perfil-wrapper">
        <h2>Minha Conta</h2>

        <?php if (!empty($mensagem)): ?>
            <div style="background: <?php echo $sucesso ? 'rgba(46,125,50,0.2)' : 'rgba(255,0,128,0.12)'; ?>; border: 1px solid <?php echo $sucesso ? '#2e7d32' : '#ff0080'; ?>; color: <?php echo $sucesso ? '#81c784' : '#ff0080'; ?>; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <section class="perfil-card">
            <div class="avatar-box">
                <img src="<?php echo htmlspecialchars($fotoAtual); ?>" alt="Foto de Perfil" class="avatar-img">
                <strong><?php echo htmlspecialchars($nomeAtual); ?></strong>
                <small style="color: #9aa0a6;"><?php echo htmlspecialchars($emailAtual); ?></small>
                <span class="badge-role"><?php echo htmlspecialchars($tipoAtual); ?></span>
            </div>

            <form method="POST" enctype="multipart/form-data" class="perfil-form">
                <label>Nome de exibição</label>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($nomeAtual); ?>" required>

                <label>Ou enviar foto do dispositivo (JPG, PNG, WEBP, GIF — até 3 MB)</label>
                <input type="file" name="foto_arquivo" accept="image/*">

                <button type="submit" name="salvar_perfil" class="btn-hina" style="align-self: flex-start; margin-top: 6px;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Perfil
                </button>
            </form>
        </section>

        <section style="background: #141519; border: 1px solid #23252b; border-radius: 8px; padding: 25px;">
            <h2 style="margin-bottom: 18px;"><i class="fa-solid fa-heart" style="color: #ff0080;"></i> Animes Favoritos (<?php echo count($favoritos); ?>)</h2>

            <?php if (!empty($favoritos)): ?>
                <div class="fav-grid">
                    <?php foreach ($favoritos as $fav): ?>
                        <div class="fav-card">
                            <img src="<?php echo htmlspecialchars($fav['capa_url']); ?>" alt="<?php echo htmlspecialchars($fav['titulo']); ?>">
                            <div class="fav-body">
                                <strong style="font-size: 14px; color: #fff;"><?php echo htmlspecialchars($fav['titulo']); ?></strong>
                                <div class="fav-actions">
                                    <a href="anime.php?id=<?php echo (int)$fav['id']; ?>" class="btn-hina" style="padding: 6px 10px; font-size: 12px;">Ver</a>
                                    <a href="perfil.php?remover_fav=<?php echo (int)$fav['id']; ?>" class="btn-remover-fav">Remover</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #888;">Ainda não adicionou animes aos favoritos.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Hina - Trabalho de Conclusão de Curso.</p>
    </footer>

</body>
</html>