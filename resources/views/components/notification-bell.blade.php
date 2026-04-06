<div id="notification-bell" class="position-relative">
    <button class="btn btn-outline-primary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <span id="notification-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
            0
        </span>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 400px; max-height: 500px; overflow-y: auto;">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Notificaciones</h6>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary btn-sm" onclick="markAllAsRead()">
                    <i class="fas fa-check-double"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="refreshNotifications()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="dropdown-divider"></div>
        
        <div id="notifications-container">
            <div class="text-center p-3">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 mb-0 text-muted">Cargando notificaciones...</p>
            </div>
        </div>
        
        <div class="dropdown-divider"></div>
        <div class="dropdown-item text-center">
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary btn-sm">
                Ver todas las notificaciones
            </a>
        </div>
    </div>
</div>

<style>
.notification-dropdown {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s;
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
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 4px;
}

.notification-title {
    font-weight: 600;
    font-size: 14px;
    margin: 0;
    flex: 1;
}

.notification-time {
    font-size: 12px;
    color: #6c757d;
    margin-left: 8px;
}

.notification-message {
    font-size: 13px;
    color: #495057;
    margin: 0;
    line-height: 1.4;
}

.notification-actions {
    margin-top: 8px;
    display: flex;
    gap: 4px;
}

.notification-actions .btn {
    font-size: 11px;
    padding: 2px 6px;
}

.notification-icon {
    width: 20px;
    height: 20px;
    margin-right: 8px;
    flex-shrink: 0;
}

.notification-empty {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.notification-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}
</style>

<script>
let notificationSocket = null;
let notificationCount = 0;

// Inicializar notificaciones
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    initializeWebSocket();
    
    // Actualizar notificaciones cada 30 segundos
    setInterval(loadNotifications, 30000);
});

// Cargar notificaciones
function loadNotifications() {
    fetch('/api/notifications?limit=10&unread_only=false')
        .then(response => response.json())
        .then(data => {
            updateNotificationCount(data.unread_count);
            renderNotifications(data.notifications);
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

// Actualizar contador de notificaciones
function updateNotificationCount(count) {
    notificationCount = count;
    const countElement = document.getElementById('notification-count');
    
    if (count > 0) {
        countElement.textContent = count;
        countElement.style.display = 'inline-block';
    } else {
        countElement.style.display = 'none';
    }
}

// Renderizar notificaciones
function renderNotifications(notifications) {
    const container = document.getElementById('notifications-container');
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>No hay notificaciones</p>
            </div>
        `;
        return;
    }
    
    const html = notifications.map(notification => {
        const isUnread = !notification.read_at;
        const priorityClass = notification.priority || 'medium';
        
        return `
            <div class="notification-item ${isUnread ? 'unread' : ''} ${priorityClass}" data-id="${notification.id}">
                <div class="notification-header">
                    <i class="${notification.icon || 'fas fa-bell'} notification-icon"></i>
                    <h6 class="notification-title">${notification.title}</h6>
                    <small class="notification-time">${notification.time_ago}</small>
                </div>
                <p class="notification-message">${notification.message}</p>
                ${notification.action_url ? `
                    <div class="notification-actions">
                        <a href="${notification.action_url}" class="btn btn-outline-primary btn-sm">
                            Ver detalles
                        </a>
                        ${isUnread ? `
                            <button class="btn btn-outline-secondary btn-sm" onclick="markAsRead(${notification.id})">
                                Marcar como leída
                            </button>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
    
    container.innerHTML = html;
}

// Marcar notificación como leída
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
            // Actualizar UI
            const item = document.querySelector(`[data-id="${id}"]`);
            if (item) {
                item.classList.remove('unread');
                const markButton = item.querySelector('button[onclick*="markAsRead"]');
                if (markButton) {
                    markButton.remove();
                }
            }
            
            // Actualizar contador
            if (notificationCount > 0) {
                updateNotificationCount(notificationCount - 1);
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
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
            // Actualizar UI
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                const markButton = item.querySelector('button[onclick*="markAsRead"]');
                if (markButton) {
                    markButton.remove();
                }
            });
            
            // Actualizar contador
            updateNotificationCount(0);
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

// Refrescar notificaciones
function refreshNotifications() {
    loadNotifications();
}

// Inicializar WebSocket para notificaciones en tiempo real
function initializeWebSocket() {
    // Verificar si Pusher está disponible
    if (typeof Pusher !== 'undefined') {
        const pusher = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', {
            cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
            encrypted: true
        });
        
        const channel = pusher.subscribe('private-notifications.{{ auth()->id() }}');
        
        channel.bind('notification.created', function(data) {
            // Mostrar notificación toast
            showNotificationToast(data);
            
            // Actualizar contador
            updateNotificationCount(notificationCount + 1);
            
            // Recargar notificaciones si el dropdown está abierto
            const dropdown = document.querySelector('.notification-dropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                loadNotifications();
            }
        });
    }
}

// Mostrar notificación toast
function showNotificationToast(notification) {
    // Crear toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${notification.color || 'primary'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="${notification.icon || 'fas fa-bell'} me-2"></i>
                <strong>${notification.title}</strong><br>
                <small>${notification.message}</small>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Agregar al contenedor de toasts
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
    // Mostrar toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bsToast.show();
    
    // Remover del DOM después de ocultar
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Reproducir sonido de notificación
function playNotificationSound() {
    const audio = new Audio('/sounds/notification.mp3');
    audio.volume = 0.3;
    audio.play().catch(e => {
        // Ignorar errores de reproducción de audio
        console.log('Could not play notification sound:', e);
    });
}
</script>
