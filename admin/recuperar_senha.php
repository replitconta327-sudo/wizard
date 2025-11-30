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
$passo = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['passo'])) {
        $passo_form = $_POST['passo'];
        
        if ($passo_form == 1) {
            $telefone = $_POST['telefone'] ?? '';
            
            if (!$telefone) {
                $erro = 'Telefone é obrigatório';
            } else {
                try {
                    $database = new Database();
                    $pdo = $database->pdo();
                    
                    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE telefone = ? AND tipo = 'admin'");
                    $stmt->execute([$telefone]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($usuario) {
                        $_SESSION['reset_usuario_id'] = $usuario['id'];
                        $_SESSION['reset_telefone'] = $telefone;
                        $sucesso = 'Digite a nova senha abaixo';
                        $passo = 2;
                    } else {
                        $erro = 'Telefone não encontrado';
                    }
                } catch (Exception $e) {
                    $erro = 'Erro: ' . $e->getMessage();
                }
            }
        } elseif ($passo_form == 2) {
            $nova_senha = $_POST['nova_senha'] ?? '';
            $confirmacao = $_POST['confirmacao'] ?? '';
            
            if (!isset($_SESSION['reset_usuario_id'])) {
                $erro = 'Sessão expirada. Tente novamente';
                $passo = 1;
            } elseif (!$nova_senha || !$confirmacao) {
                $erro = 'Todos os campos são obrigatórios';
                $passo = 2;
            } elseif ($nova_senha !== $confirmacao) {
                $erro = 'As senhas não coincidem';
                $passo = 2;
            } elseif (strlen($nova_senha) < 6) {
                $erro = 'A senha deve ter no mínimo 6 caracteres';
                $passo = 2;
            } else {
                try {
                    $database = new Database();
                    $pdo = $database->pdo();
                    
                    $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                    $stmt->execute([$senha_hash, $_SESSION['reset_usuario_id']]);
                    
                    unset($_SESSION['reset_usuario_id']);
                    unset($_SESSION['reset_telefone']);
                    
                    $sucesso = 'Senha alterada com sucesso! <a href="/admin/login.php" style="color: #3c3; text-decoration: underline;">Clique aqui para fazer login</a>';
                    $passo = 3;
                } catch (Exception $e) {
                    $erro = 'Erro ao atualizar: ' . $e->getMessage();
                    $passo = 2;
                }
            }
        }
    }
}

if (isset($_SESSION['reset_usuario_id']) && !isset($_POST['passo'])) {
    $passo = 2;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Senha - Pizzaria</title>
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
        
        .container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
        }
        
        .header {
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
        
        .header h1 { margin: 0; font-size: 1.8rem; color: #333; }
        .header p { margin: 0.5rem 0 0; color: #666; font-size: 0.95rem; }
        
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        
        .btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3); }
        
        .erro { background: #fee; color: #c33; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 4px solid #c33; }
        .sucesso { background: #efe; color: #3c3; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 4px solid #3c3; }
        
        .footer { text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee; }
        .footer p { margin: 0; color: #666; }
        .footer a { color: #667eea; text-decoration: none; font-weight: 600; }
        .footer a:hover { color: #764ba2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🍕</div>
            <h1>Pizzaria</h1>
            <p>Recuperar Senha</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <?php if ($passo == 1): ?>
            <form method="POST">
                <input type="hidden" name="passo" value="1">
                <div class="form-group">
                    <label for="telefone">Seu Telefone</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(11) 99999-9999" required autocomplete="tel">
                </div>
                <button type="submit" class="btn">Continuar</button>
            </form>
        <?php elseif ($passo == 2): ?>
            <form method="POST">
                <input type="hidden" name="passo" value="2">
                <div class="form-group">
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirmacao">Confirmar Senha</label>
                    <input type="password" id="confirmacao" name="confirmacao" placeholder="Confirme a senha" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn">Atualizar Senha</button>
            </form>
        <?php elseif ($passo == 3): ?>
            <p style="text-align: center; color: #666;">Redirecionando para login...</p>
            <script>setTimeout(() => location.href = '/admin/login.php', 3000);</script>
        <?php endif; ?>
        
        <div class="footer">
            <p><a href="/admin/login.php">Voltar para login</a></p>
        </div>
    </div>
</body>
</html>
