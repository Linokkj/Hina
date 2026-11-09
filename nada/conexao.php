<?php
$host = 'localhost';
$dbname = 'crunchytcc'; // Altere se o nome da sua base de dados for diferente
$username = 'root';     // Utilizador padrão do XAMPP/Laragon
$password = '';         // Senha padrão (geralmente vazia no local)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na ligação à base de dados: " . $e->getMessage());
}
?>