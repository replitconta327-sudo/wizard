<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefone = $_POST['telefone'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (!$telefone || !$senha) {
        $erro = 'Telefone e senha são obrigatórios';
    } else {
        try {
            $database = new Database();
            $pdo = $database->pdo();
            
            $stmt = $pdo->prepare("SELECT id, nome, telefone, senha FROM usuarios WHERE telefone = ? AND tipo = 'admin'");
            $stmt->execute([$telefone]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                header('Location: /admin/dashboard.php');
                exit;
            } else {
                $erro = 'Telefone ou senha incorretos';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao conectar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Pizzaria</title>
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
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
        }
        
        .login-header {
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
        
        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #333;
        }
        
        .login-header p {
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
        
        .btn-login {
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
        
        .btn-login:hover {
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🍕</div>
            <h1>Pizzaria</h1>
            <p>Painel de Administração</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>
        
        <div style="background: #efe; color: #3c3; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem;">
            <strong>Teste:</strong><br>
            📱 11999999999<br>
            🔒 admin123
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" placeholder="(11) 99999-9999" required autocomplete="tel">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>
        
        <div class="form-footer">
            <p>Não tem conta? <a href="/admin/registro.php">Criar cadastro</a></p>
            <p style="margin-top: 0.5rem;"><a href="/admin/recuperar_senha.php">Esqueceu a senha?</a></p>
        </div>
    </div>
</body>
</html>
