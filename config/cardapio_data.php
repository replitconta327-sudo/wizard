<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Erro de conexão com o banco de dados']);
    exit;
}

try {
    $stmtTrad = $pdo->query("SELECT p.*, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.categoria_id = 1 AND p.disponivel = 1 ORDER BY p.nome");
    $tradicionais = $stmtTrad->fetchAll();
    
    $stmtPrem = $pdo->query("SELECT p.*, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.categoria_id = 2 AND p.disponivel = 1 ORDER BY p.nome");
    $premium = $stmtPrem->fetchAll();
    
    $stmtDoces = $pdo->query("SELECT p.*, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.categoria_id = 3 AND p.disponivel = 1 ORDER BY p.nome");
    $doces = $stmtDoces->fetchAll();
    
    $stmtCalz = $pdo->query("SELECT p.*, c.nome as categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.categoria_id = 4 AND p.disponivel = 1 ORDER BY p.nome");
    $calzones = $stmtCalz->fetchAll();
    
    $stmtAdd = $pdo->query("SELECT * FROM adicionais WHERE ativo = 1 ORDER BY nome");
    $adicionais = $stmtAdd->fetchAll();
    
    $stmtBeb = $pdo->query("SELECT * FROM bebidas WHERE ativo = 1 ORDER BY ordem, nome");
    $bebidas = $stmtBeb->fetchAll();
    
    $formatPizzas = function($pizzas) {
        return array_map(function($p) {
            return [
                'id' => $p['id'],
                'nome' => $p['nome'],
                'descricao' => $p['descricao'],
                'precos' => [
                    'pequena' => $p['preco_p'] ? floatval($p['preco_p']) : null,
                    'media' => $p['preco_m'] ? floatval($p['preco_m']) : null,
                    'grande' => $p['preco_g'] ? floatval($p['preco_g']) : null
                ],
                'imagem' => $p['imagem'],
                'destaque' => (bool)$p['destaque']
            ];
        }, $pizzas);
    };
    
    $data = [
        'tradicionais' => $formatPizzas($tradicionais),
        'premium' => $formatPizzas($premium),
        'doces' => $formatPizzas($doces),
        'calzones' => $formatPizzas($calzones)
    ];
    
    $formattedAdicionais = array_map(function($a) {
        return [
            'id' => $a['id'],
            'nome' => $a['nome'],
            'descricao' => '',
            'preco' => floatval($a['preco'])
        ];
    }, $adicionais);
    
    $formattedBebidas = array_map(function($b) {
        return [
            'id' => $b['id'],
            'nome' => $b['nome'] . ($b['volume'] ? ' ' . $b['volume'] : ''),
            'preco' => floatval($b['preco']),
            'estoque' => $b['estoque']
        ];
    }, $bebidas);
    
    echo json_encode([
        'ok' => true,
        'data' => $data,
        'adicionais' => $formattedAdicionais,
        'bebidas' => $formattedBebidas
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao carregar cardápio: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro ao carregar cardápio']);
}
