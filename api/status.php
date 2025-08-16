<?php
require_once __DIR__ . '/../config.php';

// Optimized headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Optimized ping function
function fastPing($ip) {
    $startTime = microtime(true);
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = "ping -n 1 -w 1000 " . escapeshellarg($ip) . " 2>nul";
    } else {
        $command = "ping -c 1 -W 2 " . escapeshellarg($ip) . " 2>/dev/null";
    }
    
    exec($command, $output, $result);
    $endTime = microtime(true);
    
    $latency = round(($endTime - $startTime) * 1000, 1);
    $status = ($result === 0) ? 'online' : 'offline';
    
    return [
        'status' => $status,
        'latency' => $latency
    ];
}

try {
    $stmt = $pdo->query("SELECT id, nome, ip, uf, lat, lon, cidade, contato, status, last_check FROM links ORDER BY uf, nome");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    foreach ($links as $link) {
        $pingResult = fastPing($link['ip']);
        
        // Insert into history table
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO historico_status (link_id, status, latency, checked_at) VALUES (?, ?, ?, NOW())");
            $stmtInsert->execute([$link['id'], $pingResult['status'], $pingResult['latency']]);
        } catch (PDOException $e) {
            error_log("History insert error: " . $e->getMessage());
        }
        
        try {
            $stmtUpdate = $pdo->prepare("UPDATE links SET status = ?, last_check = NOW() WHERE id = ?");
            $stmtUpdate->execute([$pingResult['status'], $link['id']]);
        } catch (PDOException $e) {
            error_log("Status update error: " . $e->getMessage());
        }
        
        $result[] = [
            'id' => (int)$link['id'],
            'nome' => $link['nome'],
            'ip' => $link['ip'],
            'status' => $pingResult['status'],
            'uf' => $link['uf'],
            'lat' => (float)$link['lat'],
            'lon' => (float)$link['lon'],
            'cidade' => $link['cidade'],
            'contato' => $link['contato'],
            'latency' => $pingResult['latency'],
            'last_check' => date('Y-m-d H:i:s')
        ];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Connection failed'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'Processing failed'
    ], JSON_UNESCAPED_UNICODE);
}
?>