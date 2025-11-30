let entregas = [];
let currentFilters = {
    status: '',
    data_inicio: '',
    data_fim: ''
};

// Carregar entregas ao iniciar
document.addEventListener('DOMContentLoaded', function() {
    carregarEntregas();
    configurarEventListeners();
    carregarMotoboys();
});

function configurarEventListeners() {
    // Filtros
    document.getElementById('filtroStatus').addEventListener('change', aplicarFiltros);
    document.getElementById('filtroDataInicio').addEventListener('change', aplicarFiltros);
    document.getElementById('filtroDataFim').addEventListener('change', aplicarFiltros);
    
    // Botão limpar filtros
    document.getElementById('btnLimparFiltros').addEventListener('click', limparFiltros);
    
    // Modal de detalhes
    document.getElementById('modalDetalhes').addEventListener('hidden.bs.modal', function() {
        document.getElementById('historicoContainer').innerHTML = '';
    });
}

function carregarEntregas() {
    const params = new URLSearchParams();
    params.append('action', 'listar');
    if (currentFilters.status) params.append('status', currentFilters.status);
    if (currentFilters.data_inicio) params.append('data_inicio', currentFilters.data_inicio);
    if (currentFilters.data_fim) params.append('data_fim', currentFilters.data_fim);
    
    fetch(`/admin/api/deliveries.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                entregas = data.entregas;
                renderizarEntregas();
            } else {
                mostrarErro('Erro ao carregar entregas: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao conectar com o servidor');
        });
}

function carregarMotoboys() {
    fetch('/admin/api/motoboys.php?action=listar_disponiveis')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                window.motoboysDisponiveis = data.motoboys;
            }
        })
        .catch(error => console.error('Erro ao carregar motoboys:', error));
}

function aplicarFiltros() {
    currentFilters.status = document.getElementById('filtroStatus').value;
    currentFilters.data_inicio = document.getElementById('filtroDataInicio').value;
    currentFilters.data_fim = document.getElementById('filtroDataFim').value;
    
    carregarEntregas();
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroDataInicio').value = '';
    document.getElementById('filtroDataFim').value = '';
    
    currentFilters = { status: '', data_inicio: '', data_fim: '' };
    carregarEntregas();
}

function renderizarEntregas() {
    const container = document.getElementById('entregasContainer');
    
    if (entregas.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-motorcycle"></i>
                    <h4>Nenhuma entrega encontrada</h4>
                    <p>Não há entregas com os filtros selecionados.</p>
                </div>
            </div>
        `;
        return;
    }

    container.innerHTML = entregas.map(entrega => `
        <div class="col-md-6 col-lg-4">
            <div class="card entrega-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pedido #${entrega.numero_pedido}</h5>
                        <span class="badge" style="background-color: ${entrega.status_cor}">
                            ${entrega.status_nome}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> ${entrega.cliente_nome}</p>
                    <p><strong>Endereço:</strong> ${entrega.logradouro}, ${entrega.numero} - ${entrega.bairro}</p>
                    ${entrega.complemento ? `<p><strong>Complemento:</strong> ${entrega.complemento}</p>` : ''}
                    <p><strong>Telefone:</strong> ${entrega.cliente_telefone}</p>
                    ${entrega.motoboy_nome ? `<p><strong>Motoboy:</strong> ${entrega.motoboy_nome}</p>` : '<p><strong>Motoboy:</strong> <span class="text-warning">Não atribuído</span></p>'}
                    <p><strong>Criado em:</strong> ${formatarData(entrega.criado_em)}</p>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-info btn-sm" onclick="verDetalhes(${entrega.id})">
                            <i class="fas fa-eye"></i> Detalhes
                        </button>
                        ${renderizarAcoesStatus(entrega)}
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function renderizarAcoesStatus(entrega) {
    const statusActions = {
        'pendente': `
            <button class="btn btn-primary btn-sm" onclick="atribuirMotoboy(${entrega.id})">
                <i class="fas fa-user-plus"></i> Atribuir
            </button>
        `,
        'atribuida': `
            <button class="btn btn-warning btn-sm" onclick="atualizarStatus(${entrega.id}, 'coletada')">
                <i class="fas fa-box"></i> Coletar
            </button>
        `,
        'coletada': `
            <button class="btn btn-info btn-sm" onclick="atualizarStatus(${entrega.id}, 'em_transito')">
                <i class="fas fa-route"></i> Sair
            </button>
        `,
        'em_transito': `
            <button class="btn btn-success btn-sm" onclick="atualizarStatus(${entrega.id}, 'entregue')">
                <i class="fas fa-check"></i> Entregar
            </button>
        `,
        'entregue': '',
        'cancelada': ''
    };
    
    return statusActions[entrega.status_entrega] || '';
}

function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function verDetalhes(entrega_id) {
    Promise.all([
        fetch(`/admin/api/deliveries.php?action=detalhes&entrega_id=${entrega_id}`).then(r => r.json()),
        fetch(`/admin/api/deliveries.php?action=historico&entrega_id=${entrega_id}`).then(r => r.json())
    ])
    .then(([detalhesData, historicoData]) => {
        if (detalhesData.sucesso && historicoData.sucesso) {
            mostrarModalDetalhes(detalhesData.entrega, historicoData.historico);
        } else {
            mostrarErro('Erro ao carregar detalhes da entrega');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarErro('Erro ao conectar com o servidor');
    });
}

function mostrarModalDetalhes(entrega, historico) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
    
    // Preencher informações da entrega
    document.getElementById('detalheNumeroPedido').textContent = entrega.numero_pedido;
    document.getElementById('detalheCliente').textContent = entrega.cliente_nome;
    document.getElementById('detalheTelefone').textContent = entrega.cliente_telefone;
    document.getElementById('detalheEmail').textContent = entrega.cliente_email;
    document.getElementById('detalheEndereco').textContent = 
        `${entrega.logradouro}, ${entrega.numero} - ${entrega.bairro}${entrega.complemento ? ' - ' + entrega.complemento : ''}`;
    document.getElementById('detalheCEP').textContent = entrega.cep;
    document.getElementById('detalheValor').textContent = `R$ ${parseFloat(entrega.valor_pedido).toFixed(2)}`;
    document.getElementById('detalheFormaPagamento').textContent = entrega.forma_pagamento;
    document.getElementById('detalheMotoboy').textContent = entrega.motoboy_nome || 'Não atribuído';
    document.getElementById('detalheStatus').innerHTML = 
        `<span class="badge" style="background-color: ${entrega.status_cor}">${entrega.status_nome}</span>`;
    
    // Preencher histórico
    const historicoContainer = document.getElementById('historicoContainer');
    if (historico.length === 0) {
        historicoContainer.innerHTML = '<p class="text-muted">Nenhum histórico disponível.</p>';
    } else {
        historicoContainer.innerHTML = historico.map(item => `
            <div class="timeline-item">
                <div class="timeline-time">${formatarData(item.criado_em)}</div>
                <div class="timeline-content">
                    <strong>${item.usuario_nome || 'Sistema'}</strong>
                    <p>Mudou status de <span class="badge bg-secondary">${item.status_anterior_nome}</span> para 
                       <span class="badge bg-primary">${item.status_novo_nome}</span></p>
                    ${item.observacao ? `<small class="text-muted">${item.observacao}</small>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    modal.show();
}

function atualizarStatus(entrega_id, novo_status) {
    const observacao = prompt('Observação (opcional):');
    if (observacao === null) return;
    
    fetch('/admin/api/deliveries.php?action=atualizar_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            entrega_id: entrega_id,
            novo_status: novo_status,
            observacao: observacao
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            mostrarSucesso('Status atualizado com sucesso!');
            carregarEntregas();
        } else {
            mostrarErro(data.erro || 'Erro ao atualizar status');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarErro('Erro ao conectar com o servidor');
    });
}

function atribuirMotoboy(entrega_id) {
    if (!window.motoboysDisponiveis || window.motoboysDisponiveis.length === 0) {
        mostrarErro('Nenhum motoboy disponível');
        return;
    }
    
    const motoboy_id = prompt(
        'Selecione o motoboy:\n' + 
        window.motoboysDisponiveis.map(m => `${m.id} - ${m.nome} (${m.telefone})`).join('\n')
    );
    
    if (!motoboy_id || isNaN(motoboy_id)) return;
    
    fetch('/admin/api/deliveries.php?action=atribuir_motoboy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            entrega_id: entrega_id,
            motoboy_id: parseInt(motoboy_id)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            mostrarSucesso('Motoboy atribuído com sucesso!');
            carregarEntregas();
            carregarMotoboys(); // Recarregar lista de motoboys disponíveis
        } else {
            mostrarErro(data.erro || 'Erro ao atribuir motoboy');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarErro('Erro ao conectar com o servidor');
    });
}

function mostrarSucesso(mensagem) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-check-circle"></i> ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => alert.remove(), 5000);
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