<?php
require_once "config.php";

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

function determineLinkStatus($ip) {
    // Configurações de verificação
    $pingCount = 4;
    $pingTimeout = 5;
    $acceptableLoss = 60; // Máximo de 50% de perda aceitável

    $command = "ping -c $pingCount -W $pingTimeout " . escapeshellarg($ip);
    exec($command, $output, $result);

    // Padrão para diferentes sistemas operacionais
    $pattern = '/(\d+)% packet loss/';
    $packetLoss = 100;

    foreach ($output as $line) {
        if (preg_match($pattern, $line, $matches)) {
            $packetLoss = (int)$matches[5];
            break;
        }
    }

    // Determinar status baseado na perda de pacotes
    if ($packetLoss <= $acceptableLoss) {
        return 'online';
    }
    
    // Verificação redundante para evitar falsos positivos
    exec($command, $output2, $result2); // Segunda verificação
    foreach ($output2 as $line) {
        if (preg_match($pattern, $line, $matches)) {
            $packetLoss = (int)$matches[5];
            break;
        }
    }

    return ($packetLoss <= $acceptableLoss) ? 'online' : 'offline';
}

// Sistema de cache simples
function getCachedStatus($id) {
    $cacheFile = 'status_cache.json';
    if (!file_exists($cacheFile)) return null;
    
    $cache = json_decode(file_get_contents($cacheFile), true);
    return $cache[$id] ?? null;
}

function updateStatusCache($id, $status) {
    $cacheFile = 'status_cache.json';
    $cache = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : [];
    $cache[$id] = [
        'status' => $status,
        'last_check' => date('Y-m-d H:i:s')
    ];
    file_put_contents($cacheFile, json_encode($cache));
}

$links = $pdo->query("SELECT * FROM links")->fetchAll(PDO::FETCH_ASSOC);
$result = [];

foreach ($links as $link) {
    try {
        $currentStatus = determineLinkStatus($link['ip']);
        $cachedStatus = getCachedStatus($link['id']);
        
        // Só envia alerta se o status mudar
        if (!$cachedStatus || $cachedStatus['status'] !== $currentStatus) {
            updateStatusCache($link['id'], $currentStatus);
            
            // Aqui você pode adicionar a chamada para o webhook do Google Chat
            // sendGoogleChatAlert($link, $currentStatus);
        }

        $result[] = [
            'id' => $link['id'],
            'nome' => $link['nome'],
            'ip' => $link['ip'],
            'lat' => (float)$link['lat'],
            'lon' => (float)$link['lon'],
            'cidade' => $link['cidade'],
            'uf' => $link['uf'],
            'contato' => $link['contato'],
            'status' => $currentStatus,
            'last_check' => date('H:i:s')
        ];

    } catch (Exception $e) {
        // Fallback para status em cache em caso de erro
        $cached = getCachedStatus($link['id']);
        $result[] = [
            'id' => $link['id'],
            'status' => $cached['status'] ?? 'error',
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode($result);
?>
