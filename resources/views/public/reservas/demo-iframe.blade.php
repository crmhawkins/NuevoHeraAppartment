<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Iframe Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; padding: 24px; }
        .card { border: none; box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        iframe { width: 100%; height: 260px; border: 0; border-radius: 10px; }
        /* Evitar que el contenido de la tarjeta tape el calendario dentro del iframe */
        .card { overflow: visible; }
    </style>
    </head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card p-4">
                    <h3 class="mb-3">Demo del buscador (iframe)</h3>
                    <p class="text-muted">Este iframe carga el formulario público de búsqueda. Al pulsar "Buscar" te redirige al portal con los parámetros.</p>
                    <iframe id="iframeReserva" src="{{ route('web.reservas.iframe') }}"></iframe>
                    <div class="mt-3">
                        <a class="btn btn-primary" href="{{ route('web.reservas.portal') }}" target="_blank">Abrir portal vacío (comprobar recepción)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Escuchar mensajes del iframe para ajustar su altura dinámicamente
        window.addEventListener('message', function(event) {
            try {
                if (!event.data || event.data.type !== 'reserva-iframe') return;
                const iframe = document.getElementById('iframeReserva');
                if (!iframe) return;
                if (event.data.action === 'open') {
                    iframe.style.height = (event.data.height || 460) + 'px';
                } else if (event.data.action === 'close') {
                    iframe.style.height = (event.data.height || 260) + 'px';
                }
            } catch (e) {}
        });
    </script>
</body>
</html>


