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
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Painel Admin - Pizzaria São Paulo</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f5f5f5;
            width: 100%;
            overflow-x: hidden;
        }

        .admin-header {
            background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .logo-admin {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin: 0 auto 1rem;
        }

        .logo-admin img {
            width: 70px;
            height: 70px;
        }

        .admin-header h1 {
            margin: 0 0 0.3rem 0;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .admin-header p {
            margin: 0;
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .notification-banner {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: none;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.3s ease-in;
            gap: 1rem;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification-banner.show {
            display: flex;
        }

        .notification-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .notification-content p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.95;
        }

        .btn-close-notif {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-close-notif:hover {
            background: rgba(255,255,255,0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            grid-auto-rows: auto;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.8rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 6px solid #DC2626;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .stat-card.grande {
            grid-column: span 2;
            padding: 2.5rem;
        }

        .stat-card.pequeño {
            padding: 1.2rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #DC2626;
        }

        .stat-card.pequeño .stat-value {
            font-size: 1.5rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 0.8rem 1.5rem;
            background: #DC2626;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .tab-btn:not(.active) {
            background: white;
            color: #111;
            border: 2px solid #e5e7eb;
        }

        .tab-btn:hover {
            transform: translateY(-1px);
        }

        .btn-voltar {
            margin-left: auto;
            padding: 0.8rem 1.5rem;
            background: white;
            color: #DC2626;
            border: 2px solid #DC2626;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-voltar:hover {
            background: #DC2626;
            color: white;
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
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 0.7rem 1.2rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
            flex: 1;
            min-width: 250px;
            background: white;
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
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .data-table th {
            background: #f9fafb;
            padding: 1.2rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
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
            min-width: 100px;
        }

        .pedido-total {
            font-weight: 600;
            color: #10B981;
        }

        .cliente-nome {
            font-weight: 600;
            color: #111827;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .status-badge.novo { background: #FEF3C7; color: #92400E; }
        .status-badge.confirmado { background: #DBEAFE; color: #1E40AF; }
        .status-badge.entregue { background: #D1FAE5; color: #065F46; }
        .status-badge.cancelado { background: #FEE2E2; color: #7F1D1D; }

        .status-select {
            padding: 0.5rem 0.8rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
            min-width: 120px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .action-buttons {
            display: flex;
            gap: 0.6rem;
        }

        .btn-detalhes, .btn-imprimir {
            padding: 0.6rem 1rem;
            background: #DC2626;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-imprimir {
            background: #3B82F6;
        }

        .btn-detalhes:hover {
            background: #B91C1C;
        }

        .btn-imprimir:hover {
            background: #2563EB;
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 1.5rem 1rem;
            }

            .logo-admin {
                width: 70px;
                height: 70px;
                margin-bottom: 0.8rem;
            }

            .logo-admin img {
                width: 60px;
                height: 60px;
            }

            .admin-header h1 {
                font-size: 1.3rem;
            }

            .admin-container {
                padding: 1.5rem 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card.grande {
                grid-column: span 1;
                padding: 1.5rem;
            }

            .filters {
                flex-direction: column;
                padding: 1rem;
            }

            .filters input,
            .filters select {
                width: 100%;
                min-width: 100%;
            }

            .tabs {
                flex-direction: column;
                gap: 0.5rem;
            }

            .tab-btn, .btn-voltar {
                width: 100%;
                padding: 0.7rem;
                margin-left: 0 !important;
            }

            .data-table {
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.6rem;
            }

            .btn-detalhes, .btn-imprimir {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER NO TOPO -->
    <div class="admin-header">
        <div class="logo-admin">
            <img src="../assets/img/logo.webp" alt="Pizzaria São Paulo">
        </div>
        <h1>Painel Admin</h1>
        <p>Pedidos e Clientes</p>
    </div>

    <!-- CONTAINER PRINCIPAL -->
    <div class="admin-container">
        <!-- NOTIFICAÇÃO DE NOVO PEDIDO -->
        <div id="notification" class="notification-banner">
            <div class="notification-content">
                <h3>Novo Pedido!</h3>
                <p id="notif-texto">Você tem um novo pedido</p>
            </div>
            <button class="btn-close-notif" onclick="this.parentElement.classList.remove('show')">Fechar</button>
        </div>

        <!-- DASHBOARD ESTATÍSTICAS COM TAMANHOS DIVERSIFICADOS -->
        <div class="stats-grid">
            <div class="stat-card grande">
                <div class="stat-label">Total Pedidos</div>
                <div class="stat-value"><?php echo $total_pedidos; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Clientes</div>
                <div class="stat-value"><?php echo $total_clientes; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Faturamento</div>
                <div class="stat-value">R$ <?php echo number_format($total_vendido, 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card pequeño">
                <div class="stat-label">Hoje</div>
                <div class="stat-value"><?php echo $pedidos_hoje; ?></div>
            </div>
        </div>

        <!-- ABAS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pedidos')">Pedidos</button>
            <button class="tab-btn" onclick="showTab('clientes')">Clientes</button>
            <a href="../" class="btn-voltar">Voltar</a>
        </div>

        <!-- ABA PEDIDOS -->
        <div id="pedidos" class="tab-content active">
            <div class="filters">
                <input type="text" id="search-pedido" placeholder="Buscar pedido ou cliente...">
                <select id="filter-status">
                    <option value="">Todos os status</option>
                    <?php foreach ($status_list as $status): ?>
                        <option value="<?php echo strtolower($status['nome']); ?>"><?php echo $status['nome']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="table-section">
                <?php if ($pedidos): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <tr class="pedido-row" data-cliente="<?php echo strtolower($p['cliente_nome']); ?>" data-status="<?php echo strtolower($p['status_nome']); ?>">
                                    <td class="pedido-numero">#<?php echo substr($p['numero_pedido'], -6); ?></td>
                                    <td class="cliente-nome"><?php echo htmlspecialchars(strlen($p['cliente_nome']) > 15 ? substr($p['cliente_nome'], 0, 12) . '...' : $p['cliente_nome']); ?></td>
                                    <td class="pedido-total">R$ <?php echo number_format($p['total'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($p['status_nome'] ?? 'novo'); ?>">
                                            <?php echo $p['status_nome'] ?? 'Novo'; ?>
                                        </span><br>
                                        <select class="status-select" onchange="mudarStatus(<?php echo $p['id']; ?>, this.value)">
                                            <option value="">Mudar</option>
                                            <?php foreach ($status_list as $s): ?>
                                                <option value="<?php echo $s['id']; ?>" <?php echo $p['status_id'] == $s['id'] ? 'selected' : ''; ?>>→ <?php echo $s['nome']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="pedido_detalhes.php?id=<?php echo $p['id']; ?>" class="btn-detalhes">Ver</a>
                                            <button class="btn-imprimir" onclick="imprimirComanda(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['cliente_nome']); ?>', '<?php echo htmlspecialchars($p['numero_pedido']); ?>', '<?php echo htmlspecialchars($p['telefone']); ?>', 'R$ <?php echo number_format($p['total'], 2, ',', '.'); ?>')">Print</button>
                                        </div>
                                    </td>
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
                <input type="text" id="search-cliente" placeholder="Buscar cliente...">
            </div>
            <div class="table-section">
                <?php if ($clientes): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Pedidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $c): ?>
                                <tr class="cliente-row" data-nome="<?php echo strtolower($c['nome']); ?>">
                                    <td class="cliente-nome"><?php echo htmlspecialchars(strlen($c['nome']) > 12 ? substr($c['nome'], 0, 10) . '...' : $c['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($c['telefone'] ?? '-'); ?></td>
                                    <td><small><?php echo htmlspecialchars(strlen($c['email']) > 15 ? substr($c['email'], 0, 12) . '...' : ($c['email'] ?? '-')); ?></small></td>
                                    <td><?php echo count(array_filter($pedidos, fn($p) => $p['usuario_id'] == $c['id'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Nenhum cliente cadastrado</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let ultimoPedidoID = <?php echo count($pedidos) > 0 ? max(array_column($pedidos, 'id')) : 0; ?>;

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
                else alert('Erro ao atualizar status');
            });
        }

        function imprimirComanda(id, cliente, numeroPedido, telefone, total) {
            const comanda = `
                <div style="text-align: center; font-family: monospace; width: 80mm; margin: 0; padding: 20px;">
                    <div style="font-size: 14px; font-weight: bold; margin-bottom: 15px;">PIZZARIA SÃO PAULO</div>
                    <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
                    <div style="font-size: 12px; margin: 8px 0;"><strong>PEDIDO:</strong> ${numeroPedido}</div>
                    <div style="font-size: 12px; margin: 8px 0;"><strong>CLIENTE:</strong> ${cliente}</div>
                    <div style="font-size: 12px; margin: 8px 0;"><strong>TELEFONE:</strong> ${telefone}</div>
                    <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
                    <div style="font-size: 12px; margin: 8px 0;"><strong>TOTAL:</strong> ${total}</div>
                    <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
                    <div style="font-size: 11px; margin-top: 15px;">${new Date().toLocaleString('pt-BR')}</div>
                    <div style="font-size: 11px; margin-top: 5px;">OBRIGADO!</div>
                </div>
            `;
            
            const printWindow = window.open('', '', 'width=300,height=400');
            printWindow.document.write('<html><head><title>Comanda</title></head><body>');
            printWindow.document.write(comanda);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            setTimeout(() => printWindow.print(), 100);
        }

        function verificarNovosPedidos() {
            fetch('../api/verificar_pedidos.php?ultimo_id=' + ultimoPedidoID)
                .then(r => r.json())
                .then(d => {
                    if (d.novo_pedido) {
                        ultimoPedidoID = d.id;
                        document.getElementById('notif-texto').innerText = `Novo: ${d.cliente} - ${d.numero_pedido}`;
                        document.getElementById('notification').classList.add('show');
                        setTimeout(() => location.reload(), 5000);
                    }
                });
        }

        setInterval(verificarNovosPedidos, 5000);
    </script>
</body>
</html>
