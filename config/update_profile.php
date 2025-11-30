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
    echo json_encode(['ok' => false, 'msg' => 'Erro de conexão']);
    exit;
}

$userId = $_POST['user_id'] ?? $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
$street = trim($_POST['street'] ?? '');
$number = trim($_POST['number'] ?? '');
$neighborhood = trim($_POST['neighborhood'] ?? '');
$cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$reference = trim($_POST['reference'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($name) || empty($phone) || empty($street) || empty($number) || empty($neighborhood) || empty($cep)) {
    echo json_encode(['ok' => false, 'msg' => 'Campos obrigatórios não preenchidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefone = ? AND id != ?");
    $stmt->execute([$phone, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'Telefone já cadastrado para outro usuário']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(['ok' => false, 'msg' => 'A senha deve ter pelo menos 6 caracteres']);
            exit;
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, telefone = ?, senha = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $phone, $hashedPassword, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, telefone = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $phone, $userId]);
    }
    
    $stmtEnd = $pdo->prepare("SELECT id FROM enderecos WHERE usuario_id = ? AND padrao = 1 LIMIT 1");
    $stmtEnd->execute([$userId]);
    $endereco = $stmtEnd->fetch();
    
    if ($endereco) {
        $stmt = $pdo->prepare("UPDATE enderecos SET logradouro = ?, numero = ?, complemento = ?, bairro = ?, cep = ? WHERE id = ?");
        $stmt->execute([$street, $number, $reference, $neighborhood, $cep, $endereco['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO enderecos (usuario_id, apelido, logradouro, numero, complemento, bairro, cidade, estado, cep, padrao) VALUES (?, 'Casa', ?, ?, ?, ?, 'Guarapari', 'ES', ?, 1)");
        $stmt->execute([$userId, $street, $number, $reference, $neighborhood, $cep]);
    }
    
    $pdo->commit();
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $stmtEndNew = $pdo->prepare("SELECT * FROM enderecos WHERE usuario_id = ? AND padrao = 1 LIMIT 1");
    $stmtEndNew->execute([$userId]);
    $enderecoNew = $stmtEndNew->fetch();
    
    $userData = [
        'id' => $user['id'],
        'nome' => $user['nome'],
        'telefone' => $user['telefone'],
        'tipo' => $user['tipo'],
        'rua' => $enderecoNew['logradouro'] ?? '',
        'numero' => $enderecoNew['numero'] ?? '',
        'bairro' => $enderecoNew['bairro'] ?? '',
        'cep' => $enderecoNew['cep'] ?? '',
        'referencia' => $enderecoNew['complemento'] ?? ''
    ];
    
    echo json_encode(['ok' => true, 'msg' => 'Dados atualizados', 'user' => $userData]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro update profile: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro ao atualizar dados']);
}
