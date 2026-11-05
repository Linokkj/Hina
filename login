<?php
// Podes incluir a ligação à base de dados mais tarde para validar utilizadores
require 'conexao.php';
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Lógica futura de autenticação para o TCC
    if (!empty($email) && !empty($senha)) {
        $mensagem = "Login efetuado com sucesso!";
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
    <style>
        .login-body {
            background-color: #141415;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            color: #ffffff;
        }

        /* Ajustado para alinhar a imagem e o texto lado a lado e centrados */
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .logo-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .login-logo h1 {
            font-size: 28px;
            margin: 0;
            letter-spacing: 1px;
            color: #ffffff;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            text-align: center;
            padding: 20px;
        }

        .login-card h2 {
            font-size: 24px;
            margin-bottom: 25px;
            font-weight: normal;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .login-input {
            width: 100%;
            padding: 14px;
            background-color: #23252b;
            border: 1px solid #3f4254;
            border-radius: 4px;
            color: #ffffff;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }

        .login-input:focus {
            border-color: #ff0080;
        }

        .btn-login {
            background-color: transparent;
            color: #ffffff;
            border: 2px solid #ff0080;
            padding: 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: #ff0080;
            color: #ffffff;
        }

        .login-links {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .login-links a {
            color: #ff0080;
            text-decoration: none;
        }

        .login-links a:hover {
            text-decoration: underline;
        }

        .login-footer {
            margin-top: 60px;
            font-size: 12px;
            color: #777777;
            text-align: center;
            width: 100%;
            position: absolute;
            bottom: 20px;
        }
    </style>
</head>
<body class="login-body">

    <div class="login-card">
        <!-- Logótipo Hina com classes unificadas para alinhamento -->
        <div class="login-logo logo">
            <img src="https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg" class="logo-img" alt="Logo Hina">
            <h1>Hina<span>.</span></h1>
        </div>

        <h2>Entrar</h2>

        <?php if (!empty($mensagem)): ?>
            <p style="color: #ff0080; font-size: 14px; margin-bottom: 15px;"><?php echo $mensagem; ?></p>
        <?php endif; ?>

        <!-- Formulário de Login -->
        <form action="login.php" method="POST" class="login-form">
            <input type="email" name="email" class="login-input" placeholder="E-mail ou Número de Telemóvel" required>
            <input type="password" name="senha" class="login-input" placeholder="Palavra-passe" required>
            
            <button type="submit" class="btn-login">PRÓXIMO</button>
        </form>

        <div class="login-links">
            <a href="#">Esqueceu a palavra-passe?</a>
            <a href="painel.php">Criar conta / Painel</a>
        </div>
    </div>

    <footer class="login-footer">
        <p>Hina Inc. &copy; 2026 — Todos os direitos reservados.</p>
    </footer>

</body>
</html>
