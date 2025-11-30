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
    
    // Busca clientes cadastrados
    $stmt = $pdo->query("
        SELECT id, nome, telefone, email, criado_em
        FROM usuarios
        ORDER BY criado_em DESC
        LIMIT 50
    ");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Busca pedidos com status
    $stmt = $pdo->query("
        SELECT p.*, u.nome as cliente_nome, u.telefone, e.logradouro, e.numero, e.bairro, sp.nome as status_nome
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN enderecos e ON p.endereco_id = e.id
        LEFT JOIN status_pedido sp ON p.status_id = sp.id
        ORDER BY p.criado_em DESC
        LIMIT 50
    ");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca status disponíveis
    $stmt = $pdo->query("SELECT id, nome FROM status_pedido ORDER BY ordem");
    $status_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clientes = [];
    $pedidos = [];
    $status_list = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin - Pedidos e Clientes</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
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
        .table-section {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
            font-size: 0.9rem;
        }
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table tr:hover {
            background: #f9fafb;
        }
        .pedido-numero {
            font-weight: 600;
            color: #dc2626;
        }
        .pedido-total {
            font-weight: 600;
            color: #059669;
        }
        .cliente-nome {
            font-weight: 600;
            color: #111827;
        }
        .data-pequena {
            font-size: 0.9rem;
            color: #6b7280;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
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
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.confirmado {
            background: #bfdbfe;
            color: #1e3a8a;
        }
        .status-badge.entregue {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.cancelado {
            background: #fee2e2;
            color: #7f1d1d;
        }
        .tracking-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .status-select {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 0.9rem;
            min-width: 150px;
        }
        .btn-atualizar {
            padding: 0.5rem 1rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-atualizar:hover {
            background: #047857;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Painel Admin</h1>
            <a href="../" class="btn btn-secondary">Voltar</a>
        </div>

        <!-- ABAS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pedidos')">
                📋 Pedidos (<?php echo count($pedidos); ?>)
            </button>
            <button class="tab-btn" onclick="showTab('clientes')">
                👥 Clientes (<?php echo count($clientes); ?>)
            </button>
        </div>

        <!-- ABA PEDIDOS COM RASTREAMENTO -->
        <div id="pedidos" class="tab-content active">
            <div class="table-section">
                <?php if ($pedidos && count($pedidos) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Número Pedido</th>
                                <th>Cliente</th>
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th>Total</th>
                                <th>Status Rastreamento</th>
                                <th>Data Pedido</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td class="pedido-numero"><?php echo htmlspecialchars($pedido['numero_pedido']); ?></td>
                                    <td class="cliente-nome"><?php echo htmlspecialchars($pedido['cliente_nome'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($pedido['telefone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <small>
                                            <?php echo htmlspecialchars(($pedido['logradouro'] ?? 'N/A') . ', ' . ($pedido['numero'] ?? '')); ?>
                                            <br/>
                                            <?php echo htmlspecialchars($pedido['bairro'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td class="pedido-total">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                                    <td>
                                        <div class="tracking-row">
                                            <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $pedido['status_nome'] ?? 'novo')); ?>">
                                                <?php echo htmlspecialchars($pedido['status_nome'] ?? 'Novo'); ?>
                                            </span>
                                            <select class="status-select" id="status-<?php echo $pedido['id']; ?>" onchange="atualizarStatus(<?php echo $pedido['id']; ?>, this.value)">
                                                <option value="">Mudar...</option>
                                                <?php foreach ($status_list as $status): ?>
                                                    <option value="<?php echo $status['id']; ?>" <?php echo $pedido['status_id'] == $status['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($status['nome']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($pedido['criado_em'])); ?>
                                            <br/>
                                            <span class="data-pequena"><?php echo date('H:i', strtotime($pedido['criado_em'])); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="pedido_detalhes.php?id=<?php echo $pedido['id']; ?>" class="btn-detalhes">Ver</a>
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
        </div>

        <!-- ABA CLIENTES -->
        <div id="clientes" class="tab-content">
            <div class="table-section">
                <?php if ($clientes && count($clientes) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome Cliente</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Data Cadastro</th>
                                <th>Pedidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td class="cliente-nome"><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($cliente['criado_em'])); ?>
                                            <br/>
                                            <span class="data-pequena"><?php echo date('H:i', strtotime($cliente['criado_em'])); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <?php 
                                        $num_pedidos = count(array_filter($pedidos, fn($p) => $p['usuario_id'] == $cliente['id']));
                                        echo $num_pedidos > 0 ? '<strong>' . $num_pedidos . '</strong> pedido(s)' : 'Sem pedidos';
                                        ?>
                                    </td>
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
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function atualizarStatus(pedidoId, statusId) {
            if (!statusId) return;
            
            fetch('../api/atualizar_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'pedido_id=' + pedidoId + '&status_id=' + statusId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao atualizar status');
                }
            })
            .catch(e => alert('Erro: ' + e));
        }
    </script>
</body>
</html>
