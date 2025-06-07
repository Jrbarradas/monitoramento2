<?php
// api/status.php
require_once __DIR__ . '/../config.php';

// Função de ping simplificada e confiável
function ping($ip) {
    $output = [];
    $result = -1;
    exec("ping -c 1 -W 1 " . escapeshellarg($ip), $output, $result);
    return $result === 0;
}

try {
    // Buscar todos os links
    $stmt = $pdo->query("SELECT * FROM links");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    foreach ($links as $link) {
        $status = ping($link['ip']) ? 'online' : 'offline';
        
        // Inserir no histórico (com tratamento de erro)
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO historico_status (link_id, status) VALUES (?, ?)");
            $stmtInsert->execute([$link['id'], $status]);
        } catch (PDOException $e) {
            error_log("Erro ao inserir histórico: " . $e->getMessage());
        }
        
        $result[] = [
            'id' => $link['id'],
            'nome' => $link['nome'],
            'ip' => $link['ip'],
            'status' => $status,
            'uf' => $link['uf'],
            'lat' => $link['lat'],
            'lon' => $link['lon']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
