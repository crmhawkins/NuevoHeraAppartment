<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buscador de Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            margin: 0;
            padding: 15px;
            font-family: 'Nunito', sans-serif;
            background: transparent;
        }
        .search-form {
            background: rgba(45, 45, 45, 0.95);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: visible; /* permitir que el calendario sobresalga */
        }
        .form-label {
            color: #fff;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-control, .form-select {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
        }
        .btn-buscar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-buscar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .search-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            overflow: visible;
        }
        .search-col {
            flex: 1;
            min-width: 150px;
            position: relative;
            overflow: visible;
        }
        /* Asegurar que el calendario esté SIEMPRE por encima dentro del iframe */
        .flatpickr-calendar {
            z-index: 2147483647 !important; /* valor máximo */
        }
        @media (max-width: 768px) {
            .search-col {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="search-form">
        <form id="searchForm" method="POST" action="{{ route('web.reservas.buscar.post') }}">
            @csrf
            <div class="search-row">
                <div class="search-col">
                    <label for="fecha_entrada" class="form-label">
                        <i class="fas fa-calendar-alt me-1"></i>Fecha de entrada
                    </label>
                    <input type="text" 
                           id="fecha_entrada" 
                           name="fecha_entrada" 
                           class="form-control" 
                           placeholder="Seleccione fecha"
                           required
                           readonly>
                </div>
                <div class="search-col">
                    <label for="fecha_salida" class="form-label">
                        <i class="fas fa-calendar-alt me-1"></i>Fecha de salida
                    </label>
                    <input type="text" 
                           id="fecha_salida" 
                           name="fecha_salida" 
                           class="form-control" 
                           placeholder="Seleccione fecha"
                           required
                           readonly>
                </div>
                <div class="search-col">
                    <label for="adultos" class="form-label">
                        <i class="fas fa-user me-1"></i>Adultos
                    </label>
                    <select id="adultos" name="adultos" class="form-select" required>
                        @for($i = 1; $i <= 20; $i++)
                            <option value="{{ $i }}" {{ $i == 2 ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="search-col">
                    <label for="ninos" class="form-label">
                        <i class="fas fa-child me-1"></i>Niños
                    </label>
                    <select id="ninos" name="ninos" class="form-select">
                        <option value="0" selected>0</option>
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="search-col" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-buscar">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar Flatpickr para fecha de entrada
            const fechaEntrada = flatpickr("#fecha_entrada", {
                locale: "es",
                dateFormat: "Y-m-d",
                minDate: "today",
                position: "auto",
                appendTo: document.body, // evitar clipping por contenedores
                onOpen: function(selectedDates, dateStr, instance) {
                    if (instance && instance.calendarContainer) {
                        instance.calendarContainer.style.zIndex = 2147483647;
                    }
                    // avisar al parent (si existe) para ampliar altura del iframe
                    try { window.parent.postMessage({ type: 'reserva-iframe', action: 'open', height: 420 }, '*'); } catch (e) {}
                },
                onClose: function() {
                    try { window.parent.postMessage({ type: 'reserva-iframe', action: 'close', height: 260 }, '*'); } catch (e) {}
                },
                onChange: function(selectedDates, dateStr, instance) {
                    // Establecer fecha mínima de salida como fecha de entrada + 1 día
                    if (selectedDates.length > 0) {
                        const fechaMin = new Date(selectedDates[0]);
                        fechaMin.setDate(fechaMin.getDate() + 1);
                        fechaSalida.set('minDate', fechaMin);
                    }
                }
            });

            // Configurar Flatpickr para fecha de salida
            const fechaSalida = flatpickr("#fecha_salida", {
                locale: "es",
                dateFormat: "Y-m-d",
                minDate: new Date().fp_incr(1), // Mañana como mínimo
                appendTo: document.body,
                onOpen: function(selectedDates, dateStr, instance) {
                    if (instance && instance.calendarContainer) {
                        instance.calendarContainer.style.zIndex = 2147483647;
                    }
                    try { window.parent.postMessage({ type: 'reserva-iframe', action: 'open', height: 420 }, '*'); } catch (e) {}
                },
                onClose: function() {
                    try { window.parent.postMessage({ type: 'reserva-iframe', action: 'close', height: 260 }, '*'); } catch (e) {}
                },
                position: "auto"
            });

            // Manejar envío del formulario
            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Validar fechas antes de enviar
                const fechaEntrada = document.getElementById('fecha_entrada').value;
                const fechaSalida = document.getElementById('fecha_salida').value;
                
                if (!fechaEntrada || !fechaSalida) {
                    alert('Por favor, seleccione las fechas de entrada y salida');
                    return;
                }
                
                const fechaEntradaObj = new Date(fechaEntrada);
                const fechaSalidaObj = new Date(fechaSalida);
                
                if (fechaSalidaObj <= fechaEntradaObj) {
                    alert('La fecha de salida debe ser posterior a la fecha de entrada');
                    return;
                }
                
                // Construir URL del portal directamente
                const params = new URLSearchParams(formData);
                const portalUrl = '{{ route("web.reservas.portal") }}?' + params.toString();
                
                // Si estamos en un iframe, abrir en la ventana superior
                if (window.self !== window.top) {
                    window.top.location.href = portalUrl;
                } else {
                    window.location.href = portalUrl;
                }
            });
        });
    </script>
</body>
</html>

