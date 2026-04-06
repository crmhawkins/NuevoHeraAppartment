// Configuración de Pusher para notificaciones en tiempo real
window.PusherConfig = {
    key: '{{ config("broadcasting.connections.pusher.key") }}',
    cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
    encrypted: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    }
};

// Inicializar Pusher si está disponible
if (typeof Pusher !== 'undefined') {
    window.pusher = new Pusher(window.PusherConfig.key, {
        cluster: window.PusherConfig.cluster,
        encrypted: window.PusherConfig.encrypted,
        authEndpoint: window.PusherConfig.authEndpoint,
        auth: window.PusherConfig.auth
    });
}
