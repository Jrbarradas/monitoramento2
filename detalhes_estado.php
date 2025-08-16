<?php
require_once "config.php";

if(!isset($_GET['uf'])) {
    die("UF não especificado!");
}

$uf = $_GET['uf'];
$nomeEstado = getNomeEstado($uf);

// Obter todos os links do estado com status
$stmt = $pdo->prepare("SELECT * FROM links WHERE uf = :uf");
$stmt->bindParam(':uf', $uf, PDO::PARAM_STR);
$stmt->execute();
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($links) === 0) {
    die("Nenhum link encontrado para o estado: " . htmlspecialchars($uf));
}

$linkIds = array_column($links, 'id');

// Criar mapa de status a partir dos próprios links
$statusMap = [];
foreach ($links as $link) {
    $statusMap[$link['id']] = $link['status'] ?? 'offline';
}

// Classificar links: offline primeiro, depois online, e ambos ordenados por nome
usort($links, function($a, $b) use ($statusMap) {
    $statusA = $statusMap[$a['id']];
    $statusB = $statusMap[$b['id']];
    
    // Offline vem antes de online
    if ($statusA === 'offline' && $statusB !== 'offline') return -1;
    if ($statusA !== 'offline' && $statusB === 'offline') return 1;
    
    // Mesmo status? Ordena por nome
    return strcmp($a['nome'], $b['nome']);
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Links do Estado - <?= $nomeEstado ?></title>
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

        .back-btn {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-right: 20px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .back-btn:hover::before {
            left: 100%;
        }

        .back-btn:hover {
            background: rgba(16, 185, 129, 0.2);
            border-color: var(--success);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
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

        .content {
            padding: 120px 30px 100px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
            color: var(--text-primary);
            font-size: 2.5rem;
            font-weight: 700;
        }

        .state-info {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .state-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); /* Cards mais compactos */
            gap: 20px;
        }

        .link-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            height: 100px; /* Altura fixa inicial */
            display: flex;
            flex-direction: column;
            justify-content: center;
            cursor: pointer;
        }

        .link-card:hover {
            height: auto; /* Altura automática no hover */
            min-height: 100px;
            z-index: 10; /* Para ficar sobre outros cards */
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .link-card.online {
            border-color: var(--success);
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.2);
        }

        .link-card.offline {
            border-color: var(--error);
            box-shadow: 0 8px 32px rgba(239, 68, 68, 0.2);
            animation: pulse-error 2s infinite;
        }

        .link-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.02), transparent);
            transition: left 0.8s;
        }

        .link-card:hover::before {
            left: 100%;
        }

        @keyframes pulse-error {
            0% {
                box-shadow: 0 8px 32px rgba(239, 68, 68, 0.2);
                border-color: var(--error);
            }
            50% {
                box-shadow: 0 8px 32px rgba(239, 68, 68, 0.4);
                border-color: #ff6b6b;
            }
            100% {
                box-shadow: 0 8px 32px rgba(239, 68, 68, 0.2);
                border-color: var(--error);
            }
        }

        .link-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .link-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
            /* Nome completo visível */
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Máximo de 2 linhas */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
            max-height: 2.6em; /* 2 linhas * 1.3 line-height */
        }

        .link-status {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-left: 10px;
            position: relative;
        }

        .status-indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            position: relative;
        }

        .link-status.online .status-indicator {
            background: var(--success);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .link-status.offline .status-indicator {
            background: var(--error);
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);
            animation: blink 1s infinite;
        }

        /* Tooltip para o status */
        .status-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--secondary-dark);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
            z-index: 20;
        }

        .link-status:hover .status-tooltip {
            opacity: 1;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .link-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 0; /* Inicialmente oculto */
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }

        .link-card:hover .link-details {
            max-height: 500px; /* Altura máxima para mostrar tudo */
            opacity: 1;
            margin-top: 10px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-icon {
            width: 18px;
            color: var(--success);
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-secondary);
            min-width: 70px;
            font-size: 0.85rem;
        }

        .detail-value {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            word-break: break-word; /* Quebra palavras longas */
        }

        .updating {
            opacity: 0.7;
            transform: scale(0.98);
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            color: var(--text-secondary);
            padding: 8px 0px;
            font-size: 0.8rem;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
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

        @media (max-width: 768px) {
            .content {
                padding: 100px 15px 80px;
            }

            .page-title {
                font-size: 2rem;
            }

            .links-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .state-stats {
                gap: 15px;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .header h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h1>Links do Estado: <?= htmlspecialchars($nomeEstado) ?></h1>
    </div>

    <div class="content">
        <h2 class="page-title"><?= htmlspecialchars($uf) ?></h2>
        
        <div class="state-info">
            <div class="state-stats">
                <div class="stat-item">
                    <div class="stat-value" id="totalLinks"><?= count($links) ?></div>
                    <div class="stat-label">Total de Links</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="onlineLinks" style="color: var(--success);">0</div>
                    <div class="stat-label">Online</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="offlineLinks" style="color: var(--error);">0</div>
                    <div class="stat-label">Offline</div>
                </div>
            </div>
        </div>

        <div class="links-grid" id="linksGrid">
            <?php foreach ($links as $link): 
                $status = $statusMap[$link['id']] ?? 'offline';
                $statusClass = ($status === 'online') ? 'online' : 'offline';
                $statusText = ($status === 'online') ? 'ONLINE' : 'OFFLINE';
            ?>
                <div class="link-card <?= $statusClass ?>" data-id="<?= $link['id'] ?>" data-status="<?= $status ?>">
                    <div class="link-header">
                        <div class="link-name"><?= htmlspecialchars($link['nome']) ?></div>
                        <div class="link-status <?= $statusClass ?>" id="status-<?= $link['id'] ?>">
                            <div class="status-indicator"></div>
                            <div class="status-tooltip"><?= $statusText ?></div>
                        </div>
                    </div>
                    <div class="link-details">
                        <div class="detail-item">
                            <i class="fas fa-network-wired detail-icon"></i>
                            <span class="detail-label">IP:</span>
                            <span class="detail-value"><?= htmlspecialchars($link['ip']) ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt detail-icon"></i>
                            <span class="detail-label">Endereço:</span>
                            <span class="detail-value"><?= htmlspecialchars($link['endereco']) ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-city detail-icon"></i>
                            <span class="detail-label">Cidade:</span>
                            <span class="detail-value"><?= htmlspecialchars($link['cidade']) ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-user detail-icon"></i>
                            <span class="detail-label">Contato:</span>
                            <span class="detail-value"><?= htmlspecialchars($link['contato']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="footer">
        <span>Spacecom Monitoramento S/A © 2025</span>
        <span class="update-counter" id="updateCounter">Atualizando em: 2s</span>
    </div>

    <script>
        let updateTime = 2;
        let updateInterval;
        let isUpdating = false;
        const linkIds = <?= json_encode($linkIds) ?>;
        let lastStatusMap = <?= json_encode($statusMap) ?>;

        async function checkStatusFast() {
            if (isUpdating) return;
            isUpdating = true;
            
            try {
                // Add updating class to all cards
                document.querySelectorAll('.link-card').forEach(card => {
                    card.classList.add('updating');
                });
                
                const response = await fetch('api/status.php', {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const links = await response.json();
                
                let onlineCount = 0;
                let offlineCount = 0;
                const newStatusMap = {};
                let statusChanged = false;
                
                // Atualizar status e verificar mudanças
                links.forEach(link => {
                    if (linkIds.includes(parseInt(link.id))) {
                        newStatusMap[link.id] = link.status;
                        
                        const statusElement = document.getElementById(`status-${link.id}`);
                        const card = document.querySelector(`.link-card[data-id="${link.id}"]`);
                        
                        if (statusElement && card) {
                            // Verifica se houve mudança de status
                            const oldStatus = lastStatusMap[link.id] || 'offline';
                            if (oldStatus !== link.status) {
                                statusChanged = true;
                                
                                // Efeito visual para mudança de status
                                if (link.status === 'online') {
                                    card.classList.remove('offline');
                                    card.classList.add('online');
                                    card.style.animation = 'pulse-success 1s';
                                    setTimeout(() => card.style.animation = '', 1000);
                                } else {
                                    card.classList.remove('online');
                                    card.classList.add('offline');
                                    card.style.animation = 'pulse-error 1s';
                                    setTimeout(() => card.style.animation = '', 1000);
                                }
                            }
                            
                            // Atualizar exibição do status
                            statusElement.className = `link-status ${link.status}`;
                            statusElement.querySelector('.status-tooltip').textContent = link.status.toUpperCase();
                            
                            // Atualizar data-status
                            card.dataset.status = link.status;
                            
                            // Contar status
                            if (link.status === 'online') {
                                onlineCount++;
                            } else {
                                offlineCount++;
                            }
                        }
                    }
                });
                
                // Atualizar contadores
                document.getElementById('onlineLinks').textContent = onlineCount;
                document.getElementById('offlineLinks').textContent = offlineCount;
                
                // Atualizar status map
                lastStatusMap = newStatusMap;
                
                // Reordenar se houver mudança de status
                if (statusChanged) {
                    reorderCards();
                }
                
            } catch (error) {
                console.error('Error checking status:', error);
            } finally {
                isUpdating = false;
                
                // Remove updating class
                document.querySelectorAll('.link-card').forEach(card => {
                    card.classList.remove('updating');
                });
            }
        }

        // Função para reordenar os cards (offline primeiro)
        function reorderCards() {
            const container = document.getElementById('linksGrid');
            const cards = Array.from(container.querySelectorAll('.link-card'));
            
            // Ordenar cards: offline primeiro, depois online, e ambos ordenados por nome
            cards.sort((a, b) => {
                const statusA = a.dataset.status;
                const statusB = b.dataset.status;
                const nameA = a.querySelector('.link-name').textContent.toLowerCase();
                const nameB = b.querySelector('.link-name').textContent.toLowerCase();
                
                // Offline vem antes de online
                if (statusA === 'offline' && statusB !== 'offline') return -1;
                if (statusA !== 'offline' && statusB === 'offline') return 1;
                
                // Mesmo status? Ordena por nome
                return nameA.localeCompare(nameB);
            });
            
            // Reinserir na ordem correta com animação
            cards.forEach((card, index) => {
                // Aplicar animação apenas se a posição mudou
                card.style.transition = 'transform 0.5s ease, opacity 0.5s ease';
                card.style.transform = `translateY(${index * 10}px)`;
                card.style.opacity = '0';
                
                setTimeout(() => {
                    container.appendChild(card);
                    card.style.transform = 'translateY(0)';
                    card.style.opacity = '1';
                }, 50 * index);
                
                // Remover a transição após a animação
                setTimeout(() => {
                    card.style.transition = '';
                    card.style.transform = '';
                    card.style.opacity = '';
                }, 500 + (50 * index));
            });
        }

        function startUpdateCycle() {
            updateInterval = setInterval(() => {
                updateTime--;
                document.getElementById('updateCounter').textContent = `Atualizando em: ${updateTime}s`;
                
                if (updateTime <= 0) {
                    updateTime = 2;
                    checkStatusFast();
                }
            }, 1000);
        }

        // Adicionar animação para mudança de status
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes pulse-success {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
                70% { transform: scale(1.03); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
            }
            @keyframes pulse-error {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
                70% { transform: scale(1.03); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
            }
        `;
        document.head.appendChild(style);

        // Initialize
        document.addEventListener('DOMContentLoaded', async () => {
            await checkStatusFast();
            startUpdateCycle();
        });
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
