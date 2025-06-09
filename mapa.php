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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-dark: #0f172a;
            --secondary-dark: #1e293b;
            --accent-dark: #334155;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --border-color: #475569;
            --glass-bg: rgba(30, 41, 59, 0.8);
            --glass-border: rgba(148, 163, 184, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-primary);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 80px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .header h1 {
            flex-grow: 1;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            background: linear-gradient(135deg, var(--text-primary), var(--success));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .menu-toggle {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .menu-toggle::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .menu-toggle:hover::before {
            left: 100%;
        }

        .menu-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            border-color: var(--success);
        }

        .menu-icon {
            width: 24px;
            height: 18px;
            position: relative;
            transform: rotate(0deg);
            transition: 0.3s ease-in-out;
        }

        .menu-icon span {
            display: block;
            position: absolute;
            height: 2px;
            width: 100%;
            background: var(--text-primary);
            border-radius: 2px;
            opacity: 1;
            left: 0;
            transform: rotate(0deg);
            transition: 0.3s ease-in-out;
        }

        .menu-icon span:nth-child(1) { top: 0px; }
        .menu-icon span:nth-child(2) { top: 8px; }
        .menu-icon span:nth-child(3) { top: 16px; }

        .menu-toggle.active .menu-icon span:nth-child(1) {
            top: 8px;
            transform: rotate(135deg);
        }

        .menu-toggle.active .menu-icon span:nth-child(2) {
            opacity: 0;
            left: -60px;
        }

        .menu-toggle.active .menu-icon span:nth-child(3) {
            top: 8px;
            transform: rotate(-135deg);
        }

        .sidebar {
            position: fixed;
            left: -320px;
            top: 80px;
            height: calc(100vh - 80px);
            width: 320px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 999;
            overflow-y: auto;
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.3);
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-header {
            padding: 30px 25px 20px;
            border-bottom: 1px solid var(--glass-border);
            background: linear-gradient(135deg, var(--secondary-dark), var(--accent-dark));
        }

        .sidebar-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .sidebar-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            opacity: 0.8;
        }

        .nav-menu {
            padding: 25px 0;
        }

        .nav-item {
            margin: 0 15px 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.1), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            color: var(--text-primary);
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            transform: translateX(8px);
        }

        .nav-link.active {
            color: var(--success);
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .nav-icon {
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .nav-text {
            font-size: 1rem;
            letter-spacing: 0.025em;
        }

        .content {
            margin: 80px 0 0;
            height: calc(100vh - 80px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar.open ~ .content {
            margin-left: 320px;
        }

        #map { 
            height: 100%;
            width: 100%;
            border-radius: 0;
        }

        .map-controls {
            position: absolute;
            top: 100px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .map-control-btn {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.1rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .map-control-btn:hover {
            background: rgba(16, 185, 129, 0.2);
            border-color: var(--success);
            transform: scale(1.05);
        }

        .legend {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            z-index: 1000;
            min-width: 200px;
        }

        .legend-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .legend-marker {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .legend-marker.online {
            background: var(--success);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
        }

        .legend-marker.offline {
            background: var(--error);
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        .leaflet-popup-content-wrapper {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
            color: var(--text-primary) !important;
        }

        .leaflet-popup-content {
            color: var(--text-primary) !important;
            font-family: 'Inter', sans-serif !important;
        }

        .leaflet-popup-tip {
            background: var(--glass-bg) !important;
            border: 1px solid var(--glass-border) !important;
        }

        .popup-content {
            padding: 5px;
        }

        .popup-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .popup-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .popup-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 8px;
        }

        .popup-status.online {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .popup-status.offline {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error);
        }

        .status-counter {
            position: absolute;
            top: 100px;
            left: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            z-index: 1000;
            min-width: 180px;
        }

        .counter-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .counter-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .counter-label {
            color: var(--text-secondary);
        }

        .counter-value {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            min-width: 30px;
            text-align: center;
        }

        .counter-value.online {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .counter-value.offline {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error);
        }

        @media (max-width: 768px) {
            .sidebar.open ~ .content {
                margin-left: 0;
            }

            .map-controls {
                top: 90px;
                right: 10px;
            }

            .legend,
            .status-counter {
                position: relative;
                margin: 10px;
                width: calc(100% - 20px);
            }

            .legend {
                bottom: auto;
                left: auto;
            }

            .status-counter {
                top: auto;
                left: auto;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <button class="menu-toggle" id="menuToggle">
            <div class="menu-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
        <h1>Mapa da Rede</h1>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Menu Principal</div>
            <div class="sidebar-subtitle">Sistema de Monitoramento</div>
        </div>
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-home"></i></div>
                    <div class="nav-text">Dashboard</div>
                </a>
            </div>
            <div class="nav-item">
                <a href="cadastrar.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="nav-text">Cadastrar Link</div>
                </a>
            </div>
            <div class="nav-item">
                <a href="list_links.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-list"></i></div>
                    <div class="nav-text">Lista de Links</div>
                </a>
            </div>
            <div class="nav-item">
                <a href="teste_ping.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-network-wired"></i></div>
                    <div class="nav-text">Teste de Ping</div>
                </a>
            </div>
            <div class="nav-item">
                <a href="mapa.php" class="nav-link active">
                    <div class="nav-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <div class="nav-text">Mapa da Rede</div>
                </a>
            </div>
            <div class="nav-item">
                <a href="historico.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-history"></i></div>
                    <div class="nav-text">Histórico</div>
                </a>
            </div>
        </nav>
    </div>

    <div class="content">
        <div id="map"></div>
        
        <div class="status-counter">
            <div class="counter-title">Status da Rede</div>
            <div class="counter-item">
                <span class="counter-label">Online:</span>
                <span class="counter-value online" id="onlineCount">0</span>
            </div>
            <div class="counter-item">
                <span class="counter-label">Offline:</span>
                <span class="counter-value offline" id="offlineCount">0</span>
            </div>
            <div class="counter-item">
                <span class="counter-label">Total:</span>
                <span class="counter-value" id="totalCount">0</span>
            </div>
        </div>

        <div class="map-controls">
            <button class="map-control-btn" onclick="centerMap()" title="Centralizar Mapa">
                <i class="fas fa-crosshairs"></i>
            </button>
            <button class="map-control-btn" onclick="refreshData()" title="Atualizar Dados">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>

        <div class="legend">
            <div class="legend-title">Legenda</div>
            <div class="legend-item">
                <div class="legend-marker online"></div>
                <span>Links Online</span>
            </div>
            <div class="legend-item">
                <div class="legend-marker offline"></div>
                <span>Links Offline</span>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                menuToggle.classList.remove('active');
                sidebar.classList.remove('open');
            }
        });

        // Map initialization
        const map = L.map('map', {
            zoomControl: false
        }).setView([-14.2350, -51.9253], 4.5);
        
        // Dark tile layer
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap contributors, © CARTO',
            maxZoom: 19
        }).addTo(map);

        // Add zoom control to bottom right
        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);

        // Custom icons
        const onlineIcon = L.divIcon({
            className: 'custom-marker online-marker',
            html: '<div style="background: #10b981; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);"></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        const offlineIcon = L.divIcon({
            className: 'custom-marker offline-marker',
            html: '<div style="background: #ef4444; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(239, 68, 68, 0.6); animation: pulse-red 2s infinite;"></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        // Marker management
        let markers = new Map();
        let allLinks = [];

        async function updateNetworkFast() {
            try {
                const response = await fetch('api/status.php', {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const links = await response.json();
                allLinks = links;

                // Clear old markers efficiently
                markers.forEach(marker => map.removeLayer(marker));
                markers.clear();

                let onlineCount = 0;
                let offlineCount = 0;

                // Add new markers with batch processing
                const markersToAdd = [];
                
                links.forEach(link => {
                    const isOnline = link.status === 'online';
                    if (isOnline) onlineCount++;
                    else offlineCount++;

                    const marker = L.marker([link.lat, link.lon], {
                        icon: isOnline ? onlineIcon : offlineIcon
                    }).bindPopup(`
                        <div class="popup-content">
                            <div class="popup-title">${link.nome}</div>
                            <div class="popup-info"><i class="fas fa-network-wired"></i> ${link.ip}</div>
                            <div class="popup-info"><i class="fas fa-map-marker-alt"></i> ${link.cidade}, ${link.uf}</div>
                            <div class="popup-info"><i class="fas fa-user"></i> ${link.contato}</div>
                            <div class="popup-status ${link.status}">
                                <i class="fas fa-${isOnline ? 'check-circle' : 'exclamation-circle'}"></i>
                                ${link.status.toUpperCase()}
                            </div>
                        </div>
                    `);
                    
                    markersToAdd.push({ marker, id: link.id });
                });

                // Add all markers at once
                markersToAdd.forEach(({ marker, id }) => {
                    marker.addTo(map);
                    markers.set(id, marker);
                });

                // Update counters
                document.getElementById('onlineCount').textContent = onlineCount;
                document.getElementById('offlineCount').textContent = offlineCount;
                document.getElementById('totalCount').textContent = links.length;

                // Auto-fit bounds only on first load
                if (links.length > 0 && markers.size === links.length) {
                    const bounds = L.latLngBounds(links.map(link => [link.lat, link.lon]));
                    map.fitBounds(bounds, { padding: [50, 50] });
                }

            } catch (error) {
                console.error('Error updating network:', error);
            }
        }

        // Map control functions
        function centerMap() {
            if (allLinks.length > 0) {
                const bounds = L.latLngBounds(allLinks.map(link => [link.lat, link.lon]));
                map.fitBounds(bounds, { padding: [50, 50] });
            } else {
                map.setView([-14.2350, -51.9253], 4.5);
            }
        }

        function refreshData() {
            const refreshBtn = document.querySelector('.map-control-btn i.fa-sync-alt');
            refreshBtn.style.animation = 'spin 1s linear infinite';
            
            updateNetworkFast().then(() => {
                setTimeout(() => {
                    refreshBtn.style.animation = '';
                }, 1000);
            });
        }

        // Initialize with immediate update
        document.addEventListener('DOMContentLoaded', () => {
            updateNetworkFast();
            // Faster updates every 3 seconds
            setInterval(updateNetworkFast, 3000);
        });

        // Add spin animation for refresh button
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        // Preload critical resources
        const preloadLink = document.createElement('link');
        preloadLink.rel = 'preload';
        preloadLink.href = 'api/status.php';
        preloadLink.as = 'fetch';
        document.head.appendChild(preloadLink);
    </script>
</body>
</html>