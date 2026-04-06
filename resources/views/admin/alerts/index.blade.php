@extends('layouts.appAdmin')

@section('title', 'Alertas del Sistema')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i>
                        Alertas del Sistema
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="markAllRead">
                            <i class="fas fa-check-double"></i>
                            Marcar Todas como Leídas
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="alertsContainer">
                        <!-- Las alertas se cargarán aquí dinámicamente -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando alertas...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar detalles de la alerta -->
<div class="modal fade" id="alertModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertModalTitle">Detalles de la Alerta</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="alertModalBody">
                <!-- Contenido de la alerta -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" id="alertActionButton" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    Ver Detalles
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.alert-item {
    border-left: 4px solid;
    margin-bottom: 15px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.alert-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.alert-item.unread {
    background: #f8f9fa;
    border-left-color: #007bff;
}

.alert-item.info {
    border-left-color: #17a2b8;
}

.alert-item.warning {
    border-left-color: #ffc107;
}

.alert-item.error {
    border-left-color: #dc3545;
}

.alert-item.success {
    border-left-color: #28a745;
}

.alert-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.alert-title {
    font-weight: 600;
    color: #333;
    margin: 0;
    flex: 1;
}

.alert-time {
    font-size: 0.85em;
    color: #666;
    margin-left: 15px;
}

.alert-content {
    color: #555;
    margin-bottom: 10px;
    line-height: 1.4;
}

.alert-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.alert-badge {
    font-size: 0.75em;
    padding: 2px 8px;
    border-radius: 12px;
    color: white;
    font-weight: 500;
}

.alert-badge.info { background: #17a2b8; }
.alert-badge.warning { background: #ffc107; color: #212529; }
.alert-badge.error { background: #dc3545; }
.alert-badge.success { background: #28a745; }

.btn-mark-read {
    padding: 4px 8px;
    font-size: 0.8em;
}

.empty-alerts {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-alerts i {
    font-size: 3em;
    color: #ddd;
    margin-bottom: 15px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadAlerts();
    
    // Marcar todas como leídas
    document.getElementById('markAllRead').addEventListener('click', function() {
        markAllAsRead();
    });
});

function loadAlerts() {
    fetch('/api/alerts/unread')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAlerts(data.alerts);
            } else {
                showError('Error al cargar las alertas');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al cargar las alertas');
        });
}

function displayAlerts(alerts) {
    const container = document.getElementById('alertsContainer');
    
    if (alerts.length === 0) {
        container.innerHTML = `
            <div class="empty-alerts">
                <i class="fas fa-bell-slash"></i>
                <h4>No hay alertas pendientes</h4>
                <p>¡Todo está bajo control!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = alerts.map(alert => createAlertHTML(alert)).join('');
    
    // Agregar event listeners a los botones
    alerts.forEach(alert => {
        // Marcar como leída
        const markReadBtn = document.querySelector(`[data-alert-id="${alert.id}"]`);
        if (markReadBtn) {
            markReadBtn.addEventListener('click', () => markAsRead(alert.id));
        }
        
        // Ver detalles
        const viewBtn = document.querySelector(`[data-view-alert-id="${alert.id}"]`);
        if (viewBtn) {
            viewBtn.addEventListener('click', () => showAlertDetails(alert));
        }
    });
}

function createAlertHTML(alert) {
    const timeAgo = getTimeAgo(alert.created_at);
    const badgeClass = `alert-badge ${alert.type}`;
    const badgeText = getBadgeText(alert.type);
    
    return `
        <div class="alert-item ${alert.type} ${!alert.is_read ? 'unread' : ''}" data-alert-id="${alert.id}">
            <div class="alert-header">
                <h6 class="alert-title">${alert.title}</h6>
                <span class="alert-time">${timeAgo}</span>
            </div>
            
            <div class="alert-content">
                ${alert.content}
            </div>
            
            <div class="alert-actions">
                <span class="${badgeClass}">${badgeText}</span>
                
                ${alert.scenario ? `<span class="badge badge-secondary">${alert.scenario}</span>` : ''}
                
                <div class="ml-auto">
                    ${!alert.is_read ? `
                        <button class="btn btn-sm btn-outline-primary btn-mark-read" data-alert-id="${alert.id}">
                            <i class="fas fa-check"></i> Marcar como leída
                        </button>
                    ` : ''}
                    
                    ${alert.action_url ? `
                        <button class="btn btn-sm btn-primary" data-view-alert-id="${alert.id}">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function getBadgeText(type) {
    const types = {
        'info': 'Información',
        'warning': 'Advertencia',
        'error': 'Error',
        'success': 'Éxito'
    };
    return types[type] || type;
}

function getTimeAgo(createdAt) {
    const now = new Date();
    const created = new Date(createdAt);
    const diffInMinutes = Math.floor((now - created) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Ahora mismo';
    if (diffInMinutes < 60) return `Hace ${diffInMinutes} min`;
    if (diffInMinutes < 1440) return `Hace ${Math.floor(diffInMinutes / 60)}h`;
    return `Hace ${Math.floor(diffInMinutes / 1440)}d`;
}

function markAsRead(alertId) {
    fetch('/api/alerts/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ alert_id: alertId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar la UI
            const alertItem = document.querySelector(`[data-alert-id="${alertId}"]`);
            if (alertItem) {
                alertItem.classList.remove('unread');
                const markReadBtn = alertItem.querySelector('.btn-mark-read');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error al marcar la alerta como leída');
    });
}

function markAllAsRead() {
    fetch('/api/alerts/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar las alertas
            loadAlerts();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error al marcar todas las alertas como leídas');
    });
}

function showAlertDetails(alert) {
    document.getElementById('alertModalTitle').textContent = alert.title;
    document.getElementById('alertModalBody').innerHTML = `
        <div class="mb-3">
            <strong>Contenido:</strong>
            <p class="mt-2">${alert.content}</p>
        </div>
        
        ${alert.metadata ? `
            <div class="mb-3">
                <strong>Información adicional:</strong>
                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(alert.metadata, null, 2)}</pre>
            </div>
        ` : ''}
        
        <div class="mb-3">
            <strong>Creada:</strong> ${new Date(alert.created_at).toLocaleString()}
        </div>
    `;
    
    if (alert.action_url) {
        document.getElementById('alertActionButton').href = alert.action_url;
        document.getElementById('alertActionButton').style.display = 'inline-block';
    } else {
        document.getElementById('alertActionButton').style.display = 'none';
    }
    
    $('#alertModal').modal('show');
}

function showError(message) {
    const container = document.getElementById('alertsContainer');
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            ${message}
        </div>
    `;
}
</script>
@endsection
