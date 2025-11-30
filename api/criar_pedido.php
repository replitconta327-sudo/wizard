<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['endereco_id'], $data['forma_pagamento'], $data['total'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }

    $usuario_id = $_SESSION['usuario_id'] ?? null;
    if (!$usuario_id) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        exit;
    }

    // Gera número do pedido
    $numero_pedido = 'PED-' . date('YmdHis') . '-' . substr(uniqid(), -6);

    // Prepara totais
    $subtotal = $data['subtotal'] ?? 0;
    $taxa_entrega = $data['taxa_entrega'] ?? 0;
    $total = $data['total'] ?? 0;

    // Inicia transação
    $pdo->beginTransaction();

    // Insere pedido
    $stmt = $pdo->prepare("
        INSERT INTO pedidos (usuario_id, endereco_id, status_id, numero_pedido, subtotal, taxa_entrega, total, forma_pagamento, criado_em, atualizado_em)
        VALUES (?, ?, 1, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
    ");
    $stmt->execute([$usuario_id, $data['endereco_id'], $numero_pedido, $subtotal, $taxa_entrega, $total, $data['forma_pagamento']]);
    $pedido_id = $pdo->lastInsertId();

    // Insere pizzas como itens
    if (isset($data['pizzas']) && is_array($data['pizzas'])) {
        $stmt = $pdo->prepare("
            INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, tamanho, preco_unitario, subtotal, observacoes)
            VALUES (?, 0, ?, ?, ?, ?, ?)
        ");
        
        foreach ($data['pizzas'] as $pizza) {
            $quantidade = $pizza['quantidade'] ?? 1;
            $tamanho = $pizza['tamanho']['nome'] ?? 'M';
            $sabores = implode(' + ', array_map(fn($s) => $s['nome'], $pizza['sabores'] ?? []));
            $preco_unitario = floatval($pizza['preco'] ?? 0);
            $subtotal_item = $preco_unitario * $quantidade;
            
            $stmt->execute([$pedido_id, $quantidade, $tamanho, $preco_unitario, $subtotal_item, $sabores]);
        }
    }

    // Insere adicionais
    if (isset($data['adicionais']) && is_array($data['adicionais'])) {
        $stmt = $pdo->prepare("
            INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, tamanho, preco_unitario, subtotal, observacoes)
            VALUES (?, 0, 1, 'ADIC', ?, ?, ?)
        ");
        
        foreach ($data['adicionais'] as $adic) {
            $preco = floatval($adic['preco'] ?? 0);
            $stmt->execute([$pedido_id, $preco, $preco, $adic['nome'] ?? 'Adicional']);
        }
    }

    // Insere bebidas
    if (isset($data['bebidas']) && is_array($data['bebidas'])) {
        $stmt = $pdo->prepare("
            INSERT INTO pedido_bebidas (pedido_id, bebida_id, quantidade, preco_unitario, subtotal)
            VALUES (?, 0, ?, ?, ?)
        ");
        
        foreach ($data['bebidas'] as $bebida) {
            $quantidade = $bebida['quantidade'] ?? 1;
            $preco = floatval($bebida['preco'] ?? 0);
            $subtotal_item = $preco * $quantidade;
            $stmt->execute([$pedido_id, $quantidade, $preco, $subtotal_item]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pedido criado com sucesso',
        'numero_pedido' => $numero_pedido,
        'pedido_id' => $pedido_id
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro ao criar pedido: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao criar pedido: ' . $e->getMessage()]);
}
