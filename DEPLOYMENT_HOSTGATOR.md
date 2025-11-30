# 🚀 Deploy na HostGator - Guia Completo

## ✅ Checklist Rápido
- [ ] Conta HostGator ativa
- [ ] Acesso a cPanel/PhpMyAdmin
- [ ] FTP ou File Manager
- [ ] 5 minutos

---

## 📝 PASSO 1: Criar Banco de Dados no HostGator

### 1.1 Acessar PhpMyAdmin
1. Acesse cPanel da sua conta HostGator
2. Procure por **"PhpMyAdmin"** ou **"Gerenciador de Banco de Dados"**
3. Clique para abrir

### 1.2 Criar Novo Banco
1. No menu esquerdo, clique em **"Novo"**
2. Digite o nome do banco (ex: `pizzaria_sp`)
3. Selecione **"utf8mb4"** como codificação
4. Clique em **"Criar"**

### 1.3 Executar SQL
1. Selecione o banco que acabou de criar
2. Clique na aba **"SQL"**
3. Cole TODO o conteúdo do arquivo: **`migrations/mysql_schema.sql`**
4. Clique em **"Executar"**

**✅ Banco pronto com 16 tabelas + dados iniciais!**

---

## 📤 PASSO 2: Upload dos Arquivos

### 2.1 Preparar Arquivos
Você vai enviar TODOS os arquivos do projeto EXCETO:
- ❌ `.git` (pasta inteira)
- ❌ `.gitignore`
- ❌ `.env` (vou criar no servidor)
- ❌ Arquivo `DEPLOYMENT_HOSTGATOR.md` (este arquivo)

### 2.2 Fazer Upload via FTP

**Usando Filezilla (recomendado):**
1. Baixe e instale Filezilla
2. Em cPanel, procure por **"Contas FTP"** ou **"SFTP"**
3. Crie uma nova conta FTP
4. Abra Filezilla e conecte com os dados FTP
5. Navegue para a pasta pública do seu domínio (geralmente `public_html/`)
6. Arraste TODOS os arquivos do projeto para lá

**Ou usando File Manager no cPanel:**
1. Acesse cPanel → File Manager
2. Navegue para `public_html`
3. Upload via "Upload Files"
4. Selecione todos os arquivos do projeto e envie

**Resultado esperado:**
```
public_html/
├── /cardapio/
├── /admin/
├── /api/
├── /config/
├── /migrations/
├── /data/
├── .env.example
├── README.md
├── CONFIGURACAO.md
└── [outros arquivos]
```

---

## ⚙️ PASSO 3: Criar Arquivo `.env` no Servidor

### 3.1 Via File Manager
1. Acesse cPanel → File Manager
2. Navegue para `public_html`
3. Clique em **"Criar Novo Arquivo"**
4. Nome: `.env`
5. Clique em **"Criar"**

### 3.2 Editar `.env`
1. Clique com botão direito em `.env`
2. Selecione **"Editar"**
3. Cole o conteúdo abaixo (SUBSTITUINDO os valores):

```env
# ============================================
# Pizzaria São Paulo - HostGator Production
# ============================================

APP_ENV=production
APP_DEBUG=false
APP_NAME="Pizzaria São Paulo"

# ============================================
# BANCO DE DADOS - MySQL HostGator
# ============================================
DB_TYPE=mysql
DB_MYSQL_HOST=localhost
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=seu_banco_dados_aqui
DB_MYSQL_USERNAME=seu_usuario_mysql_aqui
DB_MYSQL_PASSWORD=sua_senha_mysql_aqui

# ============================================
# SEGURANÇA
# ============================================
SESSION_NAME=pizzaria_session
SESSION_LIFETIME=86400
BCRYPT_ROUNDS=10

# Admin Padrão
ADMIN_PHONE=11999999999
ADMIN_PASSWORD=admin123
ADMIN_NAME=Admin

# ============================================
# CONFIGURAÇÕES DA PIZZARIA
# ============================================
PIZZARIA_NOME="Pizzaria São Paulo"
PIZZARIA_TELEFONE="(11) 9 9999-9999"
PIZZARIA_EMAIL="contato@pizzaria.com"
PIZZARIA_ENDERECO="Sua rua, número - Guarapari, ES"
PIZZARIA_HORA_ABERTURA=11:00
PIZZARIA_HORA_FECHAMENTO=23:00

PIZZARIA_CIDADE=Guarapari
PIZZARIA_UF=ES
PIZZARIA_PAIS=Brasil

# ============================================
# ENTREGA
# ============================================
TAXA_ENTREGA_MINIMA=5.00
TAXA_ENTREGA_MAXIMA=12.00
TEMPO_ENTREGA_MINIMO=25
TEMPO_ENTREGA_MAXIMO=60

# ============================================
# PAGAMENTO
# ============================================
ACEITA_DINHEIRO=true
ACEITA_CARTAO=true
ACEITA_PIX=false
```

