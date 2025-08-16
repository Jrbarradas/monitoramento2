<?php
session_start();

// Ativar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once "config.php";

// Verificar conexão com o banco de dados
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Verificar se a tabela 'usuarios' existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() === 0) {
        die("Erro: A tabela 'usuarios' não foi encontrada no banco de dados. Por favor, crie a tabela primeiro.");
    }
} catch (PDOException $e) {
    die("Erro ao verificar a existência da tabela: " . $e->getMessage());
}

// Verificar se o usuário atual é admin (consultando o banco)
$is_admin = false;
$estado_usuario = '';
try {
    $stmt = $pdo->prepare("SELECT nivel_acesso, estado_permitido FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $is_admin = ($user['nivel_acesso'] === 'admin');
        $estado_usuario = $user['estado_permitido'] ?? '';
    }
} catch (PDOException $e) {
    die("Erro ao verificar permissões: " . $e->getMessage());
}

// Variável para mensagens de feedback
$mensagem = '';

// Processar exclusão de usuário, se o parâmetro 'excluir' estiver presente
if (isset($_GET['excluir'])) {
    if ($is_admin) {
        $id = (int)$_GET['excluir'];
        // Não permite excluir o usuário admin (id=1) ou o próprio usuário logado
        if ($id > 0 && $id !== 1 && $id !== $_SESSION['id']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    $mensagem = '<div class="mensagem sucesso">Usuário excluído com sucesso!</div>';
                    // Recarregar a lista de usuários
                    $stmt = $pdo->query("SELECT id, username, nome_completo, nivel_acesso, estado_permitido, criado_em FROM usuarios ORDER BY id");
                    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $mensagem = '<div class="mensagem erro">Usuário não encontrado.</div>';
                }
            } catch (PDOException $e) {
                $mensagem = '<div class="mensagem erro">Erro ao excluir usuário: ' . $e->getMessage() . '</div>';
            }
        } else {
            $mensagem = '<div class="mensagem erro">Não é possível excluir este usuário.</div>';
        }
    } else {
        $mensagem = '<div class="mensagem erro">Acesso negado! Apenas administradores podem excluir usuários.</div>';
    }
}

// Buscar todos os usuários
$usuarios = [];
try {
    $stmt = $pdo->query("SELECT id, username, nome_completo, nivel_acesso, estado_permitido, criado_em FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = '<div class="mensagem erro">Erro ao carregar usuários: ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Usuários - Spacecom</title>
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
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--glass-border);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            background: linear-gradient(135deg, var(--text-primary), var(--success));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-novo {
            padding: 10px 20px;
            background: var(--success);
            border: none;
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-novo:hover {
            background: #0da271;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--glass-border);
        }

        table th {
            background: rgba(30, 41, 59, 0.5);
            font-weight: 600;
            color: var(--success);
        }

        table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .acoes {
            display: flex;
            gap: 10px;
        }

        .btn-acao {
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-editar {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }

        .btn-excluir {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .btn-acao:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        .nivel-admin {
            color: var(--success);
            font-weight: 500;
        }

        .nivel-operador {
            color: var(--warning);
            font-weight: 500;
        }

        .mensagem {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .mensagem.sucesso {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--success);
        }

        .mensagem.erro {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--error);
        }

        .mensagem.aviso {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid var(--warning);
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

        /* Responsividade */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.4rem;
            }

            .content {
                margin: 100px 15px 80px;
            }

            .sidebar.open ~ .content {
                margin-left: 15px;
            }

            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .btn-novo {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .acoes {
                flex-direction: column;
                gap: 5px;
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
        <h1>Gerenciamento de Usuários</h1>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Menu Principal</div>
            <div class="sidebar-subtitle">Sistema de Monitoramento</div>
        </div>
        <nav class="nav-menu">
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
        <?php echo $mensagem; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Lista de Usuários</h2>
                <?php if ($is_admin): ?>
                    <a href="cadastrar_usuario.php" class="btn-novo">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($usuarios)): ?>
                <div class="mensagem aviso">
                    Nenhum usuário cadastrado.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Nome Completo</th>
                            <th>Nível de Acesso</th>
                            <th>Estado Permitido</th>
                            <th>Data de Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $usuario['id'] ?></td>
                                <td><?= htmlspecialchars($usuario['username']) ?></td>
                                <td><?= htmlspecialchars($usuario['nome_completo']) ?></td>
                                <td>
                                    <span class="nivel-<?= $usuario['nivel_acesso'] ?>">
                                        <?= ucfirst($usuario['nivel_acesso']) ?>
                                    </span>
                                </td>
                                <td><?= $usuario['estado_permitido'] ?? '-' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($usuario['criado_em'])) ?></td>
                                <td class="acoes">
                                    <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn-acao btn-editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <?php if ($is_admin && $usuario['id'] != 1 && $usuario['id'] != $_SESSION['id']): ?>
                                        <a href="usuarios.php?excluir=<?= $usuario['id'] ?>" 
                                           class="btn-acao btn-excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <span>Spacecom Monitoramento S/A © 2025</span>
    </div>

    <script>
        // Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            sidebar.classList.toggle('open');
        });

        // Fechar o menu ao clicar fora
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                menuToggle.classList.remove('active');
                sidebar.classList.remove('open');
            }
        });

    </script>
</body>
</html>
