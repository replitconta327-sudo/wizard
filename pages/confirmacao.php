<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new Database();
$pdo = $db->pdo();

// Verificar se veio do pagamento
if (!isset($_SESSION['pedido_id'])) {
    header('Location: /pages/bebidas.php');
    exit;
}

$pedido_id = $_SESSION['pedido_id'];

// Buscar dados do pedido
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            ep.nome as status_nome,
            fp.nome as forma_pagamento_nome,
            e.logradouro,
            e.numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.uf,
            e.cep
        FROM pedidos p
        LEFT JOIN pedido_status ep ON p.status_id = ep.id
        LEFT JOIN forma_pagamento fp ON p.forma_pagamento_id = fp.id
        LEFT JOIN enderecos e ON p.endereco_id = e.id
        WHERE p.id = ? AND p.usuario_id = ?
    ");
    $stmt->execute([$pedido_id, $user_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        header('Location: /pages/bebidas.php');
        exit;
    }

    // Buscar itens do pedido
    $stmt_itens = $pdo->prepare("
        SELECT 
            pb.*,
            b.nome as bebida_nome,
            b.descricao as bebida_descricao,
            b.imagem_url,
            c.nome as categoria_nome
        FROM pedido_bebidas pb
        JOIN bebidas b ON pb.bebida_id = b.id
        LEFT JOIN bebidas_categorias c ON b.categoria_id = c.id
        WHERE pb.pedido_id = ?
        ORDER BY pb.id
    ");
    $stmt_itens->execute([$pedido_id]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar pedido: " . $e->getMessage());
    header('Location: /pages/bebidas.php');
    exit;
}

// Gerar número do pedido para exibição
$numero_pedido = str_pad($pedido_id, 6, '0', STR_PAD_LEFT);

// Limpar sessão do pedido após obter os dados
unset($_SESSION['pedido_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?php echo $numero_pedido; ?> Confirmado - Pizzaria</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/pages/confirmacao.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Manifest para PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#dc2626">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-pizza-slice"></i>
                    <span>Pizzaria</span>
                </a>
                <div class="user-menu">
                    <span class="user-name">Olá, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Cliente'); ?></span>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Sair
                    </a>
                </div>
            </div>
        </header>

        <!-- Progress Steps -->
        <div class="progress-container">
            <div class="progress-steps">
                <div class="step completed">
                    <div class="step-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-title">Escolher</div>
                        <div class="step-description">Bebidas</div>
                    </div>
                </div>
                <div class="step completed">
                    <div class="step-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-title">Revisar</div>
                        <div class="step-description">Pedido</div>
                    </div>
                </div>
                <div class="step completed">
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-title">Pagamento</div>
                        <div class="step-description">Realizado</div>
                    </div>
                </div>
                <div class="step active">
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-title">Confirmação</div>
                        <div class="step-description">Pedido Enviado</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="confirmation-card">
                <div class="confirmation-header">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1>Pedido Confirmado!</h1>
                    <p class="confirmation-subtitle">Seu pedido foi enviado para a loja com sucesso</p>
                </div>

                <div class="order-info">
                    <div class="order-number">
                        <span class="label">Número do Pedido:</span>
                        <span class="number">#<?php echo $numero_pedido; ?></span>
                    </div>
                    <div class="order-date">
                        <span class="label">Data e Hora:</span>
                        <span class="date"><?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></span>
                    </div>
                    <div class="order-status">
                        <span class="label">Status:</span>
                        <span class="status-badge status-<?php echo strtolower($pedido['status_nome']); ?>">
                            <?php echo htmlspecialchars($pedido['status_nome']); ?>
                        </span>
                    </div>
                </div>

                <!-- Items Summary -->
                <div class="items-section">
                    <h3>Resumo do Pedido</h3>
                    <div class="items-list">
                        <?php foreach ($itens as $item): ?>
                        <div class="item-card">
                            <?php if ($item['imagem_url']): ?>
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($item['imagem_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['bebida_nome']); ?>"
                                     loading="lazy">
                            </div>
                            <?php endif; ?>
                            <div class="item-details">
                                <div class="item-header">
                                    <h4 class="item-name"><?php echo htmlspecialchars($item['bebida_nome']); ?></h4>
                                    <span class="item-category"><?php echo htmlspecialchars($item['categoria_nome']); ?></span>
                                </div>
                                <p class="item-description"><?php echo htmlspecialchars($item['bebida_descricao']); ?></p>
                                <div class="item-info">
                                    <span class="item-quantity">Qtd: <?php echo $item['quantidade']; ?></span>
                                    <span class="item-price">R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></span>
                                </div>
                            </div>
                            <div class="item-total">
                                R$ <?php echo number_format($item['preco_total'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Address and Payment Info -->
                <div class="info-grid">
                    <div class="info-card">
                        <h4><i class="fas fa-map-marker-alt"></i> Endereço de Entrega</h4>
                        <div class="address-info">
                            <p><?php echo htmlspecialchars($pedido['logradouro'] . ', ' . $pedido['numero']); ?></p>
                            <?php if ($pedido['complemento']): ?>
                            <p><?php echo htmlspecialchars($pedido['complemento']); ?></p>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($pedido['bairro']); ?></p>
                            <p><?php echo htmlspecialchars($pedido['cidade'] . ' - ' . $pedido['uf']); ?></p>
                            <p>CEP: <?php echo htmlspecialchars($pedido['cep']); ?></p>
                        </div>
                    </div>
                    <div class="info-card">
                        <h4><i class="fas fa-credit-card"></i> Forma de Pagamento</h4>
                        <div class="payment-info">
                            <p><?php echo htmlspecialchars($pedido['forma_pagamento_nome']); ?></p>
                            <?php if ($pedido['forma_pagamento_id'] == 1 || $pedido['forma_pagamento_id'] == 2): ?>
                            <p>**** **** **** <?php echo substr($pedido['cartao_numero'] ?? '', -4); ?></p>
                            <?php elseif ($pedido['forma_pagamento_id'] == 3): ?>
                            <p>PIX - Código gerado</p>
                            <?php else: ?>
                            <p>Dinheiro</p>
                            <?php if ($pedido['troco_para']): ?>
                            <p>Troco para: R$ <?php echo number_format($pedido['troco_para'], 2, ',', '.'); ?></p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Total Summary -->
                <div class="total-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>R$ <?php echo number_format($pedido['subtotal'], 2, ',', '.'); ?></span>
                    </div>
                    <?php if ($pedido['taxa_entrega'] > 0): ?>
                    <div class="summary-row">
                        <span>Taxa de Entrega:</span>
                        <span>R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" class="btn btn-primary" onclick="imprimirPedido()">
                        <i class="fas fa-print"></i>
                        Imprimir Pedido
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="compartilharPedido()">
                        <i class="fas fa-share"></i>
                        Compartilhar
                    </button>
                    <a href="meus-pedidos.php" class="btn btn-outline">
                        <i class="fas fa-history"></i>
                        Meus Pedidos
                    </a>
                    <a href="index.php" class="btn btn-success">
                        <i class="fas fa-home"></i>
                        Voltar ao Início
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Confirmação para Loja -->
    <div id="confirmacaoLojaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmação de Pedido</h3>
                <button type="button" class="modal-close" onclick="fecharModalLoja()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="confirmation-store-info">
                    <div class="store-header">
                        <i class="fas fa-store"></i>
                        <h4>Pedido Enviado para Loja</h4>
                    </div>
                    <div class="store-details">
                        <p><strong>Número do Pedido:</strong> #<?php echo $numero_pedido; ?></p>
                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Cliente'); ?></p>
                        <p><strong>Total:</strong> R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></p>
                        <p><strong>Status:</strong> <span class="status-pending">Aguardando Preparação</span></p>
                    </div>
                    <div class="store-actions">
                        <p>O seu pedido foi enviado com sucesso para nossa loja!</p>
                        <p>Você receberá atualizações sobre o status do pedido em tempo real.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="fecharModalLoja()">
                    <i class="fas fa-check"></i>
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/confirmacao.js"></script>
    <script>
        // Dados do pedido para JavaScript
        const pedidoData = {
            id: <?php echo $pedido_id; ?>,
            numero: '<?php echo $numero_pedido; ?>',
            total: <?php echo $pedido['total']; ?>,
            status: '<?php echo $pedido['status_nome']; ?>',
            cliente: '<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Cliente'); ?>',
            itens: <?php echo json_encode($itens); ?>
        };

        // Auto-abrir modal de confirmação da loja após 1 segundo
        setTimeout(function() {
            abrirModalLoja();
        }, 1000);
    </script>
</body>
</html>