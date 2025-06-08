<?php
// api/status.php
require_once __DIR__ . '/../config.php';

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enhanced ping function with better reliability
function ping($ip) {
    $output = [];
    $result = -1;
    
    // Use different ping commands based on OS
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = "ping -n 2 -w 1000 " . escapeshellarg($ip);
    } else {
        $command = "ping -c 2 -W 1 " . escapeshellarg($ip);
    }
    
    exec($command, $output, $result);
    
    // More reliable status detection
    if ($result === 0) {
        // Check for packet loss patterns
        $outputString = implode(' ', $output);
        if (preg_match('/(\d+)% packet loss/', $outputString, $matches)) {
            $packetLoss = (int)$matches[1];
            return $packetLoss < 100; // Consider online if less than 100% packet loss
        }
        return true;
    }
    
    return false;
}

try {
    // Get all links from database
    $stmt = $pdo->query("SELECT * FROM links ORDER BY nome");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    foreach ($links as $link) {
        $startTime = microtime(true);
        $status = ping($link['ip']) ? 'online' : 'offline';
        $endTime = microtime(true);
        
        // Calculate latency in milliseconds
        $latency = round(($endTime - $startTime) * 1000, 2);
        
        // Insert into history table with error handling
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO historico_status (link_id, status, latency, checked_at) VALUES (?, ?, ?, NOW())");
            $stmtInsert->execute([$link['id'], $status, $latency]);
        } catch (PDOException $e) {
            error_log("Error inserting history: " . $e->getMessage());
        }
        
        // Update the main links table with current status
        try {
            $stmtUpdate = $pdo->prepare("UPDATE links SET status = ?, last_check = NOW() WHERE id = ?");
            $stmtUpdate->execute([$status, $link['id']]);
        } catch (PDOException $e) {
            error_log("Error updating link status: " . $e->getMessage());
        }
        
        $result[] = [
            'id' => (int)$link['id'],
            'nome' => $link['nome'],
            'ip' => $link['ip'],
            'status' => $status,
            'uf' => $link['uf'],
            'lat' => (float)$link['lat'],
            'lon' => (float)$link['lon'],
            'cidade' => $link['cidade'],
            'contato' => $link['contato'],
            'endereco' => $link['endereco'],
            'latency' => $latency,
            'last_check' => date('Y-m-d H:i:s')
        ];
    }
    
    // Return JSON response
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>