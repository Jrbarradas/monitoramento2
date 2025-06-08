<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $nome = htmlspecialchars($_POST["nome"]);
        $ip = htmlspecialchars($_POST["ip"]);
        $endereco = htmlspecialchars($_POST["endereco"]);
        $cidade = htmlspecialchars($_POST["cidade"]);
        $uf = htmlspecialchars($_POST["uf"]);
        $contato = htmlspecialchars($_POST["contato"]);
        $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
        $lon = filter_input(INPUT_POST, 'lon', FILTER_VALIDATE_FLOAT);

        $sql = "INSERT INTO links (nome, ip, endereco, cidade, uf, contato, lat, lon) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $ip, $endereco, $cidade, $uf, $contato, $lat, $lon]);

        $success = "Link cadastrado com sucesso!";
    } catch (PDOException $e) {
        $error = "Erro ao cadastrar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Link - Spacecom</title>
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
            max-width: 800px;
            margin: 20px auto;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
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

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-row {
            display: flex;
            gap: 20px;
            width: 100%;
        }

        .form-row > .form-group {
            flex: 1;
            min-width: 0;
        }

        label {
            display: block;
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 500;
            letter-spacing: 0.025em;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box;
            backdrop-filter: blur(10px);
        }

        .input-field:focus {
            border-color: var(--success);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
            transform: translateY(-2px);
        }

        .input-field::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50px;
            color: var(--success);
            font-size: 1.1rem;
            z-index: 1;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .button {
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            text-decoration: none;
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .button:hover::before {
            left: 100%;
        }

        .button.primary {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }

        .button.secondary {
            background: var(--secondary-dark);
            color: var(--text-primary);
            border: 1px solid var(--glass-border);
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }

        .button.primary:hover {
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.4);
        }

        .alert {
            border-left: 4px solid transparent;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }

        .alert.success {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .alert.error {
            border-color: var(--error);
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
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
            z-index: 1000;
        }

        @media (max-width: 768px) {
            .card {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .input-field {
                padding-left: 50px;
            }

            .content {
                margin: 100px 15px 80px;
            }

            .sidebar.open ~ .content {
                margin-left: 15px;
            }

            .form-actions {
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
        <h1>Cadastrar Novo Link</h1>
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
            <div class="nav-item">
                <a href="cadastrar.php" class="nav-link active">
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
        <div class="card">
            <?php if(isset($success)): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-container">
                <div class="form-group">
                    <label>Nome do Link</label>
                    <i class="fas fa-link input-icon"></i>
                    <input class="input-field" type="text" name="nome" required 
                        placeholder="Ex: Link Matriz-SP">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Endereço IP</label>
                        <i class="fas fa-network-wired input-icon"></i>
                        <input class="input-field" type="text" name="ip" required 
                            placeholder="192.168.1.1">
                    </div>
                    
                    <div class="form-group">
                        <label>Contato Responsável</label>
                        <i class="fas fa-user input-icon"></i>
                        <input class="input-field" type="text" name="contato" required 
                            placeholder="João Silva">
                    </div>
                </div>

                <div class="form-group">
                    <label>Endereço Físico</label>
                    <i class="fas fa-map-marker-alt input-icon"></i>
                    <input class="input-field" type="text" name="endereco" required 
                        placeholder="Rua Principal, 123">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Cidade</label>
                        <i class="fas fa-city input-icon"></i>
                        <input class="input-field" type="text" name="cidade" required 
                            placeholder="São Paulo">
                    </div>

                    <div class="form-group" style="max-width: 150px">
                        <label>UF</label>
                        <i class="fas fa-flag input-icon"></i>
                        <input class="input-field" type="text" name="uf" maxlength="3" required 
                            placeholder="SP" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Latitude</label>
                        <i class="fas fa-globe-americas input-icon"></i>
                        <input class="input-field" type="number" step="any" name="lat" required 
                            placeholder="-23.5506507">
                    </div>

                    <div class="form-group">
                        <label>Longitude</label>
                        <i class="fas fa-globe-americas input-icon"></i>
                        <input class="input-field" type="number" step="any" name="lon" required 
                            placeholder="-46.6333824">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button primary">
                        <i class="fas fa-save"></i> Cadastrar Link
                    </button>
                    <a href="index.php" class="button secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
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

        // Auto-uppercase UF field
        document.querySelector('input[name="uf"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        // Form validation and enhancement
        document.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>