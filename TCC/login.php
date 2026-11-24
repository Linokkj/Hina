<?php
session_start();
require 'conexao.php';

$mensagem = "";

if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_negado') {
    $mensagem = "Acesso restrito ao administrador. Faça login com uma conta de admin.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $mensagem = "Por favor, preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Por favor, informe um e-mail válido.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            $senhaAtualBanco = (string)($usuario['senha'] ?? '');
            $senhaValida = $usuario && (
                password_verify($senha, $senhaAtualBanco) ||
                hash_equals($senhaAtualBanco, $senha)
            );

            if ($usuario && $senhaValida) {
                if (password_get_info($senhaAtualBanco)['algo'] === null) {
                    $hashSeguro = password_hash($senha, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$hashSeguro, (int)$usuario['id']]);
                }

                $perfil = strtolower((string)(
                    $usuario['tipo'] ??
                    $usuario['cargo'] ??
                    $usuario['role'] ??
                    $usuario['nivel'] ??
                    ''
                ));

                $isAdmin = (
                    $perfil === 'admin' ||
                    (!empty($usuario['is_admin']) && (int)$usuario['is_admin'] === 1)
                );

                session_regenerate_id(true);
                $_SESSION['logado'] = true;
                $_SESSION['usuario_id'] = (int)($usuario['id'] ?? 0);
                $_SESSION['usuario_nome'] = (string)($usuario['nome'] ?? $usuario['email'] ?? 'Utilizador');
                $_SESSION['usuario_email'] = (string)($usuario['email'] ?? '');
                $_SESSION['foto_perfil'] = trim((string)($usuario['foto_perfil'] ?? ''));
                $_SESSION['tipo'] = $isAdmin ? 'admin' : ($perfil !== '' ? $perfil : 'usuario');
                $_SESSION['is_admin'] = $isAdmin;

                if ($isAdmin) {
                    header('Location: painel.php');
                    exit;
                }

                header('Location: index.php');
                exit;
            }

            $_SESSION['is_admin'] = false;
            $mensagem = "E-mail ou palavra-passe incorretos.";
        } catch (PDOException $e) {
            $_SESSION['is_admin'] = false;
            $mensagem = "Erro na autenticação: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - Hina</title>
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
            <img src="https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg" class="logo-img" alt="Logo Hina">
            <h1>Hina<span>.</span></h1>
        </a>

        <h2>Aceder à conta</h2>

        <?php if (!empty($mensagem)): ?>
            <div style="background: rgba(255,0,128,0.1); border: 1px solid #ff0080; color: #ff0080; padding: 10px; font-size: 13px; border-radius: 4px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            <label>E-mail</label>
            <input type="email" name="email" class="login-input" placeholder="exemplo@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

            <label>Palavra-passe</label>
            <input type="password" name="senha" class="login-input" placeholder="••••••••" required>

            <button type="submit" class="btn-login">ENTRAR</button>
        </form>

        <div class="login-links">
            <a href="cadastro.php">Não tenho uma conta — Criar conta</a>
            <a href="index.php">← Voltar ao Início</a>
        </div>
    </div>

    <footer class="login-footer">
        <p>Hina Inc. &copy; 2026 — Todos os direitos reservados.</p>
    </footer>

</body>
</html>