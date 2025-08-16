<?php
$host = "localhost";
$user = "root"; // ou seu usuário
$password = "Kangoo.2010"; // sua senha
$database = "monitoramento";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Dados da Tabela Links</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th><th>IP</th><th>Endereço</th><th>Cidade</th><th>UF</th><th>Contato</th><th>Atualizado em</th><th>Lat</th><th>Lon</th></tr>";

    $stmt = $conn->query("SELECT * FROM links");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nome'] . "</td>";
        echo "<td>" . $row['ip'] . "</td>";
        echo "<td>" . $row['endereco'] . "</td>";
        echo "<td>" . $row['cidade'] . "</td>";
        echo "<td>" . $row['uf'] . "</td>";
        echo "<td>" . $row['contato'] . "</td>";
        echo "<td>" . $row['updated_at'] . "</td>";
        echo "<td>" . $row['lat'] . "</td>";
        echo "<td>" . $row['lon'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch(PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
