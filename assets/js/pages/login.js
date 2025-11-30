// Sistema de Gerenciamento de Usuários com API MySQL
class UserManager {
    constructor() {
        // Manter currentUser no localStorage para persistência da sessão
        this.currentUser = JSON.parse(localStorage.getItem('currentUser')) || null;
        this.resetToken = null; // Token para reset de senha
        this.isEditMode = false; // Flag para modo de edição
        this.initEventListeners();
        this.checkAuthStatus();
    }

    // Função utilitária para converter objeto em FormData
    toFormData(obj) {
        const formData = new FormData();
        Object.entries(obj).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });
        return formData;
    }

    // API: Cadastro de usuário
    async apiRegister(userData) {
        try {
            const response = await fetch('config/register_corrigido.php', {
                method: 'POST',
                body: this.toFormData(userData)
            });
            return await response.json();
        } catch (error) {
            console.error('Erro na API de cadastro:', error);
            return { ok: false, msg: 'Erro de conexão com servidor' };
        }
    }

    // API: Login de usuário
    async apiLogin(phone, password) {
        try {
            const response = await fetch('config/login_corrigido.php', {
                method: 'POST',
                body: this.toFormData({ phone, password, start_session: true })
            });
            return await response.json();
        } catch (error) {
            console.error('Erro na API de login:', error);
            return { ok: false, msg: 'Erro de conexão com servidor' };
        }
    }

    // API: Solicitar reset de senha
    async apiResetRequest(phone, name) {
        try {
            const response = await fetch('config/reset_request.php', {
                method: 'POST',
                body: this.toFormData({ phone, name })
            });
            return await response.json();
        } catch (error) {
            console.error('Erro na API de reset request:', error);
            return { ok: false, msg: 'Erro de conexão com servidor' };
        }
    }

    // API: Confirmar reset de senha
    async apiResetConfirm(token, newPassword, confirmPassword) {
        try {
            const response = await fetch('config/reset_confirm.php', {
                method: 'POST',
                body: this.toFormData({ token, newPassword, confirmPassword })
            });
            return await response.json();
        } catch (error) {
            console.error('Erro na API de reset confirm:', error);
            return { ok: false, msg: 'Erro de conexão com servidor' };
        }
    }

    // API: Atualizar perfil do usuário
    async apiUpdateProfile(userData) {
        try {
            const response = await fetch('config/update_profile.php', {
                method: 'POST',
                body: this.toFormData(userData)
            });
            return await response.json();
        } catch (error) {
            console.error('Erro na API de atualização:', error);
            return { ok: false, msg: 'Erro de conexão com servidor' };
        }
    }

    initEventListeners() {
        // Formulário de login
        document.getElementById('login-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin(e.target);
        });

        // Formulário de cadastro
        document.getElementById('register-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister(e.target);
        });

        // Formulário de redefinição de senha
        document.getElementById('reset-password-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handlePasswordReset(e.target);
        });

        // Links para alternar entre login e cadastro
        document.getElementById('show-register').addEventListener('click', (e) => {
            e.preventDefault();
            this.showRegisterForm();
        });

        document.getElementById('show-login').addEventListener('click', (e) => {
            e.preventDefault();
            this.showLoginForm();
        });

        // Links para redefinição de senha
        document.getElementById('show-reset-password').addEventListener('click', (e) => {
            e.preventDefault();
            this.showResetPasswordForm();
        });

        document.getElementById('back-to-login').addEventListener('click', (e) => {
            e.preventDefault();
            this.showLoginForm();
        });

        // Botão de logout
        document.getElementById('logout-btn').addEventListener('click', () => {
            this.logout();
        });

        // Botão de editar dados
        document.getElementById('edit-profile-btn').addEventListener('click', () => {
            this.showEditForm();
        });

        // Formatação automática do telefone e CEP
        this.setupPhoneFormatting();
        this.setupCepFormatting();
    }

    setupPhoneFormatting() {
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.formatPhone(e.target);
            });
        });
    }

    setupCepFormatting() {
        const cepInput = document.getElementById('register-cep');
        if (!cepInput) return;
        const useIMask = typeof window.IMask === 'function' && cepInput.hasAttribute('data-mask');
        if (!useIMask) {
            cepInput.addEventListener('input', (e) => {
                this.formatCep(e.target);
            });
        }
        cepInput.addEventListener('blur', () => {
            const cleaned = this.cleanCep(cepInput.value);
            const errorEl = document.getElementById('register-cep-error');
            if (cleaned.length !== 8) {
                if (errorEl) errorEl.textContent = 'CEP deve estar no formato 00000-000';
                cepInput.setAttribute('aria-invalid', 'true');
            } else {
                if (errorEl) errorEl.textContent = '';
                cepInput.setAttribute('aria-invalid', 'false');
            }
        });
    }

    formatPhone(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.length >= 11) {
            value = value.substring(0, 11);
            input.value = `(${value.substring(0, 2)}) ${value.substring(2, 7)}-${value.substring(7, 11)}`;
        } else if (value.length >= 7) {
            input.value = `(${value.substring(0, 2)}) ${value.substring(2, 7)}-${value.substring(7)}`;
        } else if (value.length >= 2) {
            input.value = `(${value.substring(0, 2)}) ${value.substring(2)}`;
        } else {
            input.value = value;
        }
    }

    formatCep(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.length >= 8) {
            value = value.substring(0, 8);
            input.value = `${value.substring(0, 5)}-${value.substring(5, 8)}`;
        } else if (value.length >= 5) {
            input.value = `${value.substring(0, 5)}-${value.substring(5)}`;
        } else {
            input.value = value;
        }
    }

    async handleLogin(form) {
        const formData = new FormData(form);
        const phone = formData.get('phone');
        const password = formData.get('password');

        // Validar campos
        if (!phone || !password) {
            this.showMessage('Por favor, preencha todos os campos.', 'error');
            return;
        }

        // Mostrar loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Entrando...';

        try {
            // Chamar API de login
            const result = await this.apiLogin(phone, password);

            if (result.ok) {
                // Login bem-sucedido
                this.currentUser = result.user;
                localStorage.setItem('currentUser', JSON.stringify(result.user));
                const target = result.redirect || (result.user.tipo === 'admin' ? '/admin/' : '/cardapio/');
                this.showMessage('🍕 Bem-vindo! Redirecionando...', 'success');
                setTimeout(() => {
                    try { window.location.assign(target); } catch (e) { window.location.href = target; }
                }, 800);
            } else {
                this.showMessage(result.msg || 'Erro no login', 'error');
            }
        } catch (error) {
            this.showMessage('Erro de conexão. Tente novamente.', 'error');
            const link = document.createElement('a');
            link.href = '/';
            link.textContent = 'Voltar à página inicial';
            link.className = 'btn-primary';
            document.body.appendChild(link);
        } finally {
            // Restaurar botão
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    async handleRegister(form) {
        const formData = new FormData(form);
        const name = formData.get('name').trim();
        const phone = formData.get('phone');
        const street = formData.get('street').trim();
        const number = formData.get('number').trim();
        const neighborhood = formData.get('neighborhood').trim();
        const cep = formData.get('cep');
        const reference = formData.get('reference') ? formData.get('reference').trim() : '';
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');

        // Validações frontend
        if (this.isEditMode) {
            // Modo edição: senha é opcional
            if (!name || !phone || !street || !number || !neighborhood || !cep) {
                this.showMessage('Por favor, preencha todos os campos obrigatórios.', 'error');
                return;
            }
            
            // Validar senha apenas se fornecida
            if (password && password.length < 6) {
                this.showMessage('A senha deve ter pelo menos 6 caracteres.', 'error');
                return;
            }
            
            if (password && password !== confirmPassword) {
                this.showMessage('As senhas não coincidem.', 'error');
                return;
            }
        } else {
            // Modo cadastro: tudo obrigatório
            if (!name || !phone || !street || !number || !neighborhood || !cep || !password || !confirmPassword) {
                this.showMessage('Por favor, preencha todos os campos obrigatórios.', 'error');
                return;
            }
            
            if (password.length < 6) {
                this.showMessage('A senha deve ter pelo menos 6 caracteres.', 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                this.showMessage('As senhas não coincidem.', 'error');
                return;
            }
        }

        if (name.length < 2) {
            this.showMessage('Nome deve ter pelo menos 2 caracteres.', 'error');
            return;
        }

        // Mostrar loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;

        try {
            let result;
            
            if (this.isEditMode) {
                // Modo de edição - atualizar dados existentes
                submitBtn.textContent = 'Salvando...';
                
                const userData = {
                    name,
                    phone,
                    street,
                    number,
                    neighborhood,
                    cep,
                    reference,
                    user_id: this.currentUser.id
                };

                // Incluir senha apenas se fornecida
                if (password) {
                    userData.password = password;
                }

                result = await this.apiUpdateProfile(userData);

                if (result.ok) {
                    // Atualizar dados locais
                    this.currentUser = { ...this.currentUser, ...result.user };
                    localStorage.setItem('currentUser', JSON.stringify(this.currentUser));
                    
                    this.showMessage('✅ Dados atualizados com sucesso!', 'success');
                    this.cancelEdit();
                } else {
                    this.showMessage(result.msg || 'Erro ao atualizar dados', 'error');
                }
            } else {
                // Modo de cadastro normal
                submitBtn.textContent = 'Cadastrando...';
                
                const userData = {
                    name,
                    phone,
                    street,
                    number,
                    neighborhood,
                    cep,
                    reference,
                    password
                };

                result = await this.apiRegister(userData);

                if (result.ok) {
                    this.showMessage('🎉 Cadastro realizado! Fazendo login automático...', 'success');
                    
                    // Login automático após cadastro
                    setTimeout(async () => {
                        const loginResult = await this.apiLogin(phone, password);
                        if (loginResult.ok) {
                            this.currentUser = loginResult.user;
                            localStorage.setItem('currentUser', JSON.stringify(loginResult.user));
                            window.location.href = 'cardapio/';
                        } else {
                            form.reset();
                            this.showLoginForm();
                        }
                    }, 1000);
                } else {
                    this.showMessage(result.msg || 'Erro no cadastro', 'error');
                }
            }
        } catch (error) {
            this.showMessage('Erro de conexão. Tente novamente.', 'error');
        } finally {
            // Restaurar botão
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    async handlePasswordReset(form) {
        const formData = new FormData(form);
        const phone = formData.get('phone');
        const name = formData.get('name').trim();
        const newPassword = formData.get('newPassword');
        const confirmNewPassword = formData.get('confirmNewPassword');

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        // Primeira etapa: verificar telefone e nome
        if (!this.resetToken) {
            if (!phone || !name) {
                this.showMessage('Por favor, preencha o telefone e o nome.', 'error');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Verificando...';

            try {
                const result = await this.apiResetRequest(phone, name);

                if (result.ok) {
                    this.resetToken = result.token;
                    this.showNewPasswordFields();
                    submitBtn.textContent = 'Redefinir Senha';
                    this.showMessage('Dados confirmados! Agora defina sua nova senha.', 'success');
                } else {
                    this.showMessage(result.msg || 'Erro na verificação', 'error');
                }
            } catch (error) {
                this.showMessage('Erro de conexão. Tente novamente.', 'error');
            } finally {
                submitBtn.disabled = false;
                if (!this.resetToken) {
                    submitBtn.textContent = originalText;
                }
            }
            return;
        }

        // Segunda etapa: redefinir senha com token
        if (!newPassword || !confirmNewPassword) {
            this.showMessage('Por favor, preencha a nova senha e confirmação.', 'error');
            return;
        }

        if (newPassword.length < 6) {
            this.showMessage('A nova senha deve ter pelo menos 6 caracteres.', 'error');
            return;
        }

        if (newPassword !== confirmNewPassword) {
            this.showMessage('As senhas não coincidem.', 'error');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Redefinindo...';

        try {
            const result = await this.apiResetConfirm(this.resetToken, newPassword, confirmNewPassword);

            if (result.ok) {
                this.showMessage('Senha redefinida com sucesso! Faça login com a nova senha.', 'success');
                form.reset();
                this.hideNewPasswordFields();
                this.resetToken = null;
                this.showLoginForm();
            } else {
                this.showMessage(result.msg || 'Erro ao redefinir senha', 'error');
            }
        } catch (error) {
            this.showMessage('Erro de conexão. Tente novamente.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    cleanPhone(phone) {
        return phone ? phone.replace(/\D/g, '') : '';
    }

    cleanCep(cep) {
        return cep ? cep.replace(/\D/g, '') : '';
    }

    isValidCep(cep) {
        const cleaned = this.cleanCep(cep);
        return cleaned.length === 8;
    }

    checkAuthStatus() {
        if (this.currentUser) {
            this.showDashboard();
        }
    }

    showLoginForm() {
        this.hideAllContainers();
        document.getElementById('login-container').style.display = 'block';
    }

    showRegisterForm() {
        this.hideAllContainers();
        document.getElementById('register-container').style.display = 'block';
    }

    showResetPasswordForm() {
        this.hideAllContainers();
        document.getElementById('reset-password-container').style.display = 'block';
        this.hideNewPasswordFields();
        this.resetToken = null; // Resetar token quando mostrar o formulário
        document.getElementById('reset-submit-btn').textContent = 'Verificar Dados';
    }

    showDashboard() {
        this.hideAllContainers();
        document.getElementById('dashboard').style.display = 'block';
        this.updateDashboard();
    }

    hideAllContainers() {
        document.getElementById('login-container').style.display = 'none';
        document.getElementById('register-container').style.display = 'none';
        document.getElementById('reset-password-container').style.display = 'none';
        document.getElementById('dashboard').style.display = 'none';
    }

    showEditForm() {
        // Preenche o formulário de cadastro com os dados atuais do usuário
        this.hideAllContainers();
        
        // Preencher campos com dados atuais
        if (this.currentUser) {
            document.getElementById('register-name').value = this.currentUser.nome || this.currentUser.name || '';
            document.getElementById('register-phone').value = this.currentUser.telefone || this.currentUser.phone || '';
            
            // Dados da API MySQL (campos diretos) ou localStorage antigo (objeto address)
            if (this.currentUser.rua) {
                // Dados da API MySQL
                document.getElementById('register-street').value = this.currentUser.rua || '';
                document.getElementById('register-number').value = this.currentUser.numero || '';
                document.getElementById('register-neighborhood').value = this.currentUser.bairro || '';
                document.getElementById('register-cep').value = this.currentUser.cep || '';
                document.getElementById('register-reference').value = this.currentUser.referencia || '';
            } else if (this.currentUser.address && typeof this.currentUser.address === 'object') {
                // Dados antigos do localStorage
                document.getElementById('register-street').value = this.currentUser.address.street || '';
                document.getElementById('register-number').value = this.currentUser.address.number || '';
                document.getElementById('register-neighborhood').value = this.currentUser.address.neighborhood || '';
                document.getElementById('register-cep').value = this.currentUser.address.cep || '';
                document.getElementById('register-reference').value = this.currentUser.address.reference || '';
            }
        }
        
        // Mostrar o formulário de cadastro como editor
        document.getElementById('register-container').style.display = 'block';
        
        // Alterar o título e botão para indicar que é edição
        document.querySelector('#register-container h2').textContent = 'Editar Seus Dados';
        document.querySelector('#register-form button[type="submit"]').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salvar Alterações';
        
        // Tornar campos de senha opcionais no modo de edição
        document.getElementById('register-password').required = false;
        document.getElementById('confirm-password').required = false;
        document.getElementById('register-password').placeholder = 'Deixe em branco para manter a senha atual';
        document.getElementById('confirm-password').placeholder = 'Deixe em branco para manter a senha atual';
        
        // Adicionar texto explicativo para senhas
        const passwordGroup = document.getElementById('register-password').parentNode;
        if (!document.getElementById('password-help-text')) {
            const helpText = document.createElement('small');
            helpText.id = 'password-help-text';
            helpText.className = 'form-text';
            helpText.textContent = 'Deixe os campos de senha em branco se não quiser alterar sua senha atual.';
            passwordGroup.appendChild(helpText);
        }
        
        // Adicionar botão de cancelar se não existir
        if (!document.getElementById('cancel-edit-btn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn-secondary';
            cancelBtn.id = 'cancel-edit-btn';
            cancelBtn.innerHTML = '<i class="fa-solid fa-times"></i> Cancelar';
            
            const submitBtn = document.querySelector('#register-form button[type="submit"]');
            submitBtn.parentNode.insertBefore(cancelBtn, submitBtn.nextSibling);
            
            cancelBtn.addEventListener('click', () => {
                this.cancelEdit();
            });
        } else {
            document.getElementById('cancel-edit-btn').style.display = 'block';
        }
        
        // Marcar como modo de edição
        this.isEditMode = true;
    }

    cancelEdit() {
        this.isEditMode = false;
        
        // Restaurar título e botão original
        document.querySelector('#register-container h2').textContent = 'Cadastro de Cliente';
        document.querySelector('#register-form button[type="submit"]').innerHTML = 'Finalizar Cadastro';
        
        // Restaurar campos de senha como obrigatórios
        document.getElementById('register-password').required = true;
        document.getElementById('confirm-password').required = true;
        document.getElementById('register-password').placeholder = 'Mínimo 6 caracteres';
        document.getElementById('confirm-password').placeholder = 'Digite a senha novamente';
        
        // Remover texto explicativo das senhas
        const helpText = document.getElementById('password-help-text');
        if (helpText) {
            helpText.remove();
        }
        
        // Esconder botão cancelar
        const cancelBtn = document.getElementById('cancel-edit-btn');
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
        
        // Limpar formulário
        document.getElementById('register-form').reset();
        
        // Voltar para o dashboard
        this.showDashboard();
    }

    showNewPasswordFields() {
        const newPasswordGroup = document.getElementById('new-password-group');
        const confirmPasswordGroup = document.getElementById('confirm-new-password-group');
        
        newPasswordGroup.style.display = 'block';
        confirmPasswordGroup.style.display = 'block';
        
        // Tornar os campos obrigatórios
        document.getElementById('reset-new-password').required = true;
        document.getElementById('reset-confirm-password').required = true;
    }

    hideNewPasswordFields() {
        const newPasswordGroup = document.getElementById('new-password-group');
        const confirmPasswordGroup = document.getElementById('confirm-new-password-group');
        
        newPasswordGroup.style.display = 'none';
        confirmPasswordGroup.style.display = 'none';
        
        // Remover obrigatoriedade e limpar campos
        document.getElementById('reset-new-password').required = false;
        document.getElementById('reset-confirm-password').required = false;
        document.getElementById('reset-new-password').value = '';
        document.getElementById('reset-confirm-password').value = '';
    }

    updateDashboard() {
        if (this.currentUser) {
            document.getElementById('user-name').textContent = this.currentUser.nome || this.currentUser.name;
            document.getElementById('user-name-welcome').textContent = (this.currentUser.nome || this.currentUser.name).split(' ')[0]; // Primeiro nome apenas
            document.getElementById('user-phone').textContent = this.currentUser.telefone || this.currentUser.phone;
            
            // Dados da API MySQL (campos diretos) ou localStorage antigo (objeto address)
            if (this.currentUser.rua) {
                // Dados da API MySQL
                document.getElementById('user-street').textContent = this.currentUser.rua;
                document.getElementById('user-number').textContent = this.currentUser.numero;
                document.getElementById('user-neighborhood').textContent = this.currentUser.bairro;
                document.getElementById('user-cep').textContent = this.currentUser.cep;
                
                // Mostrar referência se existir
                if (this.currentUser.referencia) {
                    document.getElementById('user-reference').textContent = this.currentUser.referencia;
                    document.getElementById('user-reference-display').style.display = 'block';
                } else {
                    document.getElementById('user-reference-display').style.display = 'none';
                }
            } else if (this.currentUser.address && typeof this.currentUser.address === 'object') {
                // Dados antigos do localStorage
                document.getElementById('user-street').textContent = this.currentUser.address.street;
                document.getElementById('user-number').textContent = this.currentUser.address.number;
                document.getElementById('user-neighborhood').textContent = this.currentUser.address.neighborhood;
                document.getElementById('user-cep').textContent = this.currentUser.address.cep;
                
                if (this.currentUser.address.reference) {
                    document.getElementById('user-reference').textContent = this.currentUser.address.reference;
                    document.getElementById('user-reference-display').style.display = 'block';
                } else {
                    document.getElementById('user-reference-display').style.display = 'none';
                }
            } else {
                // Fallback
                document.getElementById('user-street').textContent = 'N/A';
                document.getElementById('user-number').textContent = 'N/A';
                document.getElementById('user-neighborhood').textContent = 'N/A';
                document.getElementById('user-cep').textContent = 'N/A';
                document.getElementById('user-reference-display').style.display = 'none';
            }
        }
    }

    formatPhoneDisplay(phone) {
        if (phone.length === 11) {
            return `(${phone.substring(0, 2)}) ${phone.substring(2, 7)}-${phone.substring(7, 11)}`;
        }
        return phone;
    }

    formatCepDisplay(cep) {
        if (cep && cep.length === 8) {
            return `${cep.substring(0, 5)}-${cep.substring(5, 8)}`;
        }
        return cep;
    }

    logout() {
        this.currentUser = null;
        localStorage.removeItem('currentUser');
        this.showMessage('Logout realizado com sucesso!', 'success');
        this.showLoginForm();
        
        // Limpar formulários
        document.getElementById('login-form').reset();
        document.getElementById('register-form').reset();
        document.getElementById('reset-password-form').reset();
        this.hideNewPasswordFields();
    }

    showMessage(message, type = 'info') {
        const container = document.getElementById('message-container');
        const messageEl = document.createElement('div');
        messageEl.className = `message ${type}`;
        messageEl.textContent = message;

        container.appendChild(messageEl);

        // Remover mensagem após 5 segundos
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.parentNode.removeChild(messageEl);
            }
        }, 5000);
    }

    // Método para desenvolvedores - listar todos os usuários (apenas para debug)
    getAllUsers() {
        return this.users;
    }

    // Método para desenvolvedores - limpar todos os dados
    clearAllData() {
        if (confirm('Tem certeza que deseja limpar todos os dados? Esta ação não pode ser desfeita.')) {
            localStorage.clear();
            this.users = [];
            this.currentUser = null;
            this.showLoginForm();
            this.showMessage('Todos os dados foram limpos.', 'success');
        }
    }
}

// Funções auxiliares para validação
class Validator {
    static isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10 && cleaned.length <= 11;
    }

    static isValidName(name) {
        return name.length >= 2 && /^[a-zA-ZÀ-ÿ\s]+$/.test(name);
    }

    static isStrongPassword(password) {
        return password.length >= 6;
    }
}

// Inicializar o sistema quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.userManager = new UserManager();
    
    // Adicionar algumas funções globais para debug (apenas em desenvolvimento)
    window.debugUsers = () => window.userManager.getAllUsers();
    window.clearData = () => window.userManager.clearAllData();
    
    console.log('Sistema de Login e Cadastro iniciado!');
    console.log('Comandos de debug disponíveis:');
    console.log('- debugUsers(): lista todos os usuários');
    console.log('- clearData(): limpa todos os dados');
});