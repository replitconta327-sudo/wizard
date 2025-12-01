<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['usuario_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/Validator.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $erro = 'CSRF inválido';
    } else {
        $nome = $_POST['nome'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $confirmacao = $_POST['confirmacao'] ?? '';
        if (!$nome || !$telefone || !$senha || !$confirmacao) {
            $erro = 'Todos os campos são obrigatórios';
        } elseif ($senha !== $confirmacao) {
            $erro = 'As senhas não coincidem';
        } elseif (strlen($senha) < 6) {
            $erro = 'A senha deve ter no mínimo 6 caracteres';
        } elseif (!Validator::phoneNumber($telefone)) {
            $erro = 'Telefone inválido';
        } else {
            try {
                $database = new Database();
                $pdo = $database->pdo();
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefone = ?");
                $stmt->execute([$telefone]);
                $existe = $stmt->fetch();
                if ($existe) {
                    $erro = 'Este telefone já está cadastrado';
                } else {
                    $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, telefone, senha, tipo, ativo) VALUES (?, ?, ?, 'admin', 1)");
                    $stmt->execute([$nome, $telefone, $senha_hash]);
                    $sucesso = 'Cadastro realizado com sucesso! <a href="/admin/login.php" style="color: #3c3; text-decoration: underline;">Clique aqui para fazer login</a>';
                }
            } catch (Exception $e) {
                $erro = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro Admin - Pizzaria</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .registro-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
        }
        
        .registro-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        
        .registro-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #333;
        }
        
        .registro-header p {
            margin: 0.5rem 0 0;
            color: #666;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-registrar {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-registrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .erro {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #c33;
        }
        
        .sucesso {
            background: #efe;
            color: #3c3;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #3c3;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .form-footer p {
            margin: 0;
            color: #666;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .form-footer a:hover {
            color: #764ba2;
        }

        .sucesso a {
            color: #3c3;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="registro-header">
            <div class="logo">🍕</div>
            <h1>Pizzaria</h1>
            <p>Criar Conta Admin</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso"><?php echo $sucesso; ?></div>
        <?php else: ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" placeholder="(11) 99999-9999" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required>
            </div>
            
            <div class="form-group">
                <label for="confirmacao">Confirmar Senha</label>
                <input type="password" id="confirmacao" name="confirmacao" placeholder="Confirme a senha" required>
            </div>
            
            <button type="submit" class="btn-registrar">Criar Conta</button>
        </form>
        
        <?php endif; ?>
        
        <div class="form-footer">
            <p>Já tem conta? <a href="/admin/login.php">Fazer login</a></p>
        </div>
    </div>
</body>
</html>
