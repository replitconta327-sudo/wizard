<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /');
    exit;
}

$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pedido_id) {
    header('Location: pedidos.php');
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    // Busca pedido
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome as cliente_nome, u.telefone, u.email,
               e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.cep,
               sp.nome as status_nome
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN enderecos e ON p.endereco_id = e.id
        LEFT JOIN status_pedido sp ON p.status_id = sp.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        header('Location: pedidos.php');
        exit;
    }

    // Busca itens do pedido
    $stmt = $pdo->query("SELECT * FROM pedido_itens WHERE pedido_id = $pedido_id");
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca bebidas
    $stmt = $pdo->query("SELECT * FROM pedido_bebidas WHERE pedido_id = $pedido_id");
    $bebidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca status disponíveis
    $stmt = $pdo->query("SELECT * FROM status_pedido ORDER BY ordem");
    $status_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pedido = null;
    $itens = [];
    $bebidas = [];
    $status_list = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalhes do Pedido</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1000px;
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
        .pedido-info {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-item {
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 1rem;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .info-value {
            color: #111827;
            margin-top: 0.5rem;
            font-size: 1.05rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.confirmado { background: #bfdbfe; color: #1e3a8a; }
        .status-badge.entregue { background: #d1fae5; color: #065f46; }
        .status-badge.cancelado { background: #fee2e2; color: #7f1d1d; }
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-bottom: 1.5rem;
        }
        .items-table th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table tr:hover {
            background: #f9fafb;
        }
        .total-row {
            font-size: 1.2rem;
            font-weight: 600;
            background: #f3f4f6;
        }
        .status-select {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn-update-status {
            padding: 0.6rem 1.2rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-update-status:hover {
            background: #047857;
        }
        .address-block {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #059669;
        }
        .actions-bar {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Detalhes do Pedido #<?php echo htmlspecialchars($pedido['numero_pedido'] ?? 'N/A'); ?></h1>
            <a href="pedidos.php" class="btn btn-secondary">← Voltar</a>
        </div>

        <?php if ($pedido): ?>
            <!-- INFORMAÇÕES DO CLIENTE -->
            <div class="pedido-info">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="margin: 0;">Informações do Pedido</h2>
                    <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $pedido['status_nome'] ?? 'novo')); ?>">
                        <?php echo htmlspecialchars($pedido['status_nome'] ?? 'Novo'); ?>
                    </span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Número do Pedido</div>
                        <div class="info-value" style="color: #dc2626; font-weight: 700;">
                            <?php echo htmlspecialchars($pedido['numero_pedido']); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Data</div>
                        <div class="info-value">
                            <?php echo date('d/m/Y H:i', strtotime($pedido['criado_em'])); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Forma de Pagamento</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars(ucfirst($pedido['forma_pagamento'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INFORMAÇÕES DO CLIENTE -->
            <div class="pedido-info">
                <h3>Cliente</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nome</div>
                        <div class="info-value"><?php echo htmlspecialchars($pedido['cliente_nome'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Telefone</div>
                        <div class="info-value"><?php echo htmlspecialchars($pedido['telefone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($pedido['email'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <!-- ENDEREÇO -->
            <div class="pedido-info">
                <h3>Endereço de Entrega</h3>
                <div class="address-block">
                    <p style="margin: 0 0 0.5rem 0;">
                        <strong><?php echo htmlspecialchars($pedido['logradouro'] ?? ''); ?>, <?php echo htmlspecialchars($pedido['numero'] ?? ''); ?></strong>
                        <?php if ($pedido['complemento']): ?>
                            <br/>Complemento: <?php echo htmlspecialchars($pedido['complemento']); ?>
                        <?php endif; ?>
                    </p>
                    <p style="margin: 0; color: #6b7280;">
                        <?php echo htmlspecialchars($pedido['bairro'] ?? ''); ?>, <?php echo htmlspecialchars($pedido['cidade'] ?? ''); ?> - <?php echo htmlspecialchars($pedido['cep'] ?? ''); ?>
                    </p>
                </div>
            </div>

            <!-- ITENS DO PEDIDO -->
            <h3 class="section-title">Itens do Pedido</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th style="width: 80px;">Quantidade</th>
                        <th style="width: 100px;">Preço Unit.</th>
                        <th style="width: 100px; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($itens): ?>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['observacoes'] ?? 'Pizza'); ?></strong>
                                    <?php if ($item['tamanho']): ?>
                                        <br/><small style="color: #6b7280;">Tamanho: <?php echo htmlspecialchars($item['tamanho']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['quantidade']; ?></td>
                                <td>R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                <td style="text-align: right; font-weight: 600;">
                                    R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($bebidas): ?>
                        <?php foreach ($bebidas as $bebida): ?>
                            <tr>
                                <td><strong>Bebida</strong></td>
                                <td><?php echo $bebida['quantidade']; ?></td>
                                <td>R$ <?php echo number_format($bebida['preco_unitario'], 2, ',', '.'); ?></td>
                                <td style="text-align: right; font-weight: 600;">
                                    R$ <?php echo number_format($bebida['subtotal'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right; color: #059669;">
                            R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- GERENCIAR STATUS -->
            <div class="pedido-info">
                <h3>Gerenciar Pedido</h3>
                <form method="POST" action="../api/atualizar_pedido.php" style="display: flex; gap: 1rem; align-items: end;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Alterar Status:</label>
                        <select name="status_id" class="status-select">
                            <option value="">Selecione um status</option>
                            <?php foreach ($status_list as $status): ?>
                                <option value="<?php echo $status['id']; ?>" 
                                    <?php echo $pedido['status_id'] == $status['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                    <input type="hidden" name="action" value="update_status">
                    <button type="submit" class="btn-update-status">Atualizar Status</button>
                </form>
            </div>

            <div class="actions-bar">
                <a href="pedidos.php" class="btn btn-secondary">← Voltar para Pedidos</a>
                <a href="javascript:window.print();" class="btn btn-primary">🖨️ Imprimir</a>
            </div>

        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #6b7280;">
                <p>Pedido não encontrado.</p>
                <a href="pedidos.php" class="btn btn-secondary">Voltar</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
