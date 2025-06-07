<?php
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM links WHERE id = :id");
        $stmt->execute([':id' => filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT)]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao excluir: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
