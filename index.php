<?php
session_start();
require_once "includes/functions.php";
require_once "config.php";

verificarLogin();

$userInfo = verificarAdmin($pdo, $_SESSION['id']);
$is_admin = $userInfo['is_admin'];
$estado_usuario = $userInfo['estado_permitido'];

// Get links based on user permissions
if ($is_admin) {
    $stmt = $pdo->query("SELECT id, uf, ip FROM links ORDER BY uf, nome");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalLinks = $pdo->query("SELECT COUNT(*) FROM links")->fetchColumn();
} else {
    $stmt = $pdo->prepare("SELECT id, uf, ip FROM links WHERE uf = ?");
    $stmt->execute([$estado_usuario]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalLinks = count($links);
}

// Group links by state
$estados = [];
if ($is_admin) {
    foreach ($links as $link) {
        $uf = $link['uf'];
        $estados[$uf][] = $link;
    }
    uksort($estados, function($a, $b) {
        return strcmp($a, $b);
    });
} else {
    $estados[$estado_usuario] = $links;
}

// Page configuration
$pageTitle = 'Dashboard - Monitoramento Spacecom';
$showStatusCounters = $is_admin;
$showUpdateCounter = true;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento de Links Spacecom</title>
    <meta name="description" content="Sistema de monitoramento de links em tempo real">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css?v=<?= gerarHashCache('css/index.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="content">
        <div class="cards-grid">
            <?php foreach ($estados as $uf => $linksEstado): ?>
                <div class="state-card" 
                     data-uf="<?= htmlspecialchars($uf) ?>"
                     data-link-ids="<?= htmlspecialchars(json_encode(array_column($linksEstado, 'id'))) ?>"
                     tabindex="0"
                     role="button"
                     aria-label="Ver detalhes do estado <?= htmlspecialchars($uf) ?>">
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

    <?php include 'includes/footer.php'; ?>

    <script defer>
        // Modal functionality - Optimized
        const modalOverlay = document.getElementById('modalOverlay');
        const modalTitle = document.getElementById('modalTitle');
        const detailsBtn = document.getElementById('detailsBtn');
        const historyBtn = document.getElementById('historyBtn');

        function openModal(uf, linkIds) {
            modalTitle.textContent = `Estado: ${uf}`;
            detailsBtn.href = `detalhes_estado.php?uf=${encodeURIComponent(uf)}`;
            historyBtn.href = `historico.php?uf=${encodeURIComponent(uf)}`;
            modalOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modalOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Card event handlers - Optimized
        function handleCardInteraction(card) {
            const uf = card.dataset.uf;
            const linkIds = JSON.parse(card.dataset.linkIds);
            openModal(uf, linkIds);
        }

        document.addEventListener('click', (e) => {
            const card = e.target.closest('.state-card');
            if (card) {
                handleCardInteraction(card);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const card = e.target.closest('.state-card');
                if (card) {
                    e.preventDefault();
                    handleCardInteraction(card);
                }
            }
        });

        // Status monitoring - Optimized
        let updateTime = 5;
        let isUpdating = false;
        const stateCards = new Map();
        
        document.querySelectorAll('.state-card').forEach(card => {
            stateCards.set(card.dataset.uf, card);
        });

        async function checkStatusFast() {
            if (isUpdating) return;
            isUpdating = true;

            try {
                stateCards.forEach(card => card.classList.add('updating'));

                const response = await fetch('api/status.php', {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });

                if (!response.ok) throw new Error('Network response was not ok');
                const links = await response.json();

                const statusByState = new Map();
                let totalOnline = 0;
                let totalOffline = 0;

                // Process links efficiently
                links.forEach(link => {
                    const uf = link.uf;
                    if (!statusByState.has(uf)) {
                        statusByState.set(uf, { online: 0, offline: 0, hasOffline: false });
                    }

                    const stateStatus = statusByState.get(uf);
                    if (link.status === 'online') {
                        stateStatus.online++;
                        totalOnline++;
                    } else {
                        stateStatus.offline++;
                        stateStatus.hasOffline = true;
                        totalOffline++;
                    }
                });

                // Update UI efficiently
                statusByState.forEach((status, uf) => {
                    const card = stateCards.get(uf);
                    if (card) {
                        card.classList.remove('online', 'offline', 'updating');
                        card.classList.add(status.hasOffline ? 'offline' : 'online');

                        const infoElement = card.querySelector('.state-info');
                        if (infoElement) {
                            const total = status.online + status.offline;
                            const offlineText = status.offline > 0 ? ` (${status.offline} offline)` : '';
                            infoElement.textContent = `${total} link(s)${offlineText}`;
                        }
                    }
                });

                // Update counters if admin
                <?php if ($is_admin): ?>
                const onlineCounter = document.getElementById('total-online');
                const offlineCounter = document.getElementById('total-offline');
                if (onlineCounter) onlineCounter.textContent = totalOnline;
                if (offlineCounter) offlineCounter.textContent = totalOffline;
                <?php endif; ?>

            } catch (error) {
                console.error('Error checking status:', error);
            } finally {
                isUpdating = false;
                stateCards.forEach(card => card.classList.remove('updating'));
            }
        }

        function startUpdateCycle() {
            const updateCounter = document.getElementById('updateCounter');
            if (!updateCounter) return;

            setInterval(() => {
                updateTime--;
                updateCounter.textContent = `Atualizando em: ${updateTime}s`;

                if (updateTime <= 0) {
                    updateTime = 5;
                    checkStatusFast();
                }
            }, 1000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            checkStatusFast();
            <?php if (isset($showUpdateCounter) && $showUpdateCounter): ?>
            startUpdateCycle();
            <?php endif; ?>
        });

        // Visibility API for performance
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                checkStatusFast();
            }
        });
    </script>
