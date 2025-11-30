<?php
class Database {
    private $pdo;
    
    public function __construct() {
        $dbPath = __DIR__ . '/../data/pizzaria.db';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        try {
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $this->initDatabase();
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function pdo() {
        return $this->pdo;
    }
    
    private function initDatabase() {
        $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetchColumn();
        
        if (!$tables) {
            $this->createTables();
            $this->insertInitialData();
        }
    }
    
    private function createTables() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT UNIQUE,
            telefone TEXT UNIQUE NOT NULL,
            senha TEXT NOT NULL,
            cpf TEXT,
            data_nascimento DATE,
            tipo TEXT DEFAULT 'cliente',
            ativo INTEGER DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            descricao TEXT,
            icone TEXT DEFAULT 'fa-pizza-slice',
            ordem INTEGER DEFAULT 0,
            ativo INTEGER DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tamanhos_pizza (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL UNIQUE,
            descricao TEXT,
            fatias INTEGER NOT NULL,
            pessoas TEXT,
            ordem INTEGER DEFAULT 0,
            ativo INTEGER DEFAULT 1
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria_id INTEGER NOT NULL,
            nome TEXT NOT NULL,
            descricao TEXT,
            preco_p REAL,
            preco_m REAL,
            preco_g REAL,
            preco_gg REAL,
            imagem TEXT,
            disponivel INTEGER DEFAULT 1,
            destaque INTEGER DEFAULT 0,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS bebidas_categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL UNIQUE,
            icone TEXT,
            cor TEXT DEFAULT '#333333',
            ordem INTEGER DEFAULT 0,
            ativo INTEGER DEFAULT 1
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS bebidas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categoria_id INTEGER,
            nome TEXT NOT NULL,
            descricao TEXT,
            volume TEXT,
            preco REAL NOT NULL,
            imagem TEXT,
            estoque INTEGER DEFAULT 100,
            ativo INTEGER DEFAULT 1,
            destaque INTEGER DEFAULT 0,
            ordem INTEGER DEFAULT 0,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES bebidas_categorias(id) ON DELETE SET NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS bairros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            cidade TEXT NOT NULL DEFAULT 'Guarapari',
            uf TEXT NOT NULL DEFAULT 'ES',
            taxa_entrega REAL NOT NULL DEFAULT 5.00,
            tempo_estimado INTEGER DEFAULT 30,
            ativo INTEGER DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(nome, cidade, uf)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS enderecos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            apelido TEXT NOT NULL,
            logradouro TEXT NOT NULL,
            numero TEXT NOT NULL,
            complemento TEXT,
            bairro TEXT NOT NULL,
            cidade TEXT NOT NULL DEFAULT 'Guarapari',
            estado TEXT NOT NULL DEFAULT 'ES',
            cep TEXT NOT NULL,
            padrao INTEGER DEFAULT 0,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS status_pedido (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            descricao TEXT,
            cor TEXT DEFAULT '#333333',
            ordem INTEGER DEFAULT 0
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS pedidos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            endereco_id INTEGER NOT NULL,
            status_id INTEGER NOT NULL DEFAULT 1,
            numero_pedido TEXT UNIQUE NOT NULL,
            subtotal REAL NOT NULL,
            taxa_entrega REAL DEFAULT 0,
            desconto REAL DEFAULT 0,
            total REAL NOT NULL,
            forma_pagamento TEXT NOT NULL,
            troco REAL DEFAULT 0,
            observacoes TEXT,
            previsao_entrega DATETIME,
            entregue_em DATETIME,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (endereco_id) REFERENCES enderecos(id),
            FOREIGN KEY (status_id) REFERENCES status_pedido(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS pedido_itens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pedido_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            quantidade INTEGER NOT NULL DEFAULT 1,
            tamanho TEXT DEFAULT 'M',
            preco_unitario REAL NOT NULL,
            subtotal REAL NOT NULL,
            observacoes TEXT,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
            FOREIGN KEY (produto_id) REFERENCES produtos(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS pedido_bebidas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pedido_id INTEGER NOT NULL,
            bebida_id INTEGER NOT NULL,
            quantidade INTEGER NOT NULL DEFAULT 1,
            preco_unitario REAL NOT NULL,
            subtotal REAL NOT NULL,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
            FOREIGN KEY (bebida_id) REFERENCES bebidas(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS adicionais (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            preco REAL NOT NULL,
            ativo INTEGER DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS promocoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            descricao TEXT,
            preco REAL NOT NULL,
            desconto REAL DEFAULT 0,
            ativo INTEGER DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS motoboys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            telefone TEXT NOT NULL,
            cnh TEXT,
            placa_moto TEXT,
            modelo_moto TEXT,
            cor_moto TEXT,
            foto TEXT,
            ativo INTEGER DEFAULT 1,
            disponivel INTEGER DEFAULT 1,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS entregas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pedido_id INTEGER NOT NULL,
            motoboy_id INTEGER,
            status_entrega TEXT DEFAULT 'pendente',
            observacoes TEXT,
            atribuida_em DATETIME,
            coletada_em DATETIME,
            entregue_em DATETIME,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
            FOREIGN KEY (motoboy_id) REFERENCES motoboys(id) ON DELETE SET NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS admin_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER,
            acao TEXT NOT NULL,
            detalhes TEXT,
            ip TEXT,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )");
    }
    
    private function insertInitialData() {
        $this->pdo->exec("INSERT OR IGNORE INTO status_pedido (id, nome, descricao, cor, ordem) VALUES
            (1, 'Aguardando', 'Pedido aguardando confirmacao', '#FFA500', 1),
            (2, 'Confirmado', 'Pedido confirmado', '#17a2b8', 2),
            (3, 'Preparando', 'Pedido em preparacao', '#007BFF', 3),
            (4, 'Saiu para Entrega', 'Pedido saiu para entrega', '#28A745', 4),
            (5, 'Entregue', 'Pedido entregue', '#198754', 5),
            (6, 'Cancelado', 'Pedido cancelado', '#DC3545', 6)
        ");
        
        $this->pdo->exec("INSERT OR IGNORE INTO categorias (id, nome, descricao, icone, ordem) VALUES
            (1, 'Pizzas Tradicionais', 'Sabores classicos que todo mundo ama', 'fa-pizza-slice', 1),
            (2, 'Pizzas Premium', 'Ingredientes especiais e sabores unicos', 'fa-star', 2),
            (3, 'Pizzas Doces', 'Para adocar seu pedido', 'fa-candy-cane', 3),
            (4, 'Calzones', 'Pizza fechada recheada', 'fa-bread-slice', 4)
        ");
        
        $this->pdo->exec("INSERT OR IGNORE INTO tamanhos_pizza (id, nome, descricao, fatias, pessoas, ordem) VALUES
            (1, 'Pequena', '6 fatias', 6, '1-2 pessoas', 1),
            (2, 'Media', '8 fatias', 8, '2-3 pessoas', 2),
            (3, 'Grande', '12 fatias', 12, '3-4 pessoas', 3)
        ");
        
        $this->pdo->exec("INSERT OR IGNORE INTO bairros (nome, cidade, uf, taxa_entrega, tempo_estimado) VALUES
            ('Centro', 'Guarapari', 'ES', 5.00, 25),
            ('Muquicaba', 'Guarapari', 'ES', 6.00, 30),
            ('Praia do Morro', 'Guarapari', 'ES', 7.00, 35),
            ('Aeroporto', 'Guarapari', 'ES', 6.00, 30),
            ('Itapebussu', 'Guarapari', 'ES', 8.00, 40),
            ('Santa Monica', 'Guarapari', 'ES', 7.00, 35),
            ('Meaipe', 'Guarapari', 'ES', 10.00, 45),
            ('Setiba', 'Guarapari', 'ES', 12.00, 50)
        ");
        
        $this->pdo->exec("INSERT OR IGNORE INTO bebidas_categorias (id, nome, icone, cor, ordem) VALUES
            (1, 'Refrigerantes', 'fa-glass-whiskey', '#e74c3c', 1),
            (2, 'Sucos', 'fa-blender', '#f39c12', 2),
            (3, 'Aguas', 'fa-tint', '#3498db', 3),
            (4, 'Cervejas', 'fa-beer', '#f1c40f', 4)
        ");
        
        $this->pdo->exec("INSERT OR IGNORE INTO bebidas (nome, categoria_id, volume, preco, estoque) VALUES
            ('Coca-Cola 2L', 1, '2L', 15.00, 100),
            ('Coca-Cola 1,5L', 1, '1,5L', 12.00, 50),
            ('Guarana Coroa 2L', 1, '2L', 8.00, 100),
            ('Lata', 1, '350ml', 6.00, 200),
            ('Suco caixinha 1L', 2, '1L', 12.00, 50),
            ('Latao Brahma', 4, '473ml', 8.00, 80),
            ('Latao Budweiser', 4, '473ml', 8.00, 60)
        ");
        
        $this->pdo->exec("INSERT OR IGNORE INTO adicionais (nome, preco, ativo) VALUES
            ('Catupiry Original', 12.00, 1),
            ('Bacon', 10.00, 1),
            ('Mussarela Extra', 12.00, 1),
            ('Cheddar', 10.00, 1)
        ");

        $this->pdo->exec("INSERT OR IGNORE INTO promocoes (nome, descricao, preco, desconto, ativo) VALUES
            ('Promo 8 Fatias', 'Escolha 2 pizzas de qualquer categoria por um preco especial', 99.90, 15.00, 1),
            ('Dupla + Guarana', 'Leve 2 pizzas + Guarana Coroa 2L', 109.90, 10.00, 1),
            ('Combo Familiar', '1 pizza grande + 2 refrigerantes', 89.90, 5.00, 1)
        ");
        
        $this->insertPizzas();
    }
    
    private function insertPizzas() {
        $pizzas = [
            [1, 'AMERICANA', 'Presunto, palmito, ervilha, ovo, mussarela', 44.90, 66.00, 83.00],
            [1, 'A MODA DA CASA', 'Palmito, ovo, catupiry, cebola, mussarela', 44.90, 66.00, 83.00],
            [1, 'ATUM', 'Atum, cebola, mussarela', 44.90, 68.00, 86.00],
            [1, 'ARIELA', 'Lombo, palmito, mussarela', 44.90, 68.00, 85.00],
            [1, 'A MODA DO PIZZAIOLO', 'Frango, milho, ervilha, palmito, bacon, mussarela', 44.90, 68.00, 85.00],
            [1, 'BACON', 'Bacon, cebola, mussarela', 44.90, 65.00, 82.00],
            [1, 'BAIANA', 'Calabresa moida, ovo, cebola, pimenta', 44.90, 64.00, 81.00],
            [1, 'BAMBU', 'Presunto, ervilha, milho, ovo, cebola, mussarela', 44.90, 63.00, 80.00],
            [1, 'BAURU', 'Presunto, tomate, mussarela', 44.90, 63.00, 82.00],
            [1, 'BENVENUTTI', 'Frango, calabresa, presunto, ovo, mussarela', 44.90, 66.00, 83.00],
            [1, 'BRASILEIRA', 'Lombo, milho, ervilha, bacon, mussarela', 44.90, 67.00, 83.00],
            [1, 'CAIPIRA', 'Frango, milho, catupiry, mussarela', 44.90, 65.00, 82.00],
            [1, 'CALABRESA', 'Calabresa fatiada, cebola', 44.90, 63.00, 82.00],
            [1, 'CINCO QUEIJOS', 'Mussarela, catupiry, parmesao, gorgonzola, provolone', 44.90, 67.00, 84.00],
            [1, 'DA CASA', 'Presunto, palmito, milho, cebola, mussarela', 44.90, 67.00, 83.00],
            [1, 'FRANGO C/ CATUPIRY', 'Frango desfiado com catupiry', 44.90, 64.00, 81.00],
            [1, 'FRANBACON', 'Frango, bacon, mussarela, milho', 44.90, 66.00, 83.00],
            [1, 'CATUPYRYONE', 'Pepperoni, catupiry', 44.90, 65.00, 84.00],
            [1, 'JARDINEIRA', 'Presunto, ovo, bacon, catupiry, mussarela', 44.90, 66.00, 83.00],
            [1, 'LASENA', 'Atum, palmito, bacon, mussarela', 44.90, 66.00, 83.00],
            [1, 'LIFORNO', 'Lombo, calabresa, catupiry, mussarela', 44.90, 66.00, 83.00],
            [1, 'LOMBO', 'Mussarela, lombo, cebola', 44.90, 65.00, 81.00],
            [1, 'MARGUERITA', 'Mussarela, tomate, parmesao, manjericao', 44.90, 65.00, 82.00],
            [1, 'MUSSARELA', 'Mussarela, tomate, oregano', 44.90, 64.00, 82.00],
            [1, 'MARIA BONITA', 'Calabresa moida, milho, bacon, ovos', 44.90, 64.00, 82.00],
            [1, 'SAO PAULO', 'Presunto, frango, calabresa, catupiry, bacon, mussarela', 44.90, 68.00, 85.00],
            [1, 'NAPOLITANA', 'Palmito, tomate, mussarela, parmesao', 44.90, 66.00, 83.00],
            [1, 'PALMITO', 'Palmito, mussarela, catupiry', 44.90, 66.00, 83.00],
            [1, 'PORTUGUESA', 'Presunto, ervilha, ovos, mussarela, cebola', 44.90, 65.00, 81.00],
            [1, 'QUATRO QUEIJOS', 'Mussarela, parmesao, catupiry, gorgonzola', 44.90, 66.00, 83.00],
            [1, 'SERTANEJA', 'Frango, bacon, champignon, mussarela', 44.90, 67.00, 84.00],
            [1, 'SICILIANA', 'Champignon, bacon, mussarela', 44.90, 66.00, 83.00],
            [1, 'VEGETARIANA', 'Ovo, palmito, milho, ervilha, champignon, mussarela', 44.90, 68.00, 85.00],
            [1, 'CALACATU', 'Calabresa, catupiry', 44.90, 63.00, 82.00],
            [2, 'PEPPE CHEESE', 'Mussarela, pepperoni, cream cheese, bacon', null, 70.00, 85.00],
            [2, 'PEPPERONI', 'Mussarela, pepperoni, champignon, cebola', null, 65.00, 85.00],
            [2, 'A MODA DO PIZZAIOLO II', 'Frango, champignon, palmito, lombo, bacon, mussarela', null, 68.00, 82.00],
            [2, 'SEIS QUEIJOS', 'Mussarela, catupiry, parmesao, gorgonzola, provolone, cheddar', null, 72.00, 85.00],
            [2, 'DO CLIENTE', 'Monte sua pizza com 5 itens', null, 72.00, 85.00],
            [2, 'MALUCA', 'Calabresa moida, bacon, mussarela, tomate, parmesao, manjericao', null, 70.00, 83.00],
            [2, 'FRANGO COM CHEDDAR', 'Frango, cheddar, mussarela, bacon', null, 70.00, 83.00],
            [2, 'GORGONZOLA', 'Gorgonzola, parmesao, mussarela', 44.90, 68.00, 85.00],
            [3, 'BRIGADEIRO', 'Chocolate granulado, cereja', 44.90, 62.00, null],
            [3, 'ROMEU E JULIETA', 'Leite condensado, mussarela, goiabada', 44.90, 62.00, null],
            [3, 'BANANA', 'Banana, mussarela, leite condensado, canela', 44.90, 60.00, null],
            [3, 'PRESTIGIO', 'Chocolate, coco ralado, leite condensado', 44.90, 62.00, null],
        ];
        
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO produtos (categoria_id, nome, descricao, preco_p, preco_m, preco_g, disponivel, destaque) VALUES (?, ?, ?, ?, ?, ?, 1, 0)");
        foreach ($pizzas as $pizza) {
            $stmt->execute($pizza);
        }
    }
}
