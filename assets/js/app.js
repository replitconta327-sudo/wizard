/**
 * ========================================
 * PIZZARIA SÃO PAULO - SISTEMA DE CADASTRO
 * JavaScript Principal com Funcionalidades Completas
 * ========================================
 * 
 * Este arquivo contém toda a lógica da aplicação, incluindo:
 * - Gerenciamento de formulários e navegação
 * - Validação de campos com máscaras
 * - Sistema de notificações
 * - Armazenamento local de dados
 * - Tratamento de erros robusto
 * - Acessibilidade aprimorada
 * 
 * @author Pizzaria São Paulo
 * @version 2.0.0
 * ========================================
 */

/**
 * ========================================
 * CONFIGURAÇÃO INICIAL E VARIÁVEIS GLOBAIS
 * ========================================
 */

// Configurações da aplicação
const APP_CONFIG = {
    STORAGE_KEY: 'pizzaria_sp_clientes',
    SESSION_KEY: 'pizzaria_sp_session',
    MAX_LOGIN_ATTEMPTS: 3,
    SESSION_TIMEOUT: 24 * 60 * 60 * 1000, // 24 horas
    NOTIFICATION_DURATION: 5000, // 5 segundos
    LOADING_DELAY: 1000 // 1 segundo para simular carregamento
};

// Estado global da aplicação
const APP_STATE = {
    currentSection: 'login',
    isLoading: false,
    loginAttempts: 0,
    resetStep: 1,
    currentUser: null,
    masks: {},
    notifications: []
};

// Elementos DOM cacheados para performance
const DOM_ELEMENTS = {};

/**
 * ========================================
 * CLASSES E TIPOS DE DADOS
 * ========================================
 */

/**
 * Classe para representar um cliente
 */
class Cliente {
    constructor(data) {
        this.id = data.id || this.generateId();
        this.nome = data.nome?.trim() || '';
        this.telefone = data.telefone?.trim() || '';
        this.rua = data.rua?.trim() || '';
        this.numero = data.numero?.trim() || '';
        this.bairro = data.bairro?.trim() || '';
        this.cep = data.cep?.trim() || '';
        this.referencia = data.referencia?.trim() || '';
        this.senha = data.senha || '';
        this.criadoEm = data.criadoEm || new Date().toISOString();
        this.ultimoAcesso = data.ultimoAcesso || null;
        this.ativo = data.ativo !== undefined ? data.ativo : true;
    }

