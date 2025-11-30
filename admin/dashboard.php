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
    
    // Estatísticas gerais
    $stats = [];
    
    // Total de pedidos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
    $stats['total_pedidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pedidos hoje
    $stmt = $pdo->query("
        SELECT COUNT(*) as total FROM pedidos 
        WHERE DATE(criado_em) = DATE('now')
    ");
    $stats['pedidos_hoje'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total vendido
    $stmt = $pdo->query("SELECT SUM(total) as total FROM pedidos");
    $stats['total_vendido'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Pedidos pendentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE status_id = 1");
    $stats['pedidos_pendentes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pedidos últimos 7 dias por dia
    $stmt = $pdo->query("
        SELECT 
            DATE(criado_em) as data,
            COUNT(*) as total,
            SUM(total) as valor
        FROM pedidos
        WHERE criado_em >= datetime('now', '-7 days')
        GROUP BY DATE(criado_em)
        ORDER BY data DESC
    ");
    $pedidos_semana = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $stats = [];
    $pedidos_semana = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Pizzaria</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .admin-nav a {
            padding: 0.7rem 1.5rem;
            background: #f3f4f6;
            color: #111827;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .admin-nav a:hover,
        .admin-nav a.active {
            background: #059669;
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #059669;
        }
        .stat-value.red { color: #dc2626; }
        .stat-value.blue { color: #3b82f6; }
        .chart-section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .chart-row {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .chart-label {
            width: 120px;
            font-weight: 600;
        }
        .chart-bar {
            flex: 1;
            height: 30px;
            background: #e5e7eb;
            border-radius: 4px;
            margin: 0 1rem;
            position: relative;
            overflow: hidden;
        }
        .chart-bar-fill {
            height: 100%;
            background: #059669;
            transition: width 0.3s;
        }
        .chart-value {
            width: 60px;
            text-align: right;
            font-weight: 600;
        }
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .quick-actions a {
            flex: 1;
            padding: 1rem;
            background: #059669;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: background 0.2s;
        }
        .quick-actions a:hover {
            background: #047857;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Dashboard Admin</h1>
            <a href="../" class="btn btn-secondary">Voltar</a>
        </div>

        <div class="admin-nav">
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="pedidos.php">📋 Pedidos</a>
            <a href="../">🏠 Início</a>
        </div>

        <div class="quick-actions">
            <a href="pedidos.php">🚚 Ver Todos os Pedidos</a>
            <a href="pedidos.php">📈 Filtrar por Data</a>
        </div>

        <!-- CARDS DE ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total de Pedidos</div>
                <div class="stat-value"><?php echo $stats['total_pedidos'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pedidos Hoje</div>
                <div class="stat-value blue"><?php echo $stats['pedidos_hoje'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pendentes</div>
                <div class="stat-value red"><?php echo $stats['pedidos_pendentes'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Vendido</div>
                <div class="stat-value" style="color: #10b981;">
                    R$ <?php echo number_format($stats['total_vendido'], 2, ',', '.'); ?>
                </div>
            </div>
        </div>

        <!-- GRÁFICO DE PEDIDOS DA SEMANA -->
        <div class="chart-section">
            <div class="chart-title">Pedidos dos Últimos 7 Dias</div>
            
            <?php if ($pedidos_semana && count($pedidos_semana) > 0): ?>
                <?php 
                // Encontra valor máximo para normalizar gráfico
                $max_valor = max(array_column($pedidos_semana, 'total'));
                $max_valor = max($max_valor, 1); // Evita divisão por zero
                ?>
                <?php foreach ($pedidos_semana as $dia): ?>
                    <div class="chart-row">
                        <div class="chart-label">
                            <?php echo date('d/m', strtotime($dia['data'])); ?>
                        </div>
                        <div class="chart-bar">
                            <div class="chart-bar-fill" style="width: <?php echo ($dia['total'] / $max_valor * 100); ?>%;"></div>
                        </div>
                        <div class="chart-value">
                            <?php echo $dia['total']; ?> pedidos
                            <br/>
                            <small style="color: #6b7280;">R$ <?php echo number_format($dia['valor'], 2, ',', '.'); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #6b7280;">Nenhum pedido nos últimos 7 dias.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
