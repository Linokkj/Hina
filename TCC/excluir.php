<?php
require 'conexao.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Apaga os registos dependentes nas tabelas filhas primeiro
        $stmtEp = $pdo->prepare("DELETE FROM episodios WHERE anime_id = ?");
        $stmtEp->execute([$id]);

        $stmtCom = $pdo->prepare("DELETE FROM comentarios_anime WHERE anime_id = ?");
        $stmtCom->execute([$id]);

        // 2. Apaga o anime selecionado
        $stmt = $pdo->prepare("DELETE FROM animes WHERE id = ?");
        $stmt->execute([$id]);

        // 3. REORGANIZAÇÃO AUTOMÁTICA DOS IDs EM SQL PURO
        // Desativa chaves estrangeiras temporariamente
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Reatribui IDs sequenciais (1, 2, 3...) aos registos restantes
        $pdo->exec("SET @i = 0");
        $pdo->exec("UPDATE animes SET id = (@i := @i + 1) ORDER BY id ASC");

        // Atualiza o AUTO_INCREMENT para o próximo número correto
        $proximoId = $pdo->query("SELECT MAX(id) FROM animes")->fetchColumn() + 1;
        $pdo->exec("ALTER TABLE animes AUTO_INCREMENT = " . $proximoId);

        // Reativa as chaves estrangeiras
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    } catch (PDOException $e) {
        // Garante que reativa as chaves estrangeiras mesmo se houver erro
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "<div style='background: #2a0808; color: #ff4757; padding: 20px; font-family: monospace; border: 1px solid #ff4757;'>";
        echo "<strong>Erro ao excluir e reordenar:</strong> " . htmlspecialchars($e->getMessage());
        echo "<br><br><a href='painel.php' style='color: #fff;'>Voltar ao Painel</a>";
        echo "</div>";
        exit;
    }
}

header('Location: painel.php');
exit;
?>