</body>
</html>

                const uf = card.dataset.uf;
                const linkIds = JSON.parse(card.dataset.linkIds);
                openModal(uf, linkIds);
            });
        });

        // Fast status monitoring system
        let updateTime = 3;
        let updateInterval;
        let isUpdating = false;

        const stateCards = {};
        document.querySelectorAll('.state-card').forEach(card => {
            const uf = card.dataset.uf;
            stateCards[uf] = card;
        });

       async function checkStatusFast() {
    if (isUpdating) return;
    isUpdating = true;

    try {
        Object.values(stateCards).forEach(card => {
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

        const statusByState = {};
        let totalOnline = 0;
        let totalOffline = 0;

        // Processar links em paralelo
        await Promise.all(links.map(async (link) => {
            const uf = link.uf;
            if (!statusByState[uf]) {
                statusByState[uf] = {
                    online: 0,
                    offline: 0,
                    hasOffline: false
                };
            }

            const lastSeenOnline = link.last_seen_online ? new Date(link.last_seen_online).getTime() : null;
            const currentTime = Date.now();
            const timeDifference = lastSeenOnline ? (currentTime - lastSeenOnline) / (1000 * 60) : Infinity;

            if (timeDifference <= 3) {
                statusByState[uf].online++;
                totalOnline++;
            } else {
                statusByState[uf].offline++;
                statusByState[uf].hasOffline = true;
                totalOffline++;
            }
        }));

        for (const uf in statusByState) {
            const card = stateCards[uf];
            if (card) {
                const hasOffline = statusByState[uf].hasOffline;

                // Remover todas as classes de status
                card.classList.remove('online', 'offline', 'updating');

                // Adicionar a classe correta
                if (hasOffline) {
                    card.classList.add('offline'); // Card fica vermelho piscando
                } else {
                    card.classList.add('online'); // Card fica verde
                }

                // Atualizar informações do card
                const infoElement = card.querySelector('.state-info');
                if (infoElement) {
                    const total = statusByState[uf].online + statusByState[uf].offline;
                    const offlineText = statusByState[uf].offline > 0 ?
                        ` (${statusByState[uf].offline} offline)` : '';
                    infoElement.textContent = `${total} link(s)${offlineText}`;
                }
            }
        }

        document.getElementById('total-online').textContent = totalOnline;
        document.getElementById('total-offline').textContent = totalOffline;

    } catch (error) {
        console.error('Error checking status:', error);
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
                    updateTime = 5;
                    checkStatusFast();
                }
            }, 1000);
        }

        document.addEventListener('DOMContentLoaded', async () => {
            await checkStatusFast();
            startUpdateCycle();
        });
    </script>
</body>
</html>
