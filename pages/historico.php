<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$usuario_id = $_SESSION['user_id'];
$db = new Database();
$pdo = $db->pdo();

// Parâmetros de filtro
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';

// Buscar histórico de pedidos
try {
    $sql = "
        SELECT 
            p.id,
            p.numero_pedido,
            p.status,
            p.subtotal,
            p.taxa_servico,
            p.total,
            p.tipo_entrega,
            p.data_entrega,
            p.criado_em,
            p.atualizado_em,
            e.apelido as endereco_apelido,
            e.logradouro,
            e.numero,
            e.bairro,
            COUNT(pb.id) as total_itens
        FROM pedidos p
        LEFT JOIN enderecos e ON p.endereco_id = e.id
        LEFT JOIN pedido_bebidas pb ON p.id = pb.pedido_id
        WHERE p.usuario_id = ?
    ";
    
    $params = [$usuario_id];
    
    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
    }
    
    if ($busca) {
        $sql .= " AND (p.numero_pedido LIKE ? OR e.apelido LIKE ?)";
        $params[] = "%{$busca}%";
        $params[] = "%{$busca}%";
    }
    
    if ($data_inicio && $data_fim) {
        $sql .= " AND DATE(p.criado_em) BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
    }
    
    $sql .= " GROUP BY p.id ORDER BY p.criado_em DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $pedidos = [];
    $erro = "Erro ao carregar histórico: " . $e->getMessage();
}

// Buscar status únicos para filtro
$status_list = ['pendente', 'confirmado', 'preparando', 'saiu_entrega', 'entregue', 'cancelado'];

// Buscar estatísticas
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_pedidos,
            SUM(total) as valor_total,
            AVG(total) as valor_medio,
            status
        FROM pedidos 
        WHERE usuario_id = ? AND DATE(criado_em) BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $estatisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $estatisticas = [];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pedidos - Pizzaria</title>
    <link rel="stylesheet" href="assets/css/pages/historico.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="historico-container">
        <!-- Header -->
        <header class="historico-header">
            <div class="container">
                <div class="header-content">
                    <a href="revisao.php" class="btn-voltar">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                    <h1>Histórico de Pedidos</h1>
                    <div class="header-info">
                        <span class="total-pedidos"><?php echo count($pedidos); ?> pedidos</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filtros -->
        <section class="filtros-section">
            <div class="container">
                <form method="GET" class="filtros-form">
                    <div class="filtros-grid">
                        <div class="form-group">
                            <label for="data_inicio">Data Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" 
                                   value="<?php echo htmlspecialchars($data_inicio); ?>" 
                                   class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="data_fim">Data Fim</label>
                            <input type="date" id="data_fim" name="data_fim" 
                                   value="<?php echo htmlspecialchars($data_fim); ?>" 
                                   class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">Todos os status</option>
                                <?php foreach ($status_list as $s): ?>
                                    <option value="<?php echo $s; ?>" 
                                            <?php echo $status === $s ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="busca">Buscar</label>
                            <input type="text" id="busca" name="busca" 
                                   value="<?php echo htmlspecialchars($busca); ?>" 
                                   placeholder="Número do pedido ou endereço..." 
                                   class="form-control">
                        </div>
                    </div>
                    <div class="filtros-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            Aplicar Filtros
                        </button>
                        <a href="historico.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Limpar
                        </a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Estatísticas -->
        <?php if (!empty($estatisticas)): ?>
            <section class="estatisticas-section">
                <div class="container">
                    <h2>Resumo do Período</h2>
                    <div class="estatisticas-grid">
                        <?php foreach ($estatisticas as $stat): ?>
                            <div class="estatistica-card status-<?php echo $stat['status']; ?>">
                                <div class="estatistica-icon">
                                    <i class="fas fa-<?php echo getStatusIcon($stat['status']); ?>"></i>
                                </div>
                                <div class="estatistica-content">
                                    <h3><?php echo ucfirst(str_replace('_', ' ', $stat['status'])); ?></h3>
                                    <p class="quantidade"><?php echo $stat['total_pedidos']; ?> pedidos</p>
                                    <p class="valor">R$ <?php echo number_format($stat['valor_total'], 2, ',', '.'); ?></p>
                                    <p class="media">Média: R$ <?php echo number_format($stat['valor_medio'], 2, ',', '.'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Lista de Pedidos -->
        <section class="pedidos-section">
            <div class="container">
                <?php if (empty($pedidos)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h2>Nenhum pedido encontrado</h2>
                        <p>Não há pedidos no período selecionado.</p>
                        <a href="bebidas.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Fazer Novo Pedido
                        </a>
                    </div>
                <?php else: ?>
                    <div class="pedidos-list">
                        <?php foreach ($pedidos as $pedido): ?>
                            <div class="pedido-card status-<?php echo $pedido['status']; ?>">
                                <div class="pedido-header">
                                    <div class="pedido-info">
                                        <h3 class="pedido-numero">#<?php echo htmlspecialchars($pedido['numero_pedido']); ?></h3>
                                        <span class="pedido-status status-<?php echo $pedido['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="pedido-data">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['criado_em'])); ?>
                                    </div>
                                </div>

                                <div class="pedido-content">
                                    <div class="pedido-detalhes">
                                        <div class="detalhe-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($pedido['endereco_apelido'] ?? 'Endereço não informado'); ?></span>
                                        </div>
                                        <div class="detalhe-item">
                                            <i class="fas fa-glass-cheers"></i>
                                            <span><?php echo $pedido['total_itens']; ?> itens</span>
                                        </div>
                                        <div class="detalhe-item">
                                            <i class="fas fa-truck"></i>
                                            <span><?php echo $pedido['tipo_entrega'] === 'agendada' ? 'Entrega agendada' : 'Entrega imediata'; ?></span>
                                        </div>
                                    </div>

                                    <div class="pedido-valores">
                                        <div class="valor-item">
                                            <span class="valor-label">Subtotal:</span>
                                            <span class="valor">R$ <?php echo number_format($pedido['subtotal'], 2, ',', '.'); ?></span>
                                        </div>
                                        <div class="valor-item">
                                            <span class="valor-label">Taxa de serviço:</span>
                                            <span class="valor">R$ <?php echo number_format($pedido['taxa_servico'], 2, ',', '.'); ?></span>
                                        </div>
                                        <div class="valor-item total">
                                            <span class="valor-label">Total:</span>
                                            <span class="valor">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pedido-actions">
                                    <button type="button" class="btn btn-outline" onclick="verDetalhes('<?php echo $pedido['id']; ?>')">
                                        <i class="fas fa-eye"></i>
                                        Ver Detalhes
                                    </button>
                                    <?php if ($pedido['status'] === 'pendente'): ?>
                                        <button type="button" class="btn btn-primary" onclick="confirmarPedido('<?php echo $pedido['id']; ?>')">
                                            <i class="fas fa-check"></i>
                                            Confirmar Pagamento
                                        </button>
                                    <?php endif; ?>
                                    <?php if (in_array($pedido['status'], ['pendente', 'confirmado'])): ?>
                                        <button type="button" class="btn btn-danger" onclick="cancelarPedido('<?php echo $pedido['id']; ?>')">
                                            <i class="fas fa-times"></i>
                                            Cancelar
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-success" onclick="repetirPedido('<?php echo $pedido['id']; ?>')">
                                        <i class="fas fa-redo"></i>
                                        Repetir Pedido
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Modal de Detalhes -->
    <div id="modal-detalhes" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Detalhes do Pedido</h3>
                <button class="modal-close" onclick="fecharModal('modal-detalhes')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="detalhes-content">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal('modal-detalhes')">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Processando...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <script src="assets/js/historico.js"></script>
</body>
</html>

<?php
// Função auxiliar para ícones de status
function getStatusIcon($status) {
    $icons = [
        'pendente' => 'clock',
        'confirmado' => 'check-circle',
        'preparando' => 'utensils',
        'saiu_entrega' => 'truck',
        'entregue' => 'home',
        'cancelado' => 'times-circle'
    ];
    return $icons[$status] ?? 'question-circle';
}
?>