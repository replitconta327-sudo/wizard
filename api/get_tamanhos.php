<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $stmt = $pdo->query("SELECT id, nome, descricao as fatias, fatias as num_fatias, pessoas, ordem, ativo FROM tamanhos_pizza WHERE ativo = 1 ORDER BY ordem");
    $tamanhos = $stmt->fetchAll();
    
    $formatted = array_map(function($t) {
        $icons = ['Pequena' => '🍕', 'Média' => '🍕🍕', 'Grande' => '🍕🍕🍕'];
        return [
            'id' => $t['id'],
            'nome' => $t['nome'],
            'fatias' => $t['fatias'],
            'icone' => $icons[$t['nome']] ?? '🍕',
            'pessoas' => $t['pessoas'],
            'ordem' => $t['ordem'],
            'ativo' => (bool)$t['ativo'],
            'preco_pequeno' => 0,
            'preco_medio' => 0,
            'preco_grande' => 0
        ];
    }, $tamanhos);
    
    $response = json_encode(['success' => true, 'data' => $formatted]);
    header('ETag: ' . md5($response));
    echo $response;
    
} catch (Exception $e) {
    error_log('Erro ao carregar tamanhos: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao carregar tamanhos']);
}
