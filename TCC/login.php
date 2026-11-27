<?php
session_start();
require 'conexao.php';

$fotoPadrao = 'https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg';
$mensagem = "";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL DEFAULT '',
            email VARCHAR(160) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            tipo VARCHAR(30) NOT NULL DEFAULT 'usuario',
            foto_perfil VARCHAR(255) NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    $mensagem = "Aviso na base de dados: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $mensagem = "Por favor, preencha o e-mail e a palavra-passe.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            $hashBanco = (string)($usuario['senha'] ?? $usuario['senha_hash'] ?? '');
            $senhaCorreta = false;

            if ($usuario && $hashBanco !== '') {
                if (password_verify($senha, $hashBanco)) {
                    $senhaCorreta = true;
                } elseif (hash_equals($hashBanco, $senha)) {
                    $senhaCorreta = true;
                }
            }

            if ($usuario && $senhaCorreta) {
                $tipo = strtolower((string)($usuario['tipo'] ?? 'usuario'));
                $fotoSalva = trim((string)($usuario['foto_perfil'] ?? ''));
                $_SESSION['logado'] = true;
                $_SESSION['usuario_id'] = (int)$usuario['id'];
                $_SESSION['usuario_nome'] = (string)($usuario['nome'] ?? '');
                $_SESSION['usuario_email'] = (string)($usuario['email'] ?? '');
                $_SESSION['foto_perfil'] = $fotoSalva !== '' ? $fotoSalva : $fotoPadrao;
                $_SESSION['tipo'] = $tipo;
                $_SESSION['is_admin'] = ($tipo === 'admin');

                header('Location: index.php');
                exit;
            } else {
                $mensagem = "E-mail ou palavra-passe incorretos.";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao iniciar sessão: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sessão - Hina</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-links {
            position: relative;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .login-links a {
            position: relative;
            z-index: 10;
            pointer-events: auto;
            cursor: pointer;
        }
    </style>
</head>
<body class="login-body">

    <div class="login-card">
        <a href="index.php" class="logo">
            <img src="<?php echo htmlspecialchars($fotoPadrao); ?>" class="logo-img" alt="Logo Hina">
            <h1>Hina<span>.</span></h1>
        </a>

        <h2>Iniciar sessão</h2>

        <?php if (!empty($mensagem)): ?>
            <div style="background: rgba(255,0,128,0.1); border: 1px solid #ff0080; color: #ff0080; padding: 10px; font-size: 13px; border-radius: 4px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            <label>E-mail</label>
            <input type="email" name="email" class="login-input" placeholder="exemplo@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

            <label>Palavra-passe</label>
            <input type="password" name="senha" class="login-input" placeholder="A sua palavra-passe" required>

            <button type="submit" class="btn-login">ENTRAR</button>
        </form>

        <div class="login-links">
            <a href="cadastro.php">Não tem conta? Criar conta</a>
            <a href="index.php">← Voltar ao Início</a>
        </div>
    </div>

    <footer class="login-footer">
        <p>Hina Inc. &copy; 2026 — Todos os direitos reservados.</p>
    </footer>

</body>
</html>