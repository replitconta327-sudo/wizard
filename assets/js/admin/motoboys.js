// Mapa de rastreamento
let trackingMap;
let motoboyMarkers = {};
let deliveryRoutes = {};

// Inicializar mapa
document.addEventListener('DOMContentLoaded', function() {
    initTrackingMap();
    startRealTimeTracking();
});

function initTrackingMap() {
    trackingMap = L.map('tracking-map').setView([-23.5505, -46.6333], 12); // São Paulo
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(trackingMap);
    
    // Adicionar marcadores dos motoboys
    updateMotoboyLocations();
}

function updateMotoboyLocations() {
    fetch('/admin/api/motoboy_locations.php')
        .then(response => response.json())
        .then(data => {
            // Limpar marcadores antigos
            Object.values(motoboyMarkers).forEach(marker => {
                trackingMap.removeLayer(marker);
            });
            motoboyMarkers = {};
            
            // Adicionar novos marcadores
            data.locations.forEach(location => {
                const marker = L.marker([location.latitude, location.longitude])
                    .addTo(trackingMap)
                    .bindPopup(`
                        <strong>${location.motoboy_nome}</strong><br>
                        Status: ${location.status}<br>
                        Velocidade: ${location.velocidade} km/h<br>
                        Bateria: ${location.bateria}%<br>
                        Última atualização: ${location.timestamp}
                    `);
                
                motoboyMarkers[location.motoboy_id] = marker;
            });
        })
        .catch(error => console.error('Erro ao buscar localizações:', error));
}

function startRealTimeTracking() {
    // Atualizar a cada 30 segundos
    setInterval(updateMotoboyLocations, 30000);
}

function showAddMotoboyModal() {
    document.getElementById('addMotoboyModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function toggleMotoboyStatus(motoboyId, ativo, disponivel) {
    if (confirm('Tem certeza que deseja alterar o status deste motoboy?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="motoboy_id" value="${motoboyId}">
            <input type="hidden" name="ativo" value="${ativo}">
            <input type="hidden" name="disponivel" value="${disponivel}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewMotoboyLocation(motoboyId) {
    const marker = motoboyMarkers[motoboyId];
    if (marker) {
        trackingMap.setView(marker.getLatLng(), 15);
        marker.openPopup();
    } else {
        alert('Localização não disponível para este motoboy.');
    }
}

function assignDelivery(entregaId, motoboyId) {
    if (!motoboyId) return;
    
    if (confirm('Atribuir esta entrega ao motoboy selecionado?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" value="assign_delivery">
            <input type="hidden" name="entrega_id" value="${entregaId}">
            <input type="hidden" name="motoboy_id" value="${motoboyId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewDeliveryRoute(entregaId) {
    fetch(`/admin/api/delivery_route.php?id=${entregaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.route) {
                // Centralizar mapa na rota
                trackingMap.setView([data.route.lat, data.route.lng], 14);
                
                // Mostrar popup com informações da rota
                L.popup()
                    .setLatLng([data.route.lat, data.route.lng])
                    .setContent(`
                        <strong>Entrega #${data.entrega.numero_pedido}</strong><br>
                        Cliente: ${data.entrega.cliente_nome}<br>
                        Status: ${data.entrega.status}<br>
                        Motoboy: ${data.entrega.motoboy_nome || 'N/A'}
                    `)
                    .openOn(trackingMap);
            } else {
                alert('Rota não disponível para esta entrega.');
            }
        })
        .catch(error => {
            console.error('Erro ao buscar rota:', error);
            alert('Erro ao carregar rota da entrega.');
        });
}

function markDelivered(entregaId) {
    if (confirm('Marcar esta entrega como entregue?')) {
        fetch('/admin/api/update_delivery_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                entrega_id: entregaId,
                status: 'entregue',
                csrf: '<?php echo $csrf; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao atualizar status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar status da entrega.');
        });
    }
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Auto-atualização de status
setInterval(function() {
    // Atualizar contagens e status
    fetch('/admin/api/delivery_counts.php')
        .then(response => response.json())
        .then(data => {
            // Atualizar estatísticas na interface
            updateDashboardStats(data);
        })
        .catch(error => console.error('Erro ao buscar contagens:', error));
}, 10000); // Atualizar a cada 10 segundos

function updateDashboardStats(data) {
    // Implementar atualização das estatísticas do dashboard
    console.log('Dashboard stats updated:', data);
}