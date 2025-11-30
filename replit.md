# Pizzaria São Paulo - Sistema de Pedidos Online

## Visão Geral
Sistema completo de pedidos de pizza com fluxo wizard mobile (8 passos) e painel admin para gerenciamento com notificações e impressão de comanda. 

**Stack:** PHP 8.2 + SQLite + JavaScript puro (vanilla)

## Status: ✅ MVP COMPLETO

### 🔗 Links de Acesso
- **Cliente:** `/cardapio/` - Fluxo wizard de pedidos
- **Admin Demo:** Clique em "Acessar Painel Admin" na homepage
- **Admin Dashboard:** `/admin/pedidos.php`

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
**Localização:** `/admin/pedidos.php`

**Design:** Sidebar vermelho + cards brancos, interface clean e profissional

#### Funcionalidades:
1. **Notificação em Tempo Real**
   - Banner verde automático quando novo pedido chega
   - Mostra cliente e número do pedido
   - Atualiza a cada 5 segundos
   - Auto-recarrega quando há novo pedido

2. **Dashboard com Estatísticas**
   - Total de pedidos
   - Total de clientes
   - Faturamento total
   - Pedidos de hoje

3. **Aba Pedidos**
   - Lista completa com todas as informações
   - Rastreamento com badge de status
   - Dropdown para mudar status em tempo real
   - Botão **Imprimir Comanda** (formatada 80mm para impressora térmica)
   - Busca por número ou cliente
   - Filtro por status
   - Link para detalhes completo

4. **Aba Clientes**
   - Lista de clientes cadastrados
   - Busca por nome
   - Dados: Nome, Telefone, Email, Data Cadastro
   - Contagem de pedidos por cliente

### 🖨️ Impressão de Comanda
- Botão "Imprimir" em azul em cada pedido
- Formato otimizado para impressora térmica 80mm
- Inclui: número pedido, cliente, telefone, total
- Data e hora do pedido
- Mensagem de agradecimento

### 📱 APIs
- `POST /api/criar_pedido.php` - Cria novo pedido no banco
- `POST /api/atualizar_status.php` - Atualiza status do pedido
- `GET /api/verificar_pedidos.php` - Verifica novos pedidos em tempo real
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
- Rastreamento de status em tempo real
- Notificação automática de novo pedido
- Impressão de comanda formatada para 80mm

### 📂 Estrutura
```
/cardapio/index.php          → Interface wizard cliente
/assets/
  /css/pages/cardapio.css    → Estilos do wizard
  /css/style.css             → Estilos principais com design system
  /js/pages/cardapio.js      → Lógica do wizard
/admin/
  /pedidos.php               → Dashboard com notificação e impressão
/api/
  /criar_pedido.php          → Salva novo pedido
  /atualizar_status.php      → Atualiza status do pedido
  /verificar_pedidos.php     → Verifica novos pedidos em tempo real
  /enderecos.php             → CRUD endereços
  /get_tamanhos.php          → Lista tamanhos
/config/
  /database.php              → Conexão SQLite
  /cardapio_data.php         → Dados em JSON
```

## Design System
- **Cor Primária:** Vermelho #DC2626 (pizza)
- **Cor Secundária:** Laranja #F59E0B (queijo)
- **Cor de Sucesso:** Verde #10B981 (entregue/notificação)
- **Cor Info:** Azul #3B82F6 (ações)
- **Tipografia:** Inter sans-serif
- **Espaçamento:** Escala modular (4px base)
- **Sombras:** Sutis para profundidade
- **Transições:** 150-350ms ease-in-out

## Workflow
- **Pizzaria Server** - PHP dev server na porta 5000

## Próximas Melhorias (Opcionais)
- [ ] Autenticação admin com login seguro
- [ ] Som de notificação para novo pedido
- [ ] Relatórios de vendas por período
- [ ] Sistema de promoções/cupons
- [ ] Notificações por email/SMS
- [ ] API pública para integração
- [ ] Modo escuro
- [ ] Exportar pedidos em PDF
- [ ] Histórico de pedidos do cliente

---
**Última atualização:** 30/11/2025 - Notificação de novo pedido e impressão de comanda implementadas

