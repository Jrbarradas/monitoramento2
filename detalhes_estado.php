<?php
require_once "config.php";

if(!isset($_GET['uf'])) {
    die("UF não especificado!");
}

$uf = $_GET['uf'];
$nomeEstado = getNomeEstado($uf);

$stmt = $pdo->prepare("SELECT * FROM links WHERE uf = :uf");
$stmt->bindParam(':uf', $uf, PDO::PARAM_STR);
$stmt->execute();
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($links) === 0) {
    die("Nenhum link encontrado para o estado: " . htmlspecialchars($uf));
}

$linkIds = array_column($links, 'id');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Links do Estado - <?= $nomeEstado ?></title>
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
            height: 70px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .voltar-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            margin-right: 20px;
        }

        .voltar-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .conteudo {
            padding: 100px 30px 80px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .titulo-estado {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .links-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .link-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid #3498db; /* BORDA AZUL PADRÃO */
        }

        /* REMOVIDAS AS CLASSES ONLINE/OFFLINE PARA BORDA */
        
        .link-card .nome {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .link-card .detalhe {
            display: flex;
            margin-bottom: 8px;
        }

        .link-card .detalhe .rotulo {
            font-weight: 500;
            color: #555;
            width: 100px;
        }

        .link-card .detalhe .valor {
            flex: 1;
            color: #333;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .status.online {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .status.offline {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        /* CORREÇÃO: CORES PARA OS ÍCONES */
        .status.online i.fas.fa-circle {
            color: #28a745; /* VERDE PARA ONLINE */
        }

        .status.offline i.fas.fa-circle {
            color: #dc3545; /* VERMELHO PARA OFFLINE */
        }

        .status-icon {
            margin-right: 5px;
            font-size: 0.8rem;
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
        
        .updating {
            filter: brightness(0.9);
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="voltar-btn" onclick="window.history.back();">
            <i class="fas fa-arrow-left"></i> Voltar
        </button>
        <h1>Links do Estado: <?= htmlspecialchars($nomeEstado) ?></h1>
    </div>

    <div class="conteudo">
        <h2 class="titulo-estado">Detalhes dos Links - <?= htmlspecialchars($uf) ?></h2>
        <div class="links-container" id="links-container">
            <?php foreach ($links as $link): ?>
                <div class="link-card" data-id="<?= $link['id'] ?>">
                    <div class="nome"><?= htmlspecialchars($link['nome']) ?></div>
                    <div class="detalhe">
                        <span class="rotulo">IP:</span>
                        <span class="valor"><?= htmlspecialchars($link['ip']) ?></span>
                    </div>
                    <div class="detalhe">
                        <span class="rotulo">Endereço:</span>
                        <span class="valor"><?= htmlspecialchars($link['endereco']) ?></span>
                    </div>
                    <div class="detalhe">
                        <span class="rotulo">Status:</span>
                        <span class="valor">
                            <span class="status <?= $link['status'] ?>" id="status-<?= $link['id'] ?>">
                                <i class="fas fa-circle status-icon"></i> 
                                <?= ucfirst($link['status']) ?>
                            </span>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="rodape">
        Spacecom Monitoramento S/A © 2025 
        <span class="atualizacao-contador" id="contador">Atualizando em: 5s</span>
    </div>

    <script>
        let tempoRestante = 5;
        let intervaloAtualizacao;
        let verificacaoEmAndamento = false;
        const linkIds = <?= json_encode($linkIds) ?>;

        async function verificarStatus() {
            if(verificacaoEmAndamento) return;
            verificacaoEmAndamento = true;
            
            try {
                linkIds.forEach(id => {
                    const card = document.querySelector(`.link-card[data-id="${id}"]`);
                    if(card) card.classList.add('updating');
                });
                
                const response = await fetch('api/status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ids: linkIds })
                });
                
                const linksStatus = await response.json();
                
                linksStatus.forEach(link => {
                    const statusElement = document.getElementById(`status-${link.id}`);
                    if(statusElement) {
                        // Atualiza o texto do status
                        statusElement.textContent = link.status.charAt(0).toUpperCase() + link.status.slice(1);
                        
                        // Atualiza a classe do elemento de status
                        statusElement.className = `status ${link.status}`;
                        
                        // Adiciona o ícone novamente
                        const icon = document.createElement('i');
                        icon.className = 'fas fa-circle status-icon';
                        statusElement.prepend(icon);
                    }
                });
                
            } catch(error) {
                console.error('Erro na verificação de status:', error);
            } finally {
                verificacaoEmAndamento = false;
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

        document.addEventListener('DOMContentLoaded', async () => {
            await verificarStatus();
            iniciarCicloAtualizacao();
            setInterval(verificarStatus, 5000);
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
