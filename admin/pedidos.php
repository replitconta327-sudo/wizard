<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Simples verificação de acesso (deve ser expandida com autenticação real)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /');
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    // Busca todos os pedidos (para simplificar, mostra todos)
    $stmt = $pdo->query("
        SELECT p.*, u.nome as cliente_nome, u.telefone, e.logradouro, e.numero, e.bairro
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN enderecos e ON p.endereco_id = e.id
        ORDER BY p.criado_em DESC
        LIMIT 50
    ");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pedidos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin - Pedidos</title>
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
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 1rem;
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
        .filters-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
        }
        .filters-bar select,
        .filters-bar input {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .pedidos-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .pedidos-table th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .pedidos-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .pedidos-table tr:hover {
            background: #f9fafb;
        }
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-novo { background: #fef3c7; color: #92400e; }
        .status-confirmado { background: #bfdbfe; color: #1e3a8a; }
        .status-entregue { background: #d1fae5; color: #065f46; }
        .status-cancelado { background: #fee2e2; color: #7f1d1d; }
        .pedido-numero {
            font-weight: 600;
            color: #dc2626;
        }
        .pedido-total {
            font-weight: 600;
            color: #059669;
        }
        .btn-detalhes {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn-detalhes:hover {
            background: #2563eb;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-box {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #059669;
        }
        .stat-box-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .stat-box-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #059669;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Painel Admin - Pedidos</h1>
            <a href="../" class="btn btn-secondary">Voltar</a>
        </div>

        <div class="admin-nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="pedidos.php" class="active">📋 Pedidos</a>
            <a href="../">🏠 Início</a>
        </div>

        <!-- RESUMO RÁPIDO -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-box-label">Total de Pedidos</div>
                <div class="stat-box-value"><?php echo count($pedidos); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">Hoje</div>
                <div class="stat-box-value">
                    <?php 
                    $hoje = array_filter($pedidos, function($p) { 
                        return date('Y-m-d', strtotime($p['criado_em'])) === date('Y-m-d'); 
                    });
                    echo count($hoje);
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-box-label">Total Vendido</div>
                <div class="stat-box-value">
                    R$ <?php echo number_format(array_sum(array_column($pedidos, 'total')), 2, ',', '.'); ?>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filters-bar">
            <input type="text" placeholder="🔍 Buscar por cliente ou número..." id="search-input">
            <select id="status-filter">
                <option value="">Todos os status</option>
                <option value="novo">Novo</option>
                <option value="confirmado">Confirmado</option>
                <option value="entregue">Entregue</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>

        <?php if ($pedidos && count($pedidos) > 0): ?>
            <table class="pedidos-table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th>Total</th>
                        <th>Pagamento</th>
                        <th>Data</th>
                        <th style="width: 100px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr class="pedido-row">
                            <td class="pedido-numero"><?php echo htmlspecialchars($pedido['numero_pedido']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['cliente_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($pedido['telefone'] ?? 'N/A'); ?></td>
                            <td>
                                <small>
                                    <?php echo htmlspecialchars(($pedido['logradouro'] ?? 'N/A') . ', ' . ($pedido['numero'] ?? '') . ' - ' . ($pedido['bairro'] ?? '')); ?>
                                </small>
                            </td>
                            <td class="pedido-total">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($pedido['forma_pagamento'] ?? 'N/A')); ?></td>
                            <td>
                                <small><?php echo date('d/m/Y H:i', strtotime($pedido['criado_em'] ?? 'now')); ?></small>
                            </td>
                            <td>
                                <a href="pedido_detalhes.php?id=<?php echo $pedido['id']; ?>" class="btn-detalhes">👁️ Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>Nenhum pedido encontrado.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('search-input')?.addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll('.pedido-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
