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
$userId = $_SESSION['usuario_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'msg' => 'Usuário não autenticado']);
    exit;
}

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE usuario_id = ? ORDER BY padrao DESC, id DESC");
            $stmt->execute([$userId]);
            $enderecos = $stmt->fetchAll();
            
            $formatted = array_map(function($e) {
                return [
                    'id' => $e['id'],
                    'apelido' => $e['apelido'],
                    'logradouro' => $e['logradouro'],
                    'numero' => $e['numero'],
                    'complemento' => $e['complemento'],
                    'bairro' => $e['bairro'],
                    'cidade' => $e['cidade'],
                    'uf' => $e['estado'],
                    'cep' => $e['cep'],
                    'padrao' => (bool)$e['padrao']
                ];
            }, $enderecos);
            
            echo json_encode(['success' => true, 'data' => $formatted]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                echo json_encode(['success' => false, 'msg' => 'ID não informado']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $userId]);
            $endereco = $stmt->fetch();
            
            if (!$endereco) {
                echo json_encode(['success' => false, 'msg' => 'Endereço não encontrado']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $endereco['id'],
                    'apelido' => $endereco['apelido'],
                    'logradouro' => $endereco['logradouro'],
                    'numero' => $endereco['numero'],
                    'complemento' => $endereco['complemento'],
                    'bairro' => $endereco['bairro'],
                    'cidade' => $endereco['cidade'],
                    'uf' => $endereco['estado'],
                    'cep' => $endereco['cep'],
                    'padrao' => (bool)$endereco['padrao']
                ]
            ]);
            break;
            
        case 'add':
            $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $apelido = trim($inputData['apelido'] ?? 'Casa');
            $logradouro = trim($inputData['logradouro'] ?? '');
            $numero = trim($inputData['numero'] ?? '');
            $complemento = trim($inputData['complemento'] ?? '');
            $bairro = trim($inputData['bairro'] ?? '');
            $cidade = trim($inputData['cidade'] ?? 'Guarapari');
            $uf = trim($inputData['uf'] ?? 'ES');
            $cep = preg_replace('/\D/', '', $inputData['cep'] ?? '');
            $padrao = $inputData['padrao'] ?? 0;
            
            if (empty($logradouro) || empty($numero) || empty($bairro) || empty($cep)) {
                echo json_encode(['success' => false, 'msg' => 'Campos obrigatórios não preenchidos']);
                exit;
            }
            
            if ($padrao) {
                $pdo->prepare("UPDATE enderecos SET padrao = 0 WHERE usuario_id = ?")->execute([$userId]);
            }
            
            $stmt = $pdo->prepare("INSERT INTO enderecos (usuario_id, apelido, logradouro, numero, complemento, bairro, cidade, estado, cep, padrao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $apelido, $logradouro, $numero, $complemento, $bairro, $cidade, $uf, $cep, $padrao ? 1 : 0]);
            
            $newId = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'msg' => 'Endereço cadastrado', 'id' => $newId]);
            break;
            
        default:
            echo json_encode(['success' => false, 'msg' => 'Ação inválida']);
    }
    
} catch (Exception $e) {
    error_log('Erro em enderecos: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'Erro interno']);
}
