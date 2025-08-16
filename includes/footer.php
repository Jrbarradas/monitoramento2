<div class="footer">
    <span>Spacecom Monitoramento S/A © <?= date('Y') ?></span>
    <?php if (isset($showUpdateCounter) && $showUpdateCounter): ?>
    <span class="update-counter" id="updateCounter">Atualizando em: 5s</span>
    <?php endif; ?>
</div>

<script>
// Menu Toggle - Optimized
(function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (!menuToggle || !sidebar) return;

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

    // Close with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            menuToggle.classList.remove('active');
            sidebar.classList.remove('open');
        }
    });
})();
</script>