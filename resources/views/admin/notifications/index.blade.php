@extends('layouts.appAdmin')

@section('title', 'Gestión de Notificaciones')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="apple-card">
                <div class="apple-card-header">
                    <h3 class="apple-card-title">
                        <i class="fas fa-bell me-2"></i>
                        Centro de Notificaciones
                    </h3>
                    <div class="apple-card-actions">
                        <button class="btn btn-outline-primary btn-sm" onclick="markAllAsRead()">
                            <i class="fas fa-check-double me-1"></i>
                            Marcar todas como leídas
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteRead()">
                            <i class="fas fa-trash me-1"></i>
                            Eliminar leídas
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="refreshNotifications()">
                            <i class="fas fa-sync-alt me-1"></i>
                            Actualizar
                        </button>
                    </div>
                </div>
                <div class="apple-card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form id="filter-form" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="type" class="form-label">Tipo</label>
                                            <select class="form-select" id="type" name="type">
                                                <option value="">Todos los tipos</option>
                                                <option value="reserva">Reservas</option>
                                                <option value="incidencia">Incidencias</option>
                                                <option value="limpieza">Limpieza</option>
                                                <option value="facturacion">Facturación</option>
                                                <option value="inventario">Inventario</option>
                                                <option value="sistema">Sistema</option>
                                                <option value="whatsapp">WhatsApp</option>
                                                <option value="channex">Channex</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="priority" class="form-label">Prioridad</label>
                                            <select class="form-select" id="priority" name="priority">
                                                <option value="">Todas las prioridades</option>
                                                <option value="critical">Crítica</option>
                                                <option value="high">Alta</option>
                                                <option value="medium">Media</option>
                                                <option value="low">Baja</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="status" class="form-label">Estado</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="">Todos los estados</option>
                                                <option value="unread">No leídas</option>
                                                <option value="read">Leídas</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="search" class="form-label">Buscar</label>
                                            <input type="text" class="form-control" id="search" name="search" placeholder="Buscar en notificaciones...">
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i>
                                                Filtrar
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                                <i class="fas fa-times me-1"></i>
                                                Limpiar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4" id="stats-container">
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-primary">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">Total</h6>
                                    <p class="apple-list-item-subtitle" id="total-count">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-warning">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">No Leídas</h6>
                                    <p class="apple-list-item-subtitle" id="unread-count">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">Críticas</h6>
                                    <p class="apple-list-item-subtitle" id="critical-count">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="apple-list-item">
                                <div class="apple-list-item-icon bg-info">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="apple-list-item-content">
                                    <h6 class="apple-list-item-title">Hoy</h6>
                                    <p class="apple-list-item-subtitle" id="today-count">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de notificaciones -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Notificaciones</h5>
                        </div>
                        <div class="card-body p-0">
                            <div id="notifications-list">
                                <div class="text-center p-4">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 mb-0 text-muted">Cargando notificaciones...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <nav aria-label="Paginación de notificaciones">
                                <ul class="pagination justify-content-center" id="pagination">
                                    <!-- Se genera dinámicamente -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de notificación -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalTitle">Detalles de Notificación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <!-- Se llena dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="notificationActionBtn" style="display: none;">
                    Ver Detalles
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item {
    padding: 16px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s;
    cursor: pointer;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.notification-item.critical {
    background-color: #ffebee;
    border-left: 4px solid #f44336;
}

.notification-item.high {
    background-color: #fff3e0;
    border-left: 4px solid #ff9800;
}

.notification-item.medium {
    background-color: #e8f5e8;
    border-left: 4px solid #4caf50;
}

.notification-item.low {
    background-color: #f5f5f5;
    border-left: 4px solid #9e9e9e;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.notification-title {
    font-weight: 600;
    font-size: 16px;
    margin: 0;
    flex: 1;
}

.notification-time {
    font-size: 12px;
    color: #6c757d;
    margin-left: 12px;
}

.notification-message {
    font-size: 14px;
    color: #495057;
    margin: 0 0 12px 0;
    line-height: 1.4;
}

.notification-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.notification-actions .btn {
    font-size: 12px;
    padding: 4px 8px;
}

.notification-icon {
    width: 24px;
    height: 24px;
    margin-right: 12px;
    flex-shrink: 0;
}

.notification-empty {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.notification-empty i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.badge-priority {
    font-size: 10px;
    padding: 2px 6px;
}

.badge-critical {
    background-color: #dc3545;
}

.badge-high {
    background-color: #fd7e14;
}

.badge-medium {
    background-color: #198754;
}

.badge-low {
    background-color: #6c757d;
}
</style>

<script>
let currentPage = 1;
let currentFilters = {};

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    loadStats();
    
    // Configurar formulario de filtros
    document.getElementById('filter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        currentFilters = new FormData(this);
        loadNotifications();
    });
    
    // Actualizar cada 30 segundos
    setInterval(() => {
        loadNotifications();
        loadStats();
    }, 30000);
});

