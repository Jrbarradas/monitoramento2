<?php
$host = "localhost";
$dbname = "monitoramento";
$username = "app_user";
$password = "Kangoo.2010"; // Substitua pela senha do MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage();
}
?>
