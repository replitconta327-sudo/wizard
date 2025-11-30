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
    <title>Gerenciar Pedidos - Pizzaria</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #ffffff; }

        .header {
            background: #000000;
            color: white;
            padding: 2rem;
            border-bottom: 3px solid #ffffff;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 0.8rem 1.5rem;
            background: #000000;
            color: white;
            border: 2px solid #000000;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .tab-btn:not(.active) {
            background: white;
            color: #000000;
        }

        .tab-btn:hover {
            transform: translateY(-1px);
        }

        .btn-voltar {
            margin-left: auto;
            padding: 0.8rem 1.5rem;
            background: white;
            color: #000000;
            border: 2px solid #000000;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-voltar:hover {
            background: #000000;
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
            border: 2px solid #000000;
            border-radius: 8px;
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 0.7rem 1.2rem;
            border: 2px solid #000000;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
            flex: 1;
            min-width: 250px;
            background: white;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }

        .table-section {
            background: white;
            border-radius: 8px;
            overflow-x: auto;
            border: 2px solid #000000;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .data-table th {
            background: #f0f0f0;
            padding: 1.2rem;
            text-align: left;
            font-weight: 600;
            color: #000000;
            border-bottom: 2px solid #000000;
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
            color: #000000;
        }

        .pedido-total {
            font-weight: 600;
            color: #000000;
        }

        .cliente-nome {
            font-weight: 600;
            color: #000000;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #e5e7eb;
            color: #000000;
        }

        .status-select {
            padding: 0.5rem 0.8rem;
            border: 2px solid #000000;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
            min-width: 120px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #999999;
        }

        .action-buttons {
            display: flex;
            gap: 0.6rem;
        }

        .btn-detalhes, .btn-imprimir {
            padding: 0.6rem 1rem;
            background: #000000;
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

        .btn-detalhes:hover,
        .btn-imprimir:hover {
            background: #333333;
        }

        @media (max-width: 768px) {
            .header {
                padding: 1.5rem 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .header h1 {
                font-size: 1.3rem;
            }

            .container {
                padding: 1.5rem 1rem;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-btn, .btn-voltar {
                width: 100%;
                margin-left: 0 !important;
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
    <div class="header">
        <div class="header-content">
            <h1>Gerenciar Pedidos</h1>
            <a href="dashboard.php" class="btn-voltar">Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pedidos', event)">Pedidos</button>
            <button class="tab-btn" onclick="showTab('clientes', event)">Clientes</button>
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
                                        <span class="status-badge">
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
        function showTab(tab, event) {
            event.preventDefault();
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
            fetch('../api/verificar_pedidos.php')
                .then(r => r.json())
                .then(d => {
                    if (d.novo_pedido) {
                        location.reload();
                    }
                });
        }

        setInterval(verificarNovosPedidos, 5000);
    </script>
</body>
</html>
