<?php
require_once "config.php";

if(!isset($_GET['uf'])) {
    die("UF não especificado!");
}

$uf = $_GET['uf'];
$nomeEstado = getNomeEstado($uf);

$stmt = $pdo->prepare("SELECT * FROM links WHERE uf = :uf ORDER BY nome");
$stmt->bindParam(':uf', $uf, PDO::PARAM_STR);
$stmt->execute();
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($links) === 0) {
    die("Nenhum link encontrado para o estado: " . htmlspecialchars($uf));
}

$linkIds = array_column($links, 'id');
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
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .link-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
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

        .link-card:hover {
            transform: translateY(-8px) scale(1.02);
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
            margin-bottom: 20px;
        }

        .link-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .link-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .link-status.online {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .link-status.offline {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
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

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .link-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .detail-icon {
            width: 20px;
            color: var(--success);
            font-size: 1rem;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-secondary);
            min-width: 80px;
        }

        .detail-value {
            color: var(--text-primary);
            font-weight: 500;
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

        @media (max-width: 768px) {
            .content {
                padding: 100px 15px 80px;
            }

            .page-title {
                font-size: 2rem;
            }

            .links-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .state-stats {
                gap: 20px;
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
            <?php foreach ($links as $link): ?>
                <div class="link-card" data-id="<?= $link['id'] ?>">
                    <div class="link-header">
                        <div>
                            <div class="link-name"><?= htmlspecialchars($link['nome']) ?></div>
                        </div>
                        <div class="link-status" id="status-<?= $link['id'] ?>">
                            <div class="status-indicator"></div>
                            <span>Verificando...</span>
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
        <span class="update-counter" id="updateCounter">Atualizando em: 5s</span>
    </div>

    <script>
        let updateTime = 5;
        let updateInterval;
        let isUpdating = false;
        const linkIds = <?= json_encode($linkIds) ?>;

        async function checkStatus() {
            if (isUpdating) return;
            isUpdating = true;
            
            try {
                // Add updating class to all cards
                document.querySelectorAll('.link-card').forEach(card => {
                    card.classList.add('updating');
                });
                
                const response = await fetch('api/status.php');
                const links = await response.json();
                
                let onlineCount = 0;
                let offlineCount = 0;
                
                links.forEach(link => {
                    if (linkIds.includes(parseInt(link.id))) {
                        const statusElement = document.getElementById(`status-${link.id}`);
                        const card = document.querySelector(`.link-card[data-id="${link.id}"]`);
                        
                        if (statusElement && card) {
                            // Update status display
                            statusElement.className = `link-status ${link.status}`;
                            statusElement.innerHTML = `
                                <div class="status-indicator"></div>
                                <span>${link.status.toUpperCase()}</span>
                            `;
                            
                            // Update card styling
                            card.classList.remove('online', 'offline');
                            card.classList.add(link.status);
                            
                            // Count status
                            if (link.status === 'online') {
                                onlineCount++;
                            } else {
                                offlineCount++;
                            }
                        }
                    }
                });
                
                // Update counters
                document.getElementById('onlineLinks').textContent = onlineCount;
                document.getElementById('offlineLinks').textContent = offlineCount;
                
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

        function startUpdateCycle() {
            updateInterval = setInterval(() => {
                updateTime--;
                document.getElementById('updateCounter').textContent = `Atualizando em: ${updateTime}s`;
                
                if (updateTime <= 0) {
                    updateTime = 5;
                    checkStatus();
                }
            }, 1000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', async () => {
            await checkStatus();
            startUpdateCycle();
            setInterval(checkStatus, 5000);
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
?>