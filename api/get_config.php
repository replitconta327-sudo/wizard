<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $tabela = $_GET['tabela'] ?? 'pizzas';
    $dados = [];
    
    switch($tabela) {
        case 'pizzas':
            $dados = $pdo->query("SELECT c.nome as categoria, p.nome, p.descricao, p.preco_m, p.preco_g FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY c.nome, p.nome")->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'bebidas':
            $dados = $pdo->query("SELECT b.nome, bc.nome as categoria, b.volume, b.preco, b.estoque FROM bebidas b LEFT JOIN bebidas_categorias bc ON b.categoria_id = bc.id ORDER BY bc.nome, b.nome")->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'bairros':
            $dados = $pdo->query("SELECT nome, taxa_entrega, tempo_estimado, ativo FROM bairros ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'adicionais':
            $dados = $pdo->query("SELECT nome, preco, ativo FROM adicionais ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'promocoes':
            $dados = $pdo->query("SELECT nome, descricao, preco, desconto, ativo FROM promocoes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'status':
            $dados = $pdo->query("SELECT nome, descricao, cor FROM status_pedido ORDER BY ordem")->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
    
    echo json_encode($dados);
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
?>
