<?php
// historico_pesquisa.php
require_once "config.php";

// Configurar fuso horário explícito
date_default_timezone_set('America/Sao_Paulo');

// Buscar todos os links
$links = $pdo->query("SELECT id, nome, ip FROM links")->fetchAll(PDO::FETCH_ASSOC);

// Processar o formulário
$dadosGrafico = [];
$dadosTabela = [];
$filtros = [
    'link_id' => '',
    'data_inicio' => date('Y-m-d'),
    'data_fim' => date('Y-m-d'),
    'hora_inicio' => date('H:i', strtotime('-1 hour')),
    'hora_fim' => date('H:i')
];

$mensagem = '';
$mostrarResultados = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = array_merge($filtros, $_POST);
    
    // Validar datas
    $dataInicio = $filtros['data_inicio'] . ' ' . $filtros['hora_inicio'];
    $dataFim = $filtros['data_fim'] . ' ' . $filtros['hora_fim'];
    
    // Converter para formato MySQL
    $dataInicioMySQL = date('Y-m-d H:i:s', strtotime($dataInicio));
    $dataFimMySQL = date('Y-m-d H:i:s', strtotime($dataFim));
    
    if (strtotime($dataFimMySQL) < strtotime($dataInicioMySQL)) {
        $mensagem = '<div class="alert alert-danger">A data final não pode ser anterior à data inicial!</div>';
    } else {
        // Construir consulta SQL corretamente
        $sql = "SELECT 
                    hs.*, 
                    l.nome, 
                    l.ip, 
                    DATE_FORMAT(CONVERT_TZ(hs.checked_at, '+00:00', @@session.time_zone), '%d/%m/%Y %H:%i') AS data_formatada,
                    CONVERT_TZ(hs.checked_at, '+00:00', @@session.time_zone) AS checked_at_local
                FROM historico_status hs
                JOIN links l ON hs.link_id = l.id
                WHERE hs.checked_at BETWEEN ? AND ?";
        
        $params = [$dataInicioMySQL, $dataFimMySQL];
        
        // Adicionar filtro de link se selecionado
        if (!empty($filtros['link_id'])) {
            $sql .= " AND hs.link_id = ?";
            $params[] = $filtros['link_id'];
        }
        
        $sql .= " ORDER BY hs.checked_at ASC";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $dadosTabela = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($dadosTabela)) {
                $mensagem = '<div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Nenhum registro histórico encontrado para os filtros selecionados.
                </div>';
            } else {
                $mostrarResultados = true;
                
                // Preparar dados para o gráfico
                $agrupado = [];
                foreach ($dadosTabela as $registro) {
                    $data = date('Y-m-d H:i', strtotime($registro['checked_at_local']));
                    $agrupado[$data] = $registro['status'] === 'online' ? 100 : 0;
                }
                
                foreach ($agrupado as $data => $status) {
                    $dadosGrafico[] = ['x' => $data, 'y' => $status];
                }
            }
        } catch (PDOException $e) {
            $mensagem = '<div class="alert alert-danger">
                <i class="fas fa-bug"></i> Erro na consulta: ' . htmlspecialchars($e->getMessage()) . '
                <div class="debug-sql">Consulta SQL: ' . htmlspecialchars($sql) . '</div>
                <div class="debug-params">Parâmetros: ' . htmlspecialchars(print_r($params, true)) . '</div>
            </div>';
        }
    }
}