**⚠️ IMPORTANTE:**
- `DB_MYSQL_DATABASE` → Substitua pelo nome do banco criado no Passo 1
- `DB_MYSQL_USERNAME` → Seu usuário MySQL (encontra em cPanel → Contas MySQL)
- `DB_MYSQL_PASSWORD` → Sua senha MySQL

4. Salve o arquivo

---

## 🧪 PASSO 4: Testar

### 4.1 Testar Login Admin
1. Acesse: `seu-dominio.com/admin/login.php`
2. Use:
   - 📱 Telefone: `11999999999`
   - 🔒 Senha: `admin123`
3. Se entrar no dashboard → ✅ Funcionando!

### 4.2 Testar Cardápio Cliente
1. Acesse: `seu-dominio.com/cardapio/`
2. Veja as pizzas listadas
3. Se aparecer → ✅ Funcionando!

### 4.3 Testar Configurações
1. Vá para: `seu-dominio.com/admin/dashboard.php`
2. Clique em **"Configurações"**
3. Veja as pizzas, bebidas, bairros, etc.
4. Se listar tudo → ✅ Funcionando!

---

## 🔐 PASSO 5: Segurança (Importante!)

### 5.1 Remover Arquivo Desnecessário
Após fazer upload, delete via File Manager:
- ❌ `.env.example` (não precisa no servidor)
- ❌ `CONFIGURACAO.md`
- ❌ `README.md`
- ❌ `DEPLOYMENT_HOSTGATOR.md` (este arquivo)

### 5.2 Proteger `.env`
O arquivo `.env` nunca deve ser acessível publicamente. HostGator já protege arquivos que começam com `.`

### 5.3 Alterar Senha Admin
1. Acesse o painel admin
2. Vá para `/admin/recuperar_senha.php`
3. Altere a senha do admin padrão (11999999999)

---

## 📱 URLS Finais

Seu sistema estará disponível em:

| Seção | URL |
|-------|-----|
| **Cardápio** | `seu-dominio.com/cardapio/` |
| **Login Admin** | `seu-dominio.com/admin/login.php` |
| **Dashboard** | `seu-dominio.com/admin/dashboard.php` |
| **Pedidos** | `seu-dominio.com/admin/pedidos.php` |
| **Configurações** | `seu-dominio.com/admin/configuracoes.php` |

---

## 🆘 Se Algo Não Funcionar

### Erro: "Database connection failed"
- Verifique o arquivo `.env`
- Confirme que `DB_MYSQL_HOST`, `DB_MYSQL_DATABASE`, `DB_MYSQL_USERNAME` estão corretos

### Erro: "Table doesn't exist"
- O script SQL não foi executado
- Volte ao Passo 1.3 e execute o arquivo `migrations/mysql_schema.sql`

### Login não funciona
- Confirme que o banco foi criado com as tabelas
- Tente a senha padrão: `admin123`

### Página em branco
- Ative o debug em `.env`: `APP_DEBUG=true`
- Verifique a versão do PHP (precisa PHP 7.4+)

---

## 📞 Resumo do Deployment

1. ✅ Criar banco de dados no PhpMyAdmin
2. ✅ Executar SQL (`migrations/mysql_schema.sql`)
3. ✅ Upload de todos os arquivos do projeto
4. ✅ Criar arquivo `.env` com dados MySQL
5. ✅ Testar login em `/admin/login.php`
6. ✅ Pronto! Sistema funcionando!

**Tempo estimado:** 5-10 minutos

---

**Suporte:** Se tiver dúvidas, consulte `CONFIGURACAO.md` ou `README.md`
