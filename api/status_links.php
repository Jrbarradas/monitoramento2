<?php
require_once "../config.php";

// Ler dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);
$ids = $data['ids'] ?? [];

if (empty($ids)) {
    echo json_encode([]);
    exit;
}

// Criar placeholders para a query
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id, status FROM links WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($links);
