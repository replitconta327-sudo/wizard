// Sistema de Confirmação de Pedido - confirmacao.js

/**
 * Classe para gerenciar a página de confirmação de pedido
 * Inclui funcionalidades de impressão, compartilhamento e modal de loja
 */
class ConfirmacaoSystem {
    constructor() {
        this.pedidoData = window.pedidoData || {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupPrintStyles();
        this.setupShareFunctionality();
        this.startOrderStatusPolling();
    }

    setupEventListeners() {
        // Botões de ação
        const printBtn = document.querySelector('[onclick="imprimirPedido()"]');
        const shareBtn = document.querySelector('[onclick="compartilharPedido()"]');
        const homeBtn = document.querySelector('a[href="index.php"]');
        const ordersBtn = document.querySelector('a[href="meus-pedidos.php"]');

        if (printBtn) {
            printBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.imprimirPedido();
            });
        }

        if (shareBtn) {
            shareBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.compartilharPedido();
            });
        }

        if (homeBtn) {
            homeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.voltarParaHome();
            });
        }

        if (ordersBtn) {
            ordersBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.verMeusPedidos();
            });
        }

        // Modal de confirmação da loja
        const modalClose = document.querySelector('.modal-close');
        const modal = document.getElementById('confirmacaoLojaModal');

        if (modalClose) {
            modalClose.addEventListener('click', () => {
                this.fecharModalLoja();
            });
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.fecharModalLoja();
                }
            });
        }

        // Tecla ESC para fechar modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.fecharModalLoja();
            }
        });
    }

    setupPrintStyles() {
        // Adicionar estilos de impressão dinamicamente
        const printStyles = `
            <style id="print-styles" media="print">
                body {
                    font-size: 12px;
                    line-height: 1.4;
                }
                
                .header,
                .bottom-nav,
                .action-buttons,
                .modal {
                    display: none !important;
                }
                
                .confirmation-card {
                    box-shadow: none;
                    border: 1px solid #000;
                    padding: 1rem;
                }
                
                .item-card {
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                
                .total-summary {
                    border: 2px solid #000;
                }
                
                @page {
                    margin: 1cm;
                    size: A4;
                }
            </style>
        `;
        
        if (!document.getElementById('print-styles')) {
            document.head.insertAdjacentHTML('beforeend', printStyles);
        }
    }

    setupShareFunctionality() {
        // Verificar se a API de compartilhamento está disponível
        if (navigator.share) {
            this.nativeShareAvailable = true;
        } else {
            this.nativeShareAvailable = false;
        }
    }

    // Funções de ação
    imprimirPedido() {
        try {
            // Adicionar informações de impressão
            const printInfo = document.createElement('div');
            printInfo.id = 'print-info';
            printInfo.innerHTML = `
                <div style="text-align: center; margin-bottom: 1rem; padding: 1rem; border-bottom: 2px solid #000;">
                    <h2 style="margin: 0; color: #dc2626;">Pizzaria São Paulo</h2>
                    <p style="margin: 0.5rem 0; font-size: 0.9rem;">Pedido #${this.pedidoData.numero || 'N/A'}</p>
                    <p style="margin: 0; font-size: 0.8rem;">Impresso em: ${new Date().toLocaleString('pt-BR')}</p>
                </div>
            `;
            
            const confirmationCard = document.querySelector('.confirmation-card');
            if (confirmationCard) {
                confirmationCard.insertBefore(printInfo, confirmationCard.firstChild);
            }

            // Imprimir
            window.print();

            // Remover informações após impressão
            setTimeout(() => {
                const info = document.getElementById('print-info');
                if (info) {
                    info.remove();
                }
            }, 1000);

            this.showNotification('Pedido impresso com sucesso!', 'success');
        } catch (error) {
            console.error('Erro ao imprimir:', error);
            this.showNotification('Erro ao imprimir pedido', 'error');
        }
    }

    async compartilharPedido() {
        try {
            const shareData = {
                title: `Pedido Pizzaria São Paulo - #${this.pedidoData.numero || 'N/A'}`,
                text: `Confira meu pedido na Pizzaria São Paulo! Total: R$ ${this.formatCurrency(this.pedidoData.total || 0)}`,
                url: window.location.href
            };

            if (this.nativeShareAvailable) {
                await navigator.share(shareData);
            } else {
                // Fallback para navegadores que não suportam Web Share API
                this.compartilharFallback(shareData);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro ao compartilhar:', error);
                this.compartilharFallback({
                    title: `Pedido Pizzaria São Paulo - #${this.pedidoData.numero || 'N/A'}`,
                    text: `Confira meu pedido na Pizzaria São Paulo! Total: R$ ${this.formatCurrency(this.pedidoData.total || 0)}`,
                    url: window.location.href
                });
            }
        }
    }

    compartilharFallback(data) {
        // Copiar para área de transferência
        const textToCopy = `${data.title}\n${data.text}\n${data.url}`;
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                this.showNotification('Link copiado para área de transferência!', 'success');
            }).catch(() => {
                this.mostrarModalCompartilhamento(data);
            });
        } else {
            this.mostrarModalCompartilhamento(data);
        }
    }

    mostrarModalCompartilhamento(data) {
        // Criar modal de compartilhamento fallback
        const modal = document.createElement('div');
        modal.className = 'modal active';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Compartilhar Pedido</h3>
                    <button class="modal-close" onclick="this.closest('.modal').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="share-content">
                        <p>Copie o texto abaixo para compartilhar:</p>
                        <textarea readonly style="width: 100%; height: 100px; margin: 1rem 0; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">${data.title}\n\n${data.text}\n\n${data.url}</textarea>
                        <button class="btn btn-primary" onclick="this.previousElementSibling.select(); document.execCommand('copy'); this.textContent='Copiado!'; this.disabled=true;">
                            <i class="fas fa-copy"></i> Copiar Texto
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    voltarParaHome() {
        window.location.href = 'index.php';
    }

    verMeusPedidos() {
        window.location.href = 'meus-pedidos.php';
    }

    // Modal de confirmação da loja
    abrirModalLoja() {
        const modal = document.getElementById('confirmacaoLojaModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Adicionar animação de entrada
            const content = modal.querySelector('.modal-content');
            if (content) {
                content.style.animation = 'modalFadeIn 0.3s ease';
            }
        }
    }

    fecharModalLoja() {
        const modal = document.getElementById('confirmacaoLojaModal');
        if (modal) {
            const content = modal.querySelector('.modal-content');
            if (content) {
                content.style.animation = 'modalFadeOut 0.3s ease';
                
                setTimeout(() => {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }, 300);
            } else {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    }

    // Sistema de notificações
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notificationContainer') || this.createNotificationContainer();
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)} notification-icon"></i>
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(notification);
        
        // Animação de entrada
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Remover após tempo
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
        
        return notification;
    }

    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notificationContainer';
        container.className = 'notification-container';
        document.body.appendChild(container);
        return container;
    }

    removeNotification(notification) {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // Polling de status do pedido
    startOrderStatusPolling() {
        if (!this.pedidoData.id) return;
        
        // Poll a cada 30 segundos
        this.pollingInterval = setInterval(() => {
            this.checkOrderStatus();
        }, 30000);
        
        // Primeira verificação após 5 segundos
        setTimeout(() => {
            this.checkOrderStatus();
        }, 5000);
    }

    async checkOrderStatus() {
        try {
            const response = await fetch(`/api/pedidos.php?action=get_status&id=${this.pedidoData.id}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Erro ao verificar status');
            }
            
            const data = await response.json();
            
            if (data.ok && data.status) {
                this.updateOrderStatusDisplay(data.status);
                
                // Se o pedido foi entregue ou cancelado, parar o polling
                if (data.status === 'entregue' || data.status === 'cancelado') {
                    this.stopOrderStatusPolling();
                }
            }
        } catch (error) {
            console.error('Erro ao verificar status do pedido:', error);
        }
    }

    updateOrderStatusDisplay(status) {
        const statusElement = document.querySelector('.status-badge');
        if (statusElement) {
            statusElement.textContent = this.getStatusDisplayName(status);
            statusElement.className = `status-badge status-${status}`;
        }
    }

    getStatusDisplayName(status) {
        const statusNames = {
            'pendente': 'Aguardando Preparação',
            'preparando': 'Em Preparação',
            'pronto': 'Pronto para Entrega',
            'saiu_entrega': 'Saiu para Entrega',
            'entregue': 'Entregue',
            'cancelado': 'Cancelado'
        };
        return statusNames[status] || status;
    }

    stopOrderStatusPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }

    // Utilitários
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    // Limpeza ao sair da página
    destroy() {
        this.stopOrderStatusPolling();
    }
}

// Funções globais para os botões
function imprimirPedido() {
    if (window.confirmacaoSystem) {
        window.confirmacaoSystem.imprimirPedido();
    }
}

function compartilharPedido() {
    if (window.confirmacaoSystem) {
        window.confirmacaoSystem.compartilharPedido();
    }
}

function abrirModalLoja() {
    if (window.confirmacaoSystem) {
        window.confirmacaoSystem.abrirModalLoja();
    }
}

function fecharModalLoja() {
    if (window.confirmacaoSystem) {
        window.confirmacaoSystem.fecharModalLoja();
    }
}

// Inicializar o sistema quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.confirmacaoSystem = new ConfirmacaoSystem();
});

// Limpar ao sair da página
window.addEventListener('beforeunload', () => {
    if (window.confirmacaoSystem) {
        window.confirmacaoSystem.destroy();
    }
});

// Adicionar estilos CSS adicionais se necessário
const additionalStyles = `
<style>
    @keyframes modalFadeOut {
        from {
            opacity: 1;
            transform: scale(1);
        }
        to {
            opacity: 0;
            transform: scale(0.9);
        }
    }
    
    .share-content {
        text-align: center;
    }
    
    .share-content textarea {
        font-family: monospace;
        resize: none;
    }
    
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .notification {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        max-width: 400px;
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.3s ease;
        border-left: 4px solid #ccc;
    }
    
    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .notification.hide {
        transform: translateX(400px);
        opacity: 0;
    }
    
    .notification-success {
        border-left-color: #28a745;
    }
    
    .notification-error {
        border-left-color: #dc3545;
    }
    
    .notification-warning {
        border-left-color: #ffc107;
    }
    
    .notification-info {
        border-left-color: #17a2b8;
    }
    
    .notification-icon {
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .notification-success .notification-icon {
        color: #28a745;
    }
    
    .notification-error .notification-icon {
        color: #dc3545;
    }
    
    .notification-warning .notification-icon {
        color: #ffc107;
    }
    
    .notification-info .notification-icon {
        color: #17a2b8;
    }
    
    .notification-message {
        flex: 1;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.2s ease;
    }
    
    .notification-close:hover {
        color: #666;
    }
</style>
`;

if (!document.querySelector('#additional-confirmation-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'additional-confirmation-styles';
    styleElement.innerHTML = additionalStyles;
    document.head.appendChild(styleElement);
}