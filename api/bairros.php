<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Erro de conexão']);
    exit;
}

$search = $_GET['q'] ?? '';

try {
    if (empty($search)) {
        $stmt = $pdo->query("SELECT * FROM bairros WHERE ativo = 1 ORDER BY nome");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM bairros WHERE ativo = 1 AND nome LIKE ? ORDER BY nome");
        $stmt->execute(['%' . $search . '%']);
    }
    
    $bairros = $stmt->fetchAll();
    
    $formatted = array_map(function($b) {
        return [
            'id' => $b['id'],
            'nome' => $b['nome'],
            'cidade' => $b['cidade'],
            'uf' => $b['uf'],
            'taxa_entrega' => floatval($b['taxa_entrega']),
            'tempo_estimado' => $b['tempo_estimado']
        ];
    }, $bairros);
    
    $response = json_encode(['success' => true, 'data' => $formatted]);
    header('ETag: ' . md5($response));
    echo $response;
    
} catch (Exception $e) {
    error_log('Erro ao buscar bairros: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'Erro ao buscar bairros']);
}
