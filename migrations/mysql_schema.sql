-- Schema MySQL para Pizzaria São Paulo
-- Para HostGator e servidores MySQL
-- Última atualização: 30/11/2025

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefone VARCHAR(20) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    cpf VARCHAR(14),
    data_nascimento DATE,
    tipo ENUM('cliente', 'admin') DEFAULT 'cliente',
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    icone VARCHAR(50) DEFAULT 'fa-pizza-slice',
    ordem INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tamanhos de pizza
CREATE TABLE IF NOT EXISTS tamanhos_pizza (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(100),
    fatias INT NOT NULL,
    pessoas VARCHAR(50),
    ordem INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produtos (pizzas)
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco_p DECIMAL(10,2),
    preco_m DECIMAL(10,2),
    preco_g DECIMAL(10,2),
    preco_gg DECIMAL(10,2),
    imagem VARCHAR(255),
    disponivel BOOLEAN DEFAULT TRUE,
    destaque BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    INDEX idx_categoria (categoria_id),
    INDEX idx_disponivel (disponivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de bebidas categorias
CREATE TABLE IF NOT EXISTS bebidas_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    icone VARCHAR(50),
    cor VARCHAR(7) DEFAULT '#333333',
    ordem INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de bebidas
CREATE TABLE IF NOT EXISTS bebidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    volume VARCHAR(20),
    preco DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(255),
    estoque INT DEFAULT 100,
    ativo BOOLEAN DEFAULT TRUE,
    destaque BOOLEAN DEFAULT FALSE,
    ordem INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES bebidas_categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de bairros e taxas de entrega
CREATE TABLE IF NOT EXISTS bairros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    cidade VARCHAR(100) NOT NULL DEFAULT 'Guarapari',
    uf CHAR(2) NOT NULL DEFAULT 'ES',
    taxa_entrega DECIMAL(10,2) NOT NULL DEFAULT 5.00,
    tempo_estimado INT DEFAULT 30,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_bairro (nome, cidade, uf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de endereços
CREATE TABLE IF NOT EXISTS enderecos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    apelido VARCHAR(50) NOT NULL,
    logradouro VARCHAR(255) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100),
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL DEFAULT 'Guarapari',
    estado VARCHAR(2) NOT NULL DEFAULT 'ES',
    cep VARCHAR(10) NOT NULL,
    padrao BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de status de pedidos
CREATE TABLE IF NOT EXISTS status_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    cor VARCHAR(7) DEFAULT '#333333',
    ordem INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    endereco_id INT NOT NULL,
    status_id INT NOT NULL DEFAULT 1,
    numero_pedido VARCHAR(20) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    taxa_entrega DECIMAL(10,2) DEFAULT 0,
    desconto DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'cartao', 'pix') NOT NULL,
    troco DECIMAL(10,2) DEFAULT 0,
    observacoes TEXT,
    previsao_entrega DATETIME,
    entregue_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
    FOREIGN KEY (status_id) REFERENCES status_pedido(id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_status (status_id),
    INDEX idx_numero (numero_pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    tamanho ENUM('P', 'M', 'G', 'GG') DEFAULT 'M',
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de bebidas em pedidos
CREATE TABLE IF NOT EXISTS pedido_bebidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    bebida_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (bebida_id) REFERENCES bebidas(id),
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de motoboys
CREATE TABLE IF NOT EXISTS motoboys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    cnh VARCHAR(20),
    placa_moto VARCHAR(10),
    modelo_moto VARCHAR(50),
    cor_moto VARCHAR(30),
    foto VARCHAR(255),
    ativo BOOLEAN DEFAULT TRUE,
    disponivel BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de entregas
CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    motoboy_id INT,
    status_entrega ENUM('pendente', 'atribuida', 'coletada', 'em_transito', 'entregue', 'cancelada') DEFAULT 'pendente',
    observacoes TEXT,
    atribuida_em DATETIME NULL,
    coletada_em DATETIME NULL,
    entregue_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (motoboy_id) REFERENCES motoboys(id) ON DELETE SET NULL,
    INDEX idx_pedido (pedido_id),
    INDEX idx_motoboy (motoboy_id),
    INDEX idx_status (status_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de adicionais (extras)
CREATE TABLE IF NOT EXISTS adicionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de promoções
CREATE TABLE IF NOT EXISTS promocoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    desconto DECIMAL(10,2) DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs administrativos
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    detalhes TEXT,
    ip VARCHAR(45),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (criado_em),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DADOS INICIAIS
-- ============================================================================

-- Admin padrão (Telefone: 11999999999 | Senha: admin123)
INSERT IGNORE INTO usuarios (id, nome, telefone, senha, tipo, ativo) VALUES
(1, 'Admin', '11999999999', '$2y$10$N40781CrdfJXM8rnBwtP7.d0wL19m4VvKvU2sAXCVDfmhapb6JZ..', 'admin', 1);

-- Status de Pedidos
INSERT IGNORE INTO status_pedido (id, nome, descricao, cor, ordem) VALUES
(1, 'Aguardando', 'Pedido aguardando confirmação', '#FFA500', 1),
(2, 'Confirmado', 'Pedido confirmado', '#17a2b8', 2),
(3, 'Preparando', 'Pedido em preparação', '#007BFF', 3),
(4, 'Saiu para Entrega', 'Pedido saiu para entrega', '#28A745', 4),
(5, 'Entregue', 'Pedido entregue', '#198754', 5),
(6, 'Cancelado', 'Pedido cancelado', '#DC3545', 6)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO categorias (id, nome, descricao, icone, ordem) VALUES
(1, 'Pizzas Tradicionais', 'Sabores clássicos que todo mundo ama', 'fa-pizza-slice', 1),
(2, 'Pizzas Premium', 'Ingredientes especiais e sabores únicos', 'fa-star', 2),
(3, 'Pizzas Doces', 'Para adoçar seu pedido', 'fa-candy-cane', 3),
(4, 'Calzones', 'Pizza fechada recheada', 'fa-bread-slice', 4)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO tamanhos_pizza (id, nome, descricao, fatias, pessoas, ordem) VALUES
(1, 'Pequena', '4 fatias', 4, '1-2 pessoas', 1),
(2, 'Média', '8 fatias', 8, '2-3 pessoas', 2),
(3, 'Grande', '12 fatias', 12, '3-4 pessoas', 3),
(4, 'Gigante', '16 fatias', 16, '4-5 pessoas', 4)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO bairros (nome, cidade, uf, taxa_entrega, tempo_estimado) VALUES
('Centro', 'Guarapari', 'ES', 5.00, 25),
('Muquiçaba', 'Guarapari', 'ES', 6.00, 30),
('Praia do Morro', 'Guarapari', 'ES', 7.00, 35),
('Aeroporto', 'Guarapari', 'ES', 6.00, 30),
('Itapebussu', 'Guarapari', 'ES', 8.00, 40),
('Santa Mônica', 'Guarapari', 'ES', 7.00, 35),
('Meaípe', 'Guarapari', 'ES', 10.00, 45),
('Setiba', 'Guarapari', 'ES', 12.00, 50)
ON DUPLICATE KEY UPDATE taxa_entrega=VALUES(taxa_entrega);

INSERT INTO bebidas_categorias (id, nome, icone, cor, ordem) VALUES
(1, 'Refrigerantes', 'fa-glass-whiskey', '#e74c3c', 1),
(2, 'Sucos', 'fa-blender', '#f39c12', 2),
(3, 'Águas', 'fa-tint', '#3498db', 3),
(4, 'Cervejas', 'fa-beer', '#f1c40f', 4)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO bebidas (nome, categoria_id, volume, preco, estoque) VALUES
('Coca-Cola', 1, '350ml', 5.00, 100),
('Coca-Cola', 1, '2L', 12.00, 50),
('Guaraná Antarctica', 1, '350ml', 4.50, 100),
('Guaraná Antarctica', 1, '2L', 10.00, 50),
('Fanta Laranja', 1, '350ml', 4.50, 80),
('Sprite', 1, '350ml', 4.50, 80),
('Água Mineral', 3, '500ml', 3.00, 200),
('Água com Gás', 3, '500ml', 3.50, 100),
('Suco de Laranja', 2, '300ml', 6.00, 50),
('Suco de Maracujá', 2, '300ml', 6.00, 50),
('Heineken', 4, '330ml', 8.00, 60),
('Brahma', 4, '350ml', 5.00, 80)
ON DUPLICATE KEY UPDATE preco=VALUES(preco);

INSERT INTO adicionais (nome, preco, ativo) VALUES
('Catupiry Original', 12.00, 1),
('Bacon', 10.00, 1),
('Mussarela Extra', 12.00, 1),
('Cheddar', 10.00, 1)
ON DUPLICATE KEY UPDATE preco=VALUES(preco);

INSERT INTO promocoes (nome, descricao, preco, desconto, ativo) VALUES
('Promo 8 Fatias', 'Escolha 2 pizzas de qualquer categoria por um preço especial', 99.90, 15.00, 1),
('Dupla + Guaraná', 'Leve 2 pizzas + Guaraná Coroa 2L', 109.90, 10.00, 1),
('Combo Familiar', '1 pizza grande + 2 refrigerantes', 89.90, 5.00, 1)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

INSERT INTO produtos (categoria_id, nome, descricao, preco_p, preco_m, preco_g, disponivel, destaque) VALUES
(1, 'Calabresa', 'Calabresa fatiada, cebola e orégano', 32.00, 45.00, 55.00, 1, 1),
(1, 'Mussarela', 'Mussarela, tomate e orégano', 30.00, 42.00, 52.00, 1, 1),
(1, 'Portuguesa', 'Presunto, ovo, cebola, ervilha e mussarela', 35.00, 48.00, 58.00, 1, 0),
(1, 'Frango Catupiry', 'Frango desfiado com catupiry', 35.00, 48.00, 58.00, 1, 1),
(1, 'Marguerita', 'Mussarela, tomate, manjericão e parmesão', 32.00, 45.00, 55.00, 1, 0),
(1, 'Pepperoni', 'Pepperoni e mussarela', 35.00, 48.00, 58.00, 1, 0),
(1, 'Quatro Queijos', 'Mussarela, provolone, parmesão e gorgonzola', 38.00, 52.00, 62.00, 1, 1),
(2, 'Camarão', 'Camarão, mussarela e catupiry', 45.00, 60.00, 75.00, 1, 1),
(2, 'Lombo Canadense', 'Lombo canadense, cebola caramelizada e mussarela', 42.00, 55.00, 68.00, 1, 0),
(2, 'Filé Mignon', 'Filé mignon, bacon e mussarela', 48.00, 65.00, 80.00, 1, 1),
(3, 'Chocolate', 'Chocolate ao leite com granulado', 28.00, 38.00, 48.00, 1, 1),
(3, 'Romeu e Julieta', 'Goiabada com queijo minas', 30.00, 40.00, 50.00, 1, 0),
(3, 'Banana Nevada', 'Banana, leite condensado, canela e mussarela', 30.00, 40.00, 50.00, 1, 0)
ON DUPLICATE KEY UPDATE preco_p=VALUES(preco_p);
