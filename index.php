<?php
require_once "config.php";
$links = $pdo->query("SELECT * FROM links")->fetchAll(PDO::FETCH_ASSOC);

$estados = [];
foreach ($links as $link) {
    $uf = $link['uf'];
    $estados[$uf][] = $link;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Monitoramento de Links Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
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

        .status-container {
            display: flex;
            gap: 18px;
            margin-right: 60px;
            align-items: center;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 16px;
            border-radius: 30px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(6px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .status-item:hover {
            background: linear-gradient(
            to bottom,
            rgba(255,255,255,0.1),
            transparent
            );
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .status-item.online {
            background: rgba(40, 167, 69, 0.9);
	    color: #f0fff4;
            border-color: rgba(32, 135, 56, 0.5);
        }

        .status-item.offline {
            background: rgba(220, 53, 69, 0.9);
    	    color: #fff5f5;
    	    border-color: rgba(200, 35, 51, 0.5);
        }

        .status-item i {
            font-size: 1.1rem;
    	    opacity: 0.9;
    	    filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
        }

        .status-item span {
            font-size: 1.1rem;
    	    font-weight: 600;
    	    letter-spacing: 0.5px;
    	    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
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
            margin: 130px 30px 80px;
            transition: 0.3s;
        }

        .menu-aberto .conteudo {
            margin-left: 280px;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            animation: cardEntrance 0.6s ease-out;
        }

        .estado-card {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border: 2px solid white;
            position: relative;
            overflow: hidden;
        }

        .estado-card::before {
            content: '';
            position: absolute;
            background: radial-gradient(400px circle at var(--x) var(--y), 
                rgba(255,255,255,0.15), transparent);
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .estado-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 12px 25px rgba(0,0,0,0.2);
        }

        .estado-card:hover::before {
            opacity: 1;
        }

        .uf {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            z-index: 1;
        }

        .count {
            color: #666;
            font-size: 13px;
            margin-top: 8px;
            font-weight: 500;
            z-index: 1;
        }

        .online {
            background: linear-gradient(145deg, #28a745, #218838) !important;
            border-color: #28a745;
        }

        .online .uf,
        .online .count {
            color: white;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .offline {
            background: linear-gradient(145deg, #dc3545, #c82333);
            border-color: #dc3545;
            animation: blink 1.2s infinite;
        }

        @keyframes blink {
            0% { opacity: 0.1; }
	    25% { opacity: 0.3;}
            50% { opacity: 0.5; }
	    75% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

	/* Adicionar efeito para card que está sendo atualizado */
        .updating {
            filter: brightness(0.9);
            transform: scale(0.98);
        }

        .rodape {
            position: fixed;
            bottom: 0;
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
	    z-index: 1000;
        }

        .atualizacao-contador {
            font-family: 'Roboto Mono', monospace;
            background: rgba(255,255,255,0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
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
        <h1>Monitoramento de Links Spacecom</h1>
        <div class="status-container">
            <div class="status-item online">
                <i class="fas fa-link"></i>
                <span id="total-online">0</span>
            </div>
            <div class="status-item offline">
                <i class="fas fa-unlink"></i>
                <span id="total-offline">0</span>
            </div>
        </div>
    </div>

    <div class="menu-lateral" id="menu">
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="cadastrar.php"><i class="fas fa-plus-circle"></i> Cadastrar Link</a>
            <a href="list_links.php"><i class="fas fa-chart-line"></i> Lista de Links</a>
            <a href="teste_ping.php"><i class="fas fa-network-wired"></i> Teste de Ping</a>
            <a href="mapa.php"><i class="fas fa-map-marked-alt"></i> Mapa de Rede</a>
	    <a href="historico.php"><i class="fas fa-history"></i> Histórico </a>
        </nav>
    </div>

    <div class="conteudo">
        <div class="cards-container">
            <?php foreach ($estados as $uf => $linksEstado): ?>
                <!-- Adicionado link para a página de detalhes -->
                <a href="detalhes_estado.php?uf=<?= urlencode($uf) ?>" style="text-decoration: none; color: inherit;">
                    <div class="estado-card" 
                         data-ips="<?= htmlspecialchars(json_encode(array_column($linksEstado, 'ip'))) ?>">
                        <div class="uf"><?= htmlspecialchars($uf) ?></div>
                        <div class="count"><?= count($linksEstado) ?> link(s)</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="rodape">
        Spacecom Monitoramento S/A © 2025 
        <span class="atualizacao-contador" id="contador">Atualizando em: 5s</span>
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

        // Sistema de Verificação de Status usando API centralizada
        let tempoRestante = 5;
        let intervaloAtualizacao;
        let verificacaoEmAndamento = false;
        
        // Mapeamento de estado para cards
        const estadoCards = {};
        document.querySelectorAll('.estado-card').forEach(card => {
            const uf = card.querySelector('.uf').textContent;
            estadoCards[uf] = card;
        });

        async function verificarStatus() {
            if(verificacaoEmAndamento) return;
            verificacaoEmAndamento = true;
            
            try {
                // Adicionar classe de atualização aos cards
                Object.values(estadoCards).forEach(card => {
                    card.classList.add('updating');
                });
                
                const response = await fetch('api/status.php');
                const links = await response.json();
                
                // Agrupar por estado e calcular status
                const statusPorEstado = {};
                let totalOnline = 0;
                let totalOffline = 0;
                
                links.forEach(link => {
                    const uf = link.uf;
                    if(!statusPorEstado[uf]) {
                        statusPorEstado[uf] = {
                            online: 0,
                            offline: 0,
                            links: []
                        };
                    }
                    
                    if(link.status === 'online') {
                        statusPorEstado[uf].online++;
                        totalOnline++;
                    } else {
                        statusPorEstado[uf].offline++;
                        totalOffline++;
                    }
                    
                    statusPorEstado[uf].links.push(link);
                });
                
                // Atualizar UI
                for(const uf in statusPorEstado) {
                    const card = estadoCards[uf];
                    if(card) {
                        const status = statusPorEstado[uf].offline > 0 ? 'offline' : 'online';
                        
                        // Atualizar classes
                        card.classList.remove('online', 'offline', 'updating');
                        card.classList.add(status);
                        
                        // Atualizar contador
                        const countElement = card.querySelector('.count');
                        if(countElement) {
                            const total = statusPorEstado[uf].online + statusPorEstado[uf].offline;
                            const offlineText = statusPorEstado[uf].offline > 0 ? 
                                ` (<span style="color:#ff6b6b">${statusPorEstado[uf].offline} off</span>)` : '';
                            countElement.innerHTML = `${total} link(s)${offlineText}`;
                        }
                    }
                }
                
                // Atualizar contadores globais
                document.getElementById('total-online').textContent = totalOnline;
                document.getElementById('total-offline').textContent = totalOffline;
                
            } catch(error) {
                console.error('Erro na verificação de status:', error);
            } finally {
                verificacaoEmAndamento = false;
                
                // Remover classe de atualização
                Object.values(estadoCards).forEach(card => {
                    card.classList.remove('updating');
                });
            }
        }

        function iniciarCicloAtualizacao() {
            intervaloAtualizacao = setInterval(() => {
                tempoRestante--;
                document.getElementById('contador').textContent = `Atualizando em: ${tempoRestante}s`;
                
                if(tempoRestante <= 0) {
                    tempoRestante = 5;
                    verificarStatus();
                }
            }, 1000);
        }

        // Efeito de Hover Dinâmico
        document.querySelectorAll('.estado-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--x', `${x}px`);
                card.style.setProperty('--y', `${y}px`);
            });
        });

        // Inicialização
        document.addEventListener('DOMContentLoaded', async () => {
            await verificarStatus();
            iniciarCicloAtualizacao();
            setInterval(verificarStatus, 5000); // Atualizar a cada 5 segundos
        });

    </script>
</body>
</html>

<?php
function getNomeEstado($uf) {
    $estados = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SPC'=> 'Spacecom',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins'
    ];
    return $estados[strtoupper($uf)] ?? 'Estado Desconhecido';
}
?>
