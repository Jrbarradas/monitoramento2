<?php
require_once "config.php";
$links = $pdo->query("SELECT * FROM links")->fetchAll(PDO::FETCH_ASSOC);

$estados = [];
foreach ($links as $link) {
    $uf = $link['uf'];
    $estados[$uf][] = $link;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Monitoramento de Links Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            overflow-x: hidden;
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

        .status-container {
            display: flex;
            gap: 20px;
            margin-right: 60px;
            align-items: center;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            border-radius: 12px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .status-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .status-item:hover::before {
            left: 100%;
        }

        .status-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }

        .status-item.online {
            border-color: var(--success);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
        }

        .status-item.offline {
            border-color: var(--error);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }

        .status-item i {
            font-size: 1.2rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .status-item.online i {
            color: var(--success);
        }

        .status-item.offline i {
            color: var(--error);
        }

        .status-item span {
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.025em;
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
            margin: 120px 30px 100px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .sidebar.open ~ .content {
            margin-left: 350px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .state-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .state-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: left 0.6s;
        }

        .state-card:hover::before {
            left: 100%;
        }

        .state-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .state-card.online {
            border-color: var(--success);
            box-shadow: 0 5px 20px rgba(16, 185, 129, 0.2);
        }

        .state-card.offline {
            border-color: var(--error);
            box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3);
            animation: pulse-error 1.5s infinite;
        }

        @keyframes pulse-error {
            0% {
                box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3);
                border-color: var(--error);
            }
            50% {
                box-shadow: 0 5px 30px rgba(255, 0, 0, 0.6);
                border-color: #ff0000;
            }
            100% {
                box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3);
                border-color: var(--error);
            }
        }

        .alert-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #ff0000;
            font-size: 1.2rem;
            animation: blink 1s infinite;
            display: none;
        }

        .state-card.offline .alert-icon {
            display: block;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .state-name {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 8px;
        }

        .state-card.online .state-name {
            color: var(--success);
        }

        .state-card.offline .state-name {
            color: var(--error);
        }

        .state-info {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-overlay.show {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .modal-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .modal-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 18px 20px;
            background: var(--secondary-dark);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .modal-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.1), transparent);
            transition: left 0.5s;
        }

        .modal-btn:hover::before {
            left: 100%;
        }

        .modal-btn:hover {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .modal-btn i {
            font-size: 1.2rem;
            color: var(--success);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .modal-close:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.1);
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            color: var(--text-secondary);
            padding: 20px 30px;
            font-size: 0.9rem;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            z-index: 1000;
        }

        .update-counter {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--success);
            font-weight: 500;
        }

        .updating {
            opacity: 0.7;
            transform: scale(0.98);
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.4rem;
            }

            .status-container {
                margin-right: 0;
                gap: 15px;
            }

            .status-item {
                padding: 8px 12px;
            }

            .content {
                margin: 100px 15px 80px;
            }

            .sidebar.open ~ .content {
                margin-left: 15px;
            }

            .cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                padding: 10px;
            }

            .modal {
                margin: 20px;
                padding: 25px;
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
        <h1>Monitoramento de Links Spacecom</h1>
        <div class="status-container">
            <div class="status-item online">
                <i class="fas fa-link"></i>
                <span id="total-online">0</span>
            </div>
            <div class="status-item offline">
                <i class="fas fa-unlink"></i>
                <span id="total-offline">0</span>
            </div>
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Menu Principal</div>
            <div class="sidebar-subtitle">Sistema de Monitoramento</div>
        </div>
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="index.php" class="nav-link active">
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
                <a href="mapa.php" class="nav-link">
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
        <div class="cards-grid">
            <?php foreach ($estados as $uf => $linksEstado): ?>
                <div class="state-card" 
                     data-uf="<?= htmlspecialchars($uf) ?>"
                     data-ips="<?= htmlspecialchars(json_encode(array_column($linksEstado, 'ip'))) ?>"
                     data-link-ids="<?= htmlspecialchars(json_encode(array_column($linksEstado, 'id'))) ?>">
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                    <div class="state-name"><?= htmlspecialchars($uf) ?></div>
                    <div class="state-info"><?= count($linksEstado) ?> link(s)</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Estado: SP</div>
                <div class="modal-subtitle">Escolha uma opção</div>
            </div>
            <div class="modal-actions">
                <a href="#" class="modal-btn" id="detailsBtn">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <div>Detalhes</div>
                        <small style="opacity: 0.7;">Ver status dos links</small>
                    </div>
                </a>
                <a href="#" class="modal-btn" id="historyBtn">
                    <i class="fas fa-history"></i>
                    <div>
                        <div>Histórico</div>
                        <small style="opacity: 0.7;">Consultar histórico</small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="footer">
        <span>Spacecom Monitoramento S/A © 2025</span>
        <span class="update-counter" id="updateCounter">Atualizando em: 2s</span>
    </div>

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

        // Modal functionality
        const modalOverlay = document.getElementById('modalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const detailsBtn = document.getElementById('detailsBtn');
        const historyBtn = document.getElementById('historyBtn');
        let currentUf = '';
        let currentLinkIds = [];

        function openModal(uf, linkIds) {
            currentUf = uf;
            currentLinkIds = linkIds;
            modalTitle.textContent = `Estado: ${uf}`;
            detailsBtn.href = `detalhes_estado.php?uf=${encodeURIComponent(uf)}`;
            historyBtn.href = `historico.php?uf=${encodeURIComponent(uf)}&link_ids=${encodeURIComponent(linkIds.join(','))}`;
            modalOverlay.classList.add('show');
        }

        function closeModal() {
            modalOverlay.classList.remove('show');
        }

        // Close modal when clicking overlay
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Card click handlers
        document.querySelectorAll('.state-card').forEach(card => {
            card.addEventListener('click', () => {
                const uf = card.dataset.uf;
                const linkIds = JSON.parse(card.dataset.linkIds);
                openModal(uf, linkIds);
            });
        });

        // Fast status monitoring system
        let updateTime = 2;
        let updateInterval;
        let isUpdating = false;
        
        const stateCards = {};
        document.querySelectorAll('.state-card').forEach(card => {
            const uf = card.dataset.uf;
            stateCards[uf] = card;
        });

        // Optimized status checking with parallel requests
        async function checkStatusFast() {
            if (isUpdating) return;
            isUpdating = true;
            
            try {
                // Add updating class to all cards
                Object.values(stateCards).forEach(card => {
                    card.classList.add('updating');
                });
                
                // Use faster endpoint with minimal data
                const response = await fetch('api/status.php', {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const links = await response.json();
                
                const statusByState = {};
                let totalOnline = 0;
                let totalOffline = 0;
                
                // Process results quickly
                links.forEach(link => {
                    const uf = link.uf;
                    if (!statusByState[uf]) {
                        statusByState[uf] = {
                            online: 0,
                            offline: 0,
                            hasOffline: false
                        };
                    }
                    
                    if (link.status === 'online') {
                        statusByState[uf].online++;
                        totalOnline++;
                    } else {
                        statusByState[uf].offline++;
                        statusByState[uf].hasOffline = true;
                        totalOffline++;
                    }
                });
                
                // Update UI immediately
                for (const uf in statusByState) {
                    const card = stateCards[uf];
                    if (card) {
                        const hasOffline = statusByState[uf].hasOffline;
                        
                        card.classList.remove('online', 'offline', 'updating');
                        card.classList.add(hasOffline ? 'offline' : 'online');
                        
                        const infoElement = card.querySelector('.state-info');
                        if (infoElement) {
                            const total = statusByState[uf].online + statusByState[uf].offline;
                            const offlineText = statusByState[uf].offline > 0 ? 
                                ` (${statusByState[uf].offline} offline)` : '';
                            infoElement.textContent = `${total} link(s)${offlineText}`;
                        }
                    }
                }
                
                // Update global counters
                document.getElementById('total-online').textContent = totalOnline;
                document.getElementById('total-offline').textContent = totalOffline;
                
            } catch (error) {
                console.error('Error checking status:', error);
                // Remove updating class even on error
                Object.values(stateCards).forEach(card => {
                    card.classList.remove('updating');
                });
            } finally {
                isUpdating = false;
            }
        }

        function startUpdateCycle() {
            updateInterval = setInterval(() => {
                updateTime--;
                document.getElementById('updateCounter').textContent = `Atualizando em: ${updateTime}s`;
                
                if (updateTime <= 0) {
                    updateTime = 2; // Faster updates every 2 seconds
                    checkStatusFast();
                }
            }, 1000);
        }

        // Initialize with immediate check
        document.addEventListener('DOMContentLoaded', async () => {
            await checkStatusFast();
            startUpdateCycle();
        });

        // Preload critical resources
        const preloadLink = document.createElement('link');
        preloadLink.rel = 'preload';
        preloadLink.href = 'api/status.php';
        preloadLink.as = 'fetch';
        document.head.appendChild(preloadLink);
    </script>
</body>
</html>

<?php
function getNomeEstado($uf) {
    $estados = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SPC'=> 'Spacecom',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins'
    ];
    return $estados[strtoupper($uf)] ?? 'Estado Desconhecido';
}
?>