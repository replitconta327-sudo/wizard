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

// Buscar itens do carrinho atual
try {
    $stmt = $pdo->prepare("
        SELECT 
            cb.id,
            cb.bebida_id,
            cb.quantidade,
            b.nome,
            b.descricao,
            b.preco,
            b.imagem_url,
            b.volume,
            bc.nome as categoria,
            (cb.quantidade * b.preco) as subtotal
        FROM carrinho_bebidas cb
        JOIN bebidas b ON cb.bebida_id = b.id
        JOIN bebidas_categorias bc ON b.categoria_id = bc.id
        WHERE cb.usuario_id = ? AND cb.status = 'ativo'
        ORDER BY cb.data_adicionado DESC
    ");
    $stmt->execute([$usuario_id]);
    $itens_carrinho = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular total
    $total = array_sum(array_column($itens_carrinho, 'subtotal'));
    
} catch (PDOException $e) {
    $itens_carrinho = [];
    $total = 0;
    $erro = "Erro ao carregar carrinho: " . $e->getMessage();
}

// Buscar endereços do usuário
try {
    $stmt = $pdo->prepare("
        SELECT id, apelido, logradouro, numero, complemento, bairro, cidade, uf, cep, padrao
        FROM enderecos 
        WHERE usuario_id = ? AND ativo = 1
        ORDER BY padrao DESC, apelido ASC
    ");
    $stmt->execute([$usuario_id]);
    $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $enderecos = [];
}

// Gerar número do pedido
$numero_pedido = 'PED-' . date('YmdHis') . '-' . str_pad($usuario_id, 4, '0', STR_PAD_LEFT);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisão do Pedido - Pizzaria</title>
    <link rel="stylesheet" href="/assets/css/pages/revisao.css">
    <link rel="stylesheet" href="/assets/css/components/stepper.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="revisao-container">
        <!-- Header -->
        <header class="revisao-header">
            <div class="container">
                <div class="header-content">
                    <a href="/pages/bebidas.php" class="btn-voltar">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                    <h1>Revisão do Pedido</h1>
                    <div class="header-info">
                        <span class="numero-pedido">Pedido #<?php echo htmlspecialchars($numero_pedido); ?></span>
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
                <div class="step active">
                    <div class="step-icon">2</div>
                    <div class="step-label">Revisão</div>
                </div>
                <div class="step">
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
        <main class="revisao-main">
            <div class="container">
                <?php if (empty($itens_carrinho)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h2>Carrinho vazio</h2>
                        <p>Você ainda não selecionou nenhuma bebida.</p>
                        <a href="bebidas.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Escolher Bebidas
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Order Review -->
                    <div class="revisao-grid">
                        <!-- Items Section -->
                        <section class="itens-section">
                            <div class="section-header">
                                <h2>
                                    <i class="fas fa-glass-cheers"></i>
                                    Bebidas Selecionadas
                                </h2>
                                <span class="itens-count"><?php echo count($itens_carrinho); ?> itens</span>
                            </div>

                            <div class="itens-list">
                                <?php foreach ($itens_carrinho as $item): ?>
                                    <div class="item-card" data-item-id="<?php echo $item['id']; ?>">
                                        <div class="item-image">
                                            <?php if ($item['imagem_url']): ?>
                                                <img src="<?php echo htmlspecialchars($item['imagem_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['nome']); ?>"
                                                     onerror="this.src='assets/img/default-drink.jpg'">
                                            <?php else: ?>
                                                <div class="image-placeholder">
                                                    <i class="fas fa-glass-cheers"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="item-info">
                                            <div class="item-header">
                                                <h3 class="item-name"><?php echo htmlspecialchars($item['nome']); ?></h3>
                                                <span class="item-category"><?php echo htmlspecialchars($item['categoria']); ?></span>
                                            </div>
                                            
                                            <div class="item-details">
                                                <p class="item-description"><?php echo htmlspecialchars($item['descricao']); ?></p>
                                                <div class="item-meta">
                                                    <span class="item-volume"><?php echo htmlspecialchars($item['volume']); ?></span>
                                                    <span class="item-price">R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="item-controls">
                                            <div class="quantity-control">
                                                <button class="btn-quantity btn-minus" data-action="decrease" 
                                                        data-item-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <span class="quantity-value"><?php echo $item['quantidade']; ?></span>
                                                <button class="btn-quantity btn-plus" data-action="increase"
                                                        data-item-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            
                                            <div class="item-subtotal">
                                                <span class="subtotal-label">Subtotal</span>
                                                <span class="subtotal-value">R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></span>
                                            </div>
                                            
                                            <button class="btn-remove" data-item-id="<?php echo $item['id']; ?>" 
                                                    data-item-name="<?php echo htmlspecialchars($item['nome']); ?>">
                                                <i class="fas fa-trash"></i>
                                                Remover
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <!-- Summary Section -->
                        <aside class="summary-section">
                            <div class="summary-card">
                                <h3>Resumo do Pedido</h3>
                                
                                <div class="summary-items">
                                    <?php foreach ($itens_carrinho as $item): ?>
                                        <div class="summary-item">
                                            <span class="item-name">
                                                <?php echo htmlspecialchars($item['nome']); ?>
                                                <small>(<?php echo $item['quantidade']; ?>x)</small>
                                            </span>
                                            <span class="item-price">R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="summary-total">
                                    <div class="total-line">
                                        <span>Subtotal</span>
                                        <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                                    </div>
                                    <div class="total-line">
                                        <span>Taxa de serviço (10%)</span>
                                        <span>R$ <?php echo number_format($total * 0.1, 2, ',', '.'); ?></span>
                                    </div>
                                    <div class="total-line total-final">
                                        <span>Total</span>
                                        <span>R$ <?php echo number_format($total * 1.1, 2, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Address -->
                            <div class="address-card">
                                <h3>Endereço de Entrega</h3>
                                <?php if (!empty($enderecos)): ?>
                                    <div class="address-selector">
                                        <select id="endereco_entrega" class="form-control">
                                            <option value="">Selecione um endereço</option>
                                            <?php foreach ($enderecos as $endereco): ?>
                                                <option value="<?php echo $endereco['id']; ?>" 
                                                        <?php echo $endereco['padrao'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($endereco['apelido']); ?> - 
                                                    <?php echo htmlspecialchars($endereco['logradouro'] . ', ' . $endereco['numero'] . ' - ' . $endereco['bairro']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-outline" onclick="adicionarNovoEndereco()">
                                        <i class="fas fa-plus"></i>
                                        Adicionar Novo Endereço
                                    </button>
                                <?php else: ?>
                                    <div class="no-address">
                                        <p>Você ainda não tem endereços cadastrados.</p>
                                        <button type="button" class="btn btn-primary" onclick="adicionarNovoEndereco()">
                                            <i class="fas fa-plus"></i>
                                            Cadastrar Endereço
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Delivery Time -->
                            <div class="delivery-card">
                                <h3>Tempo de Entrega</h3>
                                <div class="delivery-options">
                                    <label class="delivery-option">
                                        <input type="radio" name="delivery_time" value="now" checked>
                                        <span class="option-label">O mais rápido possível</span>
                                        <span class="option-time">30-45 min</span>
                                    </label>
                                    <label class="delivery-option">
                                        <input type="radio" name="delivery_time" value="schedule">
                                        <span class="option-label">Agendar para:</span>
                                        <input type="datetime-local" id="scheduled_time" class="form-control" 
                                               min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>">
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="actions">
                                <button type="button" class="btn btn-secondary" onclick="continuarComprando()">
                                    <i class="fas fa-arrow-left"></i>
                                    Continuar Comprando
                                </button>
                                <button type="button" class="btn btn-primary btn-block" onclick="finalizarPedido()"
                                        <?php echo empty($enderecos) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-check"></i>
                                    Finalizar Pedido
                                </button>
                            </div>
                        </aside>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="modal-remover" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Remoção</h3>
                <button class="modal-close" onclick="fecharModal('modal-remover')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja remover <strong id="item-name-remover"></strong> do seu pedido?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal('modal-remover')">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmar-remocao">Remover</button>
            </div>
        </div>
    </div>

    <div id="modal-endereco" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Adicionar Endereço</h3>
                <button class="modal-close" onclick="fecharModal('modal-endereco')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-endereco">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="apelido">Apelido do Endereço</label>
                            <input type="text" id="apelido" name="apelido" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" class="form-control" required maxlength="9">
                        </div>
                        <div class="form-group">
                            <label for="logradouro">Logradouro</label>
                            <input type="text" id="logradouro" name="logradouro" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="numero">Número</label>
                            <input type="text" id="numero" name="numero" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="bairro">Bairro</label>
                            <input type="text" id="bairro" name="bairro" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="uf">UF</label>
                            <select id="uf" name="uf" class="form-control" required>
                                <option value="">Selecione</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="endereco_padrao" name="padrao" value="1">
                            <span class="checkmark"></span>
                            Definir como endereço padrão
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal('modal-endereco')">Cancelar</button>
                <button type="button" class="btn btn-primary" id="salvar-endereco">Salvar Endereço</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Processando...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <script src="/assets/js/revisao.js"></script>
</body>
</html>