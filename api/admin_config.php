<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
    $tabela = $_GET['tabela'] ?? $_POST['tabela'] ?? '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($acao === 'atualizar') {
            if ($tabela === 'pizzas') {
                $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco_m = ?, preco_g = ? WHERE id = ?");
                $stmt->execute([$_POST['nome'], $_POST['descricao'], $_POST['preco_m'], $_POST['preco_g'], $_POST['id']]);
            } elseif ($tabela === 'bebidas') {
                $stmt = $pdo->prepare("UPDATE bebidas SET nome = ?, volume = ?, preco = ?, estoque = ? WHERE id = ?");
                $stmt->execute([$_POST['nome'], $_POST['volume'], $_POST['preco'], $_POST['estoque'], $_POST['id']]);
            } elseif ($tabela === 'bairros') {
                $stmt = $pdo->prepare("UPDATE bairros SET nome = ?, taxa_entrega = ?, tempo_estimado = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$_POST['nome'], $_POST['taxa_entrega'], $_POST['tempo_estimado'], $_POST['ativo'], $_POST['id']]);
            } elseif ($tabela === 'adicionais') {
                $stmt = $pdo->prepare("UPDATE adicionais SET nome = ?, preco = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$_POST['nome'], $_POST['preco'], $_POST['ativo'], $_POST['id']]);
            } elseif ($tabela === 'promocoes') {
                $stmt = $pdo->prepare("UPDATE promocoes SET nome = ?, descricao = ?, preco = ?, desconto = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$_POST['nome'], $_POST['descricao'], $_POST['preco'], $_POST['desconto'], $_POST['ativo'], $_POST['id']]);
            } elseif ($tabela === 'status') {
                $stmt = $pdo->prepare("UPDATE status_pedido SET nome = ?, descricao = ?, cor = ? WHERE id = ?");
                $stmt->execute([$_POST['nome'], $_POST['descricao'], $_POST['cor'], $_POST['id']]);
            }
            echo json_encode(['sucesso' => true, 'mensagem' => 'Atualizado com sucesso']);
        } elseif ($acao === 'deletar') {
            if ($tabela === 'pizzas') {
                $pdo->prepare("DELETE FROM produtos WHERE id = ?")->execute([$_POST['id']]);
            } elseif ($tabela === 'bebidas') {
                $pdo->prepare("DELETE FROM bebidas WHERE id = ?")->execute([$_POST['id']]);
            } elseif ($tabela === 'bairros') {
                $pdo->prepare("DELETE FROM bairros WHERE id = ?")->execute([$_POST['id']]);
            } elseif ($tabela === 'adicionais') {
                $pdo->prepare("DELETE FROM adicionais WHERE id = ?")->execute([$_POST['id']]);
            } elseif ($tabela === 'promocoes') {
                $pdo->prepare("DELETE FROM promocoes WHERE id = ?")->execute([$_POST['id']]);
            }
            echo json_encode(['sucesso' => true, 'mensagem' => 'Deletado com sucesso']);
        } elseif ($acao === 'criar') {
            if ($tabela === 'pizzas') {
                $stmt = $pdo->prepare("INSERT INTO produtos (categoria_id, nome, descricao, preco_m, preco_g) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['categoria_id'], $_POST['nome'], $_POST['descricao'], $_POST['preco_m'], $_POST['preco_g']]);
            } elseif ($tabela === 'bebidas') {
                $stmt = $pdo->prepare("INSERT INTO bebidas (categoria_id, nome, volume, preco, estoque) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['categoria_id'], $_POST['nome'], $_POST['volume'], $_POST['preco'], $_POST['estoque']]);
            } elseif ($tabela === 'bairros') {
                $stmt = $pdo->prepare("INSERT INTO bairros (nome, taxa_entrega, tempo_estimado) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['nome'], $_POST['taxa_entrega'], $_POST['tempo_estimado']]);
            } elseif ($tabela === 'adicionais') {
                $stmt = $pdo->prepare("INSERT INTO adicionais (nome, preco) VALUES (?, ?)");
                $stmt->execute([$_POST['nome'], $_POST['preco']]);
            } elseif ($tabela === 'promocoes') {
                $stmt = $pdo->prepare("INSERT INTO promocoes (nome, descricao, preco, desconto) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['nome'], $_POST['descricao'], $_POST['preco'], $_POST['desconto']]);
            }
            echo json_encode(['sucesso' => true, 'mensagem' => 'Criado com sucesso']);
        }
    } else {
        echo json_encode(['erro' => 'Método não permitido']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>
