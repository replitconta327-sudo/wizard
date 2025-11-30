let relatorios = {
    clientes: [],
    vendas: [],
    produtos: [],
    desempenho: {},
    faturamento: []
};

// Carregar relatórios ao iniciar
document.addEventListener('DOMContentLoaded', function() {
    carregarRelatorios();
    configurarEventListeners();
});

function configurarEventListeners() {
    // Filtros de relatórios
    document.getElementById('periodoClientes').addEventListener('change', carregarClientesFrequentes);
    document.getElementById('minPedidos').addEventListener('change', carregarClientesFrequentes);
    
    document.getElementById('dataInicioVendas').addEventListener('change', carregarVendasPeriodo);
    document.getElementById('dataFimVendas').addEventListener('change', carregarVendasPeriodo);
    
    document.getElementById('periodoProdutos').addEventListener('change', carregarProdutosMaisVendidos);
    
    document.getElementById('periodoDesempenho').addEventListener('change', carregarDesempenhoEntregas);
    
    document.getElementById('anoFaturamento').addEventListener('change', carregarFaturamentoMensal);
    
    // Botões de exportação
    document.getElementById('exportarClientes').addEventListener('click', exportarClientesCSV);
    document.getElementById('exportarVendas').addEventListener('click', exportarVendasCSV);
    document.getElementById('exportarProdutos').addEventListener('click', exportarProdutosCSV);
}

function carregarRelatorios() {
    carregarClientesFrequentes();
    carregarVendasPeriodo();
    carregarProdutosMaisVendidos();
    carregarDesempenhoEntregas();
    carregarFaturamentoMensal();
}

function carregarClientesFrequentes() {
    const periodo = document.getElementById('periodoClientes').value;
    const minPedidos = document.getElementById('minPedidos').value;
    
    fetch(`/admin/api/reports.php?action=clientes_frequentes&periodo=${periodo}&min_pedidos=${minPedidos}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                relatorios.clientes = data.clientes;
                renderizarClientesFrequentes();
                atualizarGraficoClientes();
            } else {
                mostrarErro('Erro ao carregar clientes frequentes: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao conectar com o servidor');
        });
}

function carregarVendasPeriodo() {
    const dataInicio = document.getElementById('dataInicioVendas').value;
    const dataFim = document.getElementById('dataFimVendas').value;
    
    const params = new URLSearchParams();
    params.append('action', 'vendas_periodo');
    if (dataInicio) params.append('data_inicio', dataInicio);
    if (dataFim) params.append('data_fim', dataFim);
    
    fetch(`/admin/api/reports.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                relatorios.vendas = data.vendas;
                renderizarVendasPeriodo();
                atualizarGraficoVendas();
            } else {
                mostrarErro('Erro ao carregar vendas: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao conectar com o servidor');
        });
}

function carregarProdutosMaisVendidos() {
    const periodo = document.getElementById('periodoProdutos').value;
    
    fetch(`/admin/api/reports.php?action=produtos_mais_vendidos&periodo=${periodo}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                relatorios.produtos = data.produtos;
                renderizarProdutosMaisVendidos();
                atualizarGraficoProdutos();
            } else {
                mostrarErro('Erro ao carregar produtos: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao conectar com o servidor');
        });
}

function carregarDesempenhoEntregas() {
    const periodo = document.getElementById('periodoDesempenho').value;
    
    fetch(`/admin/api/reports.php?action=desempenho_entregas&periodo=${periodo}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                relatorios.desempenho = data.desempenho;
                renderizarDesempenhoEntregas();
                atualizarGraficoDesempenho();
            } else {
                mostrarErro('Erro ao carregar desempenho: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao conectar com o servidor');
        });
}

