<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$usuario_id = $_SESSION['user_id'];
$db = new Database();
$pdo = $db->pdo();

// Buscar dados do pedido
if (!isset($_GET['pedido_id'])) {
    header('Location: /pages/revisao.php');
    exit;
}

$pedido_id = $_GET['pedido_id'];

try {
    // Buscar detalhes do pedido
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.numero_pedido,
            p.total,
            p.status,
            p.tipo_entrega,
            p.horario_agendado,
            p.data_criado,
            e.apelido,
            e.logradouro,
            e.numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.uf,
            e.cep
        FROM pedidos p
        JOIN enderecos e ON p.endereco_id = e.id
        WHERE p.id = ? AND p.usuario_id = ?
    ");
    $stmt->execute([$pedido_id, $usuario_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        header('Location: /pages/revisao.php');
        exit;
    }
    
    // Buscar itens do pedido
    $stmt = $pdo->prepare("
        SELECT 
            pb.bebida_id,
            pb.quantidade,
            pb.preco_unitario,
            b.nome,
            b.imagem_url,
            bc.nome as categoria
        FROM pedido_bebidas pb
        JOIN bebidas b ON pb.bebida_id = b.id
        JOIN bebidas_categorias bc ON b.categoria_id = bc.id
        WHERE pb.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $itens_pedido = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar cartões do usuário
    $stmt = $pdo->prepare("
        SELECT id, numero_cartao_mascarado, bandeira, nome_titular, validade, padrao
        FROM cartoes_usuario
        WHERE usuario_id = ? AND ativo = 1
        ORDER BY padrao DESC, id DESC
    ");
    $stmt->execute([$usuario_id]);
    $cartoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados do pedido: " . $e->getMessage();
    $pedido = null;
    $itens_pedido = [];
    $cartoes = [];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - Pizzaria</title>
    <link rel="stylesheet" href="/assets/css/pages/pagamento.css">
    <link rel="stylesheet" href="/assets/css/components/stepper.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="pagamento-container">
        <!-- Header -->
        <header class="pagamento-header">
            <div class="container">
                <div class="header-content">
                    <a href="/pages/revisao.php" class="btn-voltar">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                    <h1>Pagamento</h1>
                    <div class="header-info">
                        <span class="numero-pedido">Pedido #<?php echo htmlspecialchars($pedido['numero_pedido']); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stepper -->
        <div class="stepper-container">
            <div class="stepper">
                <div class="step completed">
                    <div class="step-icon">1</div>
                    <div class="step-label">Bebidas</div>
                </div>
                <div class="step completed">
                    <div class="step-icon">2</div>
                    <div class="step-label">Revisão</div>
                </div>
                <div class="step active">
                    <div class="step-icon">3</div>
                    <div class="step-label">Pagamento</div>
                </div>
                <div class="step">
                    <div class="step-icon">4</div>
                    <div class="step-label">Confirmação</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="pagamento-main">
            <div class="container">
                <?php if (!$pedido): ?>
                    <!-- Error State -->
                    <div class="error-state">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2>Pedido não encontrado</h2>
                        <p><?php echo htmlspecialchars($erro ?? 'O pedido não foi encontrado ou está indisponível.'); ?></p>
                        <a href="bebidas.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Escolher Bebidas
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Payment Content -->
                    <div class="pagamento-grid">
                        <!-- Payment Methods -->
                        <section class="payment-section">
                            <div class="section-header">
                                <h2>
                                    <i class="fas fa-credit-card"></i>
                                    Forma de Pagamento
                                </h2>
                            </div>

                            <!-- Payment Options -->
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="card" checked>
                                    <div class="option-content">
                                        <div class="option-icon">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="option-details">
                                            <span class="option-title">Cartão de Crédito</span>
                                            <span class="option-subtitle">Parcelamento disponível</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="debit">
                                    <div class="option-content">
                                        <div class="option-icon">
                                            <i class="fas fa-money-check"></i>
                                        </div>
                                        <div class="option-details">
                                            <span class="option-title">Cartão de Débito</span>
                                            <span class="option-subtitle">Pagamento à vista</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="pix">
                                    <div class="option-content">
                                        <div class="option-icon">
                                            <i class="fas fa-qrcode"></i>
                                        </div>
                                        <div class="option-details">
                                            <span class="option-title">PIX</span>
                                            <span class="option-subtitle">Pagamento instantâneo</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="cash">
                                    <div class="option-content">
                                        <div class="option-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="option-details">
                                            <span class="option-title">Dinheiro</span>
                                            <span class="option-subtitle">Pagamento na entrega</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Card Payment Form -->
                            <div id="card-payment-form" class="payment-form active">
                                <div class="form-section">
                                    <h3>Dados do Cartão</h3>
                                    
                                    <!-- Saved Cards -->
                                    <?php if (!empty($cartoes)): ?>
                                        <div class="saved-cards">
                                            <h4>Cartões Salvos</h4>
                                            <?php foreach ($cartoes as $cartao): ?>
                                                <label class="saved-card-option">
                                                    <input type="radio" name="saved_card" value="<?php echo $cartao['id']; ?>"
                                                           <?php echo $cartao['padrao'] ? 'checked' : ''; ?>>
                                                    <div class="card-info">
                                                        <span class="card-number">•••• •••• •••• <?php echo substr($cartao['numero_cartao_mascarado'], -4); ?></span>
                                                        <span class="card-details"><?php echo htmlspecialchars($cartao['bandeira']); ?> - <?php echo htmlspecialchars($cartao['nome_titular']); ?></span>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- New Card Form -->
                                    <div class="new-card-form">
                                        <h4>Novo Cartão</h4>
                                        <form id="card-form">
                                            <div class="form-grid">
                                                <div class="form-group full-width">
                                                    <label for="card_number">Número do Cartão</label>
                                                    <input type="text" id="card_number" name="card_number" 
                                                           class="form-control" placeholder="0000 0000 0000 0000" maxlength="19">
                                                    <div class="card-brand" id="card_brand"></div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="card_name">Nome do Titular</label>
                                                    <input type="text" id="card_name" name="card_name" 
                                                           class="form-control" placeholder="Como aparece no cartão">
                                                </div>
                                                <div class="form-group">
                                                    <label for="card_cpf">CPF do Titular</label>
                                                    <input type="text" id="card_cpf" name="card_cpf" 
                                                           class="form-control" placeholder="000.000.000-00" maxlength="14">
                                                </div>
                                                <div class="form-group">
                                                    <label for="card_expiry">Validade</label>
                                                    <input type="text" id="card_expiry" name="card_expiry" 
                                                           class="form-control" placeholder="MM/AA" maxlength="5">
                                                </div>
                                                <div class="form-group">
                                                    <label for="card_cvv">CVV</label>
                                                    <input type="text" id="card_cvv" name="card_cvv" 
                                                           class="form-control" placeholder="000" maxlength="4">
                                                </div>
                                                <div class="form-group">
                                                    <label for="installments">Parcelamento</label>
                                                    <select id="installments" name="installments" class="form-control">
                                                        <option value="1">À vista (1x)</option>
                                                        <option value="2">2x sem juros</option>
                                                        <option value="3">3x sem juros</option>
                                                        <option value="4">4x sem juros</option>
                                                        <option value="5">5x sem juros</option>
                                                        <option value="6">6x sem juros</option>
                                                        <option value="7">7x com juros</option>
                                                        <option value="8">8x com juros</option>
                                                        <option value="9">9x com juros</option>
                                                        <option value="10">10x com juros</option>
                                                        <option value="11">11x com juros</option>
                                                        <option value="12">12x com juros</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <label class="checkbox-label">
                                                <input type="checkbox" id="save_card" name="save_card" value="1">
                                                <span class="checkmark"></span>
                                                Salvar cartão para compras futuras
                                            </label>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- PIX Payment -->
                            <div id="pix-payment-form" class="payment-form">
                                <div class="pix-instructions">
                                    <h3>Instruções para pagamento via PIX</h3>
                                    <div class="pix-steps">
                                        <div class="step">
                                            <span class="step-number">1</span>
                                            <span class="step-text">Abra o app do seu banco</span>
                                        </div>
                                        <div class="step">
                                            <span class="step-number">2</span>
                                            <span class="step-text">Escaneie o QR Code ou copie o código</span>
                                        </div>
                                        <div class="step">
                                            <span class="step-number">3</span>
                                            <span class="step-text">Confirme o pagamento</span>
                                        </div>
                                    </div>
                                    <div class="pix-code-container">
                                        <div class="pix-qr" id="pix-qr">
                                            <div class="qr-placeholder">
                                                <i class="fas fa-qrcode"></i>
                                                <span>QR Code será gerado</span>
                                            </div>
                                        </div>
                                        <div class="pix-code">
                                            <label>Código PIX:</label>
                                            <div class="code-input-group">
                                                <input type="text" id="pix-code" readonly value="Aguardando geração...">
                                                <button type="button" class="btn-copy" onclick="copiarCodigoPix()">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pix-warning">
                                        <i class="fas fa-clock"></i>
                                        <span>O código PIX expira em 30 minutos</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cash Payment -->
                            <div id="cash-payment-form" class="payment-form">
                                <div class="cash-instructions">
                                    <h3>Instruções para pagamento em dinheiro</h3>
                                    <div class="form-group">
                                        <label for="cash_amount">Valor para troco</label>
                                        <select id="cash_amount" name="cash_amount" class="form-control">
                                            <option value="exact">Valor exato</option>
                                            <option value="10">R$ 10,00</option>
                                            <option value="20">R$ 20,00</option>
                                            <option value="50">R$ 50,00</option>
                                            <option value="100">R$ 100,00</option>
                                            <option value="custom">Outro valor</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="custom-cash-group" style="display: none;">
                                        <label for="custom_cash_amount">Qual valor?</label>
                                        <input type="number" id="custom_cash_amount" name="custom_cash_amount" 
                                               class="form-control" step="0.01" min="0">
                                    </div>
                                    <div class="cash-warning">
                                        <i class="fas fa-info-circle"></i>
                                        <span>O entregador levará o troco solicitado</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Order Summary -->
                        <aside class="summary-section">
                            <div class="summary-card">
                                <h3>Resumo do Pedido</h3>
                                
                                <div class="order-info">
                                    <div class="info-row">
                                        <span class="info-label">Número do Pedido:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($pedido['numero_pedido']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Data:</span>
                                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['data_criado'])); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Entrega:</span>
                                        <span class="info-value">
                                            <?php echo $pedido['tipo_entrega'] === 'now' ? 'O mais rápido' : date('d/m H:i', strtotime($pedido['horario_agendado'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="delivery-address">
                                    <h4>Endereço de Entrega</h4>
                                    <div class="address-info">
                                        <span class="address-label"><?php echo htmlspecialchars($pedido['apelido']); ?></span>
                                        <span class="address-street"><?php echo htmlspecialchars($pedido['logradouro'] . ', ' . $pedido['numero']); ?></span>
                                        <?php if ($pedido['complemento']): ?>
                                            <span class="address-complement"><?php echo htmlspecialchars($pedido['complemento']); ?></span>
                                        <?php endif; ?>
                                        <span class="address-neighborhood"><?php echo htmlspecialchars($pedido['bairro']); ?></span>
                                        <span class="address-city"><?php echo htmlspecialchars($pedido['cidade'] . ' - ' . $pedido['uf'] . ', ' . $pedido['cep']); ?></span>
                                    </div>
                                </div>

                                <div class="items-summary">
                                    <h4>Itens do Pedido</h4>
                                    <?php foreach ($itens_pedido as $item): ?>
                                        <div class="summary-item">
                                            <span class="item-name">
                                                <?php echo htmlspecialchars($item['nome']); ?>
                                                <small>(<?php echo $item['quantidade']; ?>x)</small>
                                            </span>
                                            <span class="item-price">R$ <?php echo number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.'); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="summary-total">
                                    <div class="total-line">
                                        <span>Subtotal</span>
                                        <span>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></span>
                                    </div>
                                    <div class="total-line">
                                        <span>Taxa de serviço (10%)</span>
                                        <span>R$ <?php echo number_format($pedido['total'] * 0.1, 2, ',', '.'); ?></span>
                                    </div>
                                    <div class="total-line total-final">
                                        <span>Total</span>
                                        <span>R$ <?php echo number_format($pedido['total'] * 1.1, 2, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="actions">
                                <button type="button" class="btn btn-secondary" onclick="voltarRevisao()">
                                    <i class="fas fa-arrow-left"></i>
                                    Voltar
                                </button>
                                <button type="button" class="btn btn-primary btn-block" onclick="processarPagamento()">
                                    <i class="fas fa-lock"></i>
                                    Finalizar Pagamento
                                </button>
                            </div>
                        </aside>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="modal-confirmacao" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Pagamento</h3>
                <button class="modal-close" onclick="fecharModal('modal-confirmacao')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="confirmation-details">
                    <div class="confirmation-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Pagamento Seguro</h4>
                    <p>Você está prestes a finalizar o pagamento do pedido <strong><?php echo htmlspecialchars($pedido['numero_pedido']); ?></strong>.</p>
                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Forma de Pagamento:</span>
                            <span id="confirm-payment-method">-</span>
                        </div>
                        <div class="summary-row">
                            <span>Valor Total:</span>
                            <span id="confirm-total">R$ <?php echo number_format($pedido['total'] * 1.1, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    <div class="security-info">
                        <i class="fas fa-lock"></i>
                        <span>Seu pagamento é processado de forma segura e criptografada.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal('modal-confirmacao')">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmar-pagamento">
                    <i class="fas fa-check"></i>
                    Confirmar Pagamento
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="modal-sucesso" class="modal">
        <div class="modal-content success">
            <div class="modal-header">
                <h3>Pagamento Realizado com Sucesso!</h3>
                <button class="modal-close" onclick="fecharModal('modal-sucesso')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="success-content">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>Obrigado pelo seu pedido!</h4>
                    <p>Seu pagamento foi processado com sucesso.</p>
                    <div class="order-details">
                        <div class="detail-row">
                            <span>Número do Pedido:</span>
                            <strong><?php echo htmlspecialchars($pedido['numero_pedido']); ?></strong>
                        </div>
                        <div class="detail-row">
                            <span>Status:</span>
                            <span class="status-badge status-confirmed">Confirmado</span>
                        </div>
                        <div class="detail-row">
                            <span>Previsão de Entrega:</span>
                            <strong>30-45 minutos</strong>
                        </div>
                    </div>
                    <div class="next-steps">
                        <p>Você receberá atualizações sobre o status do seu pedido.</p>
                        <p>Acompanhe o progresso em tempo real no app ou site.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="irParaConfirmacao()">
                    <i class="fas fa-arrow-right"></i>
                    Ver Detalhes do Pedido
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p id="loading-message">Processando pagamento...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <script src="/assets/js/pagamento.js"></script>
</body>
</html>