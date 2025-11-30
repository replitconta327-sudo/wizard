# 🍕 Pizzaria São Paulo - Sistema de Pedidos Online

Sistema completo de pedidos de pizza com painel admin profissional, autenticação segura e suporte para SQLite (desenvolvimento) e MySQL (produção).

## 🚀 Início Rápido

### Local (Desenvolvimento)

```bash
# 1. Copie o arquivo de configuração
cp .env.example .env

# 2. Configure para usar SQLite (padrão)
# O arquivo .env já vem configurado

# 3. Acesse o sistema
# Cliente: http://localhost:5000/cardapio/
# Admin: http://localhost:5000/admin/login.php
```

**Login Admin:**
- 📱 Telefone: `11999999999`
- 🔒 Senha: `admin123`

### Produção (HostGator)

Veja o guia completo em **[CONFIGURACAO.md](CONFIGURACAO.md)**

## 📋 Recursos

✅ **Cliente**
- Fluxo wizard 8 passos para pedidos
- Múltiplas pizzas por pedido
- Carrinho dinâmico
- Seleção de adicionais e bebidas
- Endereço inteligente

✅ **Admin**
- Dashboard com estatísticas
- Gerenciamento de pedidos em tempo real
- CRUD completo de:
  - Pizzas e categorias
  - Bebidas
  - Bairros e taxas
  - Adicionais
  - Promoções
  - Status de pedidos
- Autenticação segura com bcrypt
- Recuperação de senha
- Registro de novos admins

## 🗄️ Banco de Dados

**SQLite:** Para desenvolvimento local (automático)
**MySQL:** Para produção em HostGator (via migrations/mysql_schema.sql)

## 📁 Estrutura

```
/cardapio/              → Interface cliente
/admin/                 → Painel administrativo
  /login.php            → Login seguro
  /registro.php         → Criar novo admin
  /recuperar_senha.php  → Recuperar acesso
  /dashboard.php        → Dashboard
  /pedidos.php          → Gerenciar pedidos
  /configuracoes.php    → CRUD de dados
/api/                   → APIs REST
/config/                → Configurações
  /database.php         → Conexão BD
  /env.php              → Leitor de .env
/migrations/            → Scripts SQL
```

## 🔐 Segurança

- ✅ Prepared Statements (prevenção SQL Injection)
- ✅ Bcrypt para senhas
- ✅ Session-based authentication
- ✅ Variáveis de ambiente para configuração

## 📝 Variáveis de Ambiente

Veja todas as opções em `.env.example` ou consulte **[CONFIGURACAO.md](CONFIGURACAO.md)**

```env
DB_TYPE=sqlite                      # sqlite ou mysql
DB_MYSQL_HOST=seu-host.mysql.com   # Para MySQL
DB_MYSQL_DATABASE=seu_banco         # Para MySQL
```

## 🎨 Design

- Interface responsiva (mobile/tablet/desktop)
- Sidebar fixo na navegação
- Cores: Vermelho (#DC2626), Verde (#10B981), Azul (#3B82F6)
- Tipografia: Inter sans-serif

## 📞 Credenciais Padrão

| Campo | Valor |
|-------|-------|
| Telefone | 11999999999 |
| Senha | admin123 |

## 📚 Documentação

- **[CONFIGURACAO.md](CONFIGURACAO.md)** - Guia de configuração completo
- **[replit.md](replit.md)** - Documentação técnica
- **[migrations/mysql_schema.sql](migrations/mysql_schema.sql)** - Schema do banco de dados

## 🚀 Deploy

1. Copie os arquivos para seu servidor HostGator
2. Crie arquivo `.env` com suas credenciais MySQL
3. Execute o script SQL em PhpMyAdmin
4. Acesse `seu-site.com/admin/login.php`

## ✨ Features Principais

- 🍕 46 pizzas pré-configuradas
- 🍹 7 bebidas cadastradas
- 📍 8 bairros com taxas de entrega
- 💳 Suporte a múltiplas formas de pagamento
- 🔔 Notificações de pedidos em tempo real (polling)
- 🖨️ Impressão de comanda
- 📱 Design totalmente responsivo

## 📄 Licença

Privado - Desenvolvido para Pizzaria São Paulo

---

**Última atualização:** 30/11/2025
