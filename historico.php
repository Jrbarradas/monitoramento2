<?php
// historico_pesquisa.php
require_once "config.php";

// Configurar fuso horário explícito
date_default_timezone_set('America/Sao_Paulo');

// Buscar todos os links
$links = $pdo->query("SELECT id, nome, ip FROM links ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Histórico de Monitoramento - Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .page-header {
            background: linear-gradient(135deg, var(--secondary-dark), var(--accent-dark));
            color: var(--text-primary);
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid var(--glass-border);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .filters-container {
            padding: 30px;
            background: rgba(15, 23, 42, 0.3);
            border-bottom: 1px solid var(--glass-border);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .filter-group select, 
        .filter-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            font-size: 0.95rem;
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .filter-group select:focus, 
        .filter-group input:focus {
            border-color: var(--success);
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 0.95rem;
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
        }

        .button.secondary {
            background: var(--secondary-dark);
            color: var(--text-primary);
            border: 1px solid var(--glass-border);
        }

        .button.clear {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .results-container {
            padding: 30px;
        }

        .chart-container {
            height: 400px;
            margin-bottom: 30px;
            background: rgba(15, 23, 42, 0.3);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--glass-border);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
            background: rgba(15, 23, 42, 0.3);
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--glass-border);
        }

        th {
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

        tr:nth-child(even) {
            background: rgba(15, 23, 42, 0.2);
        }

        tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }

        .status-online {
            color: var(--success);
            font-weight: 600;
        }

        .status-offline {
            color: var(--error);
            font-weight: 600;
        }

        .latencia {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--accent-dark);
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(10px);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .date-range {
            background: rgba(16, 185, 129, 0.1);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .footer {
            text-align: center;
            padding: 25px;
            background: rgba(15, 23, 42, 0.3);
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 1px solid var(--glass-border);
        }

        @media (max-width: 768px) {
            .content {
                margin: 100px 15px 80px;
            }

            .sidebar.open ~ .content {
                margin-left: 15px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 300px;
            }

            .button-group {
                flex-direction: column;
            }

            .page-title {
                font-size: 1.5rem;
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
        <h1>Histórico de Monitoramento</h1>
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
                <a href="cadastrar.php" class="nav-link">
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
                <a href="historico.php" class="nav-link active">
                    <div class="nav-icon"><i class="fas fa-history"></i></div>
                    <div class="nav-text">Histórico</div>
                </a>
            </div>
        </nav>
    </div>

    <div class="content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-history"></i> Histórico de Monitoramento</h1>
                <p class="page-subtitle">Consulte o histórico de disponibilidade dos links</p>
            </div>
            
            <form method="POST" class="filters-container">
                <?= $mensagem ?>
                
                <?php if (!$tabelaExiste): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        ATENÇÃO: A tabela de histórico não foi encontrada no banco de dados!
                    </div>
                <?php endif; ?>
                
                <div class="filters-grid">
                    <div class="filter-group">
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
                    
                    <div class="filter-group">
                        <label for="data_inicio">Data Inicial:</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?= $filtros['data_inicio'] ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="data_fim">Data Final:</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?= $filtros['data_fim'] ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="hora_inicio">Hora Inicial:</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" value="<?= $filtros['hora_inicio'] ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="hora_fim">Hora Final:</label>
                        <input type="time" id="hora_fim" name="hora_fim" value="<?= $filtros['hora_fim'] ?>">
                    </div>
                </div>
                
                <div class="button-group">
                    <a href="index.php" class="button secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    
                    <button type="button" class="button clear" id="btn-limpar">
                        <i class="fas fa-broom"></i> Limpar
                    </button>
                    
                    <button type="submit" class="button primary">
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
                
                <div class="results-container">
                    <div class="chart-container">
                        <canvas id="graficoDisponibilidade"></canvas>
                    </div>
                    
                    <div class="table-container">
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
                <div class="results-container">
                    <div class="no-results">
                        <i class="fas fa-database"></i>
                        <h3>Nenhum registro encontrado</h3>
                        <p>Não foram encontrados registros para os filtros selecionados.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="results-container">
                    <div class="no-results">
                        <i class="fas fa-chart-line"></i>
                        <h3>Selecione os filtros para pesquisar</h3>
                        <p>Utilize o formulário acima para pesquisar o histórico de disponibilidade.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                Spacecom Monitoramento &copy; <?= date('Y') ?> - Todos os direitos reservados
            </div>
        </div>
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

        // Clear button functionality
        document.getElementById('btn-limpar').addEventListener('click', function() {
            document.getElementById('link_id').selectedIndex = 0;
            
            const hoje = new Date().toISOString().split('T')[0];
            document.getElementById('data_inicio').value = hoje;
            document.getElementById('data_fim').value = hoje;
            
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
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: function(context) {
                                return context.raw.y === 100 ? '#10b981' : '#ef4444';
                            },
                            pointBorderColor: '#f8fafc',
                            pointBorderWidth: 2,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Histórico de Disponibilidade',
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                },
                                color: '#f8fafc'
                            }
                        },
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
                                    },
                                    color: '#cbd5e1'
                                },
                                ticks: {
                                    color: '#cbd5e1'
                                },
                                grid: {
                                    color: 'rgba(203, 213, 225, 0.1)'
                                }
                            },
                            y: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    },
                                    stepSize: 25,
                                    color: '#cbd5e1'
                                },
                                title: {
                                    display: true,
                                    text: 'Disponibilidade',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    color: '#cbd5e1'
                                },
                                grid: {
                                    color: 'rgba(203, 213, 225, 0.1)'
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            });
        <?php endif; ?>
        
        // Set default values for date/time
        document.addEventListener('DOMContentLoaded', function() {
            const hoje = new Date().toISOString().split('T')[0];
            if (!document.getElementById('data_inicio').value) {
                document.getElementById('data_inicio').value = hoje;
            }
            if (!document.getElementById('data_fim').value) {
                document.getElementById('data_fim').value = hoje;
            }
            
            const agora = new Date();
            const horaAtual = agora.getHours().toString().padStart(2, '0');
            const minutoAtual = agora.getMinutes().toString().padStart(2, '0');
            
            if (!document.getElementById('hora_inicio').value) {
                const horaInicio = new Date(agora);
                horaInicio.setHours(agora.getHours() - 1);
                document.getElementById('hora_inicio').value = 
                    horaInicio.getHours().toString().padStart(2, '0') + ':' + 
                    horaInicio.getMinutes().toString().padStart(2, '0');
            }
            
            if (!document.getElementById('hora_fim').value) {
                document.getElementById('hora_fim').value = horaAtual + ':' + minutoAtual;
            }
        });
    </script>
</body>
</html>