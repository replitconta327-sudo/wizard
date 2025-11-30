<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'Erro de conexão']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->prepare("
                SELECT p.*, s.nome as status_nome, s.cor as status_cor, 
                       e.logradouro, e.numero, e.bairro
                FROM pedidos p
                JOIN status_pedido s ON p.status_id = s.id
                JOIN enderecos e ON p.endereco_id = e.id
                WHERE p.usuario_id = ?
                ORDER BY p.criado_em DESC
                LIMIT 20
            ");
            $stmt->execute([$userId]);
            $pedidos = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $pedidos]);
            break;
            
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $enderecoId = $input['endereco_id'] ?? null;
            $itens = $input['itens'] ?? [];
            $bebidas = $input['bebidas'] ?? [];
            $formaPagamento = $input['forma_pagamento'] ?? 'pix';
            $observacoes = $input['observacoes'] ?? '';
            $troco = floatval($input['troco'] ?? 0);
            
            if (!$enderecoId || (empty($itens) && empty($bebidas))) {
                echo json_encode(['success' => false, 'msg' => 'Dados incompletos']);
                exit;
            }
            
            $subtotal = 0;
            foreach ($itens as $item) {
                $subtotal += floatval($item['preco']) * intval($item['quantidade'] ?? 1);
            }
            foreach ($bebidas as $bebida) {
                $subtotal += floatval($bebida['preco']) * intval($bebida['quantidade'] ?? 1);
            }
            
            $stmtEnd = $pdo->prepare("SELECT e.*, b.taxa_entrega FROM enderecos e LEFT JOIN bairros b ON LOWER(e.bairro) = LOWER(b.nome) WHERE e.id = ?");
            $stmtEnd->execute([$enderecoId]);
            $endereco = $stmtEnd->fetch();
            $taxaEntrega = $endereco['taxa_entrega'] ?? 5.00;
            
            $total = $subtotal + $taxaEntrega;
            
            $numeroPedido = 'PED' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO pedidos (usuario_id, endereco_id, status_id, numero_pedido, subtotal, taxa_entrega, total, forma_pagamento, troco, observacoes, previsao_entrega)
                VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, datetime('now', '+45 minutes'))
            ");
            $stmt->execute([$userId, $enderecoId, $numeroPedido, $subtotal, $taxaEntrega, $total, $formaPagamento, $troco, $observacoes]);
            
            $pedidoId = $pdo->lastInsertId();
            
            if (!empty($itens)) {
                $stmtItem = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, tamanho, preco_unitario, subtotal, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($itens as $item) {
                    $qty = intval($item['quantidade'] ?? 1);
                    $preco = floatval($item['preco']);
                    $stmtItem->execute([
                        $pedidoId,
                        $item['produto_id'],
                        $qty,
                        $item['tamanho'] ?? 'M',
                        $preco,
                        $preco * $qty,
                        $item['observacoes'] ?? ''
                    ]);
                }
            }
            
            if (!empty($bebidas)) {
                $stmtBeb = $pdo->prepare("INSERT INTO pedido_bebidas (pedido_id, bebida_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                foreach ($bebidas as $bebida) {
                    $qty = intval($bebida['quantidade'] ?? 1);
                    $preco = floatval($bebida['preco']);
                    $stmtBeb->execute([
                        $pedidoId,
                        $bebida['id'],
                        $qty,
                        $preco,
                        $preco * $qty
                    ]);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'msg' => 'Pedido criado com sucesso',
                'pedido_id' => $pedidoId,
                'numero_pedido' => $numeroPedido,
                'total' => $total
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'msg' => 'Ação inválida']);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro em pedidos: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'Erro interno']);
}
