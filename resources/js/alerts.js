


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
                
                // Mostrar alertas no vistas
                this.alerts.forEach(alert => {
                    if (!alert.is_read) {
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
        } catch (error) {
            console.error('Error al marcar alerta como leída:', error);
        }
    }

    // Método para crear alertas desde JavaScript
    static createAlert(alertData) {
        const event = new CustomEvent('showAlert', {
            detail: alertData
        });
        document.dispatchEvent(event);
    }

    // Método para crear alertas de ejemplo
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

    // Método para crear alertas de limpieza
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