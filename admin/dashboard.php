<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /admin/login.php');
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $usuario = $pdo->query("SELECT nome FROM usuarios WHERE id = " . $_SESSION['usuario_id'])->fetch(PDO::FETCH_ASSOC);
    $clientes = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pedidos = $pdo->query("SELECT COUNT(*) as total FROM pedidos")->fetch(PDO::FETCH_ASSOC)['total'];
    $total_vendido = $pdo->query("SELECT SUM(total) as total FROM pedidos")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $pedidos_hoje = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(criado_em) = DATE('now')")->fetch(PDO::FETCH_ASSOC)['total'];
    
    $pedidos = $pdo->query("SELECT p.*, u.nome as cliente_nome, sp.nome as status_nome FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id = u.id LEFT JOIN status_pedido sp ON p.status_id = sp.id ORDER BY p.criado_em DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $usuario = ['nome' => 'Admin'];
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
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: #f5f5f5; }

        .layout { display: flex; height: 100vh; }

        .sidebar {
            width: 250px;
            background: #1a1a1a;
            color: white;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 2px solid #333;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 1.2rem;
        }

        .sidebar-title {
            flex: 1;
        }

        .sidebar-title h2 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .sidebar-title p {
            margin: 0;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .sidebar-menu {
            flex: 1;
            padding: 2rem 0;
        }

        .menu-item {
            padding: 1rem 1.5rem;
            padding-left: 1.5rem;
            color: #ddd;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: transparent;
            transition: background 0.2s;
        }

        .menu-item:hover,
        .menu-item.active {
            background: #333;
            color: white;
        }

        .menu-item.active::before {
            background: #4CAF50;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid #333;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .user-info h4 {
            margin: 0;
            font-size: 0.9rem;
        }

        .user-info p {
            margin: 0;
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .top-bar h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: #000;
        }

        .btn-novo {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-novo:hover {
            background: #45a049;
        }

        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.verde::before { background: #4CAF50; }
        .stat-card.laranja::before { background: #FF9800; }
        .stat-card.azul::before { background: #2196F3; }
        .stat-card.roxo::before { background: #9C27B0; }

        .stat-label {
            color: #999;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 0.5rem;
        }

        .stat-desc {
            font-size: 0.75rem;
            color: #999;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 1.5rem;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .data-table th {
            background: #f9fafb;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tr:hover {
            background: #f9fafb;
        }

        .pedido-numero {
            font-weight: 700;
            color: #000;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e8f5e9;
            color: #2e7d32;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        @media (max-width: 1024px) {
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; height: auto; order: 2; }
            .main-content { order: 1; }
            .stats-cards { grid-template-columns: 1fr; }
            .content-area { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">🍕</div>
                <div class="sidebar-title">
                    <h2>Pizzaria</h2>
                    <p>São Paulo</p>
                </div>
            </div>

            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <span>📊</span> Dashboard
                </a>
                <a href="pedidos.php" class="menu-item">
                    <span>📋</span> Gerenciar Pedidos
                </a>
                <a href="configuracoes.php" class="menu-item">
                    <span>⚙️</span> Configurações
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="user-avatar"><?php echo strtoupper(substr($usuario['nome'] ?? 'A', 0, 1)); ?></div>
                <div class="user-info" style="flex: 1;">
                    <h4><?php echo htmlspecialchars($usuario['nome'] ?? 'Admin'); ?></h4>
                    <p>Admin</p>
                </div>
                <a href="logout.php" style="color: #ddd; text-decoration: none; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: all 0.2s;" title="Sair"
                   onmouseover="this.style.background='#333'; this.style.color='white';"
                   onmouseout="this.style.background='none'; this.style.color='#ddd';">🚪</a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Dashboard</h1>
            </div>

            <div class="content-area">
                <!-- ESTATÍSTICAS -->
                <div class="stats-cards">
                    <div class="stat-card verde">
                        <div class="stat-label">Total Pedidos</div>
                        <div class="stat-value"><?php echo $total_pedidos; ?></div>
                        <div class="stat-desc">Todos pedidos</div>
                    </div>
                    <div class="stat-card laranja">
                        <div class="stat-label">Pedidos Pendentes</div>
                        <div class="stat-value"><?php echo $pedidos_hoje; ?></div>
                        <div class="stat-desc">Hoje</div>
                    </div>
                    <div class="stat-card azul">
                        <div class="stat-label">Clientes Cadastrados</div>
                        <div class="stat-value"><?php echo $clientes; ?></div>
                        <div class="stat-desc">Clientes ativos</div>
                    </div>
                    <div class="stat-card roxo">
                        <div class="stat-label">Faturamento Total</div>
                        <div class="stat-value">R$ <?php echo number_format($total_vendido, 0, ',', '.'); ?></div>
                        <div class="stat-desc">Receita</div>
                    </div>
                </div>

                <!-- PEDIDOS RECENTES -->
                <div class="section-title">Últimos Pedidos</div>
                <div class="table-container">
                    <?php if ($pedidos): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID do Pedido</th>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $p): ?>
                                    <tr>
                                        <td class="pedido-numero"><?php echo substr($p['numero_pedido'], -6); ?></td>
                                        <td><?php echo htmlspecialchars($p['cliente_nome']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($p['criado_em'])); ?></td>
                                        <td>
                                            <span class="status-badge">
                                                <?php echo $p['status_nome'] ?? 'Novo'; ?>
                                            </span>
                                        </td>
                                        <td><strong>R$ <?php echo number_format($p['total'], 2, ',', '.'); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">Nenhum pedido encontrado</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarNotificacao(titulo, mensagem) {
            const banner = document.createElement('div');
            banner.style.cssText = 'position: fixed; top: 20px; left: 270px; right: 20px; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); z-index: 10000; animation: slideDown 0.3s ease-in;';
            banner.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem;">${titulo}</h3>
                        <p style="margin: 0; opacity: 0.9;">${mensagem}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">✕</button>
                </div>
            `;
            document.body.appendChild(banner);
            setTimeout(() => banner.remove(), 5000);
        }

        let ultimoPedidoId = <?php echo count($pedidos) > 0 ? max(array_column($pedidos, 'id')) : 0; ?>;

        function atualizarDashboard() {
            fetch('../api/get_pedidos.php')
                .then(r => r.json())
                .then(d => {
                    if (d.pedidos && d.pedidos.length > 0) {
                        const maxId = Math.max(...d.pedidos.map(p => p.id));
                        if (maxId > ultimoPedidoId) {
                            const novoPedido = d.pedidos.find(p => p.id > ultimoPedidoId);
                            if (novoPedido) {
                                ultimoPedidoId = maxId;
                                mostrarNotificacao('Novo Pedido!', `${novoPedido.cliente_nome} - ${novoPedido.numero_pedido}`);
                            }
                        }
                        atualizarEstatisticas(d.total_pedidos, d.pedidos_hoje, d.total_clientes, d.total_vendido);
                    }
                })
                .catch(e => console.error('Erro ao atualizar:', e));
        }

        function atualizarEstatisticas(total, hoje, clientes, vendido) {
            const cards = document.querySelectorAll('.stat-value');
            if (cards[0]) cards[0].textContent = total;
            if (cards[1]) cards[1].textContent = hoje;
            if (cards[2]) cards[2].textContent = clientes;
            if (cards[3]) cards[3].textContent = 'R$ ' + vendido.toLocaleString('pt-BR', {minimumFractionDigits: 0});
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        setInterval(atualizarDashboard, 3000);

        const style = document.createElement('style');
        style.textContent = '@keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }';
        document.head.appendChild(style);
    </script>
</body>
</html>
