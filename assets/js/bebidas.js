// Sistema de Seleção de Bebidas com Otimizações
class BeverageSystem {
    constructor() {
        this.beverages = [];
        this.categories = [];
        this.cart = new Map();
        this.suggestions = [];
        this.currentFilter = {
            category: '',
            search: '',
            priceRange: '',
            sort: 'nome'
        };
        this.isLoading = false;
        this.retryCount = 0;
        this.maxRetries = 3;
        this.offlineMode = false;
        this.cache = new Map();
        this.lastUpdate = null;
        
        this.init();
    }
    
    async init() {
        try {
            this.setupEventListeners();
            this.setupOfflineSupport();
            await this.loadInitialData();
            this.setupRealTimeUpdates();
            this.setupAccessibility();
        } catch (error) {
            console.error('Erro ao inicializar sistema de bebidas:', error);
            this.showError('Erro ao carregar sistema de bebidas');
        }
    }
    
    setupEventListeners() {
        // Filtros
        document.getElementById('searchInput').addEventListener('input', this.debounce(this.handleSearch.bind(this), 300));
        document.getElementById('categoryFilter').addEventListener('change', this.handleCategoryFilter.bind(this));
        document.getElementById('priceFilter').addEventListener('change', this.handlePriceFilter.bind(this));
        document.getElementById('sortFilter').addEventListener('change', this.handleSort.bind(this));
        document.getElementById('suggestionsBtn').addEventListener('click', this.toggleSuggestions.bind(this));
        
        // Navegação
        document.getElementById('backBtn').addEventListener('click', this.goBack.bind(this));
        document.getElementById('continueBtn').addEventListener('click', this.goToReview.bind(this));
        
        // Carrinho
        document.getElementById('clearCart').addEventListener('click', this.clearCart.bind(this));
        
        // Modal
        document.getElementById('modalClose').addEventListener('click', this.closeModal.bind(this));
        document.getElementById('cancelBtn').addEventListener('click', this.closeModal.bind(this));
        document.getElementById('confirmBtn').addEventListener('click', this.confirmQuantity.bind(this));
        document.getElementById('decreaseBtn').addEventListener('click', this.decreaseQuantity.bind(this));
        document.getElementById('increaseBtn').addEventListener('click', this.increaseQuantity.bind(this));
        document.getElementById('quantityInput').addEventListener('input', this.updateModalPrice.bind(this));
        
        // Erros
        document.getElementById('retryBtn').addEventListener('click', this.retryLoad.bind(this));
        document.getElementById('offlineBtn').addEventListener('click', this.enableOfflineMode.bind(this));
        
        // Fechar modal com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
        
        // Notificações de conexão
        window.addEventListener('online', () => {
            this.showNotification('Conexão restaurada', 'success');
            this.syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            this.showNotification('Modo offline ativado', 'warning');
            this.enableOfflineMode();
        });
    }
    
    setupOfflineSupport() {
        // Configurar cache offline
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(() => {
                console.log('Service Worker registrado');
            }).catch(err => {
                console.log('Service Worker não registrado:', err);
            });
        }
        
