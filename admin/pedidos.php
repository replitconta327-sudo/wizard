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
    
    $clientes = $pdo->query("SELECT id, nome, telefone, email, criado_em FROM usuarios ORDER BY criado_em DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    
    $pedidos = $pdo->query("
        SELECT p.*, u.nome as cliente_nome, u.telefone, e.logradouro, e.numero, e.bairro, sp.nome as status_nome
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN enderecos e ON p.endereco_id = e.id
        LEFT JOIN status_pedido sp ON p.status_id = sp.id
        ORDER BY p.criado_em DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);

    $status_list = $pdo->query("SELECT id, nome FROM status_pedido ORDER BY ordem")->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcula estatísticas
    $total_pedidos = count($pedidos);
    $total_clientes = count($clientes);
    $total_vendido = array_sum(array_column($pedidos, 'total'));
    $pedidos_hoje = count(array_filter($pedidos, fn($p) => date('Y-m-d', strtotime($p['criado_em'])) === date('Y-m-d')));
} catch (Exception $e) {
    $clientes = [];
    $pedidos = [];
    $status_list = [];
    $total_pedidos = $total_clientes = $total_vendido = $pedidos_hoje = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin - Pizzaria</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; }
        .admin-container {
            max-width: 1400px;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #059669;
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
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tab-btn {
            padding: 0.8rem 1.5rem;
            background: #f3f4f6;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: #059669;
            color: white;
        }
        .tab-btn:hover {
            background: #e5e7eb;
        }
        .tab-btn.active:hover {
            background: #047857;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
        }
        .filters input, .filters select {
            padding: 0.6rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .table-section {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .data-table th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table tr:hover {
            background: #f9fafb;
        }
        .pedido-numero { font-weight: 600; color: #dc2626; }
        .pedido-total { font-weight: 600; color: #059669; }
        .cliente-nome { font-weight: 600; color: #111827; }
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.confirmado { background: #bfdbfe; color: #1e3a8a; }
        .status-badge.entregue { background: #d1fae5; color: #065f46; }
        .status-badge.cancelado { background: #fee2e2; color: #7f1d1d; }
        .status-select {
            padding: 0.4rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .empty-state { text-align: center; padding: 3rem; color: #6b7280; }
        .btn-detalhes {
            padding: 0.4rem 0.8rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .btn-detalhes:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>📊 Painel Admin - Pizzaria</h1>
            <a href="../" class="btn btn-secondary">Voltar</a>
        </div>

        <!-- DASHBOARD ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">📋 Total Pedidos</div>
                <div class="stat-value"><?php echo $total_pedidos; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">👥 Total Clientes</div>
                <div class="stat-value"><?php echo $total_clientes; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">💰 Faturamento Total</div>
                <div class="stat-value">R$ <?php echo number_format($total_vendido, 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">📅 Hoje</div>
                <div class="stat-value"><?php echo $pedidos_hoje; ?></div>
            </div>
        </div>

        <!-- ABAS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pedidos')">📋 Pedidos (<?php echo $total_pedidos; ?>)</button>
            <button class="tab-btn" onclick="showTab('clientes')">👥 Clientes (<?php echo $total_clientes; ?>)</button>
        </div>

        <!-- ABA PEDIDOS -->
        <div id="pedidos" class="tab-content active">
            <div class="filters">
                <input type="text" id="search-pedido" placeholder="🔍 Buscar pedido ou cliente..." style="flex: 1;">
                <select id="filter-status">
                    <option value="">Todos os status</option>
                    <?php foreach ($status_list as $status): ?>
                        <option value="<?php echo strtolower($status['nome']); ?>"><?php echo $status['nome']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="table-section">
                <?php if ($pedidos): ?>
                    <table class="data-table" id="table-pedidos">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Tel</th>
                                <th>Endereço</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <tr class="pedido-row" data-cliente="<?php echo strtolower($p['cliente_nome']); ?>" data-status="<?php echo strtolower($p['status_nome']); ?>">
                                    <td class="pedido-numero"><?php echo substr($p['numero_pedido'], -6); ?></td>
                                    <td class="cliente-nome"><?php echo htmlspecialchars($p['cliente_nome'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo htmlspecialchars($p['telefone'] ?? '-'); ?></small></td>
                                    <td><small><?php echo htmlspecialchars(($p['logradouro'] ?? '') . ', ' . ($p['numero'] ?? '')); ?></small></td>
                                    <td class="pedido-total">R$ <?php echo number_format($p['total'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $p['status_nome'])); ?>">
                                            <?php echo $p['status_nome'] ?? 'Novo'; ?>
                                        </span>
                                        <select class="status-select" onchange="mudarStatus(<?php echo $p['id']; ?>, this.value)">
                                            <option value="">Mudar...</option>
                                            <?php foreach ($status_list as $s): ?>
                                                <option value="<?php echo $s['id']; ?>"><?php echo $s['nome']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><small><?php echo date('d/m H:i', strtotime($p['criado_em'])); ?></small></td>
                                    <td><a href="pedido_detalhes.php?id=<?php echo $p['id']; ?>" class="btn-detalhes">Ver</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Nenhum pedido encontrado</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ABA CLIENTES -->
        <div id="clientes" class="tab-content">
            <div class="filters">
                <input type="text" id="search-cliente" placeholder="🔍 Buscar cliente..." style="flex: 1;">
            </div>
            <div class="table-section">
                <?php if ($clientes): ?>
                    <table class="data-table" id="table-clientes">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Cadastrado em</th>
                                <th>Pedidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $c): ?>
                                <tr class="cliente-row" data-nome="<?php echo strtolower($c['nome']); ?>">
                                    <td class="cliente-nome"><?php echo htmlspecialchars($c['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($c['telefone'] ?? '-'); ?></td>
                                    <td><small><?php echo htmlspecialchars($c['email'] ?? '-'); ?></small></td>
                                    <td><small><?php echo date('d/m/Y H:i', strtotime($c['criado_em'])); ?></small></td>
                                    <td><?php echo count(array_filter($pedidos, fn($p) => $p['usuario_id'] == $c['id'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Nenhum cliente encontrado</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }

        function mudarStatus(id, status) {
            if (!status) return;
            fetch('../api/atualizar_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'pedido_id=' + id + '&status_id=' + status
            }).then(r => r.json()).then(d => {
                if (d.success) location.reload();
                else alert('Erro ao atualizar');
            });
        }

        document.getElementById('search-pedido')?.addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll('.pedido-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
            });
        });

        document.getElementById('filter-status')?.addEventListener('change', function() {
            const status = this.value.toLowerCase();
            document.querySelectorAll('.pedido-row').forEach(row => {
                row.style.display = !status || row.dataset.status.includes(status) ? '' : 'none';
            });
        });

        document.getElementById('search-cliente')?.addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll('.cliente-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
