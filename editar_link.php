<?php
require_once "config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id']) || !is_numeric($data['id'])) {
            throw new Exception("ID inválido");
        }

        // Validação rigorosa
        $campos = [
            'nome' => filter_var($data['nome'] ?? '', FILTER_SANITIZE_STRING),
            'ip' => filter_var($data['ip'] ?? '', FILTER_VALIDATE_IP),
            'uf' => substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $data['uf'] ?? '')), 0, 3)
        ];

        if (!$campos['ip'] || strlen($campos['uf']) < 2) {
            throw new Exception("Dados inválidos");
        }

        $stmt = $pdo->prepare("UPDATE links SET 
            nome = :nome,
            ip = :ip,
            endereco = :endereco,
            cidade = :cidade,
            uf = :uf,
            contato = :contato,
            updated_at = NOW()
            WHERE id = :id");

        $params = [
            ':id' => (int)$data['id'],
            ':nome' => substr($campos['nome'], 0, 255),
            ':ip' => $campos['ip'],
            ':endereco' => substr($data['endereco'] ?? '', 0, 255),
            ':cidade' => substr($data['cidade'] ?? '', 0, 100),
            ':uf' => $campos['uf'],
            ':contato' => substr($data['contato'] ?? '', 0, 100)
        ];

        if (!$stmt->execute($params)) {
            throw new Exception("Falha na atualização");
        }

        echo json_encode(['success' => true, 'message' => 'Atualizado com sucesso']);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
