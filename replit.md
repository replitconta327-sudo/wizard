# Pizzaria São Paulo - Sistema de Pedidos

## Visão Geral
Sistema de pedidos online para Pizzaria São Paulo com interface mobile-first e wizard de pedidos.

## Estrutura do Projeto

```
/
├── index.html          # Página de login/cadastro
├── config.php          # Configuração principal
├── config/
│   ├── database.php    # Classe de conexão SQLite
│   ├── login_corrigido.php
│   ├── register_corrigido.php
│   ├── reset_request.php
│   ├── reset_confirm.php
│   ├── update_profile.php
│   └── cardapio_data.php
├── api/
│   ├── get_tamanhos.php
│   ├── enderecos.php
│   ├── bairros.php
│   └── pedidos.php
├── cardapio/
│   └── index.php       # Wizard do cardápio
├── pages/
│   ├── bebidas.php
│   ├── confirmacao.php
│   ├── historico.php
│   ├── pagamento.php
│   └── revisao.php
├── assets/
│   ├── css/            # Estilos
│   ├── js/             # JavaScript
│   └── img/            # Imagens
└── data/
    └── pizzaria.db     # Banco SQLite (auto-criado)
```

## Tecnologias
- PHP 8.2
- SQLite (PDO)
- HTML5/CSS3/JavaScript vanilla
- Font Awesome para ícones

## Funcionalidades
- Login/Cadastro de clientes
- Cardápio wizard (Tamanho > Sabores > Adicionais > Endereço > Bebidas > Revisão)
- Sistema de pedidos
- Gerenciamento de endereços
- Cálculo automático de taxa de entrega por bairro

## Banco de Dados
O banco SQLite é criado automaticamente no primeiro acesso em `data/pizzaria.db`.

## Como Executar
O servidor PHP está configurado na porta 5000:
```bash
php -S 0.0.0.0:5000
```

## Categorias do Cardápio
- Pizzas Tradicionais
- Pizzas Premium
- Pizzas Doces
- Calzones
- Adicionais
- Bebidas
- Promoções