    /**
     * Gera ID único baseado em timestamp e random
     */
    generateId() {
        return 'cliente_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Valida os dados do cliente
     */
    validar() {
        const erros = [];

        // Validação do nome
        if (!this.nome || this.nome.length < 3) {
            erros.push('Nome deve ter pelo menos 3 caracteres');
        }
        if (this.nome.length > 100) {
            erros.push('Nome não pode ter mais de 100 caracteres');
        }

        // Validação do telefone
        const telefoneLimpo = this.telefone.replace(/\D/g, '');
        if (telefoneLimpo.length < 10 || telefoneLimpo.length > 11) {
            erros.push('Telefone deve ter 10 ou 11 dígitos');
        }

        // Validação do endereço
        if (!this.rua || this.rua.length < 3) {
            erros.push('Rua deve ter pelo menos 3 caracteres');
        }
        if (!this.numero) {
            erros.push('Número é obrigatório');
        }
        if (!this.bairro || this.bairro.length < 3) {
            erros.push('Bairro deve ter pelo menos 3 caracteres');
        }

        // Validação do CEP
        const cepLimpo = this.cep.replace(/\D/g, '');
        if (cepLimpo.length !== 8) {
            erros.push('CEP deve ter 8 dígitos');
        }

        // Validação da senha
        if (!this.senha || this.senha.length < 6) {
            erros.push('Senha deve ter pelo menos 6 caracteres');
        }

        return erros;
    }

    /**
     * Formata o telefone para exibição
     */
    getTelefoneFormatado() {
        const telefoneLimpo = this.telefone.replace(/\D/g, '');
        if (telefoneLimpo.length === 11) {
            return `(${telefoneLimpo.slice(0, 2)}) ${telefoneLimpo.slice(2, 7)}-${telefoneLimpo.slice(7)}`;
        } else if (telefoneLimpo.length === 10) {
            return `(${telefoneLimpo.slice(0, 2)}) ${telefoneLimpo.slice(2, 6)}-${telefoneLimpo.slice(6)}`;
        }
        return this.telefone;
    }

    /**
     * Formata o CEP para exibição
     */
    getCEPFormatado() {
        const cepLimpo = this.cep.replace(/\D/g, '');
        if (cepLimpo.length === 8) {
            return `${cepLimpo.slice(0, 5)}-${cepLimpo.slice(5)}`;
        }
        return this.cep;
    }
}

/**
 * ========================================
 * FUNÇÕES DE UTILIDADE (UTILS)
 * ========================================
 */

/**
 * Classe de utilitários gerais
 */
class Utils {
    /**
     * Formata telefone com máscara
     */
    static formatarTelefone(value) {
        if (!value) return '';
        const limpo = value.replace(/\D/g, '');
        if (limpo.length <= 10) {
            return limpo.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else {
            return limpo.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }
    }

    /**
     * Formata CEP com máscara
     */
    static formatarCEP(value) {
        if (!value) return '';
        const limpo = value.replace(/\D/g, '');
        return limpo.replace(/(\d{5})(\d{0,3})/, '$1-$2');
    }

    /**
     * Remove máscaras de formatação
     */
    static limparMascara(value) {
        return value.replace(/\D/g, '');
    }

    /**
     * Valida email
     */
    static validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Gera hash simples para senhas (apenas para demonstração)
     * EM PRODUÇÃO: Use bcrypt ou similar
     */
    static hashSenha(senha) {
        // Método simples para demonstração - NÃO use em produção
        let hash = 0;
        for (let i = 0; i < senha.length; i++) {
            const char = senha.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return Math.abs(hash).toString(36);
    }

    /**
     * Compara senhas de forma segura
     */
    static compararSenhas(senhaDigitada, senhaArmazenada) {
        // Em produção, use bcrypt.compare()
        return this.hashSenha(senhaDigitada) === senhaArmazenada;
    }

    /**
     * Debounce para otimizar chamadas frequentes
     */
    static debounce(func, wait) {
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

    /**
     * Capitaliza primeira letra de cada palavra
     */
    static capitalizarNome(nome) {
        return nome.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
            return a.toUpperCase();
        });
    }

    /**
     * Remove acentos e caracteres especiais
     */
    static removerAcentos(texto) {
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
}

/**
 * ========================================
 * SISTEMA DE NOTIFICAÇÕES
 * ========================================
 */

/**
 * Classe para gerenciar notificações toast
 */
class NotificationSystem {
    constructor() {
        this.container = null;
        this.notifications = [];
        this.init();
    }

    /**
     * Inicializa o sistema de notificações
     */
    init() {
        this.container = document.getElementById('notification-container');
        if (!this.container) {
            console.error('Container de notificações não encontrado');
            return;
        }
    }

    /**
     * Mostra uma notificação
     */
    show(message, type = 'info', duration = APP_CONFIG.NOTIFICATION_DURATION) {
        if (!this.container) {
            console.warn('Sistema de notificações não inicializado');
            return;
        }

        const notification = this.createNotification(message, type);
        this.container.appendChild(notification);
        
        // Adiciona animação de entrada
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });

        // Remove após o tempo especificado
        const timeoutId = setTimeout(() => {
            this.remove(notification);
        }, duration);

        // Armazena referência
        this.notifications.push({
            element: notification,
            timeoutId: timeoutId
        });

        // Limita número de notificações simultâneas
        if (this.notifications.length > 5) {
            this.remove(this.notifications[0].element);
        }
    }

