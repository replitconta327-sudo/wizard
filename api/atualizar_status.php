<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $pedido_id = $_POST['pedido_id'] ?? null;
    $status_id = $_POST['status_id'] ?? null;
    
    if (!$pedido_id || !$status_id) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE pedidos SET status_id = ?, atualizado_em = datetime('now') WHERE id = ?");
    $stmt->execute([$status_id, $pedido_id]);

    echo json_encode(['success' => true, 'message' => 'Status atualizado']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