function carregarFaturamentoMensal() {
    const ano = document.getElementById('anoFaturamento').value;
    
    fetch(`/admin/api/reports.php?action=faturamento_mensal&ano=${ano}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                relatorios.faturamento = data.faturamento;
                renderizarFaturamentoMensal();
                atualizarGraficoFaturamento();
            } else {
                mostrarErro('Erro ao carregar faturamento: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao conectar com o servidor');
        });
}

function renderizarClientesFrequentes() {
    const container = document.getElementById('clientesFrequentesContainer');
    
    if (relatorios.clientes.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h4>Nenhum cliente encontrado</h4>
                    <p>Não há clientes com os critérios selecionados.</p>
                </div>
            </div>
        `;
        return;
    }

    container.innerHTML = relatorios.clientes.map((cliente, index) => `
        <div class="col-md-6 col-lg-4">
            <div class="card cliente-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">#${index + 1} ${cliente.nome}</h5>
                        <span class="badge bg-primary">${cliente.total_pedidos} pedidos</span>
                    </div>
                </div>
                <div class="card-body">
                    <p><strong>Email:</strong> ${cliente.email}</p>
                    <p><strong>Telefone:</strong> ${cliente.telefone}</p>
                    <p><strong>Total Gasto:</strong> R$ ${parseFloat(cliente.total_gasto).toFixed(2)}</p>
                    <p><strong>Ticket Médio:</strong> R$ ${parseFloat(cliente.ticket_medio).toFixed(2)}</p>
                    <p><strong>Último Pedido:</strong> ${cliente.dias_ultimo_pedido} dias atrás</p>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info btn-sm w-100" onclick="verDetalhesCliente(${cliente.id})">
                        <i class="fas fa-eye"></i> Ver Detalhes
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function renderizarVendasPeriodo() {
    const container = document.getElementById('vendasPeriodoContainer');
    const resumo = calcularResumoVendas();
    
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Total de Pedidos</h5>
                        <h3 class="text-primary">${resumo.total_pedidos}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Faturamento Total</h5>
                        <h3 class="text-success">R$ ${resumo.faturamento_total.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Ticket Médio</h5>
                        <h3 class="text-info">R$ ${resumo.ticket_medio.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Média Diária</h5>
                        <h3 class="text-warning">R$ ${resumo.media_diaria.toFixed(2)}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pedidos</th>
                        <th>Faturamento</th>
                        <th>Ticket Médio</th>
                    </tr>
                </thead>
                <tbody>
                    ${relatorios.vendas.map(venda => `
                        <tr>
                            <td>${formatarData(venda.data)}</td>
                            <td>${venda.total_pedidos}</td>
                            <td>R$ ${parseFloat(venda.valor_total).toFixed(2)}</td>
                            <td>R$ ${parseFloat(venda.ticket_medio).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function renderizarProdutosMaisVendidos() {
    const container = document.getElementById('produtosMaisVendidosContainer');
    
    if (relatorios.produtos.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-pizza-slice"></i>
                    <h4>Nenhum produto encontrado</h4>
                    <p>Não há produtos vendidos no período selecionado.</p>
                </div>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Quantidade Vendida</th>
                        <th>Faturamento</th>
                    </tr>
                </thead>
                <tbody>
                    ${relatorios.produtos.map((produto, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td>
                                <strong>${produto.nome}</strong>
                                <br><small class="text-muted">${produto.descricao}</small>
                            </td>
                            <td>${produto.categoria}</td>
                            <td>R$ ${parseFloat(produto.preco).toFixed(2)}</td>
                            <td>${produto.total_quantidade}</td>
                            <td>R$ ${parseFloat(produto.faturamento_total).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function renderizarDesempenhoEntregas() {
    const container = document.getElementById('desempenhoEntregasContainer');
    const desempenho = relatorios.desempenho;
    
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Entregues</h5>
                        <h3 class="text-success">${desempenho.entregues || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Canceladas</h5>
                        <h3 class="text-danger">${desempenho.canceladas || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Em Andamento</h5>
                        <h3 class="text-warning">${desempenho.em_andamento || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Taxa de Sucesso</h5>
                        <h3 class="text-info">${(desempenho.taxa_sucesso || 0).toFixed(1)}%</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Distribuição de Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoStatusEntregas" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Tempo Médio de Entrega</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="text-center">${desempenho.tempo_medio_entrega ? Math.round(desempenho.tempo_medio_entrega) + ' minutos' : 'N/A'}</h3>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderizarFaturamentoMensal() {
    const container = document.getElementById('faturamentoMensalContainer');
    
    if (relatorios.faturamento.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h4>Nenhum dado encontrado</h4>
                    <p>Não há faturamento para o ano selecionado.</p>
                </div>
            </div>
        `;
        return;
    }

    const totalAnual = relatorios.faturamento.reduce((sum, item) => sum + parseFloat(item.faturamento_total), 0);
    
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Faturamento Total do Ano</h5>
                        <h2 class="text-success">R$ ${totalAnual.toFixed(2)}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Pedidos</th>
                        <th>Faturamento</th>
                        <th>Ticket Médio</th>
                    </tr>
                </thead>
                <tbody>
                    ${relatorios.faturamento.map(item => `
                        <tr>
                            <td>${item.nome_mes}</td>
                            <td>${item.total_pedidos}</td>
                            <td>R$ ${parseFloat(item.faturamento_total).toFixed(2)}</td>
                            <td>R$ ${parseFloat(item.ticket_medio).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Gráfico de Faturamento Mensal</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoFaturamento" width="800" height="400"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function calcularResumoVendas() {
    const total_pedidos = relatorios.vendas.reduce((sum, venda) => sum + parseInt(venda.total_pedidos), 0);
    const faturamento_total = relatorios.vendas.reduce((sum, venda) => sum + parseFloat(venda.valor_total), 0);
    const ticket_medio = total_pedidos > 0 ? faturamento_total / total_pedidos : 0;
    const dias = relatorios.vendas.length || 1;
    const media_diaria = faturamento_total / dias;
    
    return {
        total_pedidos,
        faturamento_total,
        ticket_medio,
        media_diaria
    };
}

function atualizarGraficoClientes() {
    // Implementar gráfico de clientes frequentes
    const ctx = document.getElementById('graficoClientes');
    if (ctx) {
        // Código para criar gráfico usando Chart.js ou similar
    }
}

function atualizarGraficoVendas() {
    const ctx = document.getElementById('graficoVendas');
    if (ctx) {
        // Código para criar gráfico de vendas por período
    }
}

function atualizarGraficoProdutos() {
    const ctx = document.getElementById('graficoProdutos');
    if (ctx) {
        // Código para criar gráfico de produtos mais vendidos
    }
}

function atualizarGraficoDesempenho() {
    const ctx = document.getElementById('graficoStatusEntregas');
    if (ctx) {
        // Código para criar gráfico de status de entregas
    }
}

function atualizarGraficoFaturamento() {
    const ctx = document.getElementById('graficoFaturamento');
    if (ctx) {
        // Código para criar gráfico de faturamento mensal
    }
}

function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function exportarClientesCSV() {
    const csv = [
        ['Nome', 'Email', 'Telefone', 'Total Pedidos', 'Total Gasto', 'Ticket Médio', 'Dias Último Pedido'],
        ...relatorios.clientes.map(cliente => [
            cliente.nome,
            cliente.email,
            cliente.telefone,
            cliente.total_pedidos,
            cliente.total_gasto,
            cliente.ticket_medio,
            cliente.dias_ultimo_pedido
        ])
    ].map(row => row.join(',')).join('\n');
    
    downloadCSV(csv, 'clientes_frequentes.csv');
}

function exportarVendasCSV() {
    const csv = [
        ['Data', 'Total Pedidos', 'Faturamento', 'Ticket Médio'],
        ...relatorios.vendas.map(venda => [
            venda.data,
            venda.total_pedidos,
            venda.valor_total,
            venda.ticket_medio
        ])
    ].map(row => row.join(',')).join('\n');
    
    downloadCSV(csv, 'vendas_periodo.csv');
}

function exportarProdutosCSV() {
    const csv = [
        ['Produto', 'Categoria', 'Preço', 'Quantidade Vendida', 'Faturamento'],
        ...relatorios.produtos.map(produto => [
            produto.nome,
            produto.categoria,
            produto.preco,
            produto.total_quantidade,
            produto.faturamento_total
        ])
    ].map(row => row.join(',')).join('\n');
    
    downloadCSV(csv, 'produtos_mais_vendidos.csv');
}

function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

function mostrarErro(mensagem) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i> ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => alert.remove(), 5000);
}