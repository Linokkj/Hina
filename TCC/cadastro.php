<?php
session_start();
require 'conexao.php';

$fotoPadrao = 'https://i.pinimg.com/236x/f7/6c/34/f76c34510ed704c7c0f9a1423fc3008f.jpg';
$mensagem = "";
$sucesso = false;

$colsUsuarios = [];

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

    $colsUsuarios = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('nome', $colsUsuarios, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN nome VARCHAR(120) NOT NULL DEFAULT ''");
        $colsUsuarios = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
    }

    if (!in_array('senha', $colsUsuarios, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN senha VARCHAR(255) NOT NULL DEFAULT ''");
        $colsUsuarios = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
    }

    if (!in_array('tipo', $colsUsuarios, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT 'usuario'");
        $colsUsuarios = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
    }

    if (!in_array('foto_perfil', $colsUsuarios, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL");
        $colsUsuarios = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $mensagem = "Aviso na estrutura da base de dados: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmarSenha = trim($_POST['confirmar_senha'] ?? '');

    if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
        $mensagem = "Por favor, preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Por favor, informe um e-mail válido.";
    } elseif (strlen($senha) < 6) {
        $mensagem = "A palavra-passe deve ter pelo menos 6 caracteres.";
    } elseif ($senha !== $confirmarSenha) {
        $mensagem = "As palavras-passe não coincidem.";
    } else {
        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmtCheck->execute([$email]);

            if ($stmtCheck->fetch()) {
                $mensagem = "Este e-mail já está registado.";
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $campos = [];
                $placeholders = [];
                $valores = [];

                if (in_array('nome', $colsUsuarios, true)) {
                    $campos[] = 'nome';
                    $placeholders[] = '?';
                    $valores[] = $nome;
                }

                if (in_array('email', $colsUsuarios, true)) {
                    $campos[] = 'email';
                    $placeholders[] = '?';
                    $valores[] = $email;
                }

                if (in_array('senha', $colsUsuarios, true)) {
                    $campos[] = 'senha';
                    $placeholders[] = '?';
                    $valores[] = $senhaHash;
                }

                if (in_array('tipo', $colsUsuarios, true)) {
                    $campos[] = 'tipo';
                    $placeholders[] = '?';
                    $valores[] = 'usuario';
                }

                if (in_array('foto_perfil', $colsUsuarios, true)) {
                    $campos[] = 'foto_perfil';
                    $placeholders[] = '?';
                    $valores[] = $fotoPadrao;
                }

                $sqlInsert = "INSERT INTO usuarios (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute($valores);

                $sucesso = true;
                $mensagem = "Conta criada com sucesso! Já pode iniciar sessão.";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao criar conta: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Hina</title>
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

        <h2>Criar conta</h2>

        <?php if (!empty($mensagem)): ?>
            <div style="background: <?php echo $sucesso ? 'rgba(46,125,50,0.15)' : 'rgba(255,0,128,0.1)'; ?>; border: 1px solid <?php echo $sucesso ? '#2e7d32' : '#ff0080'; ?>; color: <?php echo $sucesso ? '#81c784' : '#ff0080'; ?>; padding: 10px; font-size: 13px; border-radius: 4px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($mensagem); ?>
                <?php if ($sucesso): ?>
                    <div style="margin-top: 8px;">
                        <a href="login.php" style="color: #ffffff; font-weight: bold; text-decoration: underline;">Ir para o login agora →</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST" class="login-form">
            <label>Nome</label>
            <input type="text" name="nome" class="login-input" placeholder="O seu nome" value="<?php echo $sucesso ? '' : htmlspecialchars($_POST['nome'] ?? ''); ?>" required>

            <label>E-mail</label>
            <input type="email" name="email" class="login-input" placeholder="exemplo@email.com" value="<?php echo $sucesso ? '' : htmlspecialchars($_POST['email'] ?? ''); ?>" required>

            <label>Palavra-passe</label>
            <input type="password" name="senha" class="login-input" placeholder="Mínimo 6 caracteres" required>

            <label>Confirmar palavra-passe</label>
            <input type="password" name="confirmar_senha" class="login-input" placeholder="Repita a palavra-passe" required>

            <button type="submit" class="btn-login">CRIAR CONTA</button>
        </form>

        <div class="login-links">
            <a href="login.php">Já tenho conta — Entrar</a>
            <a href="index.php">← Voltar ao Início</a>
        </div>
    </div>

    <footer class="login-footer">
        <p>Hina Inc. &copy; 2026 — Todos os direitos reservados.</p>
    </footer>

</body>
</html>