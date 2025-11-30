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

### 👨‍💼 Admin - Painel Profissional
**Design:** Sidebar vermelho fixo à esquerda (100vh) + conteúdo à direita

#### Funcionalidades:
1. **Notificação em Tempo Real** - Banner verde automático quando novo pedido chega
2. **Dashboard com Estatísticas** - Total Pedidos, Clientes, Faturamento, Hoje
3. **Aba Pedidos** - Lista completa com rastreamento, status, filtros e busca
4. **Aba Clientes** - Base de clientes com histórico de pedidos
5. **Impressão de Comanda** - Botão Print para comanda térmica 80mm

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
  /pedidos.php               → Dashboard com notificação e impressão
/api/
  /criar_pedido.php          → Salva novo pedido
  /atualizar_status.php      → Atualiza status do pedido
  /verificar_pedidos.php     → Verifica novos pedidos em tempo real
  /enderecos.php             → CRUD endereços
  /get_tamanhos.php          → Lista tamanhos
```

## Design System
- **Cor Primária:** Vermelho #DC2626
- **Cor de Sucesso:** Verde #10B981
- **Cor Info:** Azul #3B82F6
- **Tipografia:** Inter sans-serif
- **Layout:** Sidebar + main responsivo

---
**Última atualização:** 30/11/2025 - Layout completo reformulado e responsivo