// Verificar se a tabela de histórico existe
$tabelaExiste = false;
try {
    $result = $pdo->query("SELECT 1 FROM historico_status LIMIT 1");
    $tabelaExiste = true;
} catch (Exception $e) {
    $mensagem = '<div class="alert alert-danger">
        <i class="fas fa-database"></i> A tabela de histórico não foi encontrada!
    </div>';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa de Histórico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <style>

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        header h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .filtros-container {
            padding: 20px;
            background: var(--light-color);
            border-bottom: 1px solid #eee;
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filtro-group {
            margin-bottom: 15px;
        }
        
        .filtro-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .filtro-group select, 
        .filtro-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .filtro-group select:focus, 
        .filtro-group input:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn-pesquisar {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto 0;
            transition: background 0.3s;
        }
        
        .btn-pesquisar:hover {
            background: #2980b9;
        }
        
        .btn-pesquisar i {
            margin-right: 8px;
        }
        
        .resultados-container {
            padding: 20px;
        }
        
        .grafico-container {
            height: 400px;
            margin-bottom: 30px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .tabela-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #e9f7fe;
        }
        
        .status-online {
            color: var(--success-color);
            font-weight: bold;
        }
        
        .status-offline {
            color: var(--danger-color);
            font-weight: bold;
        }
        
        .latencia {
            font-family: 'Roboto Mono', monospace;
            font-size: 14px;
        }
        
        .sem-resultados {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .sem-resultados i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
	.alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .debug-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            margin-top: 15px;
        }
        
        .debug-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .debug-sql, .debug-params {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            border-left: 3px solid #dc3545;
        }
        
        .date-range {
            background-color: #e9ecef;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }
        
        .timezone-info {
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            margin-top: 10px;
        }


        footer {
            text-align: center;
            padding: 20px;
            background: var(--light-color);
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .grafico-container {
                height: 300px;
            }
        }

	.botoes-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-voltar {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto 0;
            transition: background 0.3s;
        }
        
        .btn-voltar:hover {
            background: #5a6268;
        }
        
        .btn-limpar {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto 0;
            transition: background 0.3s;
        }
        
        .btn-limpar:hover {
            background: #d8dbdf;
        }



    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-history"></i> Pesquisa de Histórico de Links</h1>
            <p>Selecione os filtros para pesquisar o histórico de disponibilidade</p>
        </header>
        
        <form method="POST" class="filtros-container">
            <?= $mensagem ?>
            
            <?php if (!$tabelaExiste): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    ATENÇÃO: A tabela de histórico não foi encontrada no banco de dados!
                </div>
            <?php endif; ?>
            
            <div class="filtros-grid">
                <div class="filtro-group">
                    <label for="link_id">Link:</label>
                    <select id="link_id" name="link_id">
                        <option value="">Todos os Links</option>
                        <?php foreach ($links as $link): ?>
                            <option value="<?= $link['id'] ?>" <?= $filtros['link_id'] == $link['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($link['nome']) ?> (<?= htmlspecialchars($link['ip']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <label for="data_inicio">Data Inicial:</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="<?= $filtros['data_inicio'] ?>" required>
                </div>
                
                <div class="filtro-group">
                    <label for="data_fim">Data Final:</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?= $filtros['data_fim'] ?>" required>
                </div>
                
                <div class="filtro-group">
                    <label for="hora_inicio">Hora Inicial:</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" value="<?= $filtros['hora_inicio'] ?>">
                </div>
                
                <div class="filtro-group">
                    <label for="hora_fim">Hora Final:</label>
                    <input type="time" id="hora_fim" name="hora_fim" value="<?= $filtros['hora_fim'] ?>">
                </div>
            </div>
        	    
	    <div class="botoes-container">
                <!-- Botão VOLTAR -->
                <a href="index.php" class="btn btn-voltar">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                
                <!-- Botão LIMPAR -->
                <button type="button" class="btn btn-limpar" id="btn-limpar">
                    <i class="fas fa-broom"></i> Limpar
                </button>
                
                <!-- Botão PESQUISAR existente -->
                <button type="submit" class="btn btn-pesquisar">
                    <i class="fas fa-search"></i> Pesquisar Histórico
                </button>
            </div>


        </form>
        
        <?php if ($mostrarResultados): ?>
            <div class="date-range">
                <i class="fas fa-calendar-alt"></i> Exibindo resultados de 
                <?= date('d/m/Y H:i', strtotime($dataInicio)) ?> 
                até 
                <?= date('d/m/Y H:i', strtotime($dataFim)) ?>
            </div>
            
            <div class="resultados-container">
                <div class="grafico-container">
                    <canvas id="graficoDisponibilidade"></canvas>
                </div>
                
                <div class="tabela-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Link</th>
                                <th>IP</th>
                                <th>Data/Hora</th>
                                <th>Status</th>
                                <th>Latência</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dadosTabela as $registro): ?>
                                <tr>
                                    <td><?= htmlspecialchars($registro['nome']) ?></td>
                                    <td><?= htmlspecialchars($registro['ip']) ?></td>
                                    <td><?= $registro['data_formatada'] ?></td>
                                    <td class="status-<?= $registro['status'] ?>">
                                        <?= $registro['status'] === 'online' ? 'Online' : 'Offline' ?>
                                    </td>
                                    <td class="latencia">
                                        <?= $registro['status'] === 'online' ? ($registro['latency'] ?? '0') . ' ms' : '--' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="resultados-container">
                <div class="sem-resultados">
                    <i class="fas fa-database"></i>
                    <h3>Nenhum registro encontrado</h3>
                    <p>Não foram encontrados registros para os filtros selecionados.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="resultados-container">
                <div class="sem-resultados">
                    <i class="fas fa-chart-line"></i>
                    <h3>Selecione os filtros para pesquisar</h3>
                    <p>Utilize o formulário acima para pesquisar o histórico de disponibilidade.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <footer>
            Spacecom Monitoramento &copy; <?= date('Y') ?> - Todos os direitos reservados
        </footer>
    </div>
    
    <script>

	// Script para limpar os campos
        document.getElementById('btn-limpar').addEventListener('click', function() {
            // Resetar selects
            document.getElementById('link_id').selectedIndex = 0;
            
            // Resetar datas
            const hoje = new Date().toISOString().split('T')[0];
            document.getElementById('data_inicio').value = hoje;
            document.getElementById('data_fim').value = hoje;
            
            // Resetar horas
            const agora = new Date();
            const horaInicio = new Date(agora);
            horaInicio.setHours(agora.getHours() - 1);
            
            document.getElementById('hora_inicio').value = 
                horaInicio.getHours().toString().padStart(2, '0') + ':' + 
                horaInicio.getMinutes().toString().padStart(2, '0');
                
            document.getElementById('hora_fim').value = 
                agora.getHours().toString().padStart(2, '0') + ':' + 
                agora.getMinutes().toString().padStart(2, '0');
        });

        <?php if (!empty($dadosGrafico)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('graficoDisponibilidade').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: 'Disponibilidade do Link',
                            data: <?= json_encode($dadosGrafico) ?>,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: function(context) {
                                return context.raw.y === 100 ? '#28a745' : '#dc3545';
                            },
                            pointBorderColor: '#fff',
                            pointBorderWidth: 1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'minute',
                                    displayFormats: {
                                        minute: 'HH:mm'
                                    },
                                    tooltipFormat: 'dd/MM HH:mm'
                                },
                                title: {
                                    display: true,
                                    text: 'Horário',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            },
                            y: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    },
                                    stepSize: 25
                                },
                                title: {
                                    display: true,
                                    text: 'Disponibilidade',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.raw.y === 100 ? 'Online' : 'Offline';
                                    },
                                    title: function(context) {
                                        return context[0].raw.x;
                                    }
                                }
                            },
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Histórico de Disponibilidade',
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                });
            });
        <?php endif; ?>
        
        // Configurar valores padrão para data/hora
        document.addEventListener('DOMContentLoaded', function() {
            // Definir data atual como padrão
            const hoje = new Date().toISOString().split('T')[0];
            document.getElementById('data_inicio').value = hoje;
            document.getElementById('data_fim').value = hoje;
            
            // Definir hora atual -1 hora e hora atual
            const agora = new Date();
            const horaAtual = agora.getHours().toString().padStart(2, '0');
            const minutoAtual = agora.getMinutes().toString().padStart(2, '0');
            
            // Hora inicial: 1 hora atrás
            const horaInicio = new Date(agora);
            horaInicio.setHours(agora.getHours() - 1);
            document.getElementById('hora_inicio').value = 
                horaInicio.getHours().toString().padStart(2, '0') + ':' + 
                horaInicio.getMinutes().toString().padStart(2, '0');
            
            // Hora final: agora
            document.getElementById('hora_fim').value = horaAtual + ':' + minutoAtual;
        });
    </script>
</body>
</html>
