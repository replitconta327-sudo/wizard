<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /');
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $clientes = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pedidos = $pdo->query("SELECT COUNT(*) as total FROM pedidos")->fetch(PDO::FETCH_ASSOC)['total'];
    $total_vendido = $pdo->query("SELECT SUM(total) as total FROM pedidos")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $pedidos_hoje = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(criado_em) = DATE('now')")->fetch(PDO::FETCH_ASSOC)['total'];
    
    $pedidos = $pdo->query("SELECT p.*, u.nome as cliente_nome, sp.nome as status_nome FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id = u.id LEFT JOIN status_pedido sp ON p.status_id = sp.id ORDER BY p.criado_em DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $clientes = $total_pedidos = $total_vendido = $pedidos_hoje = 0;
    $pedidos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Pizzaria</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: #ffffff; }

        .header { background: #000000; color: #ffffff; padding: 2rem; border-bottom: 3px solid #ffffff; }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 2.2rem; font-weight: 700; }
        .btn { padding: 0.8rem 1.5rem; background: #ffffff; color: #000000; border: 2px solid #000000; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.2s; }
        .btn:hover { background: #000000; color: #ffffff; }

        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 3rem; }
        .stat-card { background: #ffffff; border: 3px solid #000000; border-radius: 8px; padding: 2rem; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); }
        .stat-label { color: #666666; font-size: 0.95rem; margin-bottom: 1rem; font-weight: 600; }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: #000000; }
        .stat-desc { font-size: 0.85rem; color: #999999; margin-top: 0.5rem; }

        .section-title { font-size: 1.5rem; font-weight: 700; color: #000000; margin-bottom: 1.5rem; border-bottom: 3px solid #000000; padding-bottom: 1rem; }

        .orders-box { background: #ffffff; border: 3px solid #000000; border-radius: 8px; overflow: hidden; }
        .order-item { padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .order-item:last-child { border-bottom: none; }
        .order-info { flex: 1; }
        .order-number { font-weight: 700; color: #000000; font-size: 1.1rem; }
        .order-detail { color: #666666; font-size: 0.9rem; margin-top: 0.3rem; }

        .btn-group { display: flex; gap: 0.8rem; margin-top: 2rem; flex-wrap: wrap; }
        .btn-group a { flex: 1; min-width: 200px; padding: 1rem; background: #000000; color: #ffffff; border: none; border-radius: 4px; text-align: center; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer; }
        .btn-group a:hover { background: #333333; }

        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 1rem; text-align: center; }
            .header h1 { font-size: 1.5rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .order-item { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .btn-group a { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Dashboard Pizzaria</h1>
            <a href="../" class="btn">Voltar</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total de Pedidos</div>
                <div class="stat-value"><?php echo $total_pedidos; ?></div>
                <div class="stat-desc">Todos os pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total de Clientes</div>
                <div class="stat-value"><?php echo $clientes; ?></div>
                <div class="stat-desc">Clientes cadastrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Faturamento Total</div>
                <div class="stat-value">R$ <?php echo number_format($total_vendido, 0, ',', '.'); ?></div>
                <div class="stat-desc">Receita total</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pedidos Hoje</div>
                <div class="stat-value"><?php echo $pedidos_hoje; ?></div>
                <div class="stat-desc">Hoje</div>
            </div>
        </div>

        <div class="section-title">Pedidos Recentes</div>
        <div class="orders-box">
            <?php if ($pedidos): ?>
                <?php foreach ($pedidos as $p): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <div class="order-number">#<?php echo substr($p['numero_pedido'], -6); ?> - <?php echo htmlspecialchars($p['cliente_nome']); ?></div>
                            <div class="order-detail">R$ <?php echo number_format($p['total'], 2, ',', '.'); ?> • <?php echo $p['status_nome'] ?? 'Novo'; ?> • <?php echo date('d/m H:i', strtotime($p['criado_em'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: #999999;">Nenhum pedido encontrado</div>
            <?php endif; ?>
        </div>

        <div class="btn-group">
            <a href="pedidos.php">Gerenciar Pedidos</a>
            <a href="pedidos.php">Visualizar Clientes</a>
        </div>
    </div>
</body>
</html>
