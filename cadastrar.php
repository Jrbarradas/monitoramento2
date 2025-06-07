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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f8f9fa;
            margin: 0;
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
            max-width: 700px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            box-sizing: border-box;
            position: relative;
            z-index: 1;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
            width: 100%;
        }

        .form-row {
            display: flex;
            gap: 1.2rem;
            width: 100%;
        }

        .form-row > .form-group {
            flex: 1;
            min-width: 0;
        }

        label {
            display: block;
            font-size: 0.9rem;
            color: #495057;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .input-field {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #f8f9fa;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            box-sizing: border-box;
        }

        .input-field:focus {
            border-color: #4d90fe;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(77,144,254,0.1);
            outline: none;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 34px;
            color: #6c757d;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .button {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .button.primary {
            background: #4d90fe;
            color: white;
        }

        .button.secondary {
            background: none;
            color: #6c757d;
            border: 1px solid #e0e0e0;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .alert {
            border-left: 4px solid transparent;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert.success {
            border-color: #28a745;
            background: rgba(212, 237, 218, 0.95);
        }

        .alert.error {
            border-color: #dc3545;
            background: rgba(248, 215, 218, 0.95);
        }

        @media (max-width: 768px) {
            .card {
                margin: 20px;
                padding: 1.5rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            .input-field {
                padding-left: 2.3rem;
            }
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
    <h1>Cadastrar Novo Link</h1>
   </div>

    <div class="menu-lateral" id="menu">
        <nav>
            <a href="index.php"><i class="fas fa-home"></i>  Home</a>
            <a href="list_links.php"><i class="fas fa-plus-circle"></i>  Cadastrar Link</a>
            <a href="dashboard.php"><i class="fas fa-chart-line"></i>  Lista de Links</a>
            <a href="teste_ping.php"><i class="fas fa-network-wired"></i>  Teste de Ping</a>
            <a href="mapa.php"><i class="fas fa-map-marked-alt"></i>  Mapa da Rede</a>
        </nav>
    </div>

     <div class="conteudo">
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

                <!-- Linha: IP e Contato -->
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

                <!-- Endereço Físico -->
                <div class="form-group">
                    <label>Endereço Físico</label>
                    <i class="fas fa-map-marker-alt input-icon"></i>
                    <input class="input-field" type="text" name="endereco" required 
                        placeholder="Rua Principal, 123">
                </div>

                <!-- Linha: Cidade e UF -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Cidade</label>
                        <i class="fas fa-city input-icon"></i>
                        <input class="input-field" type="text" name="cidade" required 
                            placeholder="São Paulo">
                    </div>

                    <div class="form-group" style="max-width: 120px">
                        <label>UF</label>
                        <input class="input-field" type="text" name="uf" maxlength="2" required 
                            placeholder="SP">
                    </div>
                </div>

                <!-- Linha: Coordenadas -->
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

                <!-- Ações -->
                <div class="form-actions" style="margin-top: 1.5rem">
                    <button type="submit" class="button primary">
                        <i class="fas fa-save"></i> Cadastrar
                    </button>
                    <a href="index.php" class="button secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>


    <div class="rodape">
        Spacecom Monitoramento S/A © 2025
    </div>

    <script>
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

        document.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });
        
    </script>
</body>
</html>
