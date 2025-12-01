console.log('=== cardapio.js carregado com sucesso ===');

class CardapioApp {
    constructor() {
        this.currentStep = 'tamanho';
        // Pizzas no carrinho
        this.pizzasCart = [];
        // Pizza sendo configurada atualmente
        this.currentPizza = null;
        // Seleções globais
        this.selectedAddons = [];
        this.selectedBebidas = [];
        this.selectedEnderecoId = null;
        this.paymentMethod = 'pix';
        this.taxaEntrega = null;
        // Dados do cardápio
        this.cardapioCache = null;
        this.adicionais = [];
        this.bebidas = [];
        this.tamanhos = [];
    }

    async init() {
        try {
            console.log('Inicializando cardápio...');
            let loaded = false;
            if (window.__CARDAPIO_ENDPOINT__) {
                try {
                    await this.carregarDadosRemotos(window.__CARDAPIO_ENDPOINT__);
                    loaded = true;
                } catch (e) {
                    console.warn('Falha ao carregar dados remotos:', e);
                }
            }
            if (!loaded) {
                console.log('Usando dados locais');
                await this.carregarDadosLocais();
            }
            
            this.setupEventListeners();
            this.restoreState();
            console.log('Cardápio inicializado com sucesso');
            this.renderTamanhos();
        } catch (error) {
            console.error('Erro ao inicializar cardápio:', error);
            this.showError('Erro ao carregar cardápio. Tente novamente.');
        }
    }

    async carregarDadosLocais() {
        try {
            const response = await fetch('/api/get_tamanhos.php');
            const result = await response.json();
            if (result.success && result.data && result.data.length > 0) {
                this.tamanhos = result.data;
            } else {
                this.tamanhos = this.getTamanhosPadrao();
            }
        } catch (error) {
            this.tamanhos = this.getTamanhosPadrao();
        }
        this.adicionais = [
            { id: 1, nome: 'Queijo Extra', descricao: 'Mozzarella adicional', preco: 8.90 },
            { id: 2, nome: 'Bacon', descricao: 'Bacon crocante', preco: 12.90 },
            { id: 3, nome: 'Champignon', descricao: 'Cogumelos frescos', preco: 9.90 }
        ];
        this.bebidas = [
            { id: 1, nome: 'Coca-Cola 350ml', preco: 5.90 },
            { id: 2, nome: 'Guaraná 350ml', preco: 5.90 },
            { id: 3, nome: 'Água 500ml', preco: 3.50 }
        ];
    }

    async carregarDadosRemotos(endpoint) {
        const resp = await fetch(endpoint, { method: 'GET' });
        if (!resp.ok) throw new Error('Falha HTTP ' + resp.status);
        const json = await resp.json();
        if (!json || json.ok !== true) throw new Error('Resposta inválida');
        this.cardapioCache = { data: json.data || {}, timestamp: Date.now() };
        this.adicionais = json.adicionais || [];
        this.bebidas = json.bebidas || [];
        this.tamanhos = json.tamanhos || this.getTamanhosPadrao();
        console.log('Cardápio carregado remotamente');
    }

    getTamanhosPadrao() {
        return [
            { id: 1, nome: 'Pequena', fatias: '6 fatias', icone: '🍕', ativo: true, ordem: 1 },
            { id: 2, nome: 'Média', fatias: '8 fatias', icone: '🍕🍕', ativo: true, ordem: 2 },
            { id: 3, nome: 'Grande', fatias: '12 fatias', icone: '🍕🍕🍕', ativo: true, ordem: 3 }
        ];
    }

    setupEventListeners() {
        // Navegação stepper
        document.querySelectorAll('.stepper-step').forEach(el => {
            el.addEventListener('click', () => {
                const step = el.dataset.step;
                if (this.canNavigateTo(step)) this.showStep(step);
            });
        });

        // Botões de navegação
        document.getElementById('btn-next-modo')?.addEventListener('click', () => this.showStep('modo'));
        document.getElementById('btn-next-sabores')?.addEventListener('click', () => this.showStep('sabores'));
        document.getElementById('btn-add-to-cart')?.addEventListener('click', () => this.addPizzaToCart());
        document.getElementById('btn-add-more-pizza')?.addEventListener('click', () => this.resetForNewPizza());
        document.getElementById('btn-skip-more-pizza')?.addEventListener('click', () => this.showStep('adicionais'));
        document.getElementById('btn-next-bebidas')?.addEventListener('click', () => this.showStep('bebidas'));
        document.getElementById('btn-next-endereco')?.addEventListener('click', () => this.showStep('endereco'));
        document.getElementById('btn-skip-endereco')?.addEventListener('click', () => this.confirmarEndereco());
        document.getElementById('btn-salvar-endereco')?.addEventListener('click', () => this.salvarEndereco());
        document.getElementById('btn-finalizar')?.addEventListener('click', () => this.finalizarPedido());

        // Botões de voltar
        document.querySelectorAll('.btn-back').forEach(btn => {
            btn.addEventListener('click', () => this.goBack());
        });

        // Tabs de categoria
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.renderPizzas(e.target.dataset.category);
            });
        });

        // Tabs de endereço
        document.getElementById('tab-addr-list')?.addEventListener('click', () => {
            document.getElementById('tab-addr-list').classList.add('active');
            document.getElementById('tab-addr-new').classList.remove('active');
            document.querySelector('.enderecos-list')?.classList.remove('hidden');
            document.querySelector('.endereco-form')?.classList.add('hidden');
        });
        document.getElementById('tab-addr-new')?.addEventListener('click', () => {
            document.getElementById('tab-addr-new').classList.add('active');
            document.getElementById('tab-addr-list').classList.remove('active');
            document.querySelector('.enderecos-list')?.classList.add('hidden');
            document.querySelector('.endereco-form')?.classList.remove('hidden');
        });

        // Cliques em cards
        document.addEventListener('click', (e) => {
            if (e.target.closest('.size-option')) {
                const sizeId = e.target.closest('.size-option').dataset.sizeId;
                this.selectSize(sizeId);
            }
            if (e.target.closest('.flavor-mode-option')) {
                const mode = e.target.closest('.flavor-mode-option').dataset.mode;
                this.selectFlavorMode(mode);
            }
            if (e.target.closest('.pizza-card')) {
                const pizzaId = e.target.closest('.pizza-card').dataset.pizzaId;
                const category = e.target.closest('.pizza-card').dataset.categoria;
                this.togglePizzaFlavor(pizzaId, category);
            }
            if (e.target.closest('.addon-card')) {
                const addonId = e.target.closest('.addon-card').dataset.addonId;
                this.toggleAddon(addonId);
            }
            if (e.target.closest('.bebida-card')) {
                const bebidaId = e.target.closest('.bebida-card').dataset.bebidaId;
                this.toggleBebida(bebidaId);
            }
            if (e.target.closest('.btn-edit-pizza')) {
                const idx = e.target.closest('.btn-edit-pizza').dataset.idx;
                this.editPizza(parseInt(idx));
            }
            if (e.target.closest('.btn-remove-pizza')) {
                const idx = e.target.closest('.btn-remove-pizza').dataset.idx;
                this.removePizzaFromCart(parseInt(idx));
            }
            if (e.target.closest('.qty-btn-inc')) {
                const idx = e.target.closest('.qty-btn-inc').dataset.idx;
                this.increasePizzaQty(parseInt(idx));
            }
            if (e.target.closest('.qty-btn-dec')) {
                const idx = e.target.closest('.qty-btn-dec').dataset.idx;
                this.decreasePizzaQty(parseInt(idx));
            }
        });
    }

    canNavigateTo(step) {
        const steps = ['tamanho','modo','sabores','carrinho','adicionais','bebidas','endereco','finalizacao'];
        const currentIndex = steps.indexOf(this.currentStep);
        const targetIndex = steps.indexOf(step);
        return targetIndex <= currentIndex + 1;
    }

    showStep(step) {
        this.currentStep = step;
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.stepper-step').forEach(s => s.classList.remove('active'));
        document.getElementById(`step-${step}`)?.classList.add('active');
        document.querySelector(`.stepper-step[data-step="${step}"]`)?.classList.add('active');
        this.renderStepContent(step);
        this.saveState();
    }

    goBack() {
        const steps = ['tamanho','modo','sabores','quantidade','carrinho','adicionais','bebidas','endereco','finalizacao'];
        const currentIndex = steps.indexOf(this.currentStep);
        if (currentIndex > 0) {
            this.showStep(steps[currentIndex - 1]);
        }
    }

    renderStepContent(step) {
        switch (step) {
            case 'tamanho': this.renderTamanhos(); break;
            case 'modo': this.renderFlavorMode(); break;
            case 'sabores': this.renderPizzas(); break;
            case 'carrinho': this.renderCarrinho(); break;
            case 'adicionais': this.renderAdicionais(); break;
            case 'bebidas': this.renderBebidas(); break;
            case 'endereco': this.renderEndereco(); break;
            case 'finalizacao': this.renderFinalizacao(); break;
        }
    }

    renderTamanhos() {
        const container = document.querySelector('.size-options');
        if (!container) return;
        container.innerHTML = '';
        
        this.tamanhos.filter(t => t.ativo).sort((a, b) => a.ordem - b.ordem).forEach(t => {
            const div = document.createElement('div');
            div.className = 'size-option' + (this.currentPizza?.tamanho?.id === t.id ? ' selected' : '');
            div.dataset.sizeId = t.id;
            div.innerHTML = `
                <div class="size-icon">${t.icone || '🍕'}</div>
                <div class="size-label">${t.nome}</div>
                <div class="size-price">${t.fatias}</div>
            `;
            container.appendChild(div);
        });
        
        document.getElementById('btn-next-modo').disabled = !this.currentPizza?.tamanho;
    }

    renderFlavorMode() {
        const container = document.querySelector('.flavor-mode-options');
        if (!container) return;
        container.innerHTML = '';

        const maxSabores = this.getMaxSabores();
        const modes = [
            { value: 1, label: '1 Sabor', desc: 'Clássico' },
            { value: 2, label: 'Meio a Meio', desc: '2 sabores' },
            ...(maxSabores >= 3 ? [{ value: 3, label: '3 Sabores', desc: 'Especial' }] : [])
        ];

        modes.forEach(mode => {
            const div = document.createElement('div');
            div.className = 'flavor-mode-option' + (this.currentPizza?.flavorMode === mode.value ? ' selected' : '');
            div.dataset.mode = mode.value;
            div.innerHTML = `
                <div class="size-label">${mode.value} Sabor${mode.value > 1 ? 'es' : ''}</div>
                <div class="size-price" style="font-size: 0.9rem;">${mode.desc}</div>
            `;
            container.appendChild(div);
        });
    }

    renderPizzas() {
        const container = document.getElementById('pizza-list');
        if (!container) return;
        if (!this.cardapioCache?.data) {
            container.innerHTML = '<div class="empty-state">Nenhum sabor disponível.</div>';
            return;
        }

        const category = document.querySelector('.tab-btn.active')?.dataset.category || 'tradicionais';
        const pizzas = this.cardapioCache.data[category] || [];
        
        if (!pizzas.length) {
            container.innerHTML = '<div class="empty-state">Nenhum sabor nesta categoria.</div>';
            return;
        }

        const tamanhoId = this.currentPizza?.tamanho?.id;
        let precoKey = 'pequena';
        if (tamanhoId === 2) precoKey = 'media';
        else if (tamanhoId === 3) precoKey = 'grande';

        container.innerHTML = pizzas.map(p => {
            const preco = p.precos && p.precos[precoKey] ? p.precos[precoKey] : 0;
            return `
            <div class="pizza-card" data-pizza-id="${p.id}" data-categoria="${category}">
                <div class="pizza-name">${p.nome}</div>
                <div class="pizza-description">${p.descricao}</div>
                <div class="pizza-price-display">R$ ${preco.toFixed(2).replace('.', ',')}</div>
            </div>
        `;
        }).join('');

        // Marca as pizzas já selecionadas
        if (this.currentPizza?.sabores) {
            this.currentPizza.sabores.forEach(s => {
                document.querySelector(`[data-pizza-id="${s.id}"]`)?.classList.add('selected');
            });
        }

        const maxSabores = this.currentPizza?.flavorMode || 1;
        const selecionados = this.currentPizza?.sabores?.length || 0;
        document.getElementById('sabores-hint').textContent = `Sabores selecionados: ${selecionados} / ${maxSabores}`;
        
        document.getElementById('btn-add-to-cart').disabled = selecionados === 0;
    }

    renderCarrinho() {
        const container = document.querySelector('.cart-items');
        if (!container) return;
        
        if (this.pizzasCart.length === 0) {
            container.innerHTML = '<p style="color: #6b7280;">Nenhuma pizza adicionada ainda.</p>';
            return;
        }

        container.innerHTML = this.pizzasCart.map((pizza, idx) => {
            const flavorNames = pizza.sabores.map(s => s.nome).join(' + ');
            const precoUnitario = this.getPizzaPrice(pizza);
            return `
                <div class="cart-item-card">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${pizza.tamanho.nome}</div>
                        <div class="cart-item-flavors">${flavorNames}</div>
                    </div>
                    <div class="cart-item-price">R$ ${(precoUnitario * pizza.quantidade).toFixed(2).replace('.', ',')}</div>
                    <div class="cart-item-actions">
                        <div class="qty-controls">
                            <button class="btn btn-secondary qty-btn qty-btn-dec" data-idx="${idx}">-</button>
                            <span style="min-width: 25px; text-align: center;">${pizza.quantidade}</span>
                            <button class="btn btn-secondary qty-btn qty-btn-inc" data-idx="${idx}">+</button>
                        </div>
                        <button class="btn btn-secondary btn-edit-pizza" data-idx="${idx}">Editar</button>
                        <button class="btn btn-secondary btn-remove-pizza" data-idx="${idx}">Remover</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    renderAdicionais() {
        const container = document.querySelector('.addons-grid');
        if (!container) return;
        if (!this.adicionais.length) {
            container.innerHTML = '<div class="empty-state">Nenhum adicional disponível.</div>';
            return;
        }

        container.innerHTML = this.adicionais.map(a => `
            <div class="addon-card ${this.selectedAddons.some(x => x.id === a.id) ? 'selected' : ''}" data-addon-id="${a.id}">
                <div class="addon-name">${a.nome}</div>
                <div class="addon-description">${a.descricao}</div>
                <div class="addon-price">R$ ${a.preco.toFixed(2).replace('.', ',')}</div>
            </div>
        `).join('');
    }

    renderBebidas() {
        const container = document.querySelector('.bebidas-grid');
        if (!container) return;
        if (!this.bebidas.length) {
            container.innerHTML = '<div class="empty-state">Nenhuma bebida disponível.</div>';
            return;
        }

        container.innerHTML = this.bebidas.map(b => `
            <div class="bebida-card ${this.selectedBebidas.some(x => x.id === b.id) ? 'selected' : ''}" data-bebida-id="${b.id}">
                <div class="bebida-name">${b.nome}</div>
                <div class="bebida-price">R$ ${b.preco.toFixed(2).replace('.', ',')}</div>
            </div>
        `).join('');
    }

    renderEndereco() {
        const list = document.querySelector('.enderecos-list');
        const loader = document.querySelector('.endereco-loader');
        if (!list || !loader) return;
        
        // Mostra loader, esconde lista
        loader.classList.remove('hidden');
        list.innerHTML = '';
        this.clienteTelefone = null;
    }

    async buscarEnderecosPorTelefone(telefone) {
        if (!telefone || telefone.length < 10) return;
        
        const list = document.querySelector('.enderecos-list');
        if (!list) return;
        list.innerHTML = 'Carregando...';
        
        try {
            const res = await fetch(`../api/enderecos.php?action=list&telefone=${encodeURIComponent(telefone)}`);
            const data = await res.json();
            
            if (data.success && data.data?.length) {
                this.clienteTelefone = telefone;
                list.innerHTML = data.data.map(e => `
                    <label class="endereco-item">
                        <input type="radio" name="endereco" value="${e.id}" data-endereco-id="${e.id}" ${this.selectedEnderecoId === e.id ? 'checked' : ''}>
                        <span>${e.apelido} • ${e.logradouro}, ${e.numero} - ${e.bairro}</span>
                    </label>
                `).join('');
                
                list.querySelectorAll('input').forEach(r => {
                    r.addEventListener('change', (e) => {
                        const enderecoId = parseInt(e.target.getAttribute('data-endereco-id'));
                        this.selectedEnderecoId = enderecoId;
                        this.saveState();
                    });
                });
                
                if (this.selectedEnderecoId) {
                    const radio = list.querySelector(`input[value="${this.selectedEnderecoId}"]`);
                    if (radio) radio.checked = true;
                }
            } else {
                list.innerHTML = 'Nenhum endereço cadastrado. Preencha o formulário abaixo.';
            }
        } catch (e) {
            list.innerHTML = 'Erro ao carregar endereços.';
        }
    }

    renderFinalizacao() {
        const container = document.querySelector('.finalizacao-content');
        if (!container) return;

        const pizzasTotal = this.pizzasCart.reduce((t, p) => t + (this.getPizzaPrice(p) * p.quantidade), 0);
        const adicionaisTotal = this.selectedAddons.reduce((t, a) => t + a.preco, 0);
        const bebidasTotal = this.selectedBebidas.reduce((t, b) => t + (b.preco * (b.quantidade || 1)), 0);
        const total = pizzasTotal + adicionaisTotal + bebidasTotal + (this.taxaEntrega || 0);

        let html = '<div class="review-section"><h3>Itens</h3><div class="review-card">';
        
        html += this.pizzasCart.map((p, idx) => `
            <div class="item-row">
                <div class="item-name">${p.quantidade}x ${p.tamanho.nome} - ${p.sabores.map(s => s.nome).join(' + ')}</div>
                <div class="item-price">R$ ${(this.getPizzaPrice(p) * p.quantidade).toFixed(2).replace('.', ',')}</div>
            </div>
        `).join('');

        html += this.selectedAddons.map(a => `
            <div class="item-row">
                <div class="item-name">${a.nome}</div>
                <div class="item-price">R$ ${a.preco.toFixed(2).replace('.', ',')}</div>
            </div>
        `).join('');

        html += this.selectedBebidas.map(b => `
            <div class="item-row">
                <div class="item-name">${b.quantidade || 1}x ${b.nome}</div>
                <div class="item-price">R$ ${(b.preco * (b.quantidade || 1)).toFixed(2).replace('.', ',')}</div>
            </div>
        `).join('');

        html += `
            </div>
            <div class="review-total-row">
                <div>Total</div>
                <div class="item-price">R$ ${total.toFixed(2).replace('.', ',')}</div>
            </div>
        </div>
        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button id="btn-editar-pedido" class="btn btn-secondary" style="flex: 1;">Editar Pedido</button>
            <button id="btn-confirmar-pedido" class="btn btn-success" style="flex: 1;">Confirmar & Enviar</button>
        </div>`;

        container.innerHTML = html;
        document.getElementById('btn-editar-pedido')?.addEventListener('click', () => this.editarPedido());
        document.getElementById('btn-confirmar-pedido')?.addEventListener('click', () => this.confirmarPedido());
    }

    // MÉTODOS DE SELEÇÃO
    selectSize(sizeId) {
        const tamanho = this.tamanhos.find(t => t.id === parseInt(sizeId));
        if (!tamanho) return;
        
        if (!this.currentPizza) this.currentPizza = {};
        this.currentPizza.tamanho = { id: tamanho.id, nome: tamanho.nome, fatias: tamanho.fatias };
        
        document.querySelectorAll('.size-option').forEach(el => {
            el.classList.toggle('selected', el.dataset.sizeId === sizeId.toString());
        });
        
        document.getElementById('btn-next-modo').disabled = false;
        this.saveState();
    }

    selectFlavorMode(mode) {
        if (!this.currentPizza) this.currentPizza = {};
        this.currentPizza.flavorMode = parseInt(mode);
        this.currentPizza.sabores = [];
        
        document.querySelectorAll('.flavor-mode-option').forEach(el => {
            el.classList.toggle('selected', el.dataset.mode === mode.toString());
        });
        
        document.getElementById('btn-next-sabores').disabled = false;
        this.renderPizzas();
        this.saveState();
    }

    togglePizzaFlavor(pizzaId, category) {
        if (!this.currentPizza) return;
        const pizzas = this.cardapioCache?.data[category] || [];
        const pizza = pizzas.find(p => p.id == pizzaId);
        if (!pizza) return;

        const maxFlavors = this.currentPizza.flavorMode || 1;
        const idx = this.currentPizza.sabores.findIndex(s => s.id == pizzaId);

        if (idx >= 0) {
            this.currentPizza.sabores.splice(idx, 1);
        } else {
            if (this.currentPizza.sabores.length >= maxFlavors) {
                this.showError(`Máximo ${maxFlavors} sabor${maxFlavors > 1 ? 'es' : ''}`);
                return;
            }
            this.currentPizza.sabores.push({ id: pizza.id, nome: pizza.nome, precos: pizza.precos });
        }

        this.renderPizzas();
        this.saveState();
    }

    // OPERAÇÕES DO CARRINHO
    addPizzaToCart() {
        if (!this.currentPizza || !this.currentPizza.sabores.length) {
            this.showError('Selecione sabores primeiro');
            return;
        }

        this.currentPizza.quantidade = 1;
        this.pizzasCart.push({ ...this.currentPizza });
        this.showSuccess('Pizza adicionada ao carrinho!');
        this.saveState();
        this.resetForNewPizza();
        this.showStep('carrinho');
    }

    increasePizzaQty(idx) {
        if (idx >= 0 && idx < this.pizzasCart.length) {
            this.pizzasCart[idx].quantidade = Math.min(10, (this.pizzasCart[idx].quantidade || 1) + 1);
            this.renderCarrinho();
            this.saveState();
        }
    }

    decreasePizzaQty(idx) {
        if (idx >= 0 && idx < this.pizzasCart.length) {
            this.pizzasCart[idx].quantidade = Math.max(1, (this.pizzasCart[idx].quantidade || 1) - 1);
            this.renderCarrinho();
            this.saveState();
        }
    }

    resetForNewPizza() {
        this.currentPizza = null;
        this.showStep('tamanho');
    }

    editPizza(idx) {
        if (idx < 0 || idx >= this.pizzasCart.length) return;
        this.currentPizza = { ...this.pizzasCart[idx] };
        this.pizzasCart.splice(idx, 1);
        this.showStep('tamanho');
    }

    removePizzaFromCart(idx) {
        if (idx < 0 || idx >= this.pizzasCart.length) return;
        this.pizzasCart.splice(idx, 1);
        this.showSuccess('Pizza removida');
        if (this.pizzasCart.length === 0) {
            this.resetForNewPizza();
        } else {
            this.renderCarrinho();
        }
        this.saveState();
    }

    // HELPERS
    getMaxSabores() {
        const tamanho = this.currentPizza?.tamanho;
        if (!tamanho) return 1;
        if (tamanho.id === 3) return 3;
        if (tamanho.id === 2) return 2;
        return 1;
    }

    getPizzaPrice(pizza) {
        if (!pizza.sabores.length) return 0;
        const tamanhoId = pizza.tamanho.id;
        let priceKey = 'pequena';
        if (tamanhoId === 2) priceKey = 'media';
        else if (tamanhoId === 3) priceKey = 'grande';
        
        const maxPrice = Math.max(...pizza.sabores.map(s => s.precos[priceKey] || 0));
        return maxPrice;
    }

    // ADICIONAIS E BEBIDAS
    toggleAddon(id) {
        const addon = this.adicionais.find(a => a.id == id);
        if (!addon) return;
        const idx = this.selectedAddons.findIndex(a => a.id == id);
        if (idx >= 0) {
            this.selectedAddons.splice(idx, 1);
        } else {
            this.selectedAddons.push(addon);
        }
        this.renderAdicionais();
        this.saveState();
    }

    toggleBebida(id) {
        const bebida = this.bebidas.find(b => b.id == id);
        if (!bebida) return;
        const idx = this.selectedBebidas.findIndex(b => b.id == id);
        if (idx >= 0) {
            this.selectedBebidas.splice(idx, 1);
        } else {
            this.selectedBebidas.push({ ...bebida, quantidade: 1 });
        }
        this.renderBebidas();
        this.saveState();
    }

    // ENDEREÇO
    async salvarEndereco() {
        const cep = document.getElementById('cep')?.value?.trim();
        const logradouro = document.getElementById('logradouro')?.value?.trim();
        const numero = document.getElementById('numero')?.value?.trim();
        const bairro = document.getElementById('bairro')?.value?.trim();

        if (!cep || !logradouro || !numero || !bairro) {
            this.showError('Preencha todos os campos');
            return;
        }
        const cepRegex = /^\d{5}-?\d{3}$/;
        if (!cepRegex.test(cep)) {
            this.showError('CEP inválido (formato: 12345-678)');
            return;
        }

        try {
            const res = await fetch('../api/enderecos.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ apelido: `${logradouro} ${numero}`, cep, logradouro, numero, bairro, cidade: 'Guarapari', uf: 'ES', padrao: 0 })
            });
            const data = await res.json();
            if (data.success) {
                this.selectedEnderecoId = data.data?.id;
                this.showSuccess('Endereço salvo');
                this.renderEndereco();
                this.saveState();
            }
        } catch (e) {
            this.showError('Erro ao salvar');
        }
    }

    formatarCEP(input) {
        const v = input.value.replace(/\D/g, '');
        input.value = v.replace(/(\d{5})(\d{1,3})/, '$1-$2');
        console.log('📝 CEP formatado:', input.value, '| Dígitos:', v.length);
        if (v.length === 8) {
            console.log('✅ CEP completo, buscando dados...');
            this.buscarCEP(v);
        }
    }

    async buscarCEP(cep) {
        cep = String(cep).replace(/\D/g, '');
        if (cep.length !== 8) return;
        try {
            console.log('🔍 Buscando CEP na ViaCEP:', cep);
            const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await res.json();
            console.log('📦 Resposta ViaCEP:', data);
            
            if (!data.erro) {
                const logradouroEl = document.getElementById('logradouro');
                const bairroEl = document.getElementById('bairro');
                
                if (data.logradouro && logradouroEl) {
                    logradouroEl.value = data.logradouro;
                    console.log('✅ Rua carregada:', data.logradouro);
                }
                if (data.bairro && bairroEl) {
                    bairroEl.value = data.bairro;
                    console.log('✅ Bairro carregado:', data.bairro);
                    this.buscarTaxaBairro(data.bairro);
                }
            } else {
                console.warn('❌ CEP não encontrado na ViaCEP');
            }
        } catch (e) {
            console.error('❌ Erro ao buscar CEP:', e);
        }
    }

    async buscarTaxaBairro(bairro) {
        if (!bairro) return;
        try {
            const res = await fetch(`../api/enderecos.php?action=taxa&bairro=${encodeURIComponent(bairro)}`);
            const j = await res.json();
            this.taxaEntrega = typeof j.taxa === 'number' ? j.taxa : 1.00;
            this.saveState();
        } catch (e) {}
    }

    // FINALIZAÇÃO
    confirmarEndereco() {
        const selectedRadio = document.querySelector('input[name="endereco"]:checked');
        if (!selectedRadio) {
            this.showError('Selecione um endereço');
            return;
        }
        const enderecoId = parseInt(selectedRadio.getAttribute('data-endereco-id'));
        this.selectedEnderecoId = enderecoId;
        console.log('Endereço confirmado:', enderecoId);
        this.saveState();
        this.showStep('finalizacao');
    }

    editarPedido() {
        this.showStep('tamanho');
    }

    async confirmarPedido() {
        console.log('Confirmando pedido com endereço ID:', this.selectedEnderecoId);
        
        if (!this.selectedEnderecoId) {
            console.error('Nenhum endereço selecionado');
            this.showError('Selecione um endereço');
            this.showStep('endereco');
            return;
        }
        if (this.pizzasCart.length === 0) {
            this.showError('Adicione pizzas ao pedido');
            return;
        }

        // Prepara dados do pedido
        const pizzasTotal = this.pizzasCart.reduce((t, p) => t + (this.getPizzaPrice(p) * p.quantidade), 0);
        const adicionaisTotal = this.selectedAddons.reduce((t, a) => t + a.preco, 0);
        const bebidasTotal = this.selectedBebidas.reduce((t, b) => t + (b.preco * (b.quantidade || 1)), 0);
        const total = pizzasTotal + adicionaisTotal + bebidasTotal + (this.taxaEntrega || 0);

        const payload = {
            endereco_id: this.selectedEnderecoId,
            forma_pagamento: this.paymentMethod,
            taxa_entrega: this.taxaEntrega || 0,
            subtotal: pizzasTotal + adicionaisTotal + bebidasTotal,
            total: total,
            pizzas: this.pizzasCart,
            adicionais: this.selectedAddons,
            bebidas: this.selectedBebidas
        };

        console.log('Enviando pedido:', payload);

        try {
            const res = await fetch('../api/criar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            console.log('Resposta do servidor:', data);
            
            if (data.success) {
                this.showSuccess('Pedido enviado com sucesso! Número: ' + data.numero_pedido);
                setTimeout(() => {
                    this.pizzasCart = [];
                    this.selectedAddons = [];
                    this.selectedBebidas = [];
                    this.currentPizza = null;
                    this.selectedEnderecoId = null;
                    sessionStorage.removeItem('cardapioState');
                    location.href = '/';
                }, 3000);
            } else {
                this.showError(data.message || 'Erro ao enviar pedido');
            }
        } catch (e) {
            console.error('Erro ao enviar:', e);
            this.showError('Erro ao enviar pedido: ' + e.message);
        }
    }

    async finalizarPedido() {
        this.confirmarPedido();
    }

    // STATE
    saveState() {
        const state = {
            currentStep: this.currentStep,
            pizzasCart: this.pizzasCart,
            currentPizza: this.currentPizza,
            selectedAddons: this.selectedAddons,
            selectedBebidas: this.selectedBebidas,
            selectedEnderecoId: this.selectedEnderecoId,
            paymentMethod: this.paymentMethod,
            taxaEntrega: this.taxaEntrega
        };
        sessionStorage.setItem('cardapioState', JSON.stringify(state));
    }

    restoreState() {
        const raw = sessionStorage.getItem('cardapioState');
        if (!raw) return;
        try {
            const s = JSON.parse(raw);
            this.currentStep = s.currentStep || 'tamanho';
            this.pizzasCart = s.pizzasCart || [];
            this.currentPizza = s.currentPizza || null;
            this.selectedAddons = s.selectedAddons || [];
            this.selectedBebidas = s.selectedBebidas || [];
            this.selectedEnderecoId = s.selectedEnderecoId || null;
            this.paymentMethod = s.paymentMethod || 'pix';
            this.taxaEntrega = s.taxaEntrega || null;
        } catch(e) {}
    }

    showError(msg) {
        const el = document.getElementById('message-container');
        if (!el) return;
        el.innerHTML = `<div class="error-message">${msg}</div>`;
        setTimeout(() => el.innerHTML = '', 5000);
    }

    showSuccess(msg) {
        const el = document.getElementById('message-container');
        if (!el) return;
        el.innerHTML = `<div class="success-message">${msg}</div>`;
        setTimeout(() => el.innerHTML = '', 3000);
    }
}

// INICIALIZAÇÃO
document.addEventListener('DOMContentLoaded', () => {
    const app = new CardapioApp();
    app.init();
    window.cardapioApp = app;
});
