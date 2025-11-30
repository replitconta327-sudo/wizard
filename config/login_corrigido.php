<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

require_once __DIR__ . '/database.php';

try {
    $database = new Database();
    $pdo = $database->pdo();
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Erro de conexão com o banco de dados']);
    exit;
}

$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';

$phone = preg_replace('/\D/', '', $phone);

if (empty($phone) || empty($password)) {
    echo json_encode(['ok' => false, 'msg' => 'Telefone e senha são obrigatórios']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telefone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['ok' => false, 'msg' => 'Usuário não encontrado']);
        exit;
    }
    
    if (!password_verify($password, $user['senha'])) {
        echo json_encode(['ok' => false, 'msg' => 'Senha incorreta']);
        exit;
    }
    
    if (!$user['ativo']) {
        echo json_encode(['ok' => false, 'msg' => 'Conta desativada']);
        exit;
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_tipo'] = $user['tipo'];
    
    $stmtEnd = $pdo->prepare("SELECT * FROM enderecos WHERE usuario_id = ? ORDER BY padrao DESC LIMIT 1");
    $stmtEnd->execute([$user['id']]);
    $endereco = $stmtEnd->fetch();
    
    $userData = [
        'id' => $user['id'],
        'nome' => $user['nome'],
        'telefone' => $user['telefone'],
        'email' => $user['email'],
        'tipo' => $user['tipo'],
        'rua' => $endereco['logradouro'] ?? '',
        'numero' => $endereco['numero'] ?? '',
        'bairro' => $endereco['bairro'] ?? '',
        'cep' => $endereco['cep'] ?? '',
        'referencia' => $endereco['complemento'] ?? ''
    ];
    
    $redirect = $user['tipo'] === 'admin' ? '/admin/' : '/cardapio/';
    
    echo json_encode([
        'ok' => true,
        'msg' => 'Login realizado com sucesso',
        'user' => $userData,
        'redirect' => $redirect
    ]);
    
} catch (Exception $e) {
    error_log('Erro no login: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro interno do servidor']);
}
