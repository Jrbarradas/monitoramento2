<?php
require_once "config.php";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Rede - Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f8f9fa;
            margin: 0;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 70px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
        }

        .header h1 {
            flex-grow: 1;
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .botao-menu {
            background: rgba(255,255,255,0.1);
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
            margin-right: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .botao-menu:hover {
            background: rgba(255,255,255,0.2);
        }

	.botao-menu__linha {
            width: 22px;
            height: 2px;
            background: white;
            transition: 0.3s;
            position: absolute;
        }

        .botao-menu__linha:nth-child(1) { transform: translateY(-8px); }
        .botao-menu__linha:nth-child(3) { transform: translateY(8px); }

        .menu-lateral {
            position: fixed;
            left: -250px;
            top: 70px;
            height: calc(100% - 120px);
            width: 250px;
            background: rgba(52, 73, 94, 0.95);
            backdrop-filter: blur(15px);
            transition: 0.3s;
            z-index: 999;
            padding-top: 25px;
            overflow-y: auto;
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        .menu-lateral.aberto {
            left: 0;
        }

        .menu-lateral nav {
            display: flex;
            flex-direction: column;
            padding: 1rem;
        }

        .menu-lateral a {
            color: white;
            text-decoration: none;
            padding: 1rem 1.5rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
            background: rgba(255,255,255,0.05);
        }

        .menu-lateral a:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(10px);
        }

        .conteudo {
            margin: 70px 0 0;
            height: calc(100vh - 70px);
        }

        .menu-aberto .conteudo {
            margin-left: 280px;
        }

        #map { 
            height: 100vh;
            width: 100%;
        }

	.offline-marker {
	    animation: blink 1.2s infinite;
	}

	@keyframes blink {
	    0% { opacity: 0.8; }
	    50% { opacity: 0.4; }
	    100% { opacity: 0.8; }

        .rodape {
            position: fixed;
            bottom: 0;
            z-index: 1000;
            left: 0;
            right: 0;
            background: rgba(44, 62, 80, 0.95);
            color: #ecf0f1;
            padding: 18px 25px;
            font-size: 0.95rem;
            text-align: center;
            backdrop-filter: blur(8px);
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .leaflet-marker-icon {
            background: transparent;
            border: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Mapa de Rede</h1>
    </div>

    <div class="conteudo">
        <div id="map"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Centralização do Brasil
        const map = L.map('map').setView([-14.2350, -51.9253], 4.5);
        
        // Camada de satélite hibrida
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Ícones personalizados
        const onlineIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41]
        });

        const offlineIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41]
        });

        // Gerenciamento de Marcadores
        let markers = new Map();

        async function updateNetwork() {
            try {
                const response = await fetch('api/status.php');
                const links = await response.json();

                // Limpar marcadores antigos
                markers.forEach(marker => map.removeLayer(marker));
                markers.clear();

                // Adicionar novos marcadores
                links.forEach(link => {
                    const marker = L.marker([link.lat, link.lon], {
                        icon: link.status === 'online' ? onlineIcon : offlineIcon,
                        className: link.status === 'online' ? '' : 'offline-marker'
                    }).bindPopup(`
                        <b>${link.nome}</b><br>
                        <small>${link.ip}</small><br>
                        Status: <strong class="${link.status}">${link.status.toUpperCase()}</strong>
                    `).addTo(map);
                    
                    markers.set(link.id, marker);
                });

                // Ajuste de zoom para melhor visualização
                if(links.length > 0) {
                    const bounds = L.latLngBounds(links.map(link => [link.lat, link.lon]));
                    map.fitBounds(bounds, {padding: [50, 50]});
                }

            } catch (error) {
                console.error('Erro na atualização:', error);
            }
        }

        // Atualizar a cada 5 segundos (mesmo intervalo do index.php)
        setInterval(updateNetwork, 5000);
        updateNetwork();


    </script>
</body>
</html>
