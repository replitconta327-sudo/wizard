# Pizzaria São Paulo - Sistema de Pedidos Online

## Visão Geral
Sistema completo de pedidos de pizza com fluxo wizard mobile (8 passos) e painel admin com autenticação segura, controle total do banco de dados e gerenciamento de pedidos com notificações.

**Stack:** PHP 8.2 + SQLite + JavaScript puro (vanilla)

## Status: ✅ MVP COMPLETO + ADMIN SEGURO

### 🔗 Links de Acesso
- **Cliente:** `/cardapio/` - Fluxo wizard de pedidos
- **Admin Login:** `/admin/login.php`
  - 📱 Telefone: `11999999999`
  - 🔒 Senha: `admin123`
  - 🔄 Recuperar senha: Link disponível na tela de login
- **Admin Dashboard:** `/admin/dashboard.php`
- **Configurações:** `/admin/configuracoes.php`

### 🍕 Cliente - Fluxo de Pedido (8 Passos)
1. **Tamanho** - Escolher P/M/G
2. **Modo** - Escolher 1/2/3 sabores
3. **Sabores** - Selecionar pizzas (tradicionais/premium/doces)
4. **Carrinho** - Gerenciar pizzas (pode pedir várias diferentes)
5. **Adicionais** - Queijo extra, bacon, etc
6. **Bebidas** - Refrigerantes, água
7. **Endereço** - Selecionar ou cadastrar novo
8. **Revisão** - Confirmar e enviar pedido

### 👨‍💼 Admin - Painel Profissional + Autenticação Segura
**Design:** Sidebar preto fixo à esquerda (100vh) + conteúdo à direita

#### Segurança & Autenticação:
- ✅ **Login seguro** com Prepared Statements (sem SQL Injection)
- ✅ **Senhas criptografadas** com bcrypt
- ✅ **Recuperar Senha** - Sistema de token/sessão de recuperação
- ✅ **Registro de Admin** - Criação de novas contas administrativas
- ✅ **Sessões protegidas** - Redirecionamento automático se não autenticado

#### Funcionalidades:
1. **Dashboard** - Estatísticas: Total Pedidos, Pedidos Hoje, Clientes, Faturamento
2. **Gerenciar Pedidos** - Lista com notificações em tempo real, filtros e impressão
3. **Configurações com CRUD Completo:**
   - 🍕 **Pizzas** - Adicionar, editar, deletar, categorizar
   - 🍹 **Bebidas** - Adicionar, editar, deletar, controlar estoque
   - 📍 **Bairros** - Adicionar, editar, deletar, configurar taxas e tempos
   - ➕ **Adicionais** - Adicionar, editar, deletar (extras e preços)
   - 🎁 **Promoções** - Adicionar, editar, deletar (nome, descrição, preços, descontos)
   - 📊 **Status** - Editar status de pedidos (nome, descrição, cor)

### 🖨️ Impressão de Comanda
- Formato otimizado para impressora térmica 80mm
- Inclui: número pedido, cliente, telefone, total, data e hora

### 📱 Responsivo
- Desktop: Sidebar fixo 100vh + scroll conteúdo
- Tablet: Layout adaptado
- Mobile: Sidebar no topo + coluna

### 📂 Estrutura
```
/cardapio/index.php          → Interface wizard cliente
/admin/
  /login.php                 → Login com Prepared Statements ✅
  /registro.php              → Criar novo admin (Prepared Statements) ✅
  /recuperar_senha.php       → Recuperar senha com validação ✅
  /dashboard.php             → Dashboard com estatísticas
  /pedidos.php               → Gerenciamento com notificação e impressão
  /configuracoes.php         → CRUD completo de todos os dados ✅
/api/
  /admin_config.php          → CRUD API (criar, atualizar, deletar) ✅
  /get_config.php            → GET de dados com IDs para edição ✅
  /criar_pedido.php          → Salva novo pedido
  /atualizar_status.php      → Atualiza status do pedido
  /verificar_pedidos.php     → Verifica novos pedidos em tempo real (polling 3s)
  /get_pedidos.php           → Lista pedidos com filtros
  /enderecos.php             → CRUD endereços
  /get_tamanhos.php          → Lista tamanhos
/config/
  /database.php              → SQLite com 16 tabelas
```

## 🗄️ Banco de Dados (SQLite)
**Tabelas Principais:**
- usuarios (com tipo='admin' e 'cliente'), categorias, tamanhos_pizza, produtos
- bebidas_categorias, bebidas, bairros, enderecos, status_pedido
- pedidos, pedido_itens, pedido_bebidas, motoboys, entregas
- adicionais, promocoes, admin_logs

**Dados Iniciais:**
- 46 pizzas | 7 bebidas | 8 bairros | 6 status | 4 adicionais | 3 promoções

## Design System
- **Cor Primária:** Vermelho #DC2626
- **Cor de Sucesso:** Verde #10B981
- **Cor Info:** Azul #3B82F6
- **Cor Erro:** Vermelho #C33
- **Tipografia:** Inter sans-serif
- **Layout:** Sidebar fixo 250px + conteúdo responsivo

## 🔐 Segurança Implementada
- ✅ Prepared Statements em todas as queries (SQL Injection prevention)
- ✅ Bcrypt para hash de senhas (PASSWORD_BCRYPT)
- ✅ Session-based authentication
- ✅ Admin user type validation
- ✅ Recuperação de senha com validação de sessão
- ✅ XSS protection com htmlspecialchars()

---
**Última atualização:** 30/11/2025 - Login seguro com Prepared Statements, Recuperar Senha, CRUD completo em Configurações
