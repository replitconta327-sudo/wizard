/**
 * Sistema de Pagamento
 * Gerencia a interface de pagamento com múltiplas formas de pagamento
 */

class PagamentoSystem {
    constructor() {
        this.pedidoId = this.getPedidoId();
        this.paymentMethod = 'card';
        this.cardData = {};
        this.pixData = {};
        this.cashData = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupCardValidation();
        this.setupPaymentForms();
        this.loadPaymentData();
    }

    getPedidoId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('pedido_id');
    }

    bindEvents() {
        // Payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.paymentMethod = e.target.value;
                this.showPaymentForm(e.target.value);
            });
        });

        // Cash amount selection
        document.getElementById('cash_amount')?.addEventListener('change', (e) => {
            this.toggleCustomCashAmount(e.target.value);
        });

        // Card number validation
        document.getElementById('card_number')?.addEventListener('input', (e) => {
            this.formatCardNumber(e.target);
            this.detectCardBrand(e.target.value);
        });

        // Card expiry validation
        document.getElementById('card_expiry')?.addEventListener('input', (e) => {
            this.formatCardExpiry(e.target);
        });

        // Card CPF validation
        document.getElementById('card_cpf')?.addEventListener('input', (e) => {
            this.formatCPF(e.target);
        });

        // CVV validation
        document.getElementById('card_cvv')?.addEventListener('input', (e) => {
            this.validateCVV(e.target);
        });

        // Saved card selection
        document.querySelectorAll('input[name="saved_card"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.toggleNewCardForm(e.target.value === 'new');
            });
        });

        // Payment processing
        document.getElementById('confirmar-pagamento')?.addEventListener('click', () => {
            this.processarPagamento();
        });
    }

    setupCardValidation() {
        // Card number validation
        const cardNumber = document.getElementById('card_number');
        if (cardNumber) {
            cardNumber.addEventListener('blur', (e) => {
                this.validateCardNumber(e.target);
            });
        }

        // Expiry validation
        const cardExpiry = document.getElementById('card_expiry');
        if (cardExpiry) {
            cardExpiry.addEventListener('blur', (e) => {
                this.validateCardExpiry(e.target);
            });
        }

        // CVV validation
        const cardCVV = document.getElementById('card_cvv');
        if (cardCVV) {
            cardCVV.addEventListener('blur', (e) => {
                this.validateCVV(e.target);
            });
        }

        // CPF validation
        const cardCPF = document.getElementById('card_cpf');
        if (cardCPF) {
            cardCPF.addEventListener('blur', (e) => {
                this.validateCPF(e.target);
            });
        }
    }

    setupPaymentForms() {
        // Initially show card form
        this.showPaymentForm('card');
    }

    loadPaymentData() {
        // Load any saved payment preferences
        const savedMethod = localStorage.getItem('preferred_payment_method');
        if (savedMethod) {
            const radio = document.querySelector(`input[name="payment_method"][value="${savedMethod}"]`);
            if (radio) {
                radio.checked = true;
                this.paymentMethod = savedMethod;
                this.showPaymentForm(savedMethod);
            }
        }
    }

    showPaymentForm(method) {
        // Hide all forms
        document.querySelectorAll('.payment-form').forEach(form => {
            form.classList.remove('active');
        });

        // Show selected form
        const formId = `${method}-payment-form`;
        const form = document.getElementById(formId);
        if (form) {
            form.classList.add('active');
        }

        // Update confirmation modal
        this.updateConfirmationModal();
    }

    formatCardNumber(input) {
        let value = input.value.replace(/\D/g, '');
        let formatted = '';
        
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formatted += ' ';
            }
            formatted += value[i];
        }
        
        input.value = formatted.substring(0, 19);
    }

    detectCardBrand(value) {
        const cleanValue = value.replace(/\D/g, '');
        const brandElement = document.getElementById('card_brand');
        
        if (!brandElement) return;

        let brand = '';
        let brandClass = '';

        if (/^4/.test(cleanValue)) {
            brand = 'Visa';
            brandClass = 'visa';
        } else if (/^5[1-5]/.test(cleanValue)) {
            brand = 'Mastercard';
            brandClass = 'mastercard';
        } else if (/^3[47]/.test(cleanValue)) {
            brand = 'American Express';
            brandClass = 'amex';
        } else if (/^6(?:011|5)/.test(cleanValue)) {
            brand = 'Discover';
            brandClass = 'discover';
        } else if (/^3(?:0[0-5]|[68])/.test(cleanValue)) {
            brand = 'Diners Club';
            brandClass = 'diners';
        }

        if (brand) {
            brandElement.textContent = brand;
            brandElement.className = `card-brand ${brandClass}`;
        } else {
            brandElement.textContent = '';
            brandElement.className = 'card-brand';
        }
    }

    formatCardExpiry(input) {
        let value = input.value.replace(/\D/g, '');
        let formatted = '';
        
        if (value.length >= 2) {
            formatted = value.substring(0, 2) + '/';
            if (value.length > 2) {
                formatted += value.substring(2, 4);
            }
        } else {
            formatted = value;
        }
        
        input.value = formatted;
    }

    formatCPF(input) {
        let value = input.value.replace(/\D/g, '');
        let formatted = '';
        
        if (value.length > 9) {
            formatted = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6, 9) + '-' + value.substring(9, 11);
        } else if (value.length > 6) {
            formatted = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6);
        } else if (value.length > 3) {
            formatted = value.substring(0, 3) + '.' + value.substring(3);
        } else {
            formatted = value;
        }
        
        input.value = formatted;
    }

    validateCardNumber(input) {
        const value = input.value.replace(/\D/g, '');
        
        if (value.length < 13 || value.length > 19) {
            this.showFieldError(input, 'Número do cartão inválido');
            return false;
        }
        
        if (!this.isValidCardNumber(value)) {
            this.showFieldError(input, 'Número do cartão inválido');
            return false;
        }
        
        this.clearFieldError(input);
        return true;
    }

    validateCardExpiry(input) {
        const value = input.value;
        const [month, year] = value.split('/');
        
        if (!month || !year || month.length !== 2 || year.length !== 2) {
            this.showFieldError(input, 'Data de validade inválida');
            return false;
        }
        
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear() % 100;
        const currentMonth = currentDate.getMonth() + 1;
        
        const expMonth = parseInt(month);
        const expYear = parseInt(year);
        
        if (expMonth < 1 || expMonth > 12) {
            this.showFieldError(input, 'Mês inválido');
            return false;
        }
        
        if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
            this.showFieldError(input, 'Cartão vencido');
            return false;
        }
        
        this.clearFieldError(input);
        return true;
    }

    validateCVV(input) {
        const value = input.value.replace(/\D/g, '');
        
        if (value.length < 3 || value.length > 4) {
            this.showFieldError(input, 'CVV inválido');
            return false;
        }
        
        this.clearFieldError(input);
        return true;
    }

    validateCPF(input) {
        const value = input.value.replace(/\D/g, '');
        
        if (value.length !== 11) {
            this.showFieldError(input, 'CPF inválido');
            return false;
        }
        
        if (!this.isValidCPF(value)) {
            this.showFieldError(input, 'CPF inválido');
            return false;
        }
        
        this.clearFieldError(input);
        return true;
    }

    isValidCardNumber(number) {
        // Luhn algorithm
        let sum = 0;
        let isEven = false;
        
        for (let i = number.length - 1; i >= 0; i--) {
            let digit = parseInt(number[i]);
            
            if (isEven) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            
            sum += digit;
            isEven = !isEven;
        }
        
        return sum % 10 === 0;
    }

    isValidCPF(cpf) {
        if (cpf.length !== 11 || /^\d{11}$/.test(cpf) === false) {
            return false;
        }
        
        // Elimina CPFs invalidos conhecidos
        const invalidCPFs = [
            '00000000000', '11111111111', '22222222222', '33333333333',
            '44444444444', '55555555555', '66666666666', '77777777777',
            '88888888888', '99999999999'
        ];
        
        if (invalidCPFs.includes(cpf)) {
            return false;
        }
        
        // Valida 1o digito
        let add = 0;
        for (let i = 0; i < 9; i++) {
            add += parseInt(cpf.charAt(i)) * (10 - i);
        }
        
        let rev = 11 - (add % 11);
        if (rev === 10 || rev === 11) {
            rev = 0;
        }
        
        if (rev !== parseInt(cpf.charAt(9))) {
            return false;
        }
        
        // Valida 2o digito
        add = 0;
        for (let i = 0; i < 10; i++) {
            add += parseInt(cpf.charAt(i)) * (11 - i);
        }
        
        rev = 11 - (add % 11);
        if (rev === 10 || rev === 11) {
            rev = 0;
        }
        
        return rev === parseInt(cpf.charAt(10));
    }

    showFieldError(input, message) {
        input.classList.add('error');
        
        let errorElement = input.parentElement.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            input.parentElement.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }

    clearFieldError(input) {
        input.classList.remove('error');
        
        const errorElement = input.parentElement.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    toggleCustomCashAmount(value) {
        const customGroup = document.getElementById('custom-cash-group');
        if (customGroup) {
            if (value === 'custom') {
                customGroup.style.display = 'block';
                document.getElementById('custom_cash_amount').focus();
            } else {
                customGroup.style.display = 'none';
            }
        }
    }

    toggleNewCardForm(show) {
        const newCardForm = document.querySelector('.new-card-form');
        if (newCardForm) {
            newCardForm.style.display = show ? 'block' : 'none';
        }
    }

    updateConfirmationModal() {
        const methodNames = {
            'card': 'Cartão de Crédito',
            'debit': 'Cartão de Débito',
            'pix': 'PIX',
            'cash': 'Dinheiro'
        };
        
        const confirmMethod = document.getElementById('confirm-payment-method');
        if (confirmMethod) {
            confirmMethod.textContent = methodNames[this.paymentMethod] || '-';
        }
    }

    validatePaymentData() {
        switch (this.paymentMethod) {
            case 'card':
            case 'debit':
                return this.validateCardPayment();
            case 'pix':
                return this.validatePixPayment();
            case 'cash':
                return this.validateCashPayment();
            default:
                return false;
        }
    }

    validateCardPayment() {
        const savedCard = document.querySelector('input[name="saved_card"]:checked');
        
        if (savedCard && savedCard.value !== 'new') {
            return true; // Using saved card
        }
        
        // Validate new card form
        const cardNumber = document.getElementById('card_number');
        const cardName = document.getElementById('card_name');
        const cardCPF = document.getElementById('card_cpf');
        const cardExpiry = document.getElementById('card_expiry');
        const cardCVV = document.getElementById('card_cvv');
        
        if (!cardNumber || !cardName || !cardCPF || !cardExpiry || !cardCVV) {
            return false;
        }
        
        const validations = [
            this.validateCardNumber(cardNumber),
            this.validateCardExpiry(cardExpiry),
            this.validateCVV(cardCVV),
            this.validateCPF(cardCPF),
            cardName.value.trim().length >= 3
        ];
        
        if (!cardName.value.trim()) {
            this.showFieldError(cardName, 'Nome do titular é obrigatório');
            validations.push(false);
        }
        
        return validations.every(v => v === true);
    }

    validatePixPayment() {
        // PIX validation is done server-side
        return true;
    }

    validateCashPayment() {
        const cashAmount = document.getElementById('cash_amount');
        const customAmount = document.getElementById('custom_cash_amount');
        
        if (!cashAmount) return false;
        
        if (cashAmount.value === 'custom') {
            if (!customAmount || !customAmount.value || parseFloat(customAmount.value) <= 0) {
                this.showFieldError(customAmount, 'Valor inválido');
                return false;
            }
        }
        
        return true;
    }

    collectPaymentData() {
        switch (this.paymentMethod) {
            case 'card':
            case 'debit':
                return this.collectCardData();
            case 'pix':
                return this.collectPixData();
            case 'cash':
                return this.collectCashData();
            default:
                return {};
        }
    }

    collectCardData() {
        const savedCard = document.querySelector('input[name="saved_card"]:checked');
        
        if (savedCard && savedCard.value !== 'new') {
            return {
                type: 'saved_card',
                card_id: savedCard.value
            };
        }
        
        return {
            type: 'new_card',
            card_number: document.getElementById('card_number').value.replace(/\D/g, ''),
            card_name: document.getElementById('card_name').value.trim(),
            card_cpf: document.getElementById('card_cpf').value.replace(/\D/g, ''),
            card_expiry: document.getElementById('card_expiry').value,
            card_cvv: document.getElementById('card_cvv').value,
            installments: document.getElementById('installments').value,
            save_card: document.getElementById('save_card').checked
        };
    }

    collectPixData() {
        return {
            type: 'pix'
        };
    }

    collectCashData() {
        const cashAmount = document.getElementById('cash_amount');
        let changeAmount = 0;
        
        if (cashAmount.value === 'exact') {
            changeAmount = 0;
        } else if (cashAmount.value === 'custom') {
            changeAmount = parseFloat(document.getElementById('custom_cash_amount').value) || 0;
        } else {
            changeAmount = parseFloat(cashAmount.value);
        }
        
        return {
            type: 'cash',
            change_amount: changeAmount
        };
    }

    async processarPagamento() {
        if (!this.validatePaymentData()) {
            this.showToast('Por favor, corrija os erros antes de continuar', 'error');
            return;
        }
        
        this.abrirModal('modal-confirmacao');
    }

    async confirmarPagamento() {
        try {
            this.showLoading(true, 'Processando pagamento...');
            this.fecharModal('modal-confirmacao');
            
            // Coletar dados do pagamento
            const paymentData = this.collectPaymentData();
            
            // Obter dados do carrinho e endereço
            const carrinhoData = await this.obterDadosCarrinho();
            const enderecoId = localStorage.getItem('endereco_selecionado');
            
            if (!carrinhoData || !enderecoId) {
                throw new Error('Dados incompletos para criar pedido');
            }
            
            // Criar pedido com todos os dados
            const pedidoData = {
                itens: carrinhoData.itens,
                endereco_id: parseInt(enderecoId),
                forma_pagamento_id: this.getFormaPagamentoId(),
                total: carrinhoData.total,
                subtotal: carrinhoData.subtotal,
                taxa_entrega: carrinhoData.taxa_entrega || 0,
                payment_method: this.paymentMethod,
                payment_data: paymentData
            };
            
            // Criar pedido
            const response = await fetch('api/pedidos.php?action=criar_pedido', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(pedidoData)
            });
            
            const data = await response.json();
            
            if (data.ok) {
                // Salvar método de pagamento preferido
                localStorage.setItem('preferred_payment_method', this.paymentMethod);
                
                // Limpar carrinho após criar pedido
                await this.limparCarrinho();
                
                // Redirecionar para página de confirmação
                window.location.href = '/pages/confirmacao.php';
            } else {
                throw new Error(data.mensagem || 'Erro ao criar pedido');
            }
        } catch (error) {
            console.error('Erro ao processar pagamento:', error);
            this.showToast('Erro ao processar pagamento: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async obterDadosCarrinho() {
        try {
            const response = await fetch('api/bebidas.php?action=get_cart', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.ok) {
                return {
                    itens: data.itens,
                    total: data.total,
                    subtotal: data.subtotal,
                    taxa_entrega: data.taxa_entrega || 0
                };
            } else {
                throw new Error(data.mensagem || 'Erro ao obter carrinho');
            }
        } catch (error) {
            console.error('Erro ao obter dados do carrinho:', error);
            throw error;
        }
    }
    
    getFormaPagamentoId() {
        const formaPagamentoMap = {
            'card': 1,        // Cartão de Crédito
            'debit': 2,       // Cartão de Débito
            'pix': 3,         // PIX
            'cash': 4         // Dinheiro
        };
        
        return formaPagamentoMap[this.paymentMethod] || 1;
    }
    
    async limparCarrinho() {
        try {
            await fetch('api/bebidas.php?action=clear_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
        } catch (error) {
            console.error('Erro ao limpar carrinho:', error);
        }
    }

    showPixData(pixData) {
        // Show PIX QR Code and code
        const qrElement = document.getElementById('pix-qr');
        const codeElement = document.getElementById('pix-code');
        
        if (qrElement && codeElement) {
            qrElement.innerHTML = `<img src="${pixData.qr_code_url}" alt="QR Code PIX">`;
            codeElement.value = pixData.pix_code;
        }
        
        this.showToast('Código PIX gerado com sucesso!', 'success');
        
        // Start PIX status polling
        this.startPixPolling(pixData.transaction_id);
    }

    startPixPolling(transactionId) {
        let attempts = 0;
        const maxAttempts = 60; // 5 minutes (5 second intervals)
        
        const checkPixStatus = async () => {
            if (attempts >= maxAttempts) {
                this.showToast('Tempo limite para pagamento PIX expirado', 'error');
                return;
            }
            
            try {
                const response = await fetch(`api/pagamentos.php?action=check_pix_status&transaction_id=${transactionId}`);
                const data = await response.json();
                
                if (data.success && data.status === 'paid') {
                    this.showSuccessModal();
                    return;
                }
                
                attempts++;
                setTimeout(checkPixStatus, 5000); // Check every 5 seconds
            } catch (error) {
                console.error('Erro ao verificar status PIX:', error);
                attempts++;
                setTimeout(checkPixStatus, 5000);
            }
        };
        
        checkPixStatus();
    }

    showSuccessModal() {
        this.abrirModal('modal-sucesso');
    }

    updateConfirmationModal() {
        const methodNames = {
            'card': 'Cartão de Crédito',
            'debit': 'Cartão de Débito',
            'pix': 'PIX',
            'cash': 'Dinheiro'
        };
        
        const confirmMethod = document.getElementById('confirm-payment-method');
        if (confirmMethod) {
            confirmMethod.textContent = methodNames[this.paymentMethod] || '-';
        }
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
        const messageElement = document.getElementById('loading-message');
        
        if (overlay) {
            if (show) {
                if (messageElement) {
                    messageElement.textContent = message;
                }
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

    voltarRevisao() {
        window.location.href = 'revisao.php';
    }

    irParaConfirmacao() {
        window.location.href = `/pages/confirmacao.php?pedido_id=${this.pedidoId}`;
    }
}

// Global functions for HTML
copiarCodigoPix = function() {
    const codeElement = document.getElementById('pix-code');
    if (codeElement) {
        navigator.clipboard.writeText(codeElement.value).then(() => {
            // Show feedback
            const button = event.target.closest('.btn-copy');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.add('copied');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('copied');
            }, 2000);
        }).catch(err => {
            console.error('Erro ao copiar código:', err);
        });
    }
};

voltarRevisao = function() {
    if (window.pagamentoSystem) {
        window.pagamentoSystem.voltarRevisao();
    }
};

processarPagamento = function() {
    if (window.pagamentoSystem) {
        window.pagamentoSystem.processarPagamento();
    }
};

irParaConfirmacao = function() {
    if (window.pagamentoSystem) {
        window.pagamentoSystem.irParaConfirmacao();
    }
};

fecharModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
};

// Add toast animation
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
    
    .field-error {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .form-control.error {
        border-color: #dc3545;
    }
    
    .btn-copy.copied {
        background-color: #28a745;
        color: white;
    }
`;
document.head.appendChild(style);

// Initialize system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.pagamentoSystem = new PagamentoSystem();
    
    // Add event listener for confirmation button
    const confirmButton = document.getElementById('confirmar-pagamento');
    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            window.pagamentoSystem.confirmarPagamento();
        });
    }
});