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
    <title>Painel Admin - Pizzaria São Paulo</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }

        .admin-header {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            padding: 2rem 1rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logo-admin {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .logo-admin img {
            width: 70px;
            height: 70px;
        }

        .admin-header h1 {
            color: white;
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }

        .admin-header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0.5rem 0 0 0;
            font-size: 0.95rem;
        }

        .admin-container {
            max-width: 1200px;
            margin: -2rem auto 0;
            padding: 0 1rem 2rem;
            position: relative;
            z-index: 10;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            border-left: 5px solid #DC2626;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #DC2626;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab-btn {
            padding: 1rem 1.5rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            color: #374151;
        }

        .tab-btn.active {
            background: #DC2626;
            color: white;
            border-color: #DC2626;
            box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2);
        }

        .tab-btn:hover:not(.active) {
            border-color: #DC2626;
            color: #DC2626;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            flex: 1;
            min-width: 200px;
            transition: border-color 0.2s;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: #DC2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .table-section {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .data-table th {
            background: #f3f4f6;
            padding: 1.2rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .data-table td {
            padding: 1.2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tr:hover {
            background: #f9fafb;
        }

        .pedido-numero {
            font-weight: 700;
            color: #DC2626;
            font-size: 1.1rem;
        }

        .pedido-total {
            font-weight: 600;
            color: #10B981;
            font-size: 1rem;
        }

        .cliente-nome {
            font-weight: 600;
            color: #111827;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.novo {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-badge.confirmado {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .status-badge.entregue {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-badge.cancelado {
            background: #FEE2E2;
            color: #7F1D1D;
        }

        .status-select {
            padding: 0.5rem 0.8rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            background: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .btn-detalhes {
            padding: 0.6rem 1rem;
            background: #DC2626;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-detalhes:hover {
            background: #B91C1C;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-voltar {
            padding: 0.8rem 1.5rem;
            background: white;
            color: #DC2626;
            border: 2px solid #DC2626;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-voltar:hover {
            background: #DC2626;
            color: white;
        }

        .data-pequena {
            font-size: 0.85rem;
            color: #6b7280;
            display: block;
            margin-top: 0.2rem;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select {
                min-width: 100%;
            }

            .data-table {
                font-size: 0.85rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.8rem 0.5rem;
            }

            .admin-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-header">
        <div class="logo-admin">
            <img src="../assets/img/logo.webp" alt="Pizzaria São Paulo">
        </div>
        <h1>Painel Administrativo</h1>
        <p>Gerenciamento de Pedidos e Clientes</p>
    </div>

    <div class="admin-container">
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
                <div class="stat-label">💰 Faturamento</div>
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
            <a href="../" class="btn-voltar" style="margin-left: auto;">← Voltar</a>
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
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th>Total</th>
                                <th>Rastreamento</th>
                                <th>Data</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <tr class="pedido-row" data-cliente="<?php echo strtolower($p['cliente_nome']); ?>" data-status="<?php echo strtolower($p['status_nome']); ?>">
                                    <td class="pedido-numero">#<?php echo substr($p['numero_pedido'], -6); ?></td>
                                    <td class="cliente-nome"><?php echo htmlspecialchars($p['cliente_nome'] ?? 'N/A'); ?></td>
                                    <td><small><?php echo htmlspecialchars($p['telefone'] ?? '-'); ?></small></td>
                                    <td><small><?php echo htmlspecialchars(($p['logradouro'] ?? '') . ', ' . ($p['numero'] ?? '')); ?></small></td>
                                    <td class="pedido-total">R$ <?php echo number_format($p['total'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($p['status_nome'] ?? 'novo'); ?>">
                                            <?php echo $p['status_nome'] ?? 'Novo'; ?>
                                        </span><br>
                                        <select class="status-select" onchange="mudarStatus(<?php echo $p['id']; ?>, this.value)" style="margin-top: 0.5rem; width: 100%;">
                                            <option value="">Mudar...</option>
                                            <?php foreach ($status_list as $s): ?>
                                                <option value="<?php echo $s['id']; ?>" <?php echo $p['status_id'] == $s['id'] ? 'selected' : ''; ?>>→ <?php echo $s['nome']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($p['criado_em'])); ?>
                                            <span class="data-pequena"><?php echo date('H:i', strtotime($p['criado_em'])); ?></span>
                                        </small>
                                    </td>
                                    <td><a href="pedido_detalhes.php?id=<?php echo $p['id']; ?>" class="btn-detalhes">Ver</a></td>
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
                                    <td>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($c['criado_em'])); ?>
                                            <span class="data-pequena"><?php echo date('H:i', strtotime($c['criado_em'])); ?></span>
                                        </small>
                                    </td>
                                    <td><?php echo count(array_filter($pedidos, fn($p) => $p['usuario_id'] == $c['id'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Nenhum cliente cadastrado.</p>
                    </div>
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
                if (d.success) {
                    alert('✅ Status atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('❌ Erro ao atualizar status');
                }
            }).catch(e => alert('Erro: ' + e));
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
