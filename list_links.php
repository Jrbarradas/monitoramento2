<?php
session_start();
require_once "config.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Verificar se o usuário é admin
$is_admin = false;
$estado_usuario = $_SESSION['estado'] ?? '';
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
    $links = $pdo->query("SELECT * FROM links ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT * FROM links WHERE uf = ? ORDER BY nome");
    $stmt->execute([$estado_usuario]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Links - Spacecom</title>
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
            margin: 120px 30px 100px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .sidebar.open ~ .content {
            margin-left: 350px;
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.02), transparent);
            transition: left 0.8s;
        }

        .card:hover::before {
            left: 100%;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--glass-border);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .search-container {
            position: relative;
            max-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--success);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
            background: rgba(15, 23, 42, 0.3);
        }

        .data-table th,
        .data-table td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--glass-border);
        }

        .data-table th {
            background: var(--secondary-dark);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tr {
            transition: all 0.3s;
        }

        .data-table tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .button {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 0.85rem;
            position: relative;
            overflow: hidden;
        }

        .button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .button:hover::before {
            left: 100%;
        }

        .button.edit {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .button.save {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }

        .button.delete {
            background: linear-gradient(135deg, var(--error), #dc2626);
            color: white;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .edit-form {
            display: none;
            padding: 6px 10px;
            border: 1px solid var(--glass-border);
            border-radius: 6px;
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-primary);
            font-size: 0.9rem;
            width: 100%;
        }

        .edit-form:focus {
            border-color: var(--success);
            outline: none;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
        }

        .editing .view-mode {
            display: none;
        }

        .editing .edit-form {
            display: block;
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

        @media (max-width: 768px) {
            .content {
                margin: 100px 15px 80px;
            }

            .sidebar.open ~ .content {
                margin-left: 15px;
            }

            .card {
                padding: 20px;
            }

            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .search-container {
                max-width: none;
            }

            .data-table th,
            .data-table td {
                padding: 12px 8px;
                font-size: 0.85rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }
        }

        .link-row {
            transition: all 0.5s ease;
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.95); }
        }

        @keyframes fadeOutRight {
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }

        tr {
            transition: all 0.5s ease;
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
        <h1>Gerenciamento de Links</h1>
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
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"></h2>
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Buscar links...">
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>IP</th>
                            <th>Endereço</th>
                            <th>UF</th>
                            <th>Contato</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($links as $link): ?>
                        <tr data-id="<?= $link['id'] ?>" class="link-row">
                            <td>
                                <div class="view-mode"><?= htmlspecialchars($link['nome']) ?></div>
                                <input class="edit-form" name="nome" value="<?= htmlspecialchars($link['nome']) ?>">
                            </td>
                            <td>
                                <div class="view-mode"><?= htmlspecialchars($link['ip']) ?></div>
                                <input class="edit-form" name="ip" value="<?= htmlspecialchars($link['ip']) ?>">
                            </td>
                            <td>
                                <div class="view-mode"><?= htmlspecialchars($link['endereco']) ?></div>
                                <input class="edit-form" name="endereco" value="<?= htmlspecialchars($link['endereco']) ?>">
                            </td>
                            <td>
                                <div class="view-mode"><?= htmlspecialchars($link['uf']) ?></div>
                                <input class="edit-form uf-input" name="uf" value="<?= htmlspecialchars($link['uf']) ?>" maxlength="3">
                            </td>
                            <td>
                                <div class="view-mode"><?= htmlspecialchars($link['contato']) ?></div>
                                <input class="edit-form" name="contato" value="<?= htmlspecialchars($link['contato']) ?>">
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="button edit edit-btn">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="button save save-btn" style="display: none;">
                                        <i class="fas fa-save"></i> Salvar
                                    </button>
                                    <button class="button delete delete-btn">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer">
        Spacecom Monitoramento S/A © 2025
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

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');
        const rows = tableBody.querySelectorAll('.link-row');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Edit functionality
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('tr');
                row.classList.add('editing');
                btn.style.display = 'none';
                row.querySelector('.save-btn').style.display = 'inline-flex';
            });
        });

        // Save functionality
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('tr');
                const inputs = row.querySelectorAll('.edit-form');
                const data = {
                    id: row.dataset.id,
                    nome: inputs[0].value.trim(),
                    ip: inputs[1].value.trim(),
                    endereco: inputs[2].value.trim(),
                    uf: inputs[3].value.trim().toUpperCase(),
                    contato: inputs[4].value.trim()
                };

                // Validação básica no frontend
                if (!data.nome || !data.ip || !data.uf || !data.contato) {
                    alert('Preencha todos os campos obrigatórios.');
                    return;
                }

                if (!/^([0-9]{1,3}\.){3}[0-9]{1,3}$/.test(data.ip)) {
                    alert('IP inválido.');
                    return;
                }

                // Feedback visual
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                btn.disabled = true;

                try {
                    const response = await fetch('editar_link.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        inputs.forEach((input, index) => {
                            input.previousElementSibling.textContent = input.value;
                        });
                        row.classList.remove('editing');
                        btn.style.display = 'none';
                        row.querySelector('.edit-btn').style.display = 'inline-flex';

                        // Feedback visual
                        btn.innerHTML = '<i class="fas fa-check"></i> Salvo!';
                        setTimeout(() => {
                            btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
                        }, 2000);
                    } else {
                        throw new Error(result.message || 'Erro ao salvar alterações');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message);
                    btn.innerHTML = '<i class="fas fa-save"></i> Salvar';
                    btn.disabled = false;
                }
            });
        });

        // Delete functionality
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (confirm('Tem certeza que deseja excluir este link permanentemente? Esta ação também excluirá todo o histórico associado.')) {
                    const row = btn.closest('tr');
                    const originalBtnHTML = btn.innerHTML;

                    try {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';
                        btn.disabled = true;

                        const response = await fetch('excluir_link.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: row.dataset.id })
                        });

                        const result = await response.json();

                        if (result.success) {
                            btn.innerHTML = '<i class="fas fa-check"></i> Excluído!';
                            btn.style.backgroundColor = '#10b981';

                            row.style.opacity = '0';
                            row.style.height = '0';
                            row.style.padding = '0';
                            row.style.margin = '0';
                            row.style.border = 'none';
                            row.style.overflow = 'hidden';
                            row.style.transition = 'all 0.5s ease';

                            setTimeout(() => row.remove(), 500);
                        } else {
                            throw new Error(result.message || 'Erro desconhecido ao excluir');
                        }
                    } catch (error) {
                        console.error('Erro na exclusão:', error);
                        btn.innerHTML = originalBtnHTML;
                        btn.disabled = false;
                        alert(`Erro: ${error.message}`);
                    }
                }
            });
        });

        // Auto-uppercase UF fields
        document.querySelectorAll('.uf-input').forEach(input => {
            input.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        });
    </script>
</body>
</html>
