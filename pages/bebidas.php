<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecionar Bebidas - Pizzaria São Paulo</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/pages/bebidas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <h1>São Paulo</h1>
                <span>Pizzaria</span>
            </div>
            <h2>Cardápio</h2>
            <p>Monte seu pedido</p>
        </header>

        <!-- Stepper Navigation -->
        <div class="stepper">
            <div class="step completed" data-step="1">
                <div class="step-circle">1</div>
                <div class="step-label">Tamanho</div>
            </div>
            <div class="step completed" data-step="2">
                <div class="step-circle">2</div>
                <div class="step-label">Sabores</div>
            </div>
            <div class="step completed" data-step="3">
                <div class="step-circle">3</div>
                <div class="step-label">Adicionais</div>
            </div>
            <div class="step active" data-step="4">
                <div class="step-circle">4</div>
                <div class="step-label">Bebidas</div>
            </div>
            <div class="step" data-step="5">
                <div class="step-circle">5</div>
                <div class="step-label">Revisão</div>
            </div>
        </div>

        <main class="main-content">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
                <div class="loading-message">Carregando bebidas...</div>
                <div class="loading-progress-bar hidden" id="loadingProgressBar">
                    <div class="progress-fill" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    <div class="progress-text">0%</div>
                </div>
            </div>

            <!-- Filtros e Busca -->
            <div class="filters-section">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Buscar bebidas..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
                <div class="filter-controls">
                    <select id="categoryFilter" class="filter-select">
                        <option value="">Todas as Categorias</option>
                    </select>
                    
                    <select id="priceFilter" class="filter-select">
                        <option value="">Todos os Preços</option>
                        <option value="0-5">Até R$ 5,00</option>
                        <option value="5-10">R$ 5,00 - R$ 10,00</option>
                        <option value="10-999">Acima de R$ 10,00</option>
                    </select>
                    
                    <select id="sortFilter" class="filter-select">
                        <option value="nome">Ordenar por Nome</option>
                        <option value="preco_asc">Preço: Menor para Maior</option>
                        <option value="preco_desc">Preço: Maior para Menor</option>
                    </select>
                    
                    <button id="suggestionsBtn" class="btn-suggestions">
                        <i class="fas fa-magic"></i> Sugestões
                    </button>
                </div>
            </div>

            <!-- Sugestões Personalizadas -->
            <div class="suggestions-section" id="suggestionsSection" style="display: none;">
                <h3>Sugestões para Você</h3>
                <div class="suggestions-grid" id="suggestionsGrid"></div>
            </div>

            <!-- Categorias -->
            <div class="categories-section">
                <div class="categories-tabs" id="categoriesTabs"></div>
            </div>

            <!-- Lista de Bebidas -->
            <div class="beverages-section">
                <h3 id="sectionTitle">Todas as Bebidas</h3>
                <div class="beverages-grid" id="beveragesGrid">
                    <!-- Bebidas serão carregadas dinamicamente -->
                </div>
            </div>

            <!-- Carrinho Flutuante -->
            <div class="floating-cart" id="floatingCart" style="display: none;">
                <div class="cart-header">
                    <h4>Seu Carrinho</h4>
                    <span class="cart-count" id="cartCount">0</span>
                </div>
                <div class="cart-items" id="cartItems"></div>
                <div class="cart-footer">
                    <div class="cart-total">
                        <span>Total:</span>
                        <span id="cartTotal">R$ 0,00</span>
                    </div>
                    <button class="btn-clear-cart" id="clearCart">
                        <i class="fas fa-trash"></i> Limpar
                    </button>
                </div>
            </div>

            <!-- Notificações -->
            <div class="notification-container" id="notificationContainer"></div>

            <!-- Erro Container -->
            <div class="error-container" id="errorContainer" style="display: none;">
                <div class="error-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Erro ao Carregar Bebidas</h3>
                    <p id="errorMessage"></p>
                    <div class="error-actions">
                        <button class="btn-retry" id="retryBtn">Tentar Novamente</button>
                        <button class="btn-offline" id="offlineBtn">Modo Offline</button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <button class="nav-btn" id="backBtn">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
            
            <div class="cart-info">
                <i class="fas fa-shopping-cart"></i>
                <span>Carrinho</span>
                <span class="cart-badge" id="cartBadge">0</span>
            </div>
            
            <button class="nav-btn primary" id="continueBtn" disabled>
                <span>Continuar</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </nav>
    </div>

    <!-- Modal de Quantidade -->
    <div class="modal" id="quantityModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Selecionar Quantidade</h3>
                <button class="modal-close" id="modalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="beverage-info" id="modalBeverageInfo"></div>
                <div class="quantity-selector">
                    <button class="quantity-btn" id="decreaseBtn">-</button>
                    <input type="number" id="quantityInput" min="1" max="99" value="1">
                    <button class="quantity-btn" id="increaseBtn">+</button>
                </div>
                <div class="price-display">
                    <span>Total: </span>
                    <span id="modalTotalPrice">R$ 0,00</span>
                </div>
                <div class="observation-input">
                    <label for="observationInput">Observações (opcional):</label>
                    <textarea id="observationInput" placeholder="Ex: Sem gelo, gelada, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" id="cancelBtn">Cancelar</button>
                <button class="btn-primary" id="confirmBtn">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal" id="confirmModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmação</h3>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" id="confirmCancel">Não</button>
                <button class="btn-primary" id="confirmYes">Sim</button>
            </div>
        </div>
    </div>

    <!-- Audio para notificações -->
    <audio id="notificationSound" preload="auto">
        <source src="/assets/sounds/notification.mp3" type="audio/mpeg">
    </audio>

    <script src="/assets/js/bebidas.js"></script>
    <script src="/assets/js/bebidas-realtime.js"></script>
</body>
</html>