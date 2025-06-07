<?php
require_once "config.php";

function geocode($endereco, $cidade, $uf) {
    $query = urlencode("$endereco, $cidade, $uf, Brasil");
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}";
    
    // Configura cabeçalho para evitar bloqueio
    $options = [
        'http' => ['header' => "User-Agent: SpacecomMonitoramento\r\n"]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    if(!empty($data)) {
        return [
            'lat' => $data[0]['lat'],
            'lon' => $data[0]['lon']
        ];
    }
    return null;
}

// Busca links sem coordenadas ou com coordenadas padrão
$stmt = $pdo->query("SELECT * FROM links WHERE lat = -15.780100 OR lon = -47.929200");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($links as $link) {
    $coords = geocode($link['endereco'], $link['cidade'], $link['uf']);
    
    if($coords) {
        $update = $pdo->prepare("UPDATE links SET lat = ?, lon = ? WHERE id = ?");
        $update->execute([$coords['lat'], $coords['lon'], $link['id']]);
    }
}

echo "Coordenadas atualizadas para " . count($links) . " links";
?>
