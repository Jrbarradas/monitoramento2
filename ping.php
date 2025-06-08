<?php
require_once "config.php";

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

$ip = filter_input(INPUT_GET, 'ip', FILTER_VALIDATE_IP);

if (!$ip) {
    echo json_encode([
        'status' => 'invalid',
        'message' => 'Invalid IP address',
        'ip' => $_GET['ip'] ?? 'not provided'
    ]);
    exit;
}

// Enhanced ping function
function performPing($ip) {
    $output = [];
    $result = -1;
    $startTime = microtime(true);
    
    // Use different ping commands based on OS
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = "ping -n 3 -w 1000 " . escapeshellarg($ip);
    } else {
        $command = "ping -c 3 -W 1 " . escapeshellarg($ip);
    }
    
    exec($command, $output, $result);
    $endTime = microtime(true);
    
    // Calculate latency
    $latency = round(($endTime - $startTime) * 1000, 2);
    
    // Determine status
    $status = 'offline';
    $packetLoss = 100;
    
    if ($result === 0) {
        $outputString = implode(' ', $output);
        
        // Extract packet loss percentage
        if (preg_match('/(\d+)% packet loss/', $outputString, $matches)) {
            $packetLoss = (int)$matches[1];
        }
        
        // Extract average latency if available
        if (preg_match('/time[<=](\d+\.?\d*)/', $outputString, $matches)) {
            $latency = (float)$matches[1];
        }
        
        // Consider online if packet loss is less than 100%
        if ($packetLoss < 100) {
            $status = 'online';
        }
    }
    
    return [
        'status' => $status,
        'latency' => $latency,
        'packet_loss' => $packetLoss,
        'ip' => $ip,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

try {
    $result = performPing($ip);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ping failed: ' . $e->getMessage(),
        'ip' => $ip
    ]);
}
?>