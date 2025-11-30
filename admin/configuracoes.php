<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /admin/login.php');
    exit;
}

try {
    $database = new Database();
    $pdo = $database->pdo();
    $usuario_result = $pdo->query("SELECT nome FROM usuarios WHERE id = " . $_SESSION['usuario_id'])->fetch(PDO::FETCH_ASSOC);
    $usuario = $usuario_result ?: ['nome' => 'Admin'];
} catch (Exception $e) {
    $usuario = ['nome' => 'Admin'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurações - Pizzaria</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: #f5f5f5; }

        .layout { display: flex; height: 100vh; }
        .sidebar {
            width: 250px;
            background: #1a1a1a;
            color: white;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
        }
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 2px solid #333;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 1.2rem;
        }
        .sidebar-title h2, .sidebar-title p { margin: 0; }
        .sidebar-title h2 { font-size: 1.1rem; font-weight: 700; }
        .sidebar-title p { font-size: 0.8rem; opacity: 0.7; }
        .sidebar-menu {
            flex: 1;
            padding: 2rem 0;
        }
        .menu-item {
            padding: 1rem 1.5rem;
            padding-left: 1.5rem;
            color: #ddd;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: transparent;
            transition: background 0.2s;
        }
        .menu-item:hover, .menu-item.active {
            background: #333;
            color: white;
        }
        .menu-item.active::before {
            background: #4CAF50;
        }
        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid #333;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .user-info h4, .user-info p { margin: 0; }
        .user-info h4 { font-size: 0.9rem; }
        .user-info p { font-size: 0.75rem; opacity: 0.7; }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .top-bar h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #000; }
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #ddd;
        }
        .tab-btn {
            padding: 1rem 1.5rem;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: #4CAF50;
            border-bottom-color: #4CAF50;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f9f9f9;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover { background: #f9f9f9; }
        .btn-sm {
            padding: 0.5rem 1rem;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-sm:hover { background: #1976D2; }
        .btn-del {
            background: #DC3545;
        }
        .btn-del:hover {
            background: #c82333;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .btn-salvar {
            background: #4CAF50;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-salvar:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">🍕</div>
                <div class="sidebar-title">
                    <h2>Pizzaria</h2>
                    <p>São Paulo</p>
                </div>
            </div>

            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <span>📊</span> Dashboard
                </a>
                <a href="pedidos.php" class="menu-item">
                    <span>📋</span> Gerenciar Pedidos
                </a>
                <a href="configuracoes.php" class="menu-item active">
                    <span>⚙️</span> Configurações
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="user-avatar"><?php echo strtoupper(substr($usuario['nome'] ?? 'A', 0, 1)); ?></div>
                <div class="user-info" style="flex: 1;">
                    <h4><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                    <p>Admin</p>
                </div>
                <a href="logout.php" style="color: #ddd; text-decoration: none; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: all 0.2s;" title="Sair"
                   onmouseover="this.style.background='#333'; this.style.color='white';"
                   onmouseout="this.style.background='none'; this.style.color='#ddd';">🚪</a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Configurações do Sistema</h1>
            </div>

            <div class="content-area">
                <div class="tabs">
                    <button class="tab-btn active" onclick="mudarAba('pizzas')">🍕 Pizzas</button>
                    <button class="tab-btn" onclick="mudarAba('bebidas')">🍹 Bebidas</button>
                    <button class="tab-btn" onclick="mudarAba('bairros')">📍 Bairros</button>
                    <button class="tab-btn" onclick="mudarAba('adicionais')">➕ Adicionais</button>
                    <button class="tab-btn" onclick="mudarAba('promocoes')">🎁 Promoções</button>
                    <button class="tab-btn" onclick="mudarAba('status')">📊 Status</button>
                </div>

                <!-- ABA PIZZAS -->
                <div id="pizzas" class="tab-content active">
                    <h2>Gerenciar Pizzas</h2>
                    <p>Aqui você pode visualizar e gerenciar todas as pizzas do cardápio.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Preço M</th>
                                    <th>Preço G</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="pizzas-table">
                                <tr><td colspan="6" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ABA BEBIDAS -->
                <div id="bebidas" class="tab-content">
                    <h2>Gerenciar Bebidas</h2>
                    <p>Aqui você pode visualizar e gerenciar todas as bebidas.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Volume</th>
                                    <th>Preço</th>
                                    <th>Estoque</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="bebidas-table">
                                <tr><td colspan="6" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ABA BAIRROS -->
                <div id="bairros" class="tab-content">
                    <h2>Gerenciar Bairros</h2>
                    <p>Configure os bairros e suas taxas de entrega.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Bairro</th>
                                    <th>Taxa Entrega</th>
                                    <th>Tempo Est.</th>
                                    <th>Ativo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="bairros-table">
                                <tr><td colspan="5" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ABA ADICIONAIS -->
                <div id="adicionais" class="tab-content">
                    <h2>Gerenciar Adicionais</h2>
                    <p>Mantenha os extras (catupiry, bacon, etc) do cardápio.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Preço</th>
                                    <th>Ativo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="adicionais-table">
                                <tr><td colspan="4" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ABA PROMOÇÕES -->
                <div id="promocoes" class="tab-content">
                    <h2>Gerenciar Promoções</h2>
                    <p>Configure as promoções e descontos do período.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Preço</th>
                                    <th>Desconto</th>
                                    <th>Ativo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="promocoes-table">
                                <tr><td colspan="6" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ABA STATUS -->
                <div id="status" class="tab-content">
                    <h2>Status dos Pedidos</h2>
                    <p>Configure os status disponíveis para os pedidos.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Descrição</th>
                                    <th>Cor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="status-table">
                                <tr><td colspan="4" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mudarAba(aba) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(aba).classList.add('active');
            event.target.classList.add('active');
            carregarDados(aba);
        }

        function carregarDados(aba) {
            const tabelas = {
                'pizzas': 'SELECT c.nome as categoria, p.nome, p.descricao, p.preco_m, p.preco_g FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id',
                'bebidas': 'SELECT b.nome, bc.nome as categoria, b.volume, b.preco, b.estoque FROM bebidas b LEFT JOIN bebidas_categorias bc ON b.categoria_id = bc.id',
                'bairros': 'SELECT nome, taxa_entrega, tempo_estimado, ativo FROM bairros',
                'adicionais': 'SELECT nome, preco, ativo FROM adicionais',
                'promocoes': 'SELECT nome, descricao, preco, desconto, ativo FROM promocoes',
                'status': 'SELECT nome, descricao, cor FROM status_pedido'
            };

            if (tabelas[aba]) {
                fetch(`../api/get_config.php?tabela=${aba}`)
                    .then(r => r.json())
                    .then(dados => renderizarDados(aba, dados))
                    .catch(e => console.error('Erro:', e));
            }
        }

        function renderizarDados(aba, dados) {
            let html = '';
            if (aba === 'pizzas' && dados.length) {
                html = dados.map(p => `
                    <tr>
                        <td>${p.categoria || '-'}</td>
                        <td>${p.nome}</td>
                        <td>${p.descricao || '-'}</td>
                        <td>R$ ${parseFloat(p.preco_m).toFixed(2)}</td>
                        <td>R$ ${parseFloat(p.preco_g).toFixed(2)}</td>
                        <td><button class="btn-sm" onclick="editarItem('pizzas', ${p.id})">✏️</button> <button class="btn-sm" onclick="deletarItem('pizzas', ${p.id})" style="background: #c33;">🗑️</button></td>
                    </tr>
                `).join('');
            } else if (aba === 'bebidas' && dados.length) {
                html = dados.map(b => `
                    <tr>
                        <td>${b.nome}</td>
                        <td>${b.categoria || '-'}</td>
                        <td>${b.volume}</td>
                        <td>R$ ${parseFloat(b.preco).toFixed(2)}</td>
                        <td>${b.estoque}</td>
                        <td><button class="btn-sm" onclick="editarItem('bebidas', ${b.id})">✏️</button> <button class="btn-sm" onclick="deletarItem('bebidas', ${b.id})" style="background: #c33;">🗑️</button></td>
                    </tr>
                `).join('');
            } else if (aba === 'bairros' && dados.length) {
                html = dados.map(b => `
                    <tr>
                        <td>${b.nome}</td>
                        <td>R$ ${parseFloat(b.taxa_entrega).toFixed(2)}</td>
                        <td>${b.tempo_estimado}min</td>
                        <td>${b.ativo ? '✓ Sim' : '✗ Não'}</td>
                        <td><button class="btn-sm" onclick="editarItem('bairros', ${b.id})">✏️</button> <button class="btn-sm" onclick="deletarItem('bairros', ${b.id})" style="background: #c33;">🗑️</button></td>
                    </tr>
                `).join('');
            } else if (aba === 'adicionais' && dados.length) {
                html = dados.map(a => `
                    <tr>
                        <td>${a.nome}</td>
                        <td>R$ ${parseFloat(a.preco).toFixed(2)}</td>
                        <td>${a.ativo ? '✓ Sim' : '✗ Não'}</td>
                        <td><button class="btn-sm" onclick="editarItem('adicionais', ${a.id})">✏️</button> <button class="btn-sm" onclick="deletarItem('adicionais', ${a.id})" style="background: #c33;">🗑️</button></td>
                    </tr>
                `).join('');
            } else if (aba === 'promocoes' && dados.length) {
                html = dados.map(p => `
                    <tr>
                        <td>${p.nome}</td>
                        <td>${p.descricao || '-'}</td>
                        <td>R$ ${parseFloat(p.preco).toFixed(2)}</td>
                        <td>R$ ${parseFloat(p.desconto).toFixed(2)}</td>
                        <td>${p.ativo ? '✓ Sim' : '✗ Não'}</td>
                        <td><button class="btn-sm" onclick="editarItem('promocoes', ${p.id})">✏️</button> <button class="btn-sm" onclick="deletarItem('promocoes', ${p.id})" style="background: #c33;">🗑️</button></td>
                    </tr>
                `).join('');
            } else if (aba === 'status' && dados.length) {
                html = dados.map(s => `
                    <tr>
                        <td>${s.nome}</td>
                        <td>${s.descricao || '-'}</td>
                        <td><span style="background: ${s.cor}; color: white; padding: 0.25rem 0.5rem; border-radius: 3px;">${s.cor}</span></td>
                        <td><button class="btn-sm" onclick="editarItem('status', ${s.id})">✏️</button></td>
                    </tr>
                `).join('');
            }

            const tableId = `${aba}-table`;
            const table = document.getElementById(tableId);
            if (table) {
                table.innerHTML = html || `<tr><td colspan="6" style="text-align: center; padding: 2rem;">Nenhum dado encontrado</td></tr>`;
            }
        }

        function deletarItem(tabela, id) {
            if (confirm('Tem certeza que deseja deletar?')) {
                fetch('../api/admin_config.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `acao=deletar&tabela=${tabela}&id=${id}`
                }).then(r => r.json()).then(d => {
                    alert(d.mensagem || d.erro);
                    carregarDados(tabela);
                });
            }
        }

        function editarItem(tabela, id) {
            alert('Edição será implementada na próxima versão. ID: ' + id);
        }

        window.addEventListener('load', () => carregarDados('pizzas'));
    </script>
</body>
</html>
