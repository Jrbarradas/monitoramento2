<?php
require_once "config.php";

// Busca links offline
$stmt = $pdo->query("
    SELECT l.*, MAX(h.checked_at) AS last_check
    FROM links l
    JOIN historico_status h ON l.id = h.link_id
    WHERE h.status = 'offline'
    GROUP BY l.id
");
$offlineLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($offlineLinks) > 0) {
    $to = "monitlinks@spacecom.com.br";
    $subject = "ALERTA: Links Offline Detetados";
    
    $message = "<h1>Links Offline</h1><ul>";
    foreach($offlineLinks as $link) {
        $message .= "<li>{$link['nome']} ({$link['ip']}) - Última verificação: {$link['last_check']}</li>";
    }
    $message .= "</ul>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    
    mail($to, $subject, $message, $headers);
}
?>