    /**
     * Cria elemento de notificação
     */
    createNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.setAttribute('role', 'alert');
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${icons[type]} notification-icon" aria-hidden="true"></i>
                <span class="notification-message">${this.escapeHtml(message)}</span>
            </div>
            <button class="notification-close" aria-label="Fechar notificação">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        `;

        // Adiciona evento de fechar
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.remove(notification));

        return notification;
    }

    /**
     * Remove uma notificação
     */
    remove(notification) {
        if (!notification || !notification.parentNode) return;

        // Remove animação
        notification.classList.remove('show');
        notification.classList.add('hide');

        // Remove do DOM após animação
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            
            // Remove da lista
            this.notifications = this.notifications.filter(n => n.element !== notification);
        }, 300);
    }

    /**
     * Escapa HTML para prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Métodos conveniência para tipos de notificações
     */
    success(message, duration) {
        this.show(message, 'success', duration);
    }

    error(message, duration) {
        this.show(message, 'error', duration);
    }

    warning(message, duration) {
        this.show(message, 'warning', duration);
    }

    info(message, duration) {
        this.show(message, 'info', duration);
    }
}

/**
 * ========================================
 * SISTEMA DE ARMAZENAMENTO LOCAL
 * ========================================
 */

/**
 * Classe para gerenciar armazenamento local
 */
class LocalStorageManager {
    constructor() {
        this.isAvailable = this.checkAvailability();
    }

    /**
     * Verifica se localStorage está disponível
     */
    checkAvailability() {
        try {
            const test = '__storage_test__';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Salva dados com tratamento de erro
     */
    set(key, value) {
        if (!this.isAvailable) {
            console.warn('localStorage não disponível');
            return false;
        }

        try {
            const dataToStore = JSON.stringify(value);
            localStorage.setItem(key, dataToStore);
            return true;
        } catch (error) {
            console.error('Erro ao salvar no localStorage:', error);
            
            // Trata erro de quota excedida
            if (error.name === 'QuotaExceededError') {
                this.handleQuotaExceeded();
            }
            return false;
        }
    }

    /**
     * Recupera dados com tratamento de erro
     */
    get(key, defaultValue = null) {
        if (!this.isAvailable) {
            return defaultValue;
        }

        try {
            const storedData = localStorage.getItem(key);
            return storedData ? JSON.parse(storedData) : defaultValue;
        } catch (error) {
            console.error('Erro ao recuperar do localStorage:', error);
            return defaultValue;
        }
    }

    /**
     * Remove dados
     */
    remove(key) {
        if (!this.isAvailable) return false;

        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Erro ao remover do localStorage:', error);
            return false;
        }
    }

    /**
     * Limpa todo o localStorage
     */
    clear() {
        if (!this.isAvailable) return false;

        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Erro ao limpar localStorage:', error);
            return false;
        }
    }

    /**
     * Handle quota exceeded error
     */
    handleQuotaExceeded() {
        console.warn('Quota do localStorage excedida. Tentando limpar dados antigos...');
        
        // Remove dados menos importantes
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && !key.includes('session') && !key.includes('config')) {
                keysToRemove.push(key);
            }
        }

        keysToRemove.forEach(key => {
            try {
                localStorage.removeItem(key);
            } catch (e) {
                console.error(`Erro ao remover ${key}:`, e);
            }
        });

        window.notificationSystem.warning('Dados antigos foram removidos para liberar espaço');
    }
}

/**
 * ========================================
 * SISTEMA DE VALIDAÇÃO
 * ========================================
 */

/**
 * Classe para validação de formulários
 */
class FormValidator {
    constructor() {
        this.rules = this.setupRules();
    }

    /**
     * Configura as regras de validação
     */
    setupRules() {
        return {
            nome: {
                required: true,
                minLength: 3,
                maxLength: 100,
                pattern: /^[a-zA-ZÀ-ÿ\s]+$/,
                message: 'Nome deve conter apenas letras e espaços'
            },
            telefone: {
                required: true,
                minLength: 10,
                maxLength: 11,
                pattern: /^[\d\s\-()]*$/,
                message: 'Telefone deve ter 10 ou 11 dígitos'
            },
            rua: {
                required: true,
                minLength: 3,
                maxLength: 200,
                message: 'Rua deve ter pelo menos 3 caracteres'
            },
            numero: {
                required: true,
                maxLength: 10,
                pattern: /^[a-zA-Z0-9\s]+$/,
                message: 'Número deve conter apenas letras, números e espaços'
            },
            bairro: {
                required: true,
                minLength: 3,
                maxLength: 100,
                message: 'Bairro deve ter pelo menos 3 caracteres'
            },
            cep: {
                required: true,
                pattern: /^(\d{8}|\d{5}-\d{3})$/,
                message: 'CEP deve estar no formato 00000-000'
            },
            senha: {
                required: true,
                minLength: 6,
                maxLength: 50,
                message: 'Senha deve ter pelo menos 6 caracteres'
            }
        };
    }

    /**
     * Valida um campo individual
     */
    validateField(fieldName, value, rules = null) {
        const fieldRules = rules || this.rules[fieldName];
        if (!fieldRules) return { isValid: true, errors: [] };

        const errors = [];
        const cleanValue = value?.trim() || '';

        // Required validation
        if (fieldRules.required && !cleanValue) {
            errors.push('Este campo é obrigatório');
            return { isValid: false, errors };
        }

        // Skip other validations if field is empty and not required
        if (!fieldRules.required && !cleanValue) {
            return { isValid: true, errors };
        }

        // Length validations
        if (fieldName === 'cep') {
            const digits = cleanValue.replace(/\D/g, '');
            if (digits.length !== 8) {
                errors.push('Deve ter exatamente 8 dígitos');
            }
        } else if (fieldRules.length && cleanValue.length !== fieldRules.length) {
            errors.push(`Deve ter exatamente ${fieldRules.length} caracteres`);
        }

        if (fieldRules.minLength && cleanValue.length < fieldRules.minLength) {
            errors.push(`Deve ter pelo menos ${fieldRules.minLength} caracteres`);
        }

        if (fieldRules.maxLength) {
            const valueForMaxCheck = fieldName === 'telefone' ? cleanValue.replace(/\D/g, '') : cleanValue;
            if (valueForMaxCheck.length > fieldRules.maxLength) {
                errors.push(`Não pode ter mais de ${fieldRules.maxLength} caracteres`);
            }
        }

        // Pattern validation
        if (fieldRules.pattern && !fieldRules.pattern.test(cleanValue)) {
            errors.push(fieldRules.message || 'Formato inválido');
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    /**
     * Valida um formulário completo
     */
    validateForm(formData) {
        const errors = {};
        let isFormValid = true;

        // Valida cada campo
        Object.keys(formData).forEach(fieldName => {
            const validation = this.validateField(fieldName, formData[fieldName]);
            if (!validation.isValid) {
                errors[fieldName] = validation.errors;
                isFormValid = false;
            }
        });

        return {
            isValid: isFormValid,
            errors
        };
    }

    /**
     * Mostra erros no campo
     */
    showFieldErrors(fieldElement, errors) {
        if (!fieldElement) return;

        const formGroup = fieldElement.closest('.form-group');
        if (!formGroup) return;

        const errorElement = formGroup.querySelector('.error-message');
        if (!errorElement) return;

        if (errors && errors.length > 0) {
            fieldElement.classList.add('error');
            errorElement.textContent = errors[0]; // Mostra apenas o primeiro erro
            errorElement.style.display = 'block';
        } else {
            fieldElement.classList.remove('error');
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    /**
     * Limpa erros do campo
     */
    clearFieldErrors(fieldElement) {
        this.showFieldErrors(fieldElement, []);
    }
}

/**
 * ========================================
 * CLASSE PRINCIPAL DA APLICAÇÃO
 * ========================================
 */

/**
 * Classe principal que gerencia toda a aplicação
 */
class PizzariaApp {
    constructor() {
        this.storage = new LocalStorageManager();
        this.validator = new FormValidator();
        this.notificationSystem = null;
        this.init();
    }

    /**
     * Inicializa a aplicação
     */
    async init() {
        try {
            // Aguarda DOM estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        } catch (error) {
            console.error('Erro crítico na inicialização:', error);
            this.handleCriticalError(error);
        }
    }

    /**
     * Configura a aplicação
     */
    setup() {
        try {
            // Inicializa sistema de notificações
            this.notificationSystem = new NotificationSystem();
            window.notificationSystem = this.notificationSystem;

            // Cache elementos DOM
            this.cacheDOMElements();

            // Configura máscaras de input
            this.setupInputMasks();

            // Configura event listeners
            this.setupEventListeners();

            // Verifica sessão existente
            this.checkExistingSession();

            // Configura validação de formulários
            this.setupFormValidation();

            console.log('✅ Aplicação inicializada com sucesso');
            
        } catch (error) {
            console.error('Erro na configuração:', error);
            this.notificationSystem.error('Erro ao inicializar a aplicação');
        }
    }

    /**
     * Cache de elementos DOM para performance
     */
    cacheDOMElements() {
        // Seções principais
        DOM_ELEMENTS.loginSection = document.getElementById('login-section');
        DOM_ELEMENTS.registerSection = document.getElementById('register-section');
        DOM_ELEMENTS.resetPasswordSection = document.getElementById('reset-password-section');
        DOM_ELEMENTS.dashboardSection = document.getElementById('dashboard-section');

        // Formulários
        DOM_ELEMENTS.loginForm = document.getElementById('login-form');
        DOM_ELEMENTS.registerForm = document.getElementById('register-form');
        DOM_ELEMENTS.resetPasswordForm = document.getElementById('reset-password-form');

        // Loading overlay
        DOM_ELEMENTS.loadingOverlay = document.getElementById('loading-overlay');

        // Links de navegação
        DOM_ELEMENTS.showRegister = document.getElementById('show-register');
        DOM_ELEMENTS.showResetPassword = document.getElementById('show-reset-password');
        DOM_ELEMENTS.backToLoginFromRegister = document.getElementById('back-to-login-from-register');
        DOM_ELEMENTS.backToLoginFromReset = document.getElementById('back-to-login-from-reset');

        // Dashboard
        DOM_ELEMENTS.logoutBtn = document.getElementById('logout-btn');
        DOM_ELEMENTS.editProfileBtn = document.getElementById('edit-profile-btn');
        DOM_ELEMENTS.viewMenuBtn = document.getElementById('view-menu-btn');
    }

    /**
     * Configura máscaras de input
     */
    setupInputMasks() {
        // Configura máscara de telefone
        const phoneInputs = document.querySelectorAll('[data-mask="phone"]');
        phoneInputs.forEach(input => {
            const mask = IMask(input, {
                mask: '(00) 00000-0000',
                lazy: false,
                placeholderChar: '_'
            });
            APP_STATE.masks[input.id] = mask;
        });

        // Configura máscara de CEP
        const cepInputs = document.querySelectorAll('[data-mask="cep"]');
        cepInputs.forEach(input => {
            const mask = IMask(input, {
                mask: '00000-000',
                lazy: true,
                overwrite: true,
                autofix: true,
                prepare: (str) => str.replace(/[^0-9]/g, '')
            });
            mask.on('accept', () => {
                const cleaned = mask.unmaskedValue;
                const errorEl = document.getElementById('register-cep-error');
                if (cleaned.length !== 8) {
                    if (errorEl) errorEl.textContent = 'CEP deve estar no formato 00000-000';
                    input.setAttribute('aria-invalid', 'true');
                } else {
                    if (errorEl) errorEl.textContent = '';
                    input.setAttribute('aria-invalid', 'false');
                }
            });
            APP_STATE.masks[input.id] = mask;
        });
    }

    /**
     * Configura event listeners
     */
    setupEventListeners() {
        // Navegação entre seções
        if (DOM_ELEMENTS.showRegister) {
            DOM_ELEMENTS.showRegister.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSection('register');
            });
        }

        if (DOM_ELEMENTS.showResetPassword) {
            DOM_ELEMENTS.showResetPassword.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSection('reset-password');
            });
        }

        if (DOM_ELEMENTS.backToLoginFromRegister) {
            DOM_ELEMENTS.backToLoginFromRegister.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSection('login');
            });
        }

        if (DOM_ELEMENTS.backToLoginFromReset) {
            DOM_ELEMENTS.backToLoginFromReset.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSection('login');
            });
        }

        // Dashboard actions
        if (DOM_ELEMENTS.logoutBtn) {
            DOM_ELEMENTS.logoutBtn.addEventListener('click', () => this.logout());
        }

        if (DOM_ELEMENTS.editProfileBtn) {
            DOM_ELEMENTS.editProfileBtn.addEventListener('click', () => this.editProfile());
        }

        if (DOM_ELEMENTS.viewMenuBtn) {
            DOM_ELEMENTS.viewMenuBtn.addEventListener('click', () => this.viewMenu());
        }

        // Formulários
        if (DOM_ELEMENTS.loginForm) {
            DOM_ELEMENTS.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        if (DOM_ELEMENTS.registerForm) {
            DOM_ELEMENTS.registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        if (DOM_ELEMENTS.resetPasswordForm) {
            DOM_ELEMENTS.resetPasswordForm.addEventListener('submit', (e) => this.handleResetPassword(e));
        }

        // Toggle de senhas
        this.setupPasswordToggles();

        // Validação em tempo real
        this.setupRealTimeValidation();
    }

    /**
     * Configura toggle de visibilidade de senhas
     */
    setupPasswordToggles() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                
                if (targetInput) {
                    const isPassword = targetInput.type === 'password';
                    targetInput.type = isPassword ? 'text' : 'password';
                    
                    // Atualiza ícone
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
                    }
                    
                    // Atualiza aria-label
                    button.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
                }
            });
        });
    }

    /**
     * Configura validação em tempo real
     */
    setupRealTimeValidation() {
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            // Validação com debounce para não sobrecarregar
            const debouncedValidation = Utils.debounce(() => {
                this.validateField(input);
            }, 300);

            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', debouncedValidation);
        });
    }

    /**
     * Valida campo individual
     */
    validateField(fieldElement) {
        if (!fieldElement) return;

        const fieldName = this.getFieldName(fieldElement.name);
        const fieldValue = fieldElement.value;

        const validation = this.validator.validateField(fieldName, fieldValue);
        this.validator.showFieldErrors(fieldElement, validation.errors);

        return validation.isValid;
    }

    /**
     * Mapeia nomes de campos para validação
     */
    getFieldName(fieldName) {
        const mapping = {
            'name': 'nome',
            'phone': 'telefone',
            'street': 'rua',
            'number': 'numero',
            'neighborhood': 'bairro',
            'cep': 'cep',
            'password': 'senha',
            'confirmPassword': 'senha'
        };
        return mapping[fieldName] || fieldName;
    }

    /**
     * Configura validação de formulários
     */
    setupFormValidation() {
        // Adiciona validação customizada para confirmação de senha
        if (DOM_ELEMENTS.registerForm) {
            const confirmPassword = document.getElementById('confirm-password');
            if (confirmPassword) {
                confirmPassword.addEventListener('blur', () => {
                    const password = document.getElementById('register-password').value;
                    const confirmPasswordValue = confirmPassword.value;
                    
                    if (password && confirmPasswordValue && password !== confirmPasswordValue) {
                        this.validator.showFieldErrors(confirmPassword, ['As senhas não coincidem']);
                    }
                });
            }
            const cepInput = document.getElementById('register-cep');
            if (cepInput) {
                cepInput.addEventListener('blur', () => {
                    const val = Utils.limparMascara(cepInput.value);
                    if (val.length !== 8) {
                        this.validator.showFieldErrors(cepInput, ['CEP deve ter 8 dígitos']);
                    }
                });
            }
        }
    }

    /**
     * Mostra/oculta seções
     */
    showSection(sectionName) {
        // Esconde todas as seções
        const sections = document.querySelectorAll('.form-section, .dashboard-section');
        sections.forEach(section => {
            section.classList.remove('active');
            section.style.display = 'none';
        });

        // Mostra seção solicitada
        let targetSection;
        switch (sectionName) {
            case 'login':
                targetSection = DOM_ELEMENTS.loginSection;
                APP_STATE.currentSection = 'login';
                break;
            case 'register':
                targetSection = DOM_ELEMENTS.registerSection;
                APP_STATE.currentSection = 'register';
                break;
            case 'reset-password':
                targetSection = DOM_ELEMENTS.resetPasswordSection;
                APP_STATE.currentSection = 'reset-password';
                APP_STATE.resetStep = 1;
                this.resetResetPasswordForm();
                break;
            case 'dashboard':
                targetSection = DOM_ELEMENTS.dashboardSection;
                APP_STATE.currentSection = 'dashboard';
                this.updateDashboard();
                break;
        }

        if (targetSection) {
            targetSection.style.display = 'block';
            setTimeout(() => {
                targetSection.classList.add('active');
            }, 10);
        }

        // Atualiza título da página
        this.updatePageTitle(sectionName);
    }

    /**
     * Atualiza título da página
     */
    updatePageTitle(sectionName) {
        const titles = {
            'login': 'Pizzaria São Paulo - Login',
            'register': 'Pizzaria São Paulo - Cadastro',
            'reset-password': 'Pizzaria São Paulo - Recuperar Senha',
            'dashboard': 'Pizzaria São Paulo - Meus Dados'
        };
        document.title = titles[sectionName] || 'Pizzaria São Paulo';
    }

    /**
     * Mostra/esconde loading
     */
    showLoading(show = true) {
        APP_STATE.isLoading = show;
        if (DOM_ELEMENTS.loadingOverlay) {
            DOM_ELEMENTS.loadingOverlay.style.display = show ? 'flex' : 'none';
        }
    }

    /**
     * Verifica sessão existente
     */
    checkExistingSession() {
        const session = this.storage.get(APP_CONFIG.SESSION_KEY);
        if (session && session.userId && session.expiresAt) {
            const now = new Date().getTime();
            if (now < session.expiresAt) {
                // Sessão válida
                const user = this.getUserById(session.userId);
                if (user) {
                    APP_STATE.currentUser = user;
                    this.showSection('dashboard');
                    this.notificationSystem.success('Bem-vindo de volta!');
                    return;
                }
            }
        }

        // Sem sessão ou sessão inválida
        this.showSection('login');
    }

    /**
     * Cria nova sessão
     */
    createSession(userId) {
        const session = {
            userId: userId,
            createdAt: new Date().getTime(),
            expiresAt: new Date().getTime() + APP_CONFIG.SESSION_TIMEOUT
        };

        this.storage.set(APP_CONFIG.SESSION_KEY, session);
    }

    /**
     * Remove sessão
     */
    removeSession() {
        this.storage.remove(APP_CONFIG.SESSION_KEY);
        APP_STATE.currentUser = null;
    }

    /**
     * Handle login
     */
    async handleLogin(event) {
        event.preventDefault();
        
        if (APP_STATE.isLoading) return;

        try {
            this.showLoading(true);
            
            const phone = document.getElementById('login-phone').value;
            const password = document.getElementById('login-password').value;

            // Validação básica
            if (!phone || !password) {
                this.notificationSystem.error('Preencha todos os campos');
                return;
            }

            // Limpa máscara do telefone
            const cleanPhone = Utils.limparMascara(phone);
            
            // Busca usuário
            const user = this.findUserByPhone(cleanPhone);
            if (!user) {
                this.notificationSystem.error('Usuário não encontrado');
                APP_STATE.loginAttempts++;
                return;
            }

            // Verifica senha
            if (!Utils.compararSenhas(password, user.senha)) {
                this.notificationSystem.error('Senha incorreta');
                APP_STATE.loginAttempts++;
                
                // Bloqueia após tentativas excessivas
                if (APP_STATE.loginAttempts >= APP_CONFIG.MAX_LOGIN_ATTEMPTS) {
                    this.notificationSystem.error('Muitas tentativas. Tente novamente mais tarde.');
                    // Desabilita formulário por 5 minutos
                    this.temporarilyDisableForm(DOM_ELEMENTS.loginForm, 5 * 60 * 1000);
                }
                return;
            }

            // Login bem-sucedido
            APP_STATE.currentUser = user;
            APP_STATE.loginAttempts = 0;
            
            // Atualiza último acesso
            user.ultimoAcesso = new Date().toISOString();
            this.updateUser(user);

            // Cria sessão
            this.createSession(user.id);

            // Simula delay para melhor UX
            await this.delay(APP_CONFIG.LOADING_DELAY);

            this.showSection('dashboard');
            this.notificationSystem.success('Login realizado com sucesso!');

            // Limpa formulário
            DOM_ELEMENTS.loginForm.reset();

        } catch (error) {
            console.error('Erro no login:', error);
            this.notificationSystem.error('Erro ao fazer login. Tente novamente.');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Handle register
     */
    async handleRegister(event) {
        event.preventDefault();
        
        if (APP_STATE.isLoading) return;

        try {
            this.showLoading(true);

            // Coleta dados do formulário
            const formData = new FormData(DOM_ELEMENTS.registerForm);
            const data = Object.fromEntries(formData.entries());

            // Cria objeto cliente
            const cliente = new Cliente({
                nome: Utils.capitalizarNome(data.name),
                telefone: Utils.limparMascara(data.phone),
                rua: data.street,
                numero: data.number,
                bairro: data.neighborhood,
                cep: Utils.limparMascara(data.cep),
                referencia: data.reference,
                senha: Utils.hashSenha(data.password)
            });

            // Valida cliente
            const errosValidacao = cliente.validar();
            if (errosValidacao.length > 0) {
                this.notificationSystem.error(errosValidacao[0]);
                return;
            }

            // Verifica se telefone já existe
            if (this.findUserByPhone(cliente.telefone)) {
                this.notificationSystem.error('Este telefone já está cadastrado');
                return;
            }

            // Verifica confirmação de senha
            if (data.password !== data.confirmPassword) {
                this.notificationSystem.error('As senhas não coincidem');
                return;
            }

            // Salva cliente
            this.saveUser(cliente);

            // Simula delay
            await this.delay(APP_CONFIG.LOADING_DELAY);

            this.notificationSystem.success('Cadastro realizado com sucesso!');
            
            // Limpa formulário
            DOM_ELEMENTS.registerForm.reset();
            
            // Vai para login
            this.showSection('login');

        } catch (error) {
            console.error('Erro no cadastro:', error);
            this.notificationSystem.error('Erro ao criar cadastro. Tente novamente.');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Handle reset password
     */
    async handleResetPassword(event) {
        event.preventDefault();
        
        if (APP_STATE.isLoading) return;

        try {
            this.showLoading(true);

            if (APP_STATE.resetStep === 1) {
                // Passo 1: Verificação de identidade
                const phone = document.getElementById('reset-phone').value;
                const name = document.getElementById('reset-name').value;

                if (!phone || !name) {
                    this.notificationSystem.error('Preencha todos os campos');
                    return;
                }

                // Busca usuário
                const cleanPhone = Utils.limparMascara(phone);
                const user = this.findUserByPhone(cleanPhone);
                
                if (!user) {
                    this.notificationSystem.error('Usuário não encontrado');
                    return;
                }

                // Verifica nome
                if (user.nome.toLowerCase() !== name.toLowerCase().trim()) {
                    this.notificationSystem.error('Nome não corresponde ao cadastro');
                    return;
                }

                // Avança para passo 2
                APP_STATE.resetStep = 2;
                document.getElementById('reset-step-1').style.display = 'none';
                document.getElementById('reset-step-2').style.display = 'block';
                document.getElementById('reset-btn-text').innerHTML = '<i class="fas fa-save"></i> Redefinir Senha';

                this.notificationSystem.success('Identidade verificada! Agora digite sua nova senha.');

            } else {
                // Passo 2: Redefinição de senha
                const newPassword = document.getElementById('reset-new-password').value;
                const confirmPassword = document.getElementById('reset-confirm-password').value;

                if (!newPassword || !confirmPassword) {
                    this.notificationSystem.error('Preencha todos os campos');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    this.notificationSystem.error('As senhas não coincidem');
                    return;
                }

                if (newPassword.length < 6) {
                    this.notificationSystem.error('Senha deve ter pelo menos 6 caracteres');
                    return;
                }

                // Busca usuário novamente
                const phone = document.getElementById('reset-phone').value;
                const cleanPhone = Utils.limparMascara(phone);
                const user = this.findUserByPhone(cleanPhone);

                if (!user) {
                    this.notificationSystem.error('Erro ao localizar usuário');
                    return;
                }

                // Atualiza senha
                user.senha = Utils.hashSenha(newPassword);
                this.updateUser(user);

                // Simula delay
                await this.delay(APP_CONFIG.LOADING_DELAY);

                this.notificationSystem.success('Senha redefinida com sucesso!');
                
                // Reseta formulário e volta para login
                this.resetResetPasswordForm();
                this.showSection('login');
            }

        } catch (error) {
            console.error('Erro na recuperação de senha:', error);
            this.notificationSystem.error('Erro ao redefinir senha. Tente novamente.');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Reseta formulário de recuperação de senha
     */
    resetResetPasswordForm() {
        if (DOM_ELEMENTS.resetPasswordForm) {
            DOM_ELEMENTS.resetPasswordForm.reset();
        }
        APP_STATE.resetStep = 1;
        document.getElementById('reset-step-1').style.display = 'block';
        document.getElementById('reset-step-2').style.display = 'none';
        document.getElementById('reset-btn-text').innerHTML = '<i class="fas fa-check"></i> Verificar Dados';
    }

    /**
     * Atualiza dashboard com dados do usuário
     */
    updateDashboard() {
        if (!APP_STATE.currentUser) return;

        const user = APP_STATE.currentUser;

        // Atualiza elementos do dashboard
        const nameDisplay = document.getElementById('user-name-display');
        const fullName = document.getElementById('user-full-name');
        const phoneNumber = document.getElementById('user-phone-number');
        const addressLine = document.getElementById('user-address-line');
        const neighborhoodCep = document.getElementById('user-neighborhood-cep');
        const referenceLine = document.getElementById('user-reference-line');
        const referenceText = document.getElementById('user-reference-text');

        if (nameDisplay) nameDisplay.textContent = user.nome.split(' ')[0];
        if (fullName) fullName.textContent = user.nome;
        if (phoneNumber) {
            const tel = (typeof user.getTelefoneFormatado === 'function') ? user.getTelefoneFormatado() : Utils.formatarTelefone(user.telefone || '');
            phoneNumber.textContent = tel;
        }
        if (addressLine) addressLine.textContent = `${user.rua}, ${user.numero}`;
        if (neighborhoodCep) {
            const cepFmt = (typeof user.getCEPFormatado === 'function') ? user.getCEPFormatado() : Utils.formatarCEP(user.cep || '');
            neighborhoodCep.textContent = `${user.bairro || ''} - CEP: ${cepFmt}`;
        }

        // Mostra/esconde referência
        if (referenceLine && referenceText) {
            if (user.referencia) {
                referenceText.textContent = user.referencia;
                referenceLine.style.display = 'block';
            } else {
                referenceLine.style.display = 'none';
            }
        }
    }

    /**
     * Logout
     */
    logout() {
        try {
            this.showLoading(true);
            
            // Remove sessão
            this.removeSession();
            
            // Limpa dados do usuário
            APP_STATE.currentUser = null;
            
            // Volta para login
            this.showSection('login');
            
            this.notificationSystem.success('Logout realizado com sucesso!');
            
        } catch (error) {
            console.error('Erro no logout:', error);
            this.notificationSystem.error('Erro ao fazer logout');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Editar perfil
     */
    editProfile() {
        if (!APP_STATE.currentUser) return;

        // Preenche formulário de cadastro com dados atuais
        const user = APP_STATE.currentUser;
        
        document.getElementById('register-name').value = user.nome;
        document.getElementById('register-phone').value = (typeof user.getTelefoneFormatado === 'function') ? user.getTelefoneFormatado() : Utils.formatarTelefone(user.telefone || '');
        document.getElementById('register-street').value = user.rua;
        document.getElementById('register-number').value = user.numero;
        document.getElementById('register-neighborhood').value = user.bairro;
        document.getElementById('register-cep').value = (typeof user.getCEPFormatado === 'function') ? user.getCEPFormatado() : Utils.formatarCEP(user.cep || '');
        document.getElementById('register-reference').value = user.referencia;

        // Vai para tela de cadastro (em modo edição)
        this.showSection('register');
        
        // Adiciona flag de edição
        DOM_ELEMENTS.registerForm.setAttribute('data-editing', user.id);
        
        this.notificationSystem.info('Atualize seus dados e clique em "Finalizar Cadastro" para salvar');
    }

    /**
     * Ver cardápio
     */
    viewMenu() {
        // Redireciona para cardápio
        window.location.href = 'cardapio/';
    }

    /**
     * Gerenciamento de usuários
     */
    getAllUsers() {
        return this.storage.get(APP_CONFIG.STORAGE_KEY, []);
    }

    saveUser(user) {
        const users = this.getAllUsers();
        users.push(user);
        this.storage.set(APP_CONFIG.STORAGE_KEY, users);
    }

    updateUser(updatedUser) {
        const users = this.getAllUsers();
        const index = users.findIndex(user => user.id === updatedUser.id);
        
        if (index !== -1) {
            users[index] = updatedUser;
            this.storage.set(APP_CONFIG.STORAGE_KEY, users);
        }
    }

    findUserByPhone(phone) {
        const users = this.getAllUsers();
        return users.find(user => user.telefone === phone);
    }

    getUserById(id) {
        const users = this.getAllUsers();
        return users.find(user => user.id === id);
    }

    /**
     * Desabilita formulário temporariamente
     */
    temporarilyDisableForm(form, duration) {
        if (!form) return;

        form.classList.add('disabled');
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Formulário bloqueado';
        }

        setTimeout(() => {
            form.classList.remove('disabled');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Entrar para Pedir';
            }
        }, duration);
    }

    /**
     * Tratamento de erros críticos
     */
    handleCriticalError(error) {
        console.error('Erro crítico:', error);
        
        // Mostra mensagem amigável
        const errorContainer = document.createElement('div');
        errorContainer.className = 'critical-error';
        errorContainer.innerHTML = `
            <div class="error-content">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Erro Crítico</h2>
                <p>A aplicação encontrou um erro inesperado.</p>
                <button onclick="location.reload()">Recarregar Página</button>
            </div>
        `;
        
        document.body.appendChild(errorContainer);
    }

    /**
     * Utilitário de delay
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

/**
 * ========================================
 * INICIALIZAÇÃO GLOBAL
 * ========================================
 */

// Inicializa a aplicação quando o DOM estiver pronto
let app;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        app = new PizzariaApp();
    });
} else {
    app = new PizzariaApp();
}

// Expõe a aplicação globalmente para debugging
window.PizzariaApp = app;

/**
 * ========================================
 * TRATAMENTO DE ERROS GLOBAL
 * ========================================
 */

// Captura erros não tratados
window.addEventListener('error', (event) => {
    console.error('Erro global capturado:', event.error);
    
    if (window.notificationSystem) {
        window.notificationSystem.error('Ocorreu um erro inesperado. Recarregue a página se o problema persistir.');
    }
});

// Captura promessas rejeitadas não tratadas
window.addEventListener('unhandledrejection', (event) => {
    console.error('Promessa rejeitada não tratada:', event.reason);
    
    if (window.notificationSystem) {
        window.notificationSystem.error('Ocorreu um erro inesperado na aplicação.');
    }
});

/**
 * ========================================
 * POLYFILLS E COMPATIBILIDADE
 * ========================================
 */

// Polyfill para Element.closest() - IE11
if (!Element.prototype.closest) {
    Element.prototype.closest = function(selector) {
        var element = this;
        while (element && element.nodeType === 1) {
            if (element.matches(selector)) {
                return element;
            }
            element = element.parentNode;
        }
        return null;
    };
}

// Polyfill para Element.matches() - IE11
if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}

// Polyfill para String.includes() - IE11
if (!String.prototype.includes) {
    String.prototype.includes = function(search, start) {
        'use strict';
        if (typeof start !== 'number') {
            start = 0;
        }
        
        if (start + search.length > this.length) {
            return false;
        } else {
            return this.indexOf(search, start) !== -1;
        }
    };
}

/**
 * ========================================
 * FIM DO ARQUIVO
 * ========================================
 */