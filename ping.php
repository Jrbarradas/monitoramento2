<?php
require_once "config.php";

header('Content-Type: application/json');
header('Cache-Control: no-cache, max-age=0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$ip = filter_input(INPUT_GET, 'ip', FILTER_VALIDATE_IP);

if (!$ip) {
    http_response_code(400);
    echo json_encode([
        'status' => 'invalid',
        'message' => 'Endereço IP inválido',
        'ip' => $_GET['ip'] ?? ''
    ]);
    exit;
}

// Optimized ping function
function performPing($ip) {
    $startTime = microtime(true);
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = "ping -n 2 -w 2000 " . escapeshellarg($ip) . " 2>nul";
    } else {
        $command = "ping -c 2 -W 2 " . escapeshellarg($ip) . " 2>/dev/null";
    }
    
    $output = [];
    exec($command, $output, $result);
    $endTime = microtime(true);
    
    $latency = round(($endTime - $startTime) * 1000, 1);
    $status = ($result === 0) ? 'online' : 'offline';
    $packetLoss = ($result === 0) ? 0 : 100;
    
    // Extract more detailed info if online
    if ($status === 'online') {
        $outputString = implode(' ', $output);
        if (preg_match('/(\d+)% packet loss/', $outputString, $matches)) {
            $packetLoss = (int)$matches[1];
        }
        if (preg_match('/time[<=](\d+\.?\d*)\s*ms/', $outputString, $matches)) {
            $latency = (float)$matches[1];
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
    
    // Log ping result for monitoring
    error_log("Ping result for $ip: " . json_encode($result));
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha na verificação: ' . $e->getMessage(),
        'ip' => $ip
    ]);
}
?>