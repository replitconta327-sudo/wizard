console.log('=== cardapio.js carregado com sucesso ===');

// Sistema de Cardápio Digital - Pizzaria São Paulo
// Interface limpa e profissional alinhada ao design do login/cadastro

class CardapioApp {
    constructor() {
        this.currentStep = 'tamanho';
        this.selectedSize = null;
        this.selectedPizzas = [];
        this.selectedAddons = [];
        this.selectedBebidas = [];
        this.selectedEnderecoId = null;
        this.paymentMethod = 'pix';
        this.cacheDuration = 5 * 60 * 1000;
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
                this.carregarDadosLocais();
            }
            
            this.setupEventListeners();
            
            // Forçar renderização dos tamanhos
            console.log('Inicializando passo: tamanho');
            this.currentStep = 'tamanho';
            this.renderTamanhos();
            
            // Atualizar UI
            this.updateOrderSummary();
            console.log('Cardápio inicializado com sucesso');
        } catch (error) {
            console.error('Erro ao inicializar cardápio:', error);
            this.showError('Erro ao carregar cardápio. Tente novamente.');
        }
    }

    async carregarDadosLocais() {
        // Tenta carregar do banco de dados primeiro
        try {
            const response = await fetch('/api/get_tamanhos.php');
            const result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                this.tamanhos = result.data;
                console.log('Tamanhos carregados do banco de dados:', this.tamanhos);
            } else {
                console.warn('Nenhum tamanho encontrado no banco de dados, usando valores padrão');
                this.tamanhos = this.getTamanhosPadrao();
            }
        } catch (error) {
            console.error('Erro ao carregar tamanhos:', error);
            this.tamanhos = this.getTamanhosPadrao();
        }
        
        // Dados de exemplo para o cardápio (será substituído pelo carregamento real)
        this.cardapioCache = {
            data: {
                tradicionais: [
                    { id: 1, nome: 'Margherita', descricao: 'Molho de tomate, mussarela, manjericão fresco', preco: 45.90, imagem: 'pizza-margherita.jpg', destaque: true },
                    // ... outros itens do cardápio
                    { id: 8, nome: 'Romeu e Julieta', descricao: 'Goiabada, queijo minas, canela', precos: { pequena: 26.90, media: 34.90, grande: 46.90 } }
                ]
            }
        };
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
    }

    setupEventListeners() {
        // Navegação por stepper
        document.querySelectorAll('.stepper-step').forEach(stepEl => {
            stepEl.addEventListener('click', () => {
                const step = stepEl.dataset.step;
                if (this.canNavigateTo(step)) this.showStep(step);
            });
        });

        // Botões de ação
        document.getElementById('btn-next-sabores')?.addEventListener('click', () => this.showStep('sabores'));
        document.getElementById('btn-next-addons')?.addEventListener('click', () => this.showStep('adicionais'));
        document.getElementById('btn-next-endereco')?.addEventListener('click', () => this.showStep('endereco'));
        document.getElementById('btn-next-bebidas')?.addEventListener('click', () => this.showStep('bebidas'));
        document.getElementById('btn-next-finalizacao')?.addEventListener('click', () => this.showStep('finalizacao'));
        document.getElementById('btn-finalizar')?.addEventListener('click', () => this.finalizarPedido());

        document.getElementById('btn-salvar-endereco')?.addEventListener('click', () => this.salvarEndereco());

        document.addEventListener('input', (e) => {
            if (e.target.id === 'cep') this.formatarCEP(e.target);
            if (e.target.id === 'uf') e.target.value = e.target.value.toUpperCase();
            if (e.target.id === 'bairro') this.debouncedBuscarBairros(e.target.value);
        });
        document.getElementById('bairro')?.addEventListener('blur', () => this.onBairroBlur());

        document.getElementById('tab-addr-list')?.addEventListener('click', () => {
            document.getElementById('tab-addr-list')?.classList.add('active');
            document.getElementById('tab-addr-new')?.classList.remove('active');
            document.querySelector('.enderecos-list')?.classList.remove('hidden');
            document.querySelector('.endereco-form')?.classList.add('hidden');
            this.renderEndereco();
        });
        document.getElementById('tab-addr-new')?.addEventListener('click', () => {
            document.getElementById('tab-addr-new')?.classList.add('active');
            document.getElementById('tab-addr-list')?.classList.remove('active');
            document.querySelector('.endereco-form')?.classList.remove('hidden');
            document.querySelector('.enderecos-list')?.classList.add('hidden');
        });

        // Botões voltar
        document.querySelectorAll('.btn-back').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const currentPanel = e.target.closest('.step-panel');
                const steps = ['tamanho','sabores','adicionais','endereco','bebidas','finalizacao'];
                const currentIndex = steps.indexOf(this.currentStep);
                if (currentIndex > 0) this.showStep(steps[currentIndex - 1]);
            });
        });

        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.filterPizzasByCategory(btn.dataset.category);
            });
        });

        // Cards
        document.addEventListener('click', (e) => {
            if (e.target.closest('.size-option')) this.selectSize(e.target.closest('.size-option').dataset.sizeId);
            if (e.target.closest('.pizza-card')) {
                const card = e.target.closest('.pizza-card');
                this.selectPizza(card.dataset.pizzaId, card.dataset.categoria);
            }
            if (e.target.closest('.addon-card')) {
                const card = e.target.closest('.addon-card');
                this.toggleAddon(card.dataset.addonId);
            }
            if (e.target.closest('.bebida-card')) {
                const card = e.target.closest('.bebida-card');
                this.toggleBebida(card.dataset.bebidaId);
            }
        });
    }

    canNavigateTo(step) {
        const steps = ['tamanho','sabores','adicionais','endereco','bebidas','finalizacao'];
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
    }

    renderStepContent(step) {
        console.log('Renderizando passo:', step);
        switch (step) {
            case 'tamanho': 
                console.log('Chamando renderTamanhos()');
                this.renderTamanhos(); 
                break;
            case 'sabores': this.renderPizzas(); break;
            case 'adicionais': this.renderAdicionais(); break;
            case 'endereco': this.renderEndereco(); break;
            case 'bebidas': this.renderBebidas(); break;
            case 'finalizacao': this.renderFinalizacao(); break;
        }
    }

    // Retorna os tamanhos padrão caso não consiga carregar do banco
    getTamanhosPadrao() {
        return [
            { id: 1, nome: 'Pequena', fatias: '4 fatias', icone: '🍕', preco_pequeno: 29.90, preco_medio: 0, preco_grande: 0, ativo: true, ordem: 1 },
            { id: 2, nome: 'Média', fatias: '8 fatias', icone: '🍕🍕', preco_pequeno: 0, preco_medio: 59.90, preco_grande: 0, ativo: true, ordem: 2 },
            { id: 3, nome: 'Grande', fatias: '12 fatias', icone: '🍕🍕🍕', preco_pequeno: 0, preco_medio: 0, preco_grande: 89.90, ativo: true, ordem: 3 },
            { id: 4, nome: 'Gigante', fatias: '16 fatias', icone: '🍕🍕🍕🍕', preco_pequeno: 0, preco_medio: 0, preco_grande: 109.90, ativo: true, ordem: 4 }
        ];
    }

    renderTamanhos() {
        console.log('=== Iniciando renderTamanhos() ===');
        const container = document.querySelector('.size-options');
        console.log('Elemento .size-options encontrado?', !!container);
        
        if (!container) {
            console.error('Container de tamanhos não encontrado! Verifique se existe um elemento com a classe "size-options" no DOM.');
            return;
        }
        
        // Limpa o container primeiro
        container.innerHTML = '';
        
        // Ordena os tamanhos pela ordem definida
        const tamanhosOrdenados = [...this.tamanhos].sort((a, b) => a.ordem - b.ordem);
        
        // Adiciona cada tamanho ao container
        tamanhosOrdenados.forEach(tamanho => {
            if (!tamanho.ativo) return;
            
            const div = document.createElement('div');
            div.className = 'size-option';
            div.dataset.sizeId = tamanho.id;
            div.dataset.sizeName = tamanho.nome.toLowerCase();
            
            // Formata o preço para exibição
            const preco = tamanho.preco_pequeno || tamanho.preco_medio || tamanho.preco_grande;
            const precoFormatado = preco ? `R$ ${preco.toFixed(2).replace('.', ',')}` : '';
            
            div.innerHTML = `
                <div class="size-icon">${tamanho.icone || '🍕'}</div>
                <div class="size-label">${tamanho.nome}</div>
                <div class="size-slices">${tamanho.fatias}</div>
                ${preco ? `<div class="size-price">${precoFormatado}</div>` : ''}
            `;
            
            div.addEventListener('click', () => this.selectSize(tamanho.id));
            container.appendChild(div);
        });
        
        // Atualiza a seleção se houver um tamanho já selecionado
        if (this.selectedSize) {
            this.selectSize(this.selectedSize);
        } else if (tamanhosOrdenados.length > 0) {
            // Seleciona o primeiro tamanho por padrão
            this.selectSize(tamanhosOrdenados[0].id);
        }
    }

    renderPizzas() {
        const container = document.getElementById('pizza-list');
        if (!container) return;
        if (!this.cardapioCache || !this.cardapioCache.data) {
            container.innerHTML = '<div class="empty-state">Nenhum sabor disponível. Verifique o banco de dados.</div>';
            return;
        }
        const categoriaSelecionada = this.selectedPizzas[0]?.categoria || null;
        const categoriaAtiva = categoriaSelecionada || document.querySelector('.tab-btn.active')?.dataset.category || 'tradicionais';
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelector(`.tab-btn[data-category="${categoriaAtiva}"]`)?.classList.add('active');
        const pizzas = this.cardapioCache.data[categoriaAtiva] || [];
        if (!pizzas.length) {
            container.innerHTML = '<div class="empty-state">Nenhum sabor nesta categoria.</div>';
            return;
        }
        container.innerHTML = pizzas.map(p => `
            <div class="pizza-card" data-pizza-id="${p.id}" data-categoria="${categoriaAtiva}">
                <div class="pizza-name">${p.nome}</div>
                <div class="pizza-description">${p.descricao}</div>
                <div class="pizza-price-display">R$ ${(p.precos[this.selectedSize] || 0).toFixed(2)}</div>
            </div>
        `).join('');
        const maxSabores = this.getMaxSabores();
        const saboresSelecionados = Array.isArray(this.selectedPizzas[0]?.sabores) ? this.selectedPizzas[0].sabores : [];
        const selectedIds = new Set(saboresSelecionados.map(s => String(s.id)));
        container.querySelectorAll('.pizza-card').forEach(card => {
            if (selectedIds.has(card.dataset.pizzaId)) card.classList.add('selected');
        });
        const hint = document.getElementById('sabores-hint');
        if (hint) hint.textContent = `Sabores selecionados: ${saboresSelecionados.length} / ${maxSabores}`;
        if (saboresSelecionados.length > 0) document.getElementById('btn-next-addons')?.removeAttribute('disabled');
    }

    renderAdicionais() {
        const container = document.querySelector('.addons-grid');
        if (!container) return;
        if (!Array.isArray(this.adicionais) || this.adicionais.length === 0) {
            container.innerHTML = '<div class="empty-state">Nenhum adicional disponível.</div>';
            return;
        }
        container.innerHTML = this.adicionais.map(a => `
            <div class="addon-card" data-addon-id="${a.id}">
                <div class="addon-name">${a.nome}</div>
                <div class="addon-description">${a.descricao}</div>
                <div class="addon-price">R$ ${a.preco.toFixed(2)}</div>
            </div>
        `).join('');
        const selectedIds = new Set(this.selectedAddons.map(a => String(a.id)));
        container.querySelectorAll('.addon-card').forEach(card => {
            if (selectedIds.has(card.dataset.addonId)) card.classList.add('selected');
        });
        if (this.selectedAddons.length || this.selectedPizzas.length) {
            document.getElementById('btn-next-endereco')?.removeAttribute('disabled');
        }
    }

    async renderEndereco() {
        const list = document.querySelector('.enderecos-list');
        if (!list) return;
        list.innerHTML = 'Carregando endereços...';
        try {
            const res = await fetch('../api/enderecos.php?action=list');
            const data = await res.json();
            if (data.success && Array.isArray(data.data) && data.data.length) {
                list.innerHTML = data.data.map(e => `
                    <label class="endereco-item">
                        <input type="radio" name="endereco" value="${e.id}" ${this.selectedEnderecoId==e.id?'checked':''}>
                        <span>${e.logradouro}, ${e.numero} - ${e.bairro} - ${e.cidade}/${e.uf} - ${e.cep}</span>
                    </label>
                `).join('');
                list.querySelectorAll('input[name="endereco"]').forEach(r => {
                    r.addEventListener('change', (ev) => {
                        this.selectedEnderecoId = parseInt(ev.target.value, 10);
                        this.saveState();
                    });
                });
            } else {
                list.innerHTML = 'Nenhum endereço cadastrado. Preencha o formulário para adicionar.';
            }
        } catch (err) {
            list.innerHTML = 'Erro ao carregar endereços.';
        }
    }

    renderBebidas() {
        const container = document.querySelector('.bebidas-grid');
        if (!container) return;
        if (!Array.isArray(this.bebidas) || this.bebidas.length === 0) {
            container.innerHTML = '<div class="empty-state">Nenhuma bebida disponível.</div>';
            return;
        }
        container.innerHTML = this.bebidas.map(b => `
            <div class="bebida-card" data-bebida-id="${b.id}">
                <div class="bebida-name">${b.nome}</div>
                <div class="bebida-price">R$ ${b.preco.toFixed(2)}</div>
            </div>
        `).join('');
        const selectedIds = new Set(this.selectedBebidas.map(b => String(b.id)));
        container.querySelectorAll('.bebida-card').forEach(card => {
            if (selectedIds.has(card.dataset.bebidaId)) card.classList.add('selected');
        });
        const btn = document.getElementById('btn-next-finalizacao');
        if (btn) {
            if (this.selectedBebidas.length === 0 && this.selectedPizzas.length === 0 && this.selectedAddons.length === 0) {
                btn.setAttribute('disabled', 'true');
            } else {
                btn.removeAttribute('disabled');
            }
        }
    }

    renderFinalizacao() {
        const container = document.querySelector('.finalizacao-content');
        if (!container) return;
        const total = [
            ...this.selectedPizzas.map(p => p.preco),
            ...this.selectedAddons.map(a => a.preco),
            ...this.selectedBebidas.map(b => b.preco * (b.quantidade || 1))
        ].reduce((a,b)=>a+b,0);
        const itens = [
            ...this.selectedPizzas.map(p => `
                <div class="item-row">
                    <div class="item-name">Pizza (${p.tamanho})${Array.isArray(p.sabores)&&p.sabores.length? ' - ' + p.sabores.map(s=>s.nome).join(' + ') : ''}</div>
                    <div class="item-actions">
                        <button class="btn btn-secondary" data-action="remove-pizza" data-id="pizza">Remover</button>
                    </div>
                    <div class="item-price">R$ ${p.preco.toFixed(2)}</div>
                </div>`),
            ...this.selectedAddons.map(a => `
                <div class="item-row">
                    <div class="item-name">${a.nome}</div>
                    <div class="item-actions">
                        <button class="btn btn-secondary" data-action="remove-addon" data-id="${a.id}">Remover</button>
                    </div>
                    <div class="item-price">R$ ${a.preco.toFixed(2)}</div>
                </div>`),
            ...this.selectedBebidas.map(b => `
                <div class="item-row">
                    <div class="item-name">${b.nome}</div>
                    <div class="item-actions">
                        <div class="qty-group">
                            <button class="qty-btn" data-action="dec-bebida" data-id="${b.id}">-</button>
                            <span>${b.quantidade || 1}</span>
                            <button class="qty-btn" data-action="inc-bebida" data-id="${b.id}">+</button>
                        </div>
                        <button class="btn btn-secondary" data-action="remove-bebida" data-id="${b.id}">Remover</button>
                    </div>
                    <div class="item-price">R$ ${(b.preco * (b.quantidade || 1)).toFixed(2)}</div>
                </div>`)
        ].join('');
        const enderecoInfo = this.selectedEnderecoId ? `Endereço selecionado: #${this.selectedEnderecoId}` : 'Selecione um endereço na etapa anterior';
        container.innerHTML = `
            <div class="review-section">
                <h3>Itens</h3>
                <div class="review-card">${itens || '<div class="item-row"><div class="item-name">Nenhum item</div><div></div><div></div></div>'}</div>
                <div class="review-total-row">
                    <div>Total</div>
                    <div class="item-price">R$ ${total.toFixed(2)}</div>
                </div>
            </div>
            <div class="review-section">
                <h3>Entrega</h3>
                <div class="address-summary review-address">${enderecoInfo}</div>
                <div class="address-actions">
                    <button class="btn btn-secondary" id="btn-alterar-endereco">Alterar endereço</button>
                </div>
                ${this.taxaEntrega ? `<div class="address-summary">Taxa de entrega estimada: R$ ${this.taxaEntrega.toFixed(2)}</div>` : ''}
            </div>
            <div class="review-section">
                <h3>Pagamento</h3>
                <div class="payment-cards">
                    <label class="payment-card ${this.paymentMethod==='pix'?'selected':''}">
                        <input type="radio" name="payment" value="pix" ${this.paymentMethod==='pix'?'checked':''}>
                        <span>PIX</span>
                    </label>
                    <label class="payment-card ${this.paymentMethod==='cartao'?'selected':''}">
                        <input type="radio" name="payment" value="cartao" ${this.paymentMethod==='cartao'?'checked':''}>
                        <span>Cartão (apenas na entrega)</span>
                    </label>
                </div>
            </div>
            <button class="btn btn-success btn-full" id="btn-finalizar">Confirmar Pedido</button>
        `;
        if (this.selectedEnderecoId) {
            try {
                fetch(`../api/enderecos.php?action=get&id=${this.selectedEnderecoId}`)
                    .then(r => r.json())
                    .then(j => {
                        if (j.success && j.data) {
                            const e = j.data;
                            const info = `${e.logradouro}, ${e.numero} ${e.complemento?'- '+e.complemento:''} - ${e.bairro} - Guarapari/ES - ${e.cep}`;
                            const el = container.querySelector('.review-address');
                            if (el) el.textContent = info;
                        }
                    })
                    .catch(() => {});
            } catch {}
        }
        container.querySelectorAll('input[name="payment"]').forEach(r => {
            r.addEventListener('change', (e) => {
                this.paymentMethod = e.target.value;
                this.saveState();
                container.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
                e.target.closest('.payment-card')?.classList.add('selected');
            });
        });
        container.querySelector('#btn-alterar-endereco')?.addEventListener('click', () => {
            this.showStep('endereco');
        });
        container.querySelectorAll('button[data-action]')?.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.dataset.action;
                const id = btn.dataset.id;
                if (!action || !id) return;
                if (action === 'remove-pizza') {
                    this.selectedPizzas = this.selectedPizzas.filter(p => String(p.id) !== String(id));
                } else if (action === 'remove-addon') {
                    this.selectedAddons = this.selectedAddons.filter(a => String(a.id) !== String(id));
                } else if (action === 'remove-bebida') {
                    this.selectedBebidas = this.selectedBebidas.filter(b => String(b.id) !== String(id));
                } else if (action === 'inc-bebida') {
                    const item = this.selectedBebidas.find(b => String(b.id) === String(id));
                    if (item) item.quantidade = (item.quantidade || 1) + 1;
                } else if (action === 'dec-bebida') {
                    const item = this.selectedBebidas.find(b => String(b.id) === String(id));
                    if (item && (item.quantidade || 1) > 1) item.quantidade = (item.quantidade || 1) - 1;
                }
                this.updateOrderSummary();
                this.saveState();
                this.renderFinalizacao();
            });
        });
        document.getElementById('btn-finalizar')?.addEventListener('click', () => this.finalizarPedido());
    }

    selectSize(sizeId) {
        const tamanho = this.tamanhos.find(t => t.id === sizeId || t.id.toString() === sizeId.toString());
        if (!tamanho) {
            console.error('Tamanho não encontrado:', sizeId);
            return;
        }
        
        this.selectedSize = {
            id: tamanho.id,
            nome: tamanho.nome,
            fatias: tamanho.fatias,
            preco: tamanho.preco_pequeno || tamanho.preco_medio || tamanho.preco_grande || 0
        };
        
        // Atualiza a UI
        document.querySelectorAll('.size-option').forEach(el => {
            const optionSizeId = el.dataset.sizeId || '';
            el.classList.toggle('selected', optionSizeId === sizeId.toString());
        });
        
        // Habilita o botão de próximo se estiver no passo de seleção de tamanho
        if (this.currentStep === 'tamanho') {
            const nextButton = document.getElementById('btn-next-sabores');
            if (nextButton) {
                nextButton.disabled = false;
            }
        }
        
        // Atualiza o resumo do pedido
        this.updateOrderSummary();
        this.saveState();
        
        console.log('Tamanho selecionado:', this.selectedSize);
    }

    selectPizza(id, categoria) {
        if (!this.selectedSize) {
            this.showError('Selecione um tamanho antes de escolher o sabor.');
            return;
        }
        const pizza = this.cardapioCache.data[categoria]?.find(p => p.id == id);
        if (!pizza) return;
        const maxSabores = this.getMaxSabores();
        let composite = this.selectedPizzas[0];
        if (!composite || !Array.isArray(composite.sabores)) {
            composite = { nome: 'Pizza', tamanho: this.selectedSize, preco: 0, sabores: [], categoria };
        }
        const idx = composite.sabores.findIndex(s => s.id == pizza.id);
        if (idx >= 0) {
            composite.sabores.splice(idx, 1);
        } else {
            if (composite.sabores.length >= maxSabores) {
                this.showError(`Você pode escolher até ${maxSabores} sabores.`);
                return;
            }
            composite.sabores.push({ id: pizza.id, nome: pizza.nome, precos: pizza.precos, categoria });
        }
        composite.tamanho = this.selectedSize;
        composite.preco = composite.sabores.reduce((max, s) => Math.max(max, (s.precos[this.selectedSize] || 0)), 0);
        this.selectedPizzas = composite.sabores.length ? [composite] : [];
        document.querySelectorAll('.pizza-card').forEach(c => c.classList.remove('selected'));
        composite.sabores.forEach(s => {
            document.querySelector(`[data-pizza-id="${s.id}"]`)?.classList.add('selected');
        });
        const hint = document.getElementById('sabores-hint');
        if (hint) hint.textContent = `Sabores selecionados: ${composite.sabores.length} / ${maxSabores}`;
        if (composite.sabores.length > 0) {
            document.getElementById('btn-next-addons')?.removeAttribute('disabled');
        } else {
            document.getElementById('btn-next-addons')?.setAttribute('disabled', 'true');
        }
        this.updateOrderSummary();
        this.saveState();
    }

    toggleAddon(id) {
        const addon = this.adicionais.find(a => a.id == id);
        if (!addon) return;
        const idx = this.selectedAddons.findIndex(a => a.id == id);
        const card = document.querySelector(`[data-addon-id="${id}"]`);
        if (idx >= 0) {
            this.selectedAddons.splice(idx, 1);
            card?.classList.remove('selected');
        } else {
            this.selectedAddons.push(addon);
            card?.classList.add('selected');
        }
        this.updateOrderSummary();
        this.saveState();
    }

    toggleBebida(id) {
        const bebida = this.bebidas.find(b => b.id == id);
        if (!bebida) return;
        const idx = this.selectedBebidas.findIndex(b => b.id == id);
        const card = document.querySelector(`[data-bebida-id="${id}"]`);
        if (idx >= 0) {
            this.selectedBebidas.splice(idx, 1);
            card?.classList.remove('selected');
        } else {
            this.selectedBebidas.push({ ...bebida, quantidade: 1 });
            card?.classList.add('selected');
        }
        this.updateOrderSummary();
        this.saveState();
    }

    getMaxSabores() {
        switch (this.selectedSize) {
            case 'pequena': return 2;
            case 'media': return 2;
            case 'grande': return 3;
            default: return 1;
        }
    }

    filterPizzasByCategory(categoria) {
        document.querySelectorAll('.pizza-card').forEach(card => {
            card.style.display = (card.dataset.categoria === categoria) ? 'block' : 'none';
        });
    }

    updateOrderSummary() {
        const total = [
            ...this.selectedPizzas.map(p => p.preco),
            ...this.selectedAddons.map(a => a.preco),
            ...this.selectedBebidas.map(b => b.preco * (b.quantidade || 1))
        ].reduce((a, b) => a + b, 0);
        const count = this.selectedPizzas.length + this.selectedAddons.length + this.selectedBebidas.length;
        const countEl = document.getElementById('cart-count');
        const totalEl = document.getElementById('cart-total');
        if (countEl) countEl.textContent = String(count);
        if (totalEl) totalEl.textContent = total.toFixed(2);
    }

    toggleCart() {
        document.getElementById('cart-panel')?.classList.toggle('open');
    }

    closeCart() {
        document.getElementById('cart-panel')?.classList.remove('open');
    }

    finalizarPedido() {
        if (!this.selectedEnderecoId) {
            this.showError('Selecione ou cadastre um endereço para entrega.');
            this.showStep('endereco');
            return;
        }
        if (this.paymentMethod === 'cartao') {
            this.showSuccess('Pagamento será realizado na entrega com cartão.');
        }
        this.showSuccess('Pedido confirmado!');
        setTimeout(() => {
            this.selectedPizzas = [];
            this.selectedAddons = [];
            this.selectedBebidas = [];
            this.selectedSize = null;
            this.selectedEnderecoId = null;
            this.paymentMethod = 'pix';
            sessionStorage.removeItem('cardapioState');
            this.showStep('tamanho');
            this.updateOrderSummary();
            this.closeCart();
        }, 2000);
    }

    async salvarEndereco() {
        const cep = document.getElementById('cep')?.value?.trim();
        const logradouro = document.getElementById('logradouro')?.value?.trim();
        const numero = document.getElementById('numero')?.value?.trim();
        const complemento = document.getElementById('complemento')?.value?.trim();
        const bairro = document.getElementById('bairro')?.value?.trim();
        const cidade = 'Guarapari';
        const uf = 'ES';
        if (!cep || !logradouro || !numero || !bairro) {
            this.showError('Preencha todos os campos obrigatórios.');
            return;
        }
        const apelido = `${logradouro} ${numero}`.trim();
        const payload = { apelido, cep, logradouro, numero, complemento, bairro, cidade, uf, padrao: 0 };
        try {
            const res = await fetch('../api/enderecos.php?action=add', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await res.json();
            if (data.success) {
                this.selectedEnderecoId = data.data?.id || null;
                this.showSuccess('Endereço salvo.');
                this.renderEndereco();
                this.saveState();
            } else {
                this.showError(data.message || 'Erro ao salvar endereço');
            }
        } catch (e) {
            this.showError('Erro ao salvar endereço');
        }
    }

    formatarCEP(input) {
        const v = input.value.replace(/\D/g, '');
        input.value = v.replace(/(\d{5})(\d{1,3})/, '$1-$2');
        if (v.length === 8) this.buscarCEP(v);
    }

    async buscarCEP(cep) {
        try {
            const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await res.json();
            if (!data.erro) {
                const logradouro = document.getElementById('logradouro');
                const bairro = document.getElementById('bairro');
                if (logradouro && data.logradouro) logradouro.value = data.logradouro;
                if (bairro && data.bairro) bairro.value = data.bairro;
            }
        } catch (e) {}
    }

    saveState() {
        const state = {
            currentStep: this.currentStep,
            selectedSize: this.selectedSize,
            selectedPizzas: this.selectedPizzas,
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
            this.currentStep = s.currentStep || this.currentStep;
            this.selectedSize = s.selectedSize || this.selectedSize;
            this.selectedPizzas = Array.isArray(s.selectedPizzas)?s.selectedPizzas:[];
            this.selectedAddons = Array.isArray(s.selectedAddons)?s.selectedAddons:[];
            this.selectedBebidas = Array.isArray(s.selectedBebidas)?s.selectedBebidas:[];
            this.selectedEnderecoId = s.selectedEnderecoId || null;
            this.paymentMethod = s.paymentMethod || 'pix';
            this.taxaEntrega = typeof s.taxaEntrega === 'number' ? s.taxaEntrega : null;
        } catch(e) {}
    }

    async buscarTaxaBairro(bairro) {
        const el = document.getElementById('taxa-info');
        if (!el) return;
        const b = String(bairro || '').trim();
        if (!b) { el.textContent = ''; this.taxaEntrega = null; this.saveState(); return; }
        try {
            const res = await fetch(`../api/enderecos.php?action=taxa&bairro=${encodeURIComponent(b)}`);
            const j = await res.json();
            const taxa = typeof j.taxa === 'number' ? j.taxa : 1.00;
            this.taxaEntrega = taxa;
            el.textContent = `Taxa de entrega para ${b}: R$ ${taxa.toFixed(2)}`;
            this.saveState();
        } catch (e) {
            this.taxaEntrega = 1.00;
            el.textContent = `Taxa de entrega para ${b}: R$ 1,00`;
            this.saveState();
        }
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

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM carregado, inicializando CardapioApp...');
    const app = new CardapioApp();
    
    // Tenta restaurar o estado e inicializar
    try {
        app.restoreState();
        app.init().catch(error => {
            console.error('Erro ao inicializar:', error);
            // Tenta carregar dados locais em caso de falha
            app.carregarDadosLocais();
            app.renderTamanhos();
        });
    } catch (error) {
        console.error('Erro crítico:', error);
        app.carregarDadosLocais();
        app.renderTamanhos();
    }
    app.init();
    window.cardapioApp = app;
    window.toggleCart = () => window.cardapioApp?.toggleCart();
    window.closeCart = () => window.cardapioApp?.closeCart();
    window.finalizarPedido = () => window.cardapioApp?.finalizarPedido();
});

