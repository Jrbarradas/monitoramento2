<?php
// excluir_link.php
require_once "config.php";

// Verificar se o método é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter e validar dados
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$id = (int)$data['id'];

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // 1. Excluir histórico relacionado
    $stmt = $pdo->prepare("DELETE FROM historico_status WHERE link_id = ?");
    $stmt->execute([$id]);
    
    // 2. Excluir link principal
    $stmt = $pdo->prepare("DELETE FROM links WHERE id = ?");
    $stmt->execute([$id]);
    
    // Confirmar transação
    $pdo->commit();
    
    // Retornar sucesso
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Reverter em caso de erro
    $pdo->rollBack();
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
