<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

$name = trim($_POST['name'] ?? '');
$phone = $_POST['phone'] ?? '';
$street = trim($_POST['street'] ?? '');
$number = trim($_POST['number'] ?? '');
$neighborhood = trim($_POST['neighborhood'] ?? '');
$cep = $_POST['cep'] ?? '';
$reference = trim($_POST['reference'] ?? '');
$password = $_POST['password'] ?? '';

$phone = preg_replace('/\D/', '', $phone);
$cep = preg_replace('/\D/', '', $cep);

if (empty($name) || empty($phone) || empty($street) || empty($number) || empty($neighborhood) || empty($cep) || empty($password)) {
    echo json_encode(['ok' => false, 'msg' => 'Todos os campos obrigatórios devem ser preenchidos']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['ok' => false, 'msg' => 'A senha deve ter pelo menos 6 caracteres']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefone = ?");
    $stmt->execute([$phone]);
    
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'Telefone já cadastrado']);
        exit;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, telefone, senha, tipo, ativo) VALUES (?, ?, ?, 'cliente', 1)");
    $stmt->execute([$name, $phone, $hashedPassword]);
    
    $userId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("INSERT INTO enderecos (usuario_id, apelido, logradouro, numero, complemento, bairro, cidade, estado, cep, padrao) VALUES (?, 'Casa', ?, ?, ?, ?, 'Guarapari', 'ES', ?, 1)");
    $stmt->execute([$userId, $street, $number, $reference, $neighborhood, $cep]);
    
    $pdo->commit();
    
    echo json_encode([
        'ok' => true,
        'msg' => 'Cadastro realizado com sucesso',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro no cadastro: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro ao realizar cadastro']);
}
