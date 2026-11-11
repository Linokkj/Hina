<?php
require 'conexao.php';

try {
    // Busca todos os títulos na base de dados
    $stmt = $pdo->query("SELECT id, titulo, ano_lancamento FROM animes ORDER BY titulo ASC");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Lista de Todos os Animes Registados:</h2>";
    if (!empty($animes)) {
        echo "<ul>";
        foreach ($animes as $anime) {
            echo "<li><strong>ID {$anime['id']}</strong> - " . htmlspecialchars($anime['titulo']) . " (" . ($anime['ano_lancamento'] ?? 'Ano N/D') . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Ainda não tens nenhum anime registado na base de dados.</p>";
    }
} catch (PDOException $e) {
    echo "Erro ao consultar a base de dados: " . $e->getMessage();
}
?>