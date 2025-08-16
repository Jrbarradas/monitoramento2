<?php
if (!isset($is_admin)) {
    $userInfo = verificarAdmin($pdo, $_SESSION['id']);
    $is_admin = $userInfo['is_admin'];
    $estado_usuario = $userInfo['estado_permitido'];
}
?>
<div class="header">
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <div class="menu-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </button>
    <h1><?= $pageTitle ?? 'Sistema de Monitoramento' ?></h1>
    <?php if (isset($showStatusCounters) && $showStatusCounters): ?>
    <div class="status-container">
        <div class="status-item online">
            <i class="fas fa-link" aria-hidden="true"></i>
            <span id="total-online">0</span>
        </div>
        <div class="status-item offline">
            <i class="fas fa-unlink" aria-hidden="true"></i>
            <span id="total-offline">0</span>
        </div>
        <div class="status-item total">
            <i class="fas fa-globe-americas" aria-hidden="true"></i>
            <span id="total-links"><?= $totalLinks ?? 0 ?></span>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-title">Menu Principal</div>
        <div class="sidebar-subtitle">Sistema de Monitoramento</div>
    </div>
    <nav class="nav-menu" role="navigation">
        <?= gerarMenuNavegacao($is_admin, basename($_SERVER['PHP_SELF'])) ?>
    </nav>
</div>