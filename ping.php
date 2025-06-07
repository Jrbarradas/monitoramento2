<?php
require_once "config.php";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ip = filter_input(INPUT_GET, 'ip', FILTER_VALIDATE_IP);

if (!$ip) {
    echo json_encode(['status' => 'invalid']);
    exit;
}

$command = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    ? "ping -n 2 -w 1000 " . escapeshellarg($ip)
    : "ping -c 2 -W 1 " . escapeshellarg($ip);

exec($command, $output, $result);

$status = ($result === 0) ? 'online' : 'offline';
echo json_encode(['status' => $status]);
?>
