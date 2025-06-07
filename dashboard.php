<?php
require_once "config.php";

// Dados para os gráficos
$estados = $pdo->query("
    SELECT uf, 
           COUNT(*) AS total,
           SUM(status = 'online') AS online,
           SUM(status = 'offline') AS offline
    FROM links
    GROUP BY uf
")->fetchAll(PDO::FETCH_ASSOC);

$topOffline = $pdo->query("
    SELECT l.nome, l.ip, COUNT(h.id) AS offline_count
    FROM historico_status h
    JOIN links l ON l.id = h.link_id
    WHERE h.status = 'offline'
    GROUP BY l.id
    ORDER BY offline_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Gráfico de disponibilidade por estado -->
<canvas id="estadosChart"></canvas>

<!-- Tabela de links mais problemáticos -->
<table class="problemas-table">
    <thead>
        <tr>
            <th>Link</th>
            <th>IP</th>
            <th>Qtd. Falhas</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($topOffline as $row): ?>
        <tr>
            <td><?= $row['nome'] ?></td>
            <td><?= $row['ip'] ?></td>
            <td><?= $row['offline_count'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    // Gráfico de barras para estados
    new Chart(document.getElementById('estadosChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($estados, 'uf')) ?>,
            datasets: [{
                label: 'Online',
                data: <?= json_encode(array_column($estados, 'online')) ?>,
                backgroundColor: '#28a745'
            }, {
                label: 'Offline',
                data: <?= json_encode(array_column($estados, 'offline')) ?>,
                backgroundColor: '#dc3545'
            }]
        }
    });
</script>
