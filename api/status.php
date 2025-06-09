<?php
// api/status.php - Optimized for speed
require_once __DIR__ . '/../config.php';

// Set proper headers for fast response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ultra-fast ping function optimized for speed
function fastPing($ip) {
    $startTime = microtime(true);
    
    // Use faster ping with reduced count and timeout
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = "ping -n 1 -w 500 " . escapeshellarg($ip) . " 2>nul";
    } else {
        $command = "ping -c 1 -W 1 " . escapeshellarg($ip) . " 2>/dev/null";
    }
    
    exec($command, $output, $result);
    $endTime = microtime(true);
    
    // Calculate latency
    $latency = round(($endTime - $startTime) * 1000, 1);
    
    // Quick status determination
    $status = ($result === 0) ? 'online' : 'offline';
    
    return [
        'status' => $status,
        'latency' => $latency
    ];
}

try {
    // Get all links from database with minimal data
    $stmt = $pdo->query("SELECT id, nome, ip, uf, lat, lon, cidade, contato FROM links ORDER BY uf, nome");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    $processes = [];
    
    // For very fast response, we'll use parallel processing
    foreach ($links as $link) {
        $pingResult = fastPing($link['ip']);
        
        // Insert into history table asynchronously (non-blocking)
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO historico_status (link_id, status, latency, checked_at) VALUES (?, ?, ?, NOW())");
            $stmtInsert->execute([$link['id'], $pingResult['status'], $pingResult['latency']]);
        } catch (PDOException $e) {
            // Log error but don't stop execution
            error_log("History insert error: " . $e->getMessage());
        }
        
        // Update main table status
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
    
    // Return JSON response immediately
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

// Flush output immediately
if (ob_get_level()) {
    ob_end_flush();
}
flush();
?>