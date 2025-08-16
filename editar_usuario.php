<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once "config.php";

// Verificar se o usuário atual é admin
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

$id = $_GET['id'] ?? 0;
$mensagem = '';
$erros = [];

// Buscar dados do usuário
$usuario = null;
try {
    $stmt = $pdo->prepare("SELECT id, username, nome_completo, nivel_acesso, estado_permitido FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        die("Usuário não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar usuário: " . $e->getMessage());
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nome_completo = trim($_POST['nome_completo']);
    $nivel_acesso = trim($_POST['nivel_acesso']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $estado_permitido = $is_admin ? trim($_POST['estado_permitido']) : $usuario['estado_permitido'];

    // Validações
    if (empty($username)) {
        $erros[] = "O nome de usuário é obrigatório.";
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            $erros[] = "A senha deve ter pelo menos 6 caracteres.";
        } elseif ($password !== $confirm_password) {
            $erros[] = "As senhas não coincidem.";
        }
    }

    if (empty($nome_completo)) {
        $erros[] = "O nome completo é obrigatório.";
    }

    if (empty($erros)) {
        try {
            // Verificar se o username já existe (exceto para o próprio usuário)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            
            if ($stmt->rowCount() > 0) {
                $erros[] = "Este nome de usuário já está em uso.";
            } else {
                // Atualizar usuário
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nome_completo = ?, nivel_acesso = ?, estado_permitido = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $nome_completo, $nivel_acesso, $estado_permitido, $password_hash, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nome_completo = ?, nivel_acesso = ?, estado_permitido = ? WHERE id = ?");
                    $stmt->execute([$username, $nome_completo, $nivel_acesso, $estado_permitido, $id]);
                }
                
                $mensagem = '<div class="mensagem sucesso">Usuário atualizado com sucesso!</div>';
                
                // Atualizar dados do usuário
                $usuario['username'] = $username;
                $usuario['nome_completo'] = $nome_completo;
                $usuario['nivel_acesso'] = $nivel_acesso;
                $usuario['estado_permitido'] = $estado_permitido;
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao atualizar usuário: " . $e->getMessage();
        }
    }
    
    if (!empty($erros)) {
        $mensagem = '<div class="mensagem erro"><ul>';
        foreach ($erros as $erro) {
            $mensagem .= '<li>' . $erro . '</li>';
        }
        $mensagem .= '</ul></div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário - Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
       :root {
            --primary-dark: #0f172a;
            --secondary-dark: #1e293b;
            --accent-dark: #334155;
            --success: #10b981;
            --error: #ef4444;
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
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .card-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .input-icon {
            position: absolute;
            top: 38px;
            left: 15px;
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus, 
        .form-group select:focus {
            outline: none;
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .form-group input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-salvar {
            background: var(--success);
        }

        .btn-cancelar {
            background: var(--secondary-dark);
            border: 1px solid var(--glass-border);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .mensagem {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
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

        .mensagem ul {
            list-style: none;
            text-align: left;
            margin-top: 10px;
        }

        .mensagem li {
            margin-bottom: 5px;
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

            .form-buttons {
                flex-direction: column;
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
        <h1>Editar Usuário</h1>
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
    </div>

    <div class="container">
        <?php echo $mensagem; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Editar Usuário: <?= htmlspecialchars($usuario['username']) ?></h2>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nome de Usuário *</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nome_completo">Nome Completo *</label>
                    <input type="text" id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($usuario['nome_completo']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nivel_acesso">Nível de Acesso *</label>
                    <select id="nivel_acesso" name="nivel_acesso" required>
                        <option value="admin" <?= $usuario['nivel_acesso'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="operador" <?= $usuario['nivel_acesso'] === 'operador' ? 'selected' : '' ?>>Operador</option>
                    </select>
                </div>
                
                <?php if ($is_admin): ?>
                    <div class="form-group">
                        <label for="estado_permitido">Estado Permitido *</label>
                        <select id="estado_permitido" name="estado_permitido" required>
                            <option value="">Selecione um estado</option>
                            <?php
                            $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                            foreach ($estados as $uf) {
                                $selected = ($usuario['estado_permitido'] ?? '') === $uf ? 'selected' : '';
                                echo "<option value='$uf' $selected>$uf</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Estado Permitido</label>
                        <input type="text" value="<?= $usuario['estado_permitido'] ?? '-' ?>" readonly>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="password">Nova Senha (deixe em branco para manter a atual)</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nova Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-salvar">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="usuarios.php" class="btn btn-cancelar">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
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
