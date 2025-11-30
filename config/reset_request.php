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
    echo json_encode(['ok' => false, 'msg' => 'Erro de conexão']);
    exit;
}

$phone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
$name = trim($_POST['name'] ?? '');

if (empty($phone) || empty($name)) {
    echo json_encode(['ok' => false, 'msg' => 'Telefone e nome são obrigatórios']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE telefone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['ok' => false, 'msg' => 'Telefone não encontrado']);
        exit;
    }
    
    $nameMatch = similar_text(strtolower($name), strtolower($user['nome']), $percent);
    if ($percent < 70) {
        echo json_encode(['ok' => false, 'msg' => 'Nome não confere com o cadastro']);
        exit;
    }
    
    $token = bin2hex(random_bytes(32));
    
    $_SESSION['reset_token'] = $token;
    $_SESSION['reset_user_id'] = $user['id'];
    $_SESSION['reset_expires'] = time() + 600;
    
    echo json_encode(['ok' => true, 'msg' => 'Dados verificados', 'token' => $token]);
    
} catch (Exception $e) {
    error_log('Erro reset request: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro interno']);
}
