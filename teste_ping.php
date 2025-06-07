<?php
$resultado = "";
$classeStatus = "";
$ip = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip = htmlspecialchars($_POST["ip"]);
    $ping = shell_exec("ping -c 4 " . escapeshellarg($ip));
    $resultado = (strpos($ping, "0 received") === false) ? "ONLINE" : "OFFLINE";
    $classeStatus = ($resultado == "ONLINE") ? "success" : "error";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Ping - Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos consistentes com index.php */
        body {
            font-family: 'Roboto', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 70px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
        }

        .header h1 {
            flex-grow: 1;
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .botao-menu {
            background: rgba(255,255,255,0.1);
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
            margin-right: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .botao-menu:hover {
            background: rgba(255,255,255,0.2);
        }

	.botao-menu__linha {
            width: 22px;
            height: 2px;
            background: white;
            transition: 0.3s;
            position: absolute;
        }

        .botao-menu__linha:nth-child(1) { transform: translateY(-8px); }
        .botao-menu__linha:nth-child(3) { transform: translateY(8px); }

        .menu-lateral {
            position: fixed;
            left: -250px;
            top: 70px;
            height: calc(100% - 120px);
            width: 250px;
            background: rgba(52, 73, 94, 0.95);
            backdrop-filter: blur(15px);
            transition: 0.3s;
            z-index: 999;
            padding-top: 25px;
            overflow-y: auto;
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        .menu-lateral.aberto {
            left: 0;
        }

        .menu-lateral nav {
            display: flex;
            flex-direction: column;
            padding: 1rem;
        }

        .menu-lateral a {
            color: white;
            text-decoration: none;
            padding: 1rem 1.5rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
            background: rgba(255,255,255,0.05);
        }

        .menu-lateral a:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(10px);
        }

        .conteudo {
            margin: 80px 20px 70px;
            transition: 0.3s;
        }

	.menu-aberto .conteudo {
            margin-left: 280px;
        }


        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Estilos específicos do formulário */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            color: #3498db;
            font-size: 1.2rem;
        }

        .input-field {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: 0.3s;
            background: #f8f9fa;
        }

        .input-field:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
            background: white;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            font-weight: 500;
        }

        .button.primary {
            background: #3498db;
            color: white;
        }

        .button.secondary {
            background: #95a5a6;
            color: white;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }

        .button.primary:hover {
            background: #2980b9;
        }

        .button.secondary:hover {
            background: #7f8c8d;
        }

         .rodape {
            position: fixed;
            bottom: 0;
            z-index: 1000;
            left: 0;
            right: 0;
            background: rgba(44, 62, 80, 0.95);
            color: #ecf0f1;
            padding: 18px 25px;
            font-size: 0.95rem;
            text-align: center;
            backdrop-filter: blur(8px);
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

    </style>
</head>
<body>
    <div class="header">
        <button class="botao-menu" id="botao-menu">
            <span class="botao-menu__linha"></span>
            <span class="botao-menu__linha"></span>
            <span class="botao-menu__linha"></span>
        </button>
        <h1>Teste de Ping</h1>
    </div>

    <div class="menu-lateral" id="menu">
        <nav>
            <a href="index.php"><i class="fas fa-home"></i>Home</a>
            <a href="cadastrar.php"><i class="fas fa-plus-circle"></i>Cadastrar Link</a>
            <a href="list_links.php"><i class="fas fa-chart-line"></i>Lista de Links</a>
            <a href="teste_ping.php"><i class="fas fa-network-wired"></i>Teste de Ping</a>
            <a href="mapa.php"><i class="fas fa-map-marked-alt"></i>Mapa da Rede</a>
        </nav>
    </div>

    <div class="conteudo">
        <div class="card">
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>Endereço IP para Teste</label>
                        <div class="input-with-icon">
                            <i class="fas fa-network-wired"></i>
                            <input class="input-field" type="text" name="ip" required 
                                placeholder="Ex: 192.168.1.1 ou dominio.com.br"
                                value="<?= htmlspecialchars($ip) ?>">
                        </div>
                    </div>

                    <?php if(!empty($resultado)): ?>
                    <div class="alert <?= $classeStatus ?>">
                        <i class="fas fa-<?= ($resultado == 'ONLINE') ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        IP <?= htmlspecialchars($ip) ?> está <strong><?= $resultado ?></strong>
                    </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="button primary">
                            <i class="fas fa-satellite-dish"></i> Executar Teste
                        </button>
                        <a href="index.php" class="button secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="rodape">
        Spacecom Monitoramento S/A © 2025
    </div>

    <script>
        // Controle do Menu
        const menuBtn = document.getElementById('botao-menu');
        const menu = document.getElementById('menu');
        const body = document.body;

        menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('aberto');
            body.classList.toggle('menu-aberto');
        });

        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target) && !menuBtn.contains(e.target)) {
                menu.classList.remove('aberto');
                body.classList.remove('menu-aberto');
            }
        });
    </script>
</body>
</html>