// Cargar notificaciones
function loadNotifications() {
    const params = new URLSearchParams();
    params.append('page', currentPage);
    
    // Agregar filtros
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key]) {
            params.append(key, currentFilters[key]);
        }
    });
    
    fetch(`/api/notifications?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            renderNotifications(data.notifications);
            renderPagination(data.pagination);
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            showError('Error al cargar las notificaciones');
        });
}

// Cargar estadísticas
function loadStats() {
    fetch('/api/notifications/stats')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-count').textContent = data.total || 0;
            document.getElementById('unread-count').textContent = data.unread || 0;
            document.getElementById('critical-count').textContent = data.by_priority?.critical || 0;
            document.getElementById('today-count').textContent = data.by_date?.today || 0;
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
}

// Renderizar notificaciones
function renderNotifications(notifications) {
    const container = document.getElementById('notifications-list');
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <h5>No hay notificaciones</h5>
                <p>No se encontraron notificaciones con los filtros aplicados.</p>
            </div>
        `;
        return;
    }
    
    const html = notifications.map(notification => {
        const isUnread = !notification.read_at;
        const priorityClass = notification.priority || 'medium';
        const priorityBadge = `<span class="badge badge-${priorityClass} badge-priority">${notification.priority.toUpperCase()}</span>`;
        
        return `
            <div class="notification-item ${isUnread ? 'unread' : ''} ${priorityClass}" 
                 data-id="${notification.id}" 
                 onclick="showNotificationDetails(${notification.id})">
                <div class="notification-header">
                    <div class="d-flex align-items-start">
                        <i class="${notification.icon || 'fas fa-bell'} notification-icon"></i>
                        <div class="flex-grow-1">
                            <h6 class="notification-title">${notification.title}</h6>
                            <p class="notification-message">${notification.message}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        ${priorityBadge}
                        <small class="notification-time d-block">${notification.time_ago}</small>
                    </div>
                </div>
                <div class="notification-actions">
                    ${isUnread ? `
                        <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); markAsRead(${notification.id})">
                            <i class="fas fa-check me-1"></i>
                            Marcar como leída
                        </button>
                    ` : ''}
                    ${notification.action_url ? `
                        <a href="${notification.action_url}" class="btn btn-outline-info btn-sm" onclick="event.stopPropagation()">
                            <i class="fas fa-external-link-alt me-1"></i>
                            Ver detalles
                        </a>
                    ` : ''}
                    <button class="btn btn-outline-danger btn-sm" onclick="event.stopPropagation(); deleteNotification(${notification.id})">
                        <i class="fas fa-trash me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = html;
}

// Renderizar paginación
function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    
    if (!pagination || pagination.last_page <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Botón anterior
    if (pagination.current_page > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">Anterior</a>
        </li>`;
    }
    
    // Páginas
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            html += `<li class="page-item active">
                <span class="page-link">${i}</span>
            </li>`;
        } else {
            html += `<li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>`;
        }
    }
    
    // Botón siguiente
    if (pagination.current_page < pagination.last_page) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">Siguiente</a>
        </li>`;
    }
    
    container.innerHTML = html;
}

// Cambiar página
function changePage(page) {
    currentPage = page;
    loadNotifications();
}

// Marcar como leída
function markAsRead(id) {
    fetch(`/api/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-id="${id}"]`);
            if (item) {
                item.classList.remove('unread');
                const markButton = item.querySelector('button[onclick*="markAsRead"]');
                if (markButton) {
                    markButton.remove();
                }
            }
            loadStats();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        showError('Error al marcar la notificación como leída');
    });
}

// Marcar todas como leídas
function markAllAsRead() {
    fetch('/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                const markButton = item.querySelector('button[onclick*="markAsRead"]');
                if (markButton) {
                    markButton.remove();
                }
            });
            loadStats();
            showSuccess(data.message);
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        showError('Error al marcar todas las notificaciones como leídas');
    });
}

// Eliminar notificación
function deleteNotification(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta notificación?')) {
        return;
    }
    
    fetch(`/api/notifications/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-id="${id}"]`);
            if (item) {
                item.remove();
            }
            loadStats();
            showSuccess('Notificación eliminada');
        }
    })
    .catch(error => {
        console.error('Error deleting notification:', error);
        showError('Error al eliminar la notificación');
    });
}

// Eliminar notificaciones leídas
function deleteRead() {
    if (!confirm('¿Estás seguro de que quieres eliminar todas las notificaciones leídas?')) {
        return;
    }
    
    fetch('/api/notifications/delete-read', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            loadStats();
            showSuccess(data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting read notifications:', error);
        showError('Error al eliminar las notificaciones leídas');
    });
}

// Mostrar detalles de notificación
function showNotificationDetails(id) {
    // Aquí podrías implementar un modal con más detalles
    console.log('Show details for notification:', id);
}

// Refrescar notificaciones
function refreshNotifications() {
    loadNotifications();
    loadStats();
    showSuccess('Notificaciones actualizadas');
}

// Limpiar filtros
function clearFilters() {
    document.getElementById('filter-form').reset();
    currentFilters = {};
    currentPage = 1;
    loadNotifications();
}

// Mostrar mensaje de éxito
function showSuccess(message) {
    // Implementar toast de éxito
    console.log('Success:', message);
}

// Mostrar mensaje de error
function showError(message) {
    // Implementar toast de error
    console.error('Error:', message);
}
</script>
@endsection
