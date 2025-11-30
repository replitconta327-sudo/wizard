/**
 * Sistema de Revisão do Pedido
 * Gerencia a interface de revisão de bebidas antes do pagamento
 */

class RevisaoSystem {
    constructor() {
        this.itens = [];
        this.enderecoSelecionado = null;
        this.itemParaRemover = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCartData();
        this.setupAddressValidation();
        this.setupDeliveryOptions();
    }

    bindEvents() {
        // Eventos de quantidade
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-quantity')) {
                const button = e.target.closest('.btn-quantity');
                const action = button.dataset.action;
                const itemId = button.dataset.itemId;
                this.updateQuantity(itemId, action);
            }
        });

        // Eventos de remoção
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-remove')) {
                const button = e.target.closest('.btn-remove');
                const itemId = button.dataset.itemId;
                const itemName = button.dataset.itemName;
                this.confirmarRemocao(itemId, itemName);
            }
        });

        // Confirmação de remoção
        document.getElementById('confirmar-remocao')?.addEventListener('click', () => {
            this.removerItem();
        });

        // Endereço
        document.getElementById('endereco_entrega')?.addEventListener('change', (e) => {
            this.enderecoSelecionado = e.target.value;
            this.updateFinalizarButton();
        });

        // Formulário de endereço
        document.getElementById('salvar-endereco')?.addEventListener('click', () => {
            this.salvarEndereco();
        });

        // CEP autocomplete
        document.getElementById('cep')?.addEventListener('blur', (e) => {
            this.buscarCEP(e.target.value);
        });

        // Delivery options
        document.querySelectorAll('input[name="delivery_time"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.toggleDeliveryTime(e.target.value);
            });
        });
    }

    async loadCartData() {
        try {
            const response = await fetch('api/bebidas.php?action=get_cart', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Erro ao carregar carrinho');
            }

            const data = await response.json();
            if (data.success) {
                this.itens = data.data;
                this.updateSummary();
            } else {
                throw new Error(data.message || 'Erro ao carregar dados');
            }
        } catch (error) {
            console.error('Erro ao carregar carrinho:', error);
            this.showToast('Erro ao carregar carrinho: ' + error.message, 'error');
        }
    }

    async updateQuantity(itemId, action) {
        const itemCard = document.querySelector(`[data-item-id="${itemId}"]`);
        const quantityElement = itemCard.querySelector('.quantity-value');
        const currentQuantity = parseInt(quantityElement.textContent);
        const newQuantity = action === 'increase' ? currentQuantity + 1 : Math.max(1, currentQuantity - 1);

        // Atualizar visualmente primeiro (otimista)
        quantityElement.textContent = newQuantity;

        try {
            const response = await fetch('api/bebidas.php?action=update_quantity', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    item_id: itemId,
                    quantity: newQuantity
                })
            });

            const data = await response.json();
            if (data.success) {
                this.updateItemSubtotal(itemCard, newQuantity, data.item_price);
                this.updateSummary();
                this.showToast('Quantidade atualizada com sucesso!', 'success');
            } else {
                // Reverter em caso de erro
                quantityElement.textContent = currentQuantity;
                throw new Error(data.message || 'Erro ao atualizar quantidade');
            }
        } catch (error) {
            // Reverter em caso de erro
            quantityElement.textContent = currentQuantity;
            console.error('Erro ao atualizar quantidade:', error);
            this.showToast('Erro ao atualizar quantidade: ' + error.message, 'error');
        }
    }

    updateItemSubtotal(itemCard, quantity, price) {
        const subtotalElement = itemCard.querySelector('.subtotal-value');
        const subtotal = quantity * price;
        subtotalElement.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
    }

    updateSummary() {
        // Atualizar resumo na lateral
        this.updateSummaryItems();
        this.updateTotalValues();
    }

    updateSummaryItems() {
        const summaryItems = document.querySelector('.summary-items');
        if (!summaryItems) return;

        // Limpar itens existentes
        summaryItems.innerHTML = '';

        // Adicionar itens atuais
        this.itens.forEach(item => {
            const summaryItem = document.createElement('div');
            summaryItem.className = 'summary-item';
            summaryItem.innerHTML = `
                <span class="item-name">
                    ${item.nome}
                    <small>(${item.quantidade}x)</small>
                </span>
                <span class="item-price">R$ ${(item.quantidade * item.preco).toFixed(2).replace('.', ',')}</span>
            `;
            summaryItems.appendChild(summaryItem);
        });
    }

    updateTotalValues() {
        const subtotal = this.itens.reduce((total, item) => total + (item.quantidade * item.preco), 0);
        const taxaServico = subtotal * 0.1;
        const total = subtotal + taxaServico;

        // Atualizar valores no DOM
        const subtotalElement = document.querySelector('.total-line:not(.total-final) span:last-child');
        const taxaElement = document.querySelector('.total-line:nth-child(2) span:last-child');
        const totalElement = document.querySelector('.total-final span:last-child');

        if (subtotalElement) subtotalElement.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
        if (taxaElement) taxaElement.textContent = `R$ ${taxaServico.toFixed(2).replace('.', ',')}`;
        if (totalElement) totalElement.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
    }

    confirmarRemocao(itemId, itemName) {
        this.itemParaRemover = { id: itemId, name: itemName };
        document.getElementById('item-name-remover').textContent = itemName;
        this.abrirModal('modal-remover');
    }

    async removerItem() {
        if (!this.itemParaRemover) return;

        try {
            const response = await fetch('api/bebidas.php?action=remove_from_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    item_id: this.itemParaRemover.id
                })
            });

            const data = await response.json();
            if (data.success) {
                // Remover do DOM
                const itemCard = document.querySelector(`[data-item-id="${this.itemParaRemover.id}"]`);
                if (itemCard) {
                    itemCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    itemCard.style.opacity = '0';
                    itemCard.style.transform = 'translateX(-100%)';
                    
                    setTimeout(() => {
                        itemCard.remove();
                        this.loadCartData(); // Recarregar dados
                    }, 300);
                }

                this.fecharModal('modal-remover');
                this.showToast('Item removido com sucesso!', 'success');
                
                // Verificar se o carrinho está vazio
                setTimeout(() => {
                    if (document.querySelectorAll('.item-card').length === 0) {
                        location.reload(); // Recarregar página para mostrar estado vazio
                    }
                }, 500);
            } else {
                throw new Error(data.message || 'Erro ao remover item');
            }
        } catch (error) {
            console.error('Erro ao remover item:', error);
            this.showToast('Erro ao remover item: ' + error.message, 'error');
        } finally {
            this.itemParaRemover = null;
        }
    }

    setupAddressValidation() {
        const cepInput = document.getElementById('cep');
        if (cepInput) {
            cepInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 8);
                }
                e.target.value = value;
            });
        }
    }

    async buscarCEP(cep) {
        const cleanCep = cep.replace(/\D/g, '');
        if (cleanCep.length !== 8) return;

        try {
            this.showLoading(true, 'Buscando endereço...');
            
            const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
            const data = await response.json();

            if (!data.erro) {
                document.getElementById('logradouro').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('uf').value = data.uf || '';
                
                // Focar no número se o logradouro foi preenchido
                if (data.logradouro) {
                    document.getElementById('numero').focus();
                }
            } else {
                this.showToast('CEP não encontrado', 'warning');
            }
        } catch (error) {
            console.error('Erro ao buscar CEP:', error);
            this.showToast('Erro ao buscar CEP', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async salvarEndereco() {
        const form = document.getElementById('form-endereco');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const endereco = {};
        
        for (let [key, value] of formData.entries()) {
            endereco[key] = value;
        }

        try {
            this.showLoading(true, 'Salvando endereço...');
            
            const response = await fetch('api/enderecos.php?action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(endereco)
            });

            const data = await response.json();
            if (data.success) {
                this.fecharModal('modal-endereco');
                this.showToast('Endereço salvo com sucesso!', 'success');
                
                // Recarregar endereços
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                throw new Error(data.message || 'Erro ao salvar endereço');
            }
        } catch (error) {
            console.error('Erro ao salvar endereço:', error);
            this.showToast('Erro ao salvar endereço: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    setupDeliveryOptions() {
        // Configurar datetime-local com restrições
        const scheduledTime = document.getElementById('scheduled_time');
        if (scheduledTime) {
            const now = new Date();
            now.setHours(now.getHours() + 1); // Mínimo 1 hora a partir de agora
            scheduledTime.min = now.toISOString().slice(0, 16);
        }
    }

    toggleDeliveryTime(value) {
        const scheduledTime = document.getElementById('scheduled_time');
        if (scheduledTime) {
            if (value === 'schedule') {
                scheduledTime.disabled = false;
                scheduledTime.focus();
            } else {
                scheduledTime.disabled = true;
                scheduledTime.value = '';
            }
        }
    }

    updateFinalizarButton() {
        const finalizarBtn = document.querySelector('.btn-primary.btn-block');
        const enderecoSelecionado = document.getElementById('endereco_entrega')?.value;
        
        if (finalizarBtn) {
            finalizarBtn.disabled = !enderecoSelecionado;
        }
    }

    async finalizarPedido() {
        if (!this.validarPedido()) {
            return;
        }

        try {
            this.showLoading(true, 'Finalizando pedido...');
            
            const pedidoData = this.coletarDadosPedido();
            
            const response = await fetch('api/pedidos.php?action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(pedidoData)
            });

            const data = await response.json();
            if (data.success) {
                this.showToast('Pedido finalizado com sucesso!', 'success');
                
                // Redirecionar para página de pagamento
                setTimeout(() => {
                    window.location.href = `/pages/pagamento.php?pedido_id=${data.pedido_id}`;
                }, 1500);
            } else {
                throw new Error(data.message || 'Erro ao finalizar pedido');
            }
        } catch (error) {
            console.error('Erro ao finalizar pedido:', error);
            this.showToast('Erro ao finalizar pedido: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    validarPedido() {
        // Verificar se há itens
        if (this.itens.length === 0) {
            this.showToast('Adicione pelo menos uma bebida ao pedido', 'warning');
            return false;
        }

        // Verificar endereço
        const enderecoSelecionado = document.getElementById('endereco_entrega')?.value;
        if (!enderecoSelecionado) {
            this.showToast('Selecione um endereço de entrega', 'warning');
            return false;
        }

        // Verificar horário de entrega
        const deliveryTime = document.querySelector('input[name="delivery_time"]:checked')?.value;
        if (deliveryTime === 'schedule') {
            const scheduledTime = document.getElementById('scheduled_time')?.value;
            if (!scheduledTime) {
                this.showToast('Selecione um horário para agendamento', 'warning');
                return false;
            }
            
            // Verificar se o horário é futuro
            const selectedTime = new Date(scheduledTime);
            const now = new Date();
            if (selectedTime <= now) {
                this.showToast('O horário de agendamento deve ser futuro', 'warning');
                return false;
            }
        }

        return true;
    }

    coletarDadosPedido() {
        const deliveryTime = document.querySelector('input[name="delivery_time"]:checked')?.value;
        const enderecoId = document.getElementById('endereco_entrega')?.value;
        
        return {
            items: this.itens.map(item => ({
                bebida_id: item.bebida_id,
                quantidade: item.quantidade,
                preco_unitario: item.preco
            })),
            endereco_id: enderecoId,
            tipo_entrega: deliveryTime,
            horario_agendado: deliveryTime === 'schedule' ? document.getElementById('scheduled_time')?.value : null,
            observacoes: ''
        };
    }

    continuarComprando() {
        window.location.href = '/pages/bebidas.php';
    }

    adicionarNovoEndereco() {
        this.abrirModal('modal-endereco');
    }

    abrirModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    fecharModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    showLoading(show, message = 'Processando...') {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            if (show) {
                overlay.querySelector('p').textContent = message;
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }
    }

    showToast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <i class="fas ${icons[type]} toast-icon"></i>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'toastSlideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, duration);
    }
}

// Adicionar animação de saída do toast
const style = document.createElement('style');
style.textContent = `
    @keyframes toastSlideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(style);

// Inicializar sistema quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new RevisaoSystem();
});

// Funções globais para HTML
function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function adicionarNovoEndereco() {
    const modal = document.getElementById('modal-endereco');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function continuarComprando() {
    window.location.href = 'bebidas.php';
}

function finalizarPedido() {
    // Esta função será chamada pelo objeto RevisaoSystem
    const event = new CustomEvent('finalizarPedido');
    document.dispatchEvent(event);
}

// Escutar evento customizado
document.addEventListener('finalizarPedido', () => {
    if (window.revisaoSystem) {
        window.revisaoSystem.finalizarPedido();
    }
});

// Adicionar revisaoSystem ao escopo global para acesso das funções HTML
window.revisaoSystem = null;
document.addEventListener('DOMContentLoaded', () => {
    window.revisaoSystem = new RevisaoSystem();
});