<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $pedidos = $pdo->query("
        SELECT p.*, u.nome as cliente_nome, sp.nome as status_nome
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN status_pedido sp ON p.status_id = sp.id
        ORDER BY p.criado_em DESC
        LIMIT 15
    ")->fetchAll(PDO::FETCH_ASSOC);

    $total_pedidos = $pdo->query("SELECT COUNT(*) as total FROM pedidos")->fetch(PDO::FETCH_ASSOC)['total'];
    $total_clientes = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch(PDO::FETCH_ASSOC)['total'];
    $total_vendido = $pdo->query("SELECT SUM(total) as total FROM pedidos")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $pedidos_hoje = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(criado_em) = DATE('now')")->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'pedidos' => $pedidos,
        'total_pedidos' => $total_pedidos,
        'total_clientes' => $total_clientes,
        'total_vendido' => $total_vendido,
        'pedidos_hoje' => $pedidos_hoje
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
