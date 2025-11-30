<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $ultimo_id = $_GET['ultimo_id'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.numero_pedido, p.criado_em, u.nome as cliente
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id > ? AND p.status_id = 1
        ORDER BY p.criado_em DESC
        LIMIT 1
    ");
    $stmt->execute([$ultimo_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido) {
        echo json_encode([
            'novo_pedido' => true,
            'id' => $pedido['id'],
            'numero_pedido' => $pedido['numero_pedido'],
            'cliente' => $pedido['cliente']
        ]);
    } else {
        echo json_encode(['novo_pedido' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['novo_pedido' => false, 'error' => $e->getMessage()]);
}
