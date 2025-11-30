<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    $pedido_id = $_POST['pedido_id'] ?? $_GET['pedido_id'] ?? null;
    
    if (!$action || !$pedido_id) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }

    if ($action === 'update_status') {
        $status_id = $_POST['status_id'] ?? null;
        
        if (!$status_id) {
            echo json_encode(['success' => false, 'message' => 'Status não informado']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE pedidos 
            SET status_id = ?, atualizado_em = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$status_id, $pedido_id]);

        // Se é JSON request, retorna JSON
        if (isset($_POST['json']) && $_POST['json'] === '1') {
            echo json_encode(['success' => true, 'message' => 'Status atualizado']);
        } else {
            // Se é form POST, redireciona
            header('Location: /admin/pedido_detalhes.php?id=' . $pedido_id . '&success=1');
        }
    }
} catch (Exception $e) {
    error_log('Erro ao atualizar pedido: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
}
