# 🍕 Guia de Configuração - Pizzaria São Paulo

## 📋 Variáveis de Ambiente (.env)

O sistema agora usa variáveis de ambiente configuráveis. Existem dois modos:

### 1️⃣ **Desenvolvimento Local (SQLite)**

Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Configure assim:
```env
APP_ENV=development
DB_TYPE=sqlite
DB_SQLITE_PATH=data/pizzaria.db
```

**Credenciais Admin Padrão:**
- 📱 Telefone: `11999999999`
- 🔒 Senha: `admin123`

---

### 2️⃣ **Produção (HostGator - MySQL)**

Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Configure com seus dados do HostGator:

```env
APP_ENV=production
DB_TYPE=mysql
DB_MYSQL_HOST=seu-host.mysql.com
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=seu_banco_dados
DB_MYSQL_USERNAME=seu_usuario_mysql
DB_MYSQL_PASSWORD=sua_senha_mysql
```

**⚠️ Importante:**
1. Nunca commitar o arquivo `.env` (está no `.gitignore`)
2. Guardá-lo em local seguro
3. Não compartilhar com terceiros

---

## 🗄️ Preparar Banco MySQL no HostGator

### Passo 1: Executar o Script SQL

1. Acesse o **PhpMyAdmin** da sua conta HostGator
2. Crie um novo banco de dados
3. Clique em **"SQL"** 
4. Cole TODO o conteúdo de `migrations/mysql_schema.sql`
5. Clique em **"Executar"**

O banco estará pronto com:
- ✅ 16 tabelas
- ✅ Admin padrão (11999999999 / admin123)
- ✅ 46 pizzas
- ✅ 8 bairros
- ✅ Todas as configurações iniciais

---

## 📝 Todas as Variáveis Disponíveis

```env
# Ambiente
APP_ENV=development                 # development ou production
APP_DEBUG=true                       # true ou false
APP_NAME=Pizzaria São Paulo

# Banco SQLite (Local)
DB_TYPE=sqlite                       # Tipo de BD
DB_SQLITE_PATH=data/pizzaria.db     # Caminho do arquivo

# Banco MySQL (HostGator)
DB_TYPE=mysql                        # Tipo de BD
DB_MYSQL_HOST=seu-host.mysql.com    # Host do MySQL
DB_MYSQL_PORT=3306                   # Porta MySQL
DB_MYSQL_DATABASE=seu_banco          # Nome do banco
DB_MYSQL_USERNAME=seu_usuario        # Usuário MySQL
DB_MYSQL_PASSWORD=sua_senha          # Senha MySQL

# Segurança
SESSION_NAME=pizzaria_session        # Nome da sessão
SESSION_LIFETIME=86400               # Tempo em segundos
BCRYPT_ROUNDS=10                     # Rounds bcrypt

# Admin Padrão
ADMIN_PHONE=11999999999              # Telefone admin
ADMIN_PASSWORD=admin123              # Senha admin
ADMIN_NAME=Admin                     # Nome admin

# Pizzaria
PIZZARIA_NOME=Pizzaria São Paulo
PIZZARIA_TELEFONE=(11) 9 9999-9999
PIZZARIA_EMAIL=contato@pizzaria.com
PIZZARIA_ENDERECO=Rua São Paulo, 123
PIZZARIA_HORA_ABERTURA=11:00
PIZZARIA_HORA_FECHAMENTO=23:00

# Localização
PIZZARIA_CIDADE=Guarapari
PIZZARIA_UF=ES
PIZZARIA_PAIS=Brasil

# Entrega
TAXA_ENTREGA_MINIMA=5.00
TAXA_ENTREGA_MAXIMA=12.00
TEMPO_ENTREGA_MINIMO=25
TEMPO_ENTREGA_MAXIMO=60

# Pagamento
ACEITA_DINHEIRO=true
ACEITA_CARTAO=true
ACEITA_PIX=false
```

---

## 🚀 Deploy no HostGator

### 1. Fazer upload dos arquivos

```bash
# Via FTP ou seu gerenciador de arquivos
# Envie toda a pasta do projeto
```

### 2. Criar arquivo `.env` no servidor

No painel do HostGator, criar um novo arquivo `.env` na raiz do projeto com:

```env
APP_ENV=production
DB_TYPE=mysql
DB_MYSQL_HOST=seu-host.mysql.com
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=seu_banco_dados
DB_MYSQL_USERNAME=seu_usuario_mysql
DB_MYSQL_PASSWORD=sua_senha_mysql
```

### 3. Testar

Acesse: `seu-site.com/admin/login.php`

Login com:
- 📱 Telefone: `11999999999`
- 🔒 Senha: `admin123`

---

## ✅ Checklist de Segurança

- [ ] Arquivo `.env` criado (não commitar)
- [ ] Senhas MySQL configuradas corretamente
- [ ] Banco de dados criado e populado
- [ ] Arquivo `migrations/mysql_schema.sql` executado
- [ ] `.env` adicionado ao `.gitignore`
- [ ] Teste de login funciona
- [ ] Configurações acessíveis no admin

---

## 🔄 Mudar entre SQLite e MySQL

Basta alterar a variável `DB_TYPE` no `.env`:

**Para SQLite:**
```env
DB_TYPE=sqlite
DB_SQLITE_PATH=data/pizzaria.db
```

**Para MySQL:**
```env
DB_TYPE=mysql
DB_MYSQL_HOST=seu-host.com
DB_MYSQL_DATABASE=seu_banco
DB_MYSQL_USERNAME=seu_user
DB_MYSQL_PASSWORD=sua_senha
```

Salve e recarregue a página!

---

## 📞 Suporte

Para mais informações, consulte a documentação em `replit.md`.
