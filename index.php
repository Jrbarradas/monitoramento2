<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once "config.php";

// Verificar se o usuário é admin
$is_admin = false;
$estado_usuario = $_SESSION['estado_permitido'] ?? '';
try {
    $stmt = $pdo->prepare("SELECT nivel_acesso FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $is_admin = ($user['nivel_acesso'] === 'admin');
    }
} catch (PDOException $e) {
    die("Erro ao verificar permissões: " . $e->getMessage());
}

// Consulta para obter links com filtro por estado
if ($is_admin) {
    $links = $pdo->query("SELECT id, uf, ip FROM links")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT id, uf, ip FROM links WHERE uf = ?");
    $stmt->execute([$estado_usuario]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Consulta para total de links (apenas para admin)
$totalLinks = 0;
if ($is_admin) {
    $totalLinks = $pdo->query("SELECT COUNT(*) FROM links")->fetchColumn();
} else {
    $totalLinks = count($links);
}

// Agrupar links por estado
$estados = [];
if ($is_admin) {
    foreach ($links as $link) {
        $uf = $link['uf'];
        $estados[$uf][] = $link;
    }
    // Ordenar estados alfabeticamente
    uksort($estados, function($a, $b) {
        return strcmp($a, $b);
    });
} else {
    // Não admin: apenas o estado do usuário
    $estados[$estado_usuario] = $links;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Monitoramento de Links Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
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
            <?php if ($is_admin): ?>
                <div class="status-item online">
                    <i class="fas fa-link"></i>
                    <span id="total-online">0</span>
                </div>
            <?php endif; ?>
            <?php if ($is_admin): ?>
                <div class="status-item offline">
                    <i class="fas fa-unlink"></i>
                    <span id="total-offline">0</span>
                </div>
            <?php endif; ?>
            <?php if ($is_admin): ?>
                <div class="status-item total">
                    <i class="fas fa-globe-americas"></i>
                    <span id="total-links"><?= $totalLinks ?></span>
                </div>
            <?php endif; ?>
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
            <?php if ($is_admin): ?>
            <div class="nav-item">
                <a href="cadastrar.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="nav-text">Adicionar Novo Link</div>
                </a>
            </div>
            <?php endif; ?>
            <?php if ($is_admin): ?>
            <div class="nav-item">
                <a href="list_links.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-list"></i></div>
                    <div class="nav-text">Gerenciamento de Links</div>
                </a>
            </div>
            <?php endif; ?>
            <div class="nav-item">
                <a href="teste_ping.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-network-wired"></i></div>
                    <div class="nav-text">Verificação de Status</div>
                </a>
            </div>
            <?php if ($is_admin): ?>
            <div class="nav-item">
                <a href="mapa.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <div class="nav-text">Mapa da Rede</div>
                </a>
            </div>
            <?php endif; ?>
            <?php if ($is_admin): ?>
            <div class="nav-item">
                <a href="historico.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-history"></i></div>
                    <div class="nav-text">Logs de Monitoramento</div>
                </a>
            </div>
            <?php endif; ?>
            <?php if ($is_admin): ?>
                <div class="nav-item">
                    <a href="usuarios.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-users"></i></div>
                        <div class="nav-text">Administração de Usuários</div>
                    </a>
                </div>
            <?php endif; ?>

            <div class="nav-item">
                <a href="logout.php" class="nav-link">
                    <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <div class="nav-text">Logout</div>
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
        <span class="update-counter" id="updateCounter">Atualizando em: 5s</span>
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
