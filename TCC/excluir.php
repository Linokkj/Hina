<?php
require 'conexao.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        // 1. Apaga o anime selecionado
        $stmt = $pdo->prepare("DELETE FROM animes WHERE id = ?");
        $stmt->execute([$id]);

        // 2. Verifica se ainda existem animes na tabela
        $check = $pdo->query("SELECT COUNT(*) FROM animes");
        $total = $check->fetchColumn();

        // 3. Se a tabela ficou vazia, reseta o AUTO_INCREMENT para 1 automaticamente
        if ($total == 0) {
            $pdo->exec("ALTER TABLE animes AUTO_INCREMENT = 1");
        }

    } catch (PDOException $e) {
        // Tratamento de erro (ex: se houver episódios vinculados)
    }
}

header('Location: painel.php');
exit;
?>