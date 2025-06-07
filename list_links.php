<?php
require_once "config.php";
$links = $pdo->query("SELECT * FROM links")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Links - Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
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
            margin: 130px 30px 120px;
            transition: 0.3s;
            z-index: 1;
            min-height: calc(100vh - 250px);
            overflow-y: auto;
            position: relative;
        }

	.menu-aberto .conteudo {
            margin-left: 280px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            min-width: 800px;
        }

        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background: #2c3e50;
            color: white;
            position: sticky;
            top: 60px;
            z-index: 998;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .online .status-indicator {
            background: #28a745;
        }

        .offline .status-indicator {
            background: #dc3545;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0% { opacity: 0.1; }
	    25%{ opacity: 0.3; }
            50% { opacity: 0.5; }
	    75% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
            font-weight: 500;
        }

        .button.edit {
            background: #3498db;
            color: white;
        }

        .button.delete {
            background: #dc3545;
            color: white;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
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

        .edit-form {
            display: none;
            padding: 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .editing .view-mode {
            display: none;
        }

        .editing .edit-form {
            display: block;
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
        <h1>Lista de Links</h1>
    </div>

    <div class="menu-lateral" id="menu">
        <nav>
            <a href="index.php"><i class="fas fa-home"></i>Home</a>
            <a href="cadastrar.php"><i class="fas fa-plus-circle"></i>  Cadastrar Link</a>
            <a href="list_links.php"><i class="fas fa-chart-line"></i>  Lista de Links</a>
            <a href="teste_ping.php"><i class="fas fa-network-wired"></i>  Teste de Ping</a>
            <a href="mapa.php"><i class="fas fa-map-marked-alt"></i>  Mapa da Rede</a>
        </nav>
    </div>

    <div class="conteudo">
        <div class="card">
            <h2>Status dos Links</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>IP</th>
                        <th>Endereço</th>
                        <th>UF</th>
                        <th>Contato</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr data-id="<?= $link['id'] ?>">
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
                            <span class="status-indicator" data-ip="<?= $link['ip'] ?>"></span>
                            <span class="status-text">Verificando...</span>
                        </td>
                        <td>
                            <button class="button edit edit-btn">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="button save-btn" style="display: none;">
                                <i class="fas fa-save"></i>
                            </button>
                            <button class="button delete delete-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

        // Verificação de Status
        document.querySelectorAll('.status-indicator').forEach(indicator => {
            const checkStatus = async () => {
                try {
                    const response = await fetch(`ping.php?ip=${encodeURIComponent(indicator.dataset.ip)}`);
                    const data = await response.json();
                    
                    const row = indicator.closest('tr');
                    row.classList.remove('online', 'offline');
                    row.classList.add(data.status);
                    row.querySelector('.status-text').textContent = 
                        data.status.charAt(0).toUpperCase() + data.status.slice(1);
                } catch (error) {
                    console.error('Erro na verificação:', error);
                }
            };
            
            checkStatus();
            setInterval(checkStatus, 15000);
        });

        // Controle de Edição
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('tr');
                row.classList.add('editing');
                btn.style.display = 'none';
                row.querySelector('.save-btn').style.display = 'inline-flex';
            });
        });

        // Controle de Salvamento
        document.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('tr');
                const inputs = row.querySelectorAll('.edit-form');
                const data = {
                    id: row.dataset.id,
                    nome: inputs[0].value,
                    ip: inputs[1].value,
                    endereco: inputs[2].value,
                    uf: inputs[3].value,
                    contato: inputs[4].value
                };

                try {
                    const response = await fetch('editar_link.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
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
                    }
                } catch (error) {
                    console.error('Erro:', error);
                }
            });
        });

        // Controle de Exclusão
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (confirm('Tem certeza que deseja excluir este link permanentemente?')) {
                    const row = btn.closest('tr');
                    try {
                        const response = await fetch('excluir_link.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ id: row.dataset.id })
                        });
                        
                        if (response.ok) {
                            row.remove();
                        }
                    } catch (error) {
                        console.error('Erro:', error);
                        alert('Erro ao excluir o link');
                    }
                }
            });
        });
    </script>
</body>
</html>