        // Configurar IndexedDB para cache offline
        this.setupIndexedDB();
    }
    
    async setupIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('BeverageSystem', 1);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve();
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                if (!db.objectStoreNames.contains('beverages')) {
                    db.createObjectStore('beverages', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('cart')) {
                    db.createObjectStore('cart', { keyPath: 'beverageId' });
                }
                
                if (!db.objectStoreNames.contains('pendingSync')) {
                    db.createObjectStore('pendingSync', { autoIncrement: true });
                }
            };
        });
    }
    
    async loadInitialData() {
        try {
            this.showLoadingStep(1, 5); // Conectando ao servidor...
            
            await this.delay(500); // Simular tempo de conexão
            
            this.showLoadingStep(2, 5); // Carregando categorias...
            await this.loadCategories();
            
            this.showLoadingStep(3, 5); // Carregando bebidas...
            await this.loadBeverages();
            
            this.showLoadingStep(4, 5); // Sincronizando dados...
            await Promise.all([
                this.loadSuggestions(),
                this.loadCurrentCart()
            ]);
            
            this.showLoadingStep(5, 5); // Preparando interface...
            await this.delay(300);
            
            this.renderCategories();
            this.renderBeverages();
            this.updateCartDisplay();
            
            this.lastUpdate = new Date();
            this.retryCount = 0;
            
            // Mostrar status de conexão
            this.updateConnectionStatus('online');
            
        } catch (error) {
            console.error('Erro ao carregar dados iniciais:', error);
            this.handleLoadError(error);
            this.updateConnectionStatus('offline');
        } finally {
            this.showLoading(false);
        }
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    updateConnectionStatus(status) {
        const existingStatus = document.querySelector('.connection-status');
        if (existingStatus) {
            existingStatus.remove();
        }
        
        const statusEl = document.createElement('div');
        statusEl.className = `connection-status ${status}`;
        statusEl.textContent = status === 'online' ? 'Conectado' : 
                               status === 'offline' ? 'Offline' : 'Sincronizando...';
        statusEl.setAttribute('aria-live', 'polite');
        document.body.appendChild(statusEl);
        
        // Remover após 3 segundos se estiver online
        if (status === 'online') {
            setTimeout(() => {
                if (statusEl.parentNode) {
                    statusEl.remove();
                }
            }, 3000);
        }
    }
    
    async loadCategories() {
        try {
            const response = await this.fetchWithTimeout('/api/bebidas.php?action=categorias', {
                timeout: 5000
            });
            
            if (response.ok) {
                const data = await response.json();
                this.categories = data.categorias;
                this.renderCategories();
            } else {
                throw new Error('Erro ao carregar categorias');
            }
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
            this.loadCategoriesFromCache();
        }
    }
    
    async loadBeverages() {
        try {
            const params = new URLSearchParams({
                action: 'listar',
                categoria: this.currentFilter.category,
                busca: this.currentFilter.search,
                preco_min: this.currentFilter.priceRange.split('-')[0] || 0,
                preco_max: this.currentFilter.priceRange.split('-')[1] || 999,
                ordem: this.currentFilter.sort
            });
            
            const response = await this.fetchWithTimeout(`/api/bebidas.php?${params}`, {
                timeout: 5000
            });
            
            if (response.ok) {
                const data = await response.json();
                this.beverages = data.bebidas;
                this.cache.set('beverages', this.beverages);
                this.storeInIndexedDB('beverages', this.beverages);
                this.renderBeverages();
            } else {
                throw new Error('Erro ao carregar bebidas');
            }
        } catch (error) {
            console.error('Erro ao carregar bebidas:', error);
            this.loadBeveragesFromCache();
        }
    }
    
    async loadSuggestions() {
        try {
            const response = await this.fetchWithTimeout('/api/bebidas.php?action=sugestoes&limite=6', {
                timeout: 3000
            });
            
            if (response.ok) {
                const data = await response.json();
                this.suggestions = data.sugestoes;
            }
        } catch (error) {
            console.log('Sugestões não disponíveis no momento');
        }
    }
    
    async loadCurrentCart() {
        try {
            const response = await this.fetchWithTimeout('/api/bebidas.php?action=get_cart', {
                timeout: 3000
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.cart.clear();
                    data.data.forEach(item => {
                        this.cart.set(item.bebida_id, item);
                    });
                    this.updateCartDisplay();
                }
            }
        } catch (error) {
            console.log('Carrinho não disponível no momento');
        }
    }
    
    renderCategories() {
        const container = document.getElementById('categoriesTabs');
        const filterSelect = document.getElementById('categoryFilter');
        
        // Limpar containers
        container.innerHTML = '';
        filterSelect.innerHTML = '<option value="">Todas as Categorias</option>';
        
        this.categories.forEach(category => {
            // Criar tab de categoria
            const tab = document.createElement('button');
            tab.className = 'category-tab';
            tab.dataset.category = category.nome;
            tab.innerHTML = `
                <i class="${category.icone}"></i>
                <span>${category.nome}</span>
                <small>(${category.quantidade_bebidas})</small>
            `;
            tab.addEventListener('click', () => this.selectCategory(category.nome));
            container.appendChild(tab);
            
            // Adicionar ao select de filtro
            const option = document.createElement('option');
            option.value = category.nome;
            option.textContent = `${category.nome} (${category.quantidade_bebidas})`;
            filterSelect.appendChild(option);
        });
    }
    
    renderBeverages() {
        const container = document.getElementById('beveragesGrid');
        
        if (this.beverages.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-8">
                    <i class="fas fa-glass-whiskey text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Nenhuma bebida encontrada</h3>
                    <p class="text-gray-500">Tente ajustar os filtros ou buscar por outro termo.</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.beverages.map(beverage => this.createBeverageCard(beverage)).join('');
        
        // Adicionar event listeners
        container.querySelectorAll('.beverage-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.beverage-actions')) {
                    this.openQuantityModal(parseInt(card.dataset.beverageId));
                }
            });
        });
        
        container.querySelectorAll('.btn-add').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.addToCart(parseInt(btn.dataset.beverageId));
            });
        });
        
        container.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeFromCart(parseInt(btn.dataset.beverageId));
            });
        });
    }
    
    createBeverageCard(beverage) {
        const isInCart = beverage.no_carrinho > 0;
        const categoryColor = beverage.categoria_cor || '#333333';
        
        return `
            <div class="beverage-card ${isInCart ? 'selected' : ''}" data-beverage-id="${beverage.id}">
                <div class="beverage-image">
                    ${beverage.imagem 
                        ? `<img src="${beverage.imagem}" alt="${beverage.nome}" onerror="this.src='/assets/img/placeholder-beverage.png'">`
                        : `<div class="placeholder"><i class="fas fa-glass-whiskey" style="color: ${categoryColor}"></i></div>`
                    }
                </div>
                <div class="beverage-name">${beverage.nome}</div>
                <div class="beverage-description">${beverage.descricao || ''}</div>
                <div class="beverage-volume">${beverage.volume}</div>
                <div class="beverage-footer">
                    <div class="beverage-price">${beverage.preco_formatado}</div>
                    <div class="beverage-actions">
                        ${isInCart 
                            ? `
                                <button class="btn-remove" data-beverage-id="${beverage.id}">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity-display">${beverage.no_carrinho}</span>
                                <button class="btn-add" data-beverage-id="${beverage.id}">
                                    <i class="fas fa-plus"></i>
                                </button>
                            `
                            : `<button class="btn-add" data-beverage-id="${beverage.id}">
                                <i class="fas fa-plus"></i> Adicionar
                            </button>`
                        }
                    </div>
                </div>
            </div>
        `;
    }
    
    renderSuggestions() {
        const container = document.getElementById('suggestionsGrid');
        
        if (this.suggestions.length === 0) {
            document.getElementById('suggestionsSection').style.display = 'none';
            return;
        }
        
        container.innerHTML = this.suggestions.map(beverage => `
            <div class="suggestion-card" data-beverage-id="${beverage.id}">
                <div class="suggestion-image">
                    ${beverage.imagem 
                        ? `<img src="${beverage.imagem}" alt="${beverage.nome}" onerror="this.src='/assets/img/placeholder-beverage.png'">`
                        : `<i class="${beverage.categoria_icone || 'fas fa-glass-whiskey'}"></i>`
                    }
                </div>
                <div class="suggestion-name">${beverage.nome}</div>
                <div class="suggestion-price">${beverage.preco_formatado}</div>
                <div class="suggestion-frequency">Comprado ${beverage.total_compras}x</div>
            </div>
        `).join('');
        
        // Adicionar event listeners
        container.querySelectorAll('.suggestion-card').forEach(card => {
            card.addEventListener('click', () => {
                this.openQuantityModal(parseInt(card.dataset.beverageId));
            });
        });
    }
    
    // Métodos de interação
    async selectCategory(category) {
        this.currentFilter.category = category;
        
        // Atualizar UI
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.category === category);
        });
        
        document.getElementById('categoryFilter').value = category;
        
        // Atualizar título da seção
        const sectionTitle = document.getElementById('sectionTitle');
        sectionTitle.textContent = category || 'Todas as Bebidas';
        
        // Recarregar bebidas
        await this.loadBeverages();
    }
    
    async handleSearch() {
        const searchTerm = document.getElementById('searchInput').value.trim();
        this.currentFilter.search = searchTerm;
        await this.loadBeverages();
    }
    
    async handleCategoryFilter() {
        const category = document.getElementById('categoryFilter').value;
        this.currentFilter.category = category;
        await this.loadBeverages();
    }
    
    async handlePriceFilter() {
        const priceRange = document.getElementById('priceFilter').value;
        this.currentFilter.priceRange = priceRange;
        await this.loadBeverages();
    }
    
    async handleSort() {
        const sort = document.getElementById('sortFilter').value;
        this.currentFilter.sort = sort;
        await this.loadBeverages();
    }
    
    toggleSuggestions() {
        const section = document.getElementById('suggestionsSection');
        const isVisible = section.style.display !== 'none';
        
        section.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible && this.suggestions.length === 0) {
            this.loadSuggestions();
        }
        
        const btn = document.getElementById('suggestionsBtn');
        btn.classList.toggle('active', !isVisible);
    }
    
    // Métodos do carrinho
    async addToCart(beverageId) {
        const beverage = this.beverages.find(b => b.id === beverageId);
        if (!beverage) return;
        
        try {
            const response = await this.fetchWithTimeout('/api/bebidas.php?action=add_to_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    bebida_id: beverageId,
                    quantidade: 1
                }),
                timeout: 5000
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showNotification(`${beverage.nome} adicionado ao carrinho!`, 'success');
                    this.playNotificationSound();
                    await this.loadCurrentCart();
                    this.renderBeverages(); // Atualizar visualização
                } else {
                    this.showNotification(data.message || 'Erro ao adicionar bebida', 'error');
                }
            } else {
                throw new Error('Erro na requisição');
            }
        } catch (error) {
            console.error('Erro ao adicionar ao carrinho:', error);
            this.showNotification('Erro de conexão. Tentando modo offline...', 'warning');
            this.addToCartOffline(beverageId, 1);
        }
    }
    
    async removeFromCart(beverageId) {
        const beverage = this.beverages.find(b => b.id === beverageId);
        if (!beverage) return;
        
        try {
            // Obter o item_id do carrinho
            const cartResponse = await this.fetchWithTimeout('/api/bebidas.php?action=get_cart');
            if (cartResponse.ok) {
                const cartData = await cartResponse.json();
                if (cartData.success) {
                    const item = cartData.data.find(item => item.bebida_id === beverageId);
                    if (item) {
                        const response = await this.fetchWithTimeout('/api/bebidas.php?action=remove_from_cart', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                item_id: item.id
                            }),
                            timeout: 5000
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                this.showNotification(`${beverage.nome} removido do carrinho`, 'info');
                                await this.loadCurrentCart();
                                this.renderBeverages(); // Atualizar visualização
                            } else {
                                this.showNotification(data.message || 'Erro ao remover bebida', 'error');
                            }
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Erro ao remover do carrinho:', error);
            this.showNotification('Erro de conexão', 'error');
        }
    }
    
    addToCartOffline(beverageId, quantity) {
        // Implementar adição offline
        const beverage = this.beverages.find(b => b.id === beverageId);
        if (!beverage) return;
        
        const existingItem = this.cart.get(beverageId);
        const newQuantity = (existingItem?.quantity || 0) + quantity;
        
        this.cart.set(beverageId, {
            ...beverage,
            quantity: newQuantity,
            preco_total: beverage.preco * newQuantity
        });
        
        // Armazenar para sincronização posterior
        this.storeOfflineAction('add', beverageId, quantity);
        
        this.updateCartDisplay();
        this.showNotification(`${beverage.nome} adicionado (offline)`, 'warning');
    }
    
    storeOfflineAction(action, beverageId, quantity) {
        // Armazenar ação para sincronização posterior
        const offlineAction = {
            action,
            beverageId,
            quantity,
            timestamp: new Date().toISOString(),
            synced: false
        };
        
        // Adicionar ao IndexedDB
        if (this.db) {
            const transaction = this.db.transaction(['pendingSync'], 'readwrite');
            const store = transaction.objectStore('pendingSync');
            store.add(offlineAction);
        }
    }
    
    async syncOfflineData() {
        if (!this.db) return;
        
        try {
            const transaction = this.db.transaction(['pendingSync'], 'readonly');
            const store = transaction.objectStore('pendingSync');
            const request = store.getAll();
            
            request.onsuccess = async () => {
                const pendingActions = request.result.filter(action => !action.synced);
                
                for (const action of pendingActions) {
                    try {
                        await this.syncOfflineAction(action);
                        
                        // Marcar como sincronizado
                        const updateTransaction = this.db.transaction(['pendingSync'], 'readwrite');
                        const updateStore = updateTransaction.objectStore('pendingSync');
                        action.synced = true;
                        updateStore.put(action);
                    } catch (error) {
                        console.error('Erro ao sincronizar ação offline:', error);
                    }
                }
                
                if (pendingActions.length > 0) {
                    this.showNotification(`${pendingActions.length} ações offline sincronizadas`, 'success');
                    await this.loadCurrentCart();
                    this.renderBeverages();
                }
            };
        } catch (error) {
            console.error('Erro ao sincronizar dados offline:', error);
        }
    }
    
    async syncOfflineAction(action) {
        const endpoint = action.action === 'add' ? 'selecionar' : 'remover';
        
        const response = await fetch(`/api/bebidas.php?action=${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                bebida_id: action.beverageId,
                quantidade: action.quantity
            })
        });
        
        if (!response.ok) {
            throw new Error('Erro ao sincronizar ação');
        }
    }
    
    updateCartDisplay() {
        const cartCount = Array.from(this.cart.values()).reduce((total, item) => total + item.quantity, 0);
        const cartTotal = Array.from(this.cart.values()).reduce((total, item) => total + parseFloat(item.preco_total), 0);
        
        // Atualizar badges
        document.getElementById('cartBadge').textContent = cartCount;
        document.getElementById('cartCount').textContent = cartCount;
        document.getElementById('cartTotal').textContent = `R$ ${cartTotal.toFixed(2).replace('.', ',')}`;
        
        // Atualizar botão continuar
        const continueBtn = document.getElementById('continueBtn');
        continueBtn.disabled = cartCount === 0;
        
        // Atualizar carrinho flutuante
        this.renderFloatingCart();
        
        // Mostrar/ocultar carrinho flutuante
        const floatingCart = document.getElementById('floatingCart');
        floatingCart.style.display = cartCount > 0 ? 'block' : 'none';
    }
    
    renderFloatingCart() {
        const container = document.getElementById('cartItems');
        
        if (this.cart.size === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center">Carrinho vazio</p>';
            return;
        }
        
        container.innerHTML = Array.from(this.cart.values()).map(item => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nome}</div>
                    <div class="cart-item-quantity">${item.quantity}x</div>
                </div>
                <div class="cart-item-price">R$ ${parseFloat(item.preco_total).toFixed(2).replace('.', ',')}</div>
            </div>
        `).join('');
    }
    
    async clearCart() {
        if (!confirm('Deseja realmente limpar o carrinho?')) return;
        
        try {
            // Implementar limpeza do carrinho
            this.cart.clear();
            this.updateCartDisplay();
            this.renderBeverages();
            this.showNotification('Carrinho limpo com sucesso', 'info');
        } catch (error) {
            console.error('Erro ao limpar carrinho:', error);
            this.showNotification('Erro ao limpar carrinho', 'error');
        }
    }
    
    // Modal de quantidade
    openQuantityModal(beverageId) {
        const beverage = this.beverages.find(b => b.id === beverageId);
        if (!beverage) return;
        
        const currentItem = this.cart.get(beverageId);
        const currentQuantity = currentItem ? currentItem.quantity : 0;
        
        document.getElementById('modalTitle').textContent = 'Selecionar Quantidade';
        document.getElementById('modalBeverageInfo').innerHTML = `
            <div class="beverage-info">
                ${beverage.imagem 
                    ? `<img src="${beverage.imagem}" alt="${beverage.nome}" onerror="this.src='/assets/img/placeholder-beverage.png'">`
                    : `<i class="${beverage.categoria_icone || 'fas fa-glass-whiskey'}" style="font-size: 3rem; color: ${beverage.categoria_cor || '#333'}"></i>`
                }
                <h4>${beverage.nome}</h4>
                <p>${beverage.volume} - ${beverage.preco_formatado}</p>
                ${currentQuantity > 0 ? `<p class="text-sm text-gray-500">Quantidade atual: ${currentQuantity}</p>` : ''}
            </div>
        `;
        
        document.getElementById('quantityInput').value = Math.max(1, currentQuantity);
        document.getElementById('observationInput').value = '';
        
        this.currentModalBeverage = beverage;
        this.updateModalPrice();
        
        document.getElementById('quantityModal').style.display = 'block';
    }
    
    closeModal() {
        document.getElementById('quantityModal').style.display = 'none';
        document.getElementById('confirmModal').style.display = 'none';
        this.currentModalBeverage = null;
    }
    
    updateModalPrice() {
        const quantity = parseInt(document.getElementById('quantityInput').value) || 0;
        const price = this.currentModalBeverage ? this.currentModalBeverage.preco * quantity : 0;
        document.getElementById('modalTotalPrice').textContent = `R$ ${price.toFixed(2).replace('.', ',')}`;
    }
    
    increaseQuantity() {
        const input = document.getElementById('quantityInput');
        const currentValue = parseInt(input.value) || 0;
        input.value = Math.min(currentValue + 1, 99);
        this.updateModalPrice();
    }
    
    decreaseQuantity() {
        const input = document.getElementById('quantityInput');
        const currentValue = parseInt(input.value) || 0;
        input.value = Math.max(currentValue - 1, 1);
        this.updateModalPrice();
    }
    
    async confirmQuantity() {
        const quantity = parseInt(document.getElementById('quantityInput').value) || 0;
        const observation = document.getElementById('observationInput').value.trim();
        
        if (quantity <= 0) {
            this.showNotification('Quantidade inválida', 'error');
            return;
        }
        
        if (!this.currentModalBeverage) return;
        
        try {
            const response = await this.fetchWithTimeout('/api/bebidas.php?action=atualizar_quantidade', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    bebida_id: this.currentModalBeverage.id,
                    quantidade: quantity,
                    observacao: observation
                }),
                timeout: 5000
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.sucesso) {
                    this.showNotification(`${quantity}x ${this.currentModalBeverage.nome} atualizado!`, 'success');
                    this.playNotificationSound();
                    await this.loadCurrentCart();
                    this.renderBeverages();
                    this.closeModal();
                } else {
                    this.showNotification(data.erro || 'Erro ao atualizar quantidade', 'error');
                }
            }
        } catch (error) {
            console.error('Erro ao atualizar quantidade:', error);
            this.showNotification('Erro de conexão', 'error');
        }
    }
    
    // Métodos de navegação
    goBack() {
        window.location.href = '/cardapio/adicionais.php';
    }
    
    goToReview() {
        if (this.cart.size === 0) {
            this.showNotification('Adicione pelo menos uma bebida para continuar', 'warning');
            return;
        }
        
        // Salvar estado atual no sessionStorage
        sessionStorage.setItem('beverageCart', JSON.stringify(Array.from(this.cart.entries())));
        
        window.location.href = '/pages/revisao.php';
    }
    
    // Métodos de feedback
    showNotification(message, type = 'info') {
        // Usar o novo sistema de toast
        this.showToast(message, type);
    }
    
    playNotificationSound() {
        const sound = document.getElementById('notificationSound');
        if (sound) {
            sound.play().catch(e => console.log('Erro ao tocar som:', e));
        }
    }
    
    showLoading(show, message = 'Carregando...') {
        this.isLoading = show;
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingMessage = loadingOverlay?.querySelector('.loading-message');
        const loadingSpinner = loadingOverlay?.querySelector('.loading-spinner');
        
        if (loadingOverlay) {
            loadingOverlay.style.display = show ? 'flex' : 'none';
            if (loadingMessage) loadingMessage.textContent = message;
            if (loadingSpinner) loadingSpinner.classList.toggle('animate-spin', show);
        }
        
        if (show) {
            document.getElementById('errorContainer').style.display = 'none';
            this.showSkeletonLoading();
        } else {
            this.hideSkeletonLoading();
        }
    }
    
    showSkeletonLoading() {
        const container = document.getElementById('beveragesGrid');
        if (!container || this.beverages.length > 0) return;
        
        container.innerHTML = Array.from({length: 6}, (_, i) => `
            <div class="beverage-card skeleton-loading" aria-hidden="true" role="presentation">
                <div class="skeleton-image shimmer"></div>
                <div class="skeleton-content">
                    <div class="skeleton-title shimmer"></div>
                    <div class="skeleton-description shimmer"></div>
                    <div class="skeleton-price shimmer"></div>
                    <div class="skeleton-button shimmer"></div>
                </div>
            </div>
        `).join('');
    }
    
    hideSkeletonLoading() {
        const skeletons = document.querySelectorAll('.skeleton-loading');
        skeletons.forEach(skeleton => skeleton.remove());
    }
    
    showProgressBar(progress) {
        const progressBar = document.getElementById('loadingProgressBar');
        const progressFill = progressBar?.querySelector('.progress-fill');
        const progressText = progressBar?.querySelector('.progress-text');
        
        if (progressBar && progressFill) {
            progressBar.classList.toggle('hidden', progress <= 0);
            progressFill.style.width = `${Math.min(progress, 100)}%`;
            progressFill.setAttribute('aria-valuenow', Math.min(progress, 100));
            
            if (progressText) {
                progressText.textContent = `${Math.round(progress)}%`;
            }
            
            if (progress >= 100) {
                setTimeout(() => progressBar.classList.add('hidden'), 1000);
            }
        }
    }
    
    showLoadingStep(step, totalSteps) {
        const progress = (step / totalSteps) * 100;
        const messages = [
            'Conectando ao servidor...',
            'Carregando categorias...',
            'Carregando bebidas...',
            'Sincronizando dados...',
            'Preparando interface...'
        ];
        
        this.showLoading(true, messages[step - 1] || 'Carregando...');
        this.showProgressBar(progress);
    }
    
    showToast(message, type = 'info', duration = 3000) {
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        const toast = document.createElement('div');
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        toast.className = `toast-notification ${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out`;
        toast.innerHTML = `
            <div class="flex items-center space-x-3">
                <span class="text-lg">${icons[type]}</span>
                <span class="text-sm font-medium">${message}</span>
                <button class="toast-close ml-auto text-white hover:text-gray-200" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.removeToast(toast);
        });
        
        toastContainer.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.add('translate-y-0', 'opacity-100');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            this.removeToast(toast);
        }, duration);
        
        // Announce to screen readers
        this.announceToScreenReader(message, type);
    }
    
    removeToast(toast) {
        toast.classList.add('translate-y-full', 'opacity-0');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
        return container;
    }
    
    announceToScreenReader(message, type) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'assertive');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = `${type === 'error' ? 'Erro' : type === 'success' ? 'Sucesso' : 'Aviso'}: ${message}`;
        
        document.body.appendChild(announcement);
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
    
    // Métodos de erro e retry
    handleLoadError(error) {
        console.error('Erro ao carregar dados:', error);
        
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            this.showNotification(`Tentando reconectar... (${this.retryCount}/${this.maxRetries})`, 'warning');
            
            setTimeout(() => {
                this.loadInitialData();
            }, 2000 * this.retryCount); // Backoff exponencial
        } else {
            this.showError('Não foi possível conectar ao servidor. Verifique sua conexão.');
            this.enableOfflineMode();
        }
    }
    
    showError(message) {
        document.getElementById('errorMessage').textContent = message;
        document.getElementById('errorContainer').style.display = 'flex';
        document.getElementById('loadingOverlay').style.display = 'none';
    }
    
    async retryLoad() {
        this.retryCount = 0;
        this.offlineMode = false;
        await this.loadInitialData();
    }
    
    enableOfflineMode() {
        this.offlineMode = true;
        this.showNotification('Modo offline ativado. Usando dados em cache.', 'warning');
        this.loadFromCache();
    }
    
    // Métodos de cache
    async storeInIndexedDB(storeName, data) {
        if (!this.db) return;
        
        try {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            
            // Limpar store existente
            store.clear();
            
            // Adicionar novos dados
            if (Array.isArray(data)) {
                data.forEach(item => store.add(item));
            } else {
                store.add(data);
            }
        } catch (error) {
            console.error('Erro ao armazenar em IndexedDB:', error);
        }
    }
    
    async loadFromIndexedDB(storeName) {
        if (!this.db) return [];
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    loadFromCache() {
        // Carregar do cache local
        const cachedBeverages = this.cache.get('beverages') || [];
        if (cachedBeverages.length > 0) {
            this.beverages = cachedBeverages;
            this.renderBeverages();
        } else {
            this.loadFromIndexedDB('beverages').then(cachedData => {
                if (cachedData.length > 0) {
                    this.beverages = cachedData;
                    this.renderBeverages();
                } else {
                    this.showError('Sem dados disponíveis offline');
                }
            });
        }
    }
    
    // Métodos de atualização em tempo real
    setupRealTimeUpdates() {
        // Implementar WebSocket ou polling
        this.startPolling();
    }
    
    startPolling() {
        // Poll a cada 30 segundos
        setInterval(async () => {
            if (!this.isLoading && !this.offlineMode) {
                try {
                    await this.loadBeverages();
                    await this.loadCurrentCart();
                } catch (error) {
                    console.log('Erro no polling:', error);
                }
            }
        }, 30000);
    }
    
    // Métodos utilitários
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    async fetchWithTimeout(url, options = {}) {
        const timeout = options.timeout || 5000;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Tempo de conexão esgotado');
            }
            throw error;
        }
    }
    
    setupAccessibility() {
        // Adicionar ARIA labels
        document.getElementById('searchInput').setAttribute('aria-label', 'Buscar bebidas');
        document.getElementById('categoryFilter').setAttribute('aria-label', 'Filtrar por categoria');
        document.getElementById('priceFilter').setAttribute('aria-label', 'Filtrar por preço');
        document.getElementById('sortFilter').setAttribute('aria-label', 'Ordenar bebidas');
        
        // Adicionar navegação por teclado
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                // Garantir que elementos focáveis sejam alcançáveis
                const focusableElements = document.querySelectorAll(
                    'button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                
                if (e.shiftKey) {
                    // Shift+Tab - navegação reversa
                    if (document.activeElement === focusableElements[0]) {
                        e.preventDefault();
                        focusableElements[focusableElements.length - 1].focus();
                    }
                } else {
                    // Tab - navegação normal
                    if (document.activeElement === focusableElements[focusableElements.length - 1]) {
                        e.preventDefault();
                        focusableElements[0].focus();
                    }
                }
            }
        });
    }
}

// Inicializar sistema quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.beverageSystem = new BeverageSystem();
});

// Exportar para testes
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BeverageSystem;
}