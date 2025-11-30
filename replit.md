# Pizzaria São Paulo - Sistema de Pedidos Online

## Visão Geral
Sistema completo de pedidos de pizza com fluxo wizard mobile (8 passos) e painel admin para gerenciamento. 

**Stack:** PHP 8.2 + SQLite + JavaScript puro (vanilla)

## Status: ✅ MVP COMPLETO

### 🔗 Links de Acesso
- **Cliente:** `/cardapio/` - Fluxo wizard de pedidos
- **Admin Demo:** Clique em "Acessar Painel Admin" na homepage
- **Admin Dashboard:** `/admin/dashboard.php`
- **Admin Pedidos:** `/admin/pedidos.php`

### 🍕 Cliente - Fluxo de Pedido (8 Passos)
1. **Tamanho** - Escolher P/M/G
2. **Modo** - Escolher 1/2/3 sabores
3. **Sabores** - Selecionar pizzas (tradicionais/premium/doces)
4. **Carrinho** - Gerenciar pizzas (pode pedir várias diferentes)
5. **Adicionais** - Queijo extra, bacon, etc
6. **Bebidas** - Refrigerantes, água
7. **Endereço** - Selecionar ou cadastrar novo
8. **Revisão** - Confirmar e enviar pedido

**Características:**
- ✅ Múltiplas pizzas com diferentes tamanhos/sabores na mesma encomenda
- ✅ Meio a meio (2 ou 3 sabores em 1 pizza)
- ✅ Carrinho interativo (+/- quantidade, editar, remover)
- ✅ Editar pedido antes de confirmar
- ✅ Persistência de estado (localStorage/sessionStorage)

### 👨‍💼 Admin - Painel Completo
**Localização:** `/admin/`

#### Páginas:
1. **dashboard.php** - Estatísticas gerais
   - Total de pedidos
   - Pedidos hoje
   - Pendentes
   - Total vendido
   - Gráfico dos últimos 7 dias

2. **pedidos.php** - Lista de pedidos
   - Resumo: Total, Hoje, Total Vendido
   - Busca por cliente/número
   - Filtro por status
   - Tabela com pedidos (50 últimos)
   - Link para detalhes

3. **pedido_detalhes.php** - Detalhes completo
   - Informações do pedido
   - Dados do cliente (nome, tel, email)
   - Endereço de entrega
   - Itens (pizzas, adicionais, bebidas)
   - Total com breakdown
   - Gerenciar status (dropdown + atualizar)
   - Botão imprimir

### 📱 APIs
- `POST /api/criar_pedido.php` - Cria novo pedido no banco
- `POST /api/atualizar_pedido.php` - Atualiza status do pedido
- `GET /api/enderecos.php?action=list` - Lista endereços do usuário
- `POST /api/enderecos.php?action=add` - Cadastra novo endereço
- `GET /api/get_tamanhos.php` - Lista tamanhos

### 🗄️ Banco de Dados (SQLite)
Tabelas principais:
- `usuarios` - Clientes
- `enderecos` - Endereços de entrega
- `pedidos` - Pedidos com número único, status, total
- `pedido_itens` - Pizzas, adicionais
- `pedido_bebidas` - Bebidas do pedido
- `tamanhos_pizza` - P/M/G com fatias
- `status_pedido` - Novo, Confirmado, Entregue, Cancelado
- `categorias` - Tradicionais, Premium, Doces
- `produtos` - Sabores de pizza (46 no total)
- `adicionais` - Queijo, bacon, etc
- `bebidas` - Refrigerantes, água, etc

### 🔑 Dados Padrão
- 3 tamanhos (Pequena 6f, Média 8f, Grande 12f)
- 46 sabores de pizza em 3 categorias
- 3 adicionais (queijo, bacon, cogumelo)
- 3 bebidas (coca, guaraná, água)
- 13 bairros com taxa de entrega

### 📝 Notas Técnicas
- Fluxo validado em cada passo
- Números de pedido: PED-YYYYMMDDHHMM-XXXXXX
- Estado persistido em localStorage/sessionStorage
- Suporta múltiplas pizzas na mesma encomenda
- Admin requer autenticação (session)
- Todos os totais: pizzas + adicionais + bebidas + taxa entrega

### 📂 Estrutura
```
/cardapio/index.php          → Interface wizard cliente
/assets/
  /css/pages/cardapio.css    → Estilos do wizard
  /js/pages/cardapio.js      → Lógica do wizard
/admin/
  /pedidos.php               → Lista de pedidos
  /pedido_detalhes.php       → Detalhes completo
  /dashboard.php             → Estatísticas
/api/
  /criar_pedido.php          → Salva novo pedido
  /atualizar_pedido.php      → Atualiza status
  /enderecos.php             → CRUD endereços
  /get_tamanhos.php          → Lista tamanhos
/config/
  /database.php              → Conexão SQLite
  /cardapio_data.php         → Dados em JSON
```

## Workflow
- **Pizzaria Server** - PHP dev server na porta 5000

## Próximas Melhorias (Opcionais)
- [ ] Autenticação admin com login
- [ ] Relatórios de vendas por período
- [ ] Sistema de promoções/cupons
- [ ] Notificações por email/SMS
- [ ] API pública para integração
- [ ] Modo escuro

---
**Última atualização:** 30/11/2025 - MVP Completo
