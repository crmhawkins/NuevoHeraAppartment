class AlertManager {
    constructor() {
        this.alerts = [];
        this.isChecking = false;
        this.init();
    }

    init() {
        this.checkForAlerts();
        this.setupEventListeners();
        
        // Verificar alertas cada 30 segundos
        setInterval(() => {
            this.checkForAlerts();
        }, 30000);
    }

    setupEventListeners() {
        // Escuchar eventos personalizados para mostrar alertas
        document.addEventListener('showAlert', (e) => {
            this.showAlert(e.detail);
        });

        // Eventos del modal de notificaciones
        const notificationsBtn = document.getElementById('notificationsBtn');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        const notificationsModal = document.getElementById('notificationsModal');

        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', () => {
                this.loadNotifications();
            });
        }

        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }

        if (notificationsModal) {
            notificationsModal.addEventListener('show.bs.modal', () => {
                this.loadNotifications();
            });
        }
    }

    async checkForAlerts() {
        if (this.isChecking) return;
        
        this.isChecking = true;
        
        try {
            const response = await fetch('/alerts/unread', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.alerts = data.alerts || [];
                
                // Actualizar badge de notificaciones
                this.updateNotificationBadge();
                
                // Mostrar alertas no vistas (solo las más importantes)
                this.alerts.forEach(alert => {
                    if (!alert.is_read && alert.type === 'error') {
                        this.showAlert(alert);
                    }
                });
            }
        } catch (error) {
            console.error('Error al obtener alertas:', error);
        } finally {
            this.isChecking = false;
        }
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            const unreadCount = this.alerts.filter(alert => !alert.is_read).length;
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    async loadNotifications() {
        const notificationsList = document.getElementById('notificationsList');
        if (!notificationsList) return;

        try {
            const response = await fetch('/alerts/unread', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.alerts = data.alerts || [];
                this.renderNotifications(notificationsList);
            }
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
            notificationsList.innerHTML = '<div class="alert alert-danger">Error al cargar las notificaciones</div>';
        }
    }

    renderNotifications(container) {
        if (this.alerts.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    <p class="mt-3 text-muted">No tienes notificaciones pendientes</p>
                </div>
            `;
            return;
        }

        const notificationsHtml = this.alerts.map(alert => {
            const iconClass = this.getAlertIcon(alert.type);
            const statusClass = alert.is_read ? 'text-muted' : 'fw-bold';
            const statusText = alert.is_read ? 'Leída' : 'Nueva';
            
            return `
                <div class="alert alert-${this.getAlertBootstrapClass(alert.type)} alert-dismissible" role="alert">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <i class="${iconClass} me-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="alert-heading ${statusClass}">${alert.title}</h6>
                                <small class="text-muted">${this.formatDate(alert.created_at)}</small>
                            </div>
                            <p class="mb-2">${alert.content}</p>
                            ${alert.action_url && alert.action_text ? `
                                <a href="${alert.action_url}" class="btn btn-sm btn-outline-primary">
                                    ${alert.action_text}
                                </a>
                            ` : ''}
                            ${!alert.is_read ? `
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="alertManager.markAsRead(${alert.id})">
                                    Marcar como leída
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }).join('');

        container.innerHTML = notificationsHtml;
    }

    getAlertIcon(type) {
        const icons = {
            'info': 'fas fa-info-circle text-info',
            'warning': 'fas fa-exclamation-triangle text-warning',
            'error': 'fas fa-times-circle text-danger',
            'success': 'fas fa-check-circle text-success'
        };
        return icons[type] || icons['info'];
    }

    getAlertBootstrapClass(type) {
        const classes = {
            'info': 'info',
            'warning': 'warning',
            'error': 'danger',
            'success': 'success'
        };
        return classes[type] || 'info';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInHours = (now - date) / (1000 * 60 * 60);
        
        if (diffInHours < 1) {
            return 'Hace unos minutos';
        } else if (diffInHours < 24) {
            return `Hace ${Math.floor(diffInHours)} horas`;
        } else {
            return date.toLocaleDateString('es-ES');
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/alerts/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                // Recargar notificaciones
                this.loadNotifications();
                // Actualizar badge
                this.updateNotificationBadge();
            }
        } catch (error) {
            console.error('Error al marcar todas como leídas:', error);
        }
    }

    showAlert(alert) {
        const config = this.getAlertConfig(alert);
        
        Swal.fire(config).then((result) => {
            this.handleAlertAction(alert, result);
        });
    }

    getAlertConfig(alert) {
        const baseConfig = {
            title: alert.title,
            text: alert.content,
            icon: alert.type,
            confirmButtonText: 'Aceptar',
            showCancelButton: false,
            allowOutsideClick: alert.is_dismissible,
            allowEscapeKey: alert.is_dismissible
        };

        // Si tiene acción, agregar botón
        if (alert.action_url && alert.action_text) {
            baseConfig.showCancelButton = true;
            baseConfig.cancelButtonText = 'Cerrar';
            baseConfig.confirmButtonText = alert.action_text;
        }

        // Si no es descartable, no permitir cerrar
        if (!alert.is_dismissible) {
            baseConfig.allowOutsideClick = false;
            baseConfig.allowEscapeKey = false;
            baseConfig.showCloseButton = false;
        }

        return baseConfig;
    }

    async handleAlertAction(alert, result) {
        // Marcar como leída
        await this.markAsRead(alert.id);

        // Si se confirmó y hay URL de acción, redirigir
        if (result.isConfirmed && alert.action_url) {
            window.location.href = alert.action_url;
        }
    }

    async markAsRead(alertId) {
        try {
            await fetch('/alerts/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ alert_id: alertId })
            });
            
            // Actualizar badge después de marcar como leída
            this.updateNotificationBadge();
        } catch (error) {
            console.error('Error al marcar alerta como leída:', error);
        }
    }

    // Métodos estáticos para crear alertas desde JavaScript
    static createAlert(alertData) {
        const event = new CustomEvent('showAlert', {
            detail: alertData
        });
        document.dispatchEvent(event);
    }

    static showInfoAlert(title, content, actionUrl = null, actionText = null) {
        this.createAlert({
            type: 'info',
            title: title,
            content: content,
            action_url: actionUrl,
            action_text: actionText,
            is_dismissible: true
        });
    }

    static showWarningAlert(title, content, actionUrl = null, actionText = null) {
        this.createAlert({
            type: 'warning',
            title: title,
            content: content,
            action_url: actionUrl,
            action_text: actionText,
            is_dismissible: true
        });
    }

    static showErrorAlert(title, content, actionUrl = null, actionText = null) {
        this.createAlert({
            type: 'error',
            title: title,
            content: content,
            action_url: actionUrl,
            action_text: actionText,
            is_dismissible: false
        });
    }

    static showSuccessAlert(title, content, actionUrl = null, actionText = null) {
        this.createAlert({
            type: 'success',
            title: title,
            content: content,
            action_url: actionUrl,
            action_text: actionText,
            is_dismissible: true
        });
    }

    static showCleaningObservationAlert(apartamentoNombre, observacion, limpiezaId) {
        this.createAlert({
            type: 'warning',
            title: 'Observación en Limpieza',
            content: `Se ha añadido una observación en la limpieza del apartamento ${apartamentoNombre}: ${observacion}`,
            action_url: `/aparatamento-limpieza/${limpiezaId}/show`,
            action_text: 'Ver Limpieza',
            is_dismissible: true
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.alertManager = new AlertManager();
});
