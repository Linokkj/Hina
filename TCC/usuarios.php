<?php
session_start();
require 'conexao.php';

$mensagem = "";

if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_negado') {
    $mensagem = "Acesso restrito ao administrador. Faça login com uma conta de admin.";
}

// Garante que a tabela usuarios exista para evitar erro #1146
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            tipo VARCHAR(30) NOT NULL DEFAULT 'usuario',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $totalUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    if ($totalUsuarios === 0) {
        $hashAdmin = password_hash('admin123', PASSWORD_DEFAULT);
        $stmtSeed = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $stmtSeed->execute(['Administrador', 'admin@hina.local', $hashAdmin, 'admin']);
    }
} catch (PDOException $e) {
    $mensagem = "Aviso na tabela de usuários: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!empty($email) && !empty($senha)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha, (string)($usuario['senha'] ?? ''))) {
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
                $_SESSION['tipo'] = $isAdmin ? 'admin' : ($perfil !== '' ? $perfil : 'usuario');
                $_SESSION['is_admin'] = $isAdmin;

                if ($isAdmin) {
                    header('Location: painel.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $_SESSION['is_admin'] = false;
                $mensagem = "E-mail ou palavra-passe incorretos.";
            }
        } catch (PDOException $e) {
            $_SESSION['is_admin'] = false;
            $mensagem = "Erro na autenticação: " . $e->getMessage();
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos.";
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
            <label>E-mail ou Telemóvel</label>
            <input type="email" name="email" class="login-input" placeholder="exemplo@email.com" required>

            <label>Palavra-passe</label>
            <input type="password" name="senha" class="login-input" placeholder="••••••••" required>

            <button type="submit" class="btn-login">ENTRAR</button>
        </form>

        <div class="login-links">
            <a href="index.php">← Voltar ao Início</a>
            <a href="painel.php">Painel Admin</a>
        </div>
    </div>

    <footer class="login-footer">
        <p>Hina Inc. &copy; 2026 — Todos os direitos reservados.</p>
    </footer>

</body>
</html>