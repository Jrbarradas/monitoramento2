<?php
require_once '../config.php';

header('Content-Type: application/json');

// --- Configuração ---
date_default_timezone_set('America/Sao_Paulo'); // Ajuste conforme necessário
$intervaloDias = 7; // Buscar histórico dos últimos 7 dias
// ------------------

if (!isset($_GET['uf'])) {
    echo json_encode(['error' => 'Parâmetro UF não fornecido.']);
    http_response_code(400);
    exit;
}

$uf = $_GET['uf'];
$dataLimite = date('Y-m-d H:i:s', strtotime("-{$intervaloDias} days"));

$logsResumidos = [];

try {
    // 1. Buscar todos os links do estado para iterar
    $stmtLinks = $pdo->prepare("SELECT id, nome FROM links WHERE uf = :uf");
    $stmtLinks->bindParam(':uf', $uf, PDO::PARAM_STR);
    $stmtLinks->execute();
    $linksDoEstado = $stmtLinks->fetchAll(PDO::FETCH_ASSOC);

    if (empty($linksDoEstado)) {
        echo json_encode([]); // Retorna array vazio se não há links para o estado
        exit;
    }

    // 2. Para cada link, buscar seu histórico recente e identificar mudanças
    $stmtHistorico = $pdo->prepare(
        SELECT 
            checked_at, 
            status,
            LAG(status) OVER (ORDER BY checked_at ASC) as status_anterior
         FROM historico_status 
         WHERE link_id = :link_id AND checked_at >= :data_limite
         ORDER BY checked_at ASC
    );

    foreach ($linksDoEstado as $link) {
        $linkId = $link['id'];
        $nomeLink = $link['nome'];

        $stmtHistorico->bindParam(':link_id', $linkId, PDO::PARAM_INT);
        $stmtHistorico->bindParam(':data_limite', $dataLimite, PDO::PARAM_STR);
        $stmtHistorico->execute();
        $historicoLink = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);

        foreach ($historicoLink as $log) {
            // Adiciona ao log resumido apenas se o status mudou OU se for o primeiro registro da consulta
            if ($log['status'] !== $log['status_anterior'] || $log['status_anterior'] === null) {
                $logsResumidos[] = [
                    'horario' => date('d/m/Y H:i:s', strtotime($log['checked_at'])), // Formata para exibição
                    'timestamp' => strtotime($log['checked_at']), // Para ordenação posterior
                    'status' => $log['status'],
                    'descricao' => "Link '{$nomeLink}' ficou {$log['status']}"
                ];
            }
        }
    }

    // 3. Ordenar todos os logs resumidos por timestamp
    usort($logsResumidos, function($a, $b) {
        return $b['timestamp'] <=> $a['timestamp']; // Ordena do mais recente para o mais antigo
    });

    // 4. Remover a chave timestamp antes de enviar o JSON
    $logsFinais = array_map(function($log) {
        unset($log['timestamp']);
        return $log;
    }, $logsResumidos);

    echo json_encode($logsFinais);

} catch (PDOException $e) {
    // Em produção, logar o erro em vez de expor detalhes
    error_log("Erro na API de histórico: " . $e->getMessage()); 
    echo json_encode(['error' => 'Erro ao buscar histórico.', 'details' => $e->getMessage()]); // Mantenha details apenas para debug
    http_response_code(500);
    exit;
}
?>
