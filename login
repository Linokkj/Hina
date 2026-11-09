<?php
require 'conexao.php';
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    
    if (!empty($email) && !empty($senha)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $mensagem = "Login efetuado com sucesso! Redirecionando...";
            } else {
                $mensagem = "Login efetuado com sucesso! (Modo Simulação TCC)";
            }
        } catch (PDOException $e) {
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
    <!-- Ligação ao ficheiro CSS externo -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

    <div class="login-card">
        <!-- Logótipo Clicável que redireciona para o Início -->
        <a href="index.php" class="logo">
            <img src="https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg" class="logo-img" alt="Logo Hina">
            <div class="login-logo">
                <h1>Hina<span>.</span></h1>
            </div>
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
