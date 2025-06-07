<?php
require_once "config.php";

$links = $pdo->query("SELECT * FROM links")->fetchAll(PDO::FETCH_ASSOC);
$response = [
    'online' => 0,
    'offline' => 0,
    'offlineLinks' => []
];

foreach ($links as $link) {
    $ping = shell_exec("ping -c 4 " . escapeshellarg($link['ip']));
    if(strpos($ping, "0 received") === false) {
        $response['online']++;
    } else {
        $response['offline']++;
        $response['offlineLinks'][] = [
            'nome' => $link['nome'],
            'ip' => $link['ip'],
            'cidade' => $link['cidade'],
            'uf' => $link['uf'],
            'contato' => $link['contato']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
