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

$token = $_POST['token'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['ok' => false, 'msg' => 'Todos os campos são obrigatórios']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['ok' => false, 'msg' => 'As senhas não coincidem']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['ok' => false, 'msg' => 'A senha deve ter pelo menos 6 caracteres']);
    exit;
}

$storedToken = $_SESSION['reset_token'] ?? null;
$userId = $_SESSION['reset_user_id'] ?? null;
$expires = $_SESSION['reset_expires'] ?? 0;

if (!$storedToken || $storedToken !== $token) {
    echo json_encode(['ok' => false, 'msg' => 'Token inválido']);
    exit;
}

if (time() > $expires) {
    unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);
    echo json_encode(['ok' => false, 'msg' => 'Token expirado']);
    exit;
}

try {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);
    
    echo json_encode(['ok' => true, 'msg' => 'Senha alterada com sucesso']);
    
} catch (Exception $e) {
    error_log('Erro reset confirm: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro ao alterar senha']);
}
