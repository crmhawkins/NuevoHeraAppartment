@extends('layouts.appAdmin')

@section('title', 'Detalles del Historial de Descuento')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-eye me-2"></i>Detalles del Historial de Descuento
                </h1>
                <div>
                    <a href="{{ route('admin.historial-descuentos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Historial
                    </a>
                </div>
            </div>

            <!-- Información Principal -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Información del Registro</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-home me-2"></i>Apartamento</h6>
                                    <p><strong>Nombre:</strong> {{ $historial->apartamento->nombre ?? 'N/A' }}</p>
                                    <p><strong>ID:</strong> {{ $historial->apartamento_id }}</p>
                                    @if($historial->apartamento && $historial->apartamento->id_channex)
                                        <p><strong>ID Channex:</strong> {{ $historial->apartamento->id_channex }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-money-bill me-2"></i>Información Financiera</h6>
                                    <p><strong>Precio Original:</strong> {{ $historial->precio_original }}€</p>
                                    <p><strong>Precio con Descuento:</strong> {{ $historial->precio_con_descuento }}€</p>
                                    <p><strong>Descuento Aplicado:</strong> <span class="badge bg-success">{{ $historial->porcentaje_formateado }}</span></p>
                                    <p><strong>Ahorro Total:</strong> <span class="text-success">{{ $historial->ahorro_total }}€</span></p>
                                </div>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-calendar me-2"></i>Fechas</h6>
                                    <p><strong>Fecha de Aplicación:</strong> {{ $historial->fecha_aplicacion->format('d/m/Y H:i:s') }}</p>
                                    <p><strong>Rango de Descuento:</strong> {{ $historial->rango_fechas }}</p>
                                    <p><strong>Días Aplicados:</strong> <span class="badge bg-info">{{ $historial->dias_aplicados }}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-cog me-2"></i>Configuración</h6>
                                    @if($historial->configuracionDescuento)
                                        <p><strong>Nombre:</strong> {{ $historial->configuracionDescuento->nombre }}</p>
                                        <p><strong>Edificio:</strong> {{ $historial->configuracionDescuento->edificio->nombre ?? 'N/A' }}</p>
                                    @else
                                        <p><em>Sin configuración asociada</em></p>
                                    @endif
                                    <p><strong>Estado:</strong> 
                                        <span class="badge bg-{{ $historial->estado == 'aplicado' ? 'success' : ($historial->estado == 'pendiente' ? 'warning' : 'danger') }}">
                                            {{ $historial->estado_formateado }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            @if($historial->observaciones)
                                <hr>
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-primary"><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                                        <p>{{ $historial->observaciones }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Datos del Momento -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>Datos del Momento
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($historial->datos_momento)
                                @php
                                    $datos = $historial->datos_momento;
                                    $verificacion = $historial->verificarRequisitosCumplidos();
                                @endphp
                                
                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-building me-2"></i>Edificio</h6>
                                    <p class="mb-1"><strong>Nombre:</strong> {{ $datos['edificio']['nombre'] ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Total Apartamentos:</strong> {{ $datos['edificio']['total_apartamentos'] ?? 'N/A' }}</p>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-chart-line me-2"></i>Ocupación</h6>
                                    <p class="mb-1">
                                        <strong>Actual:</strong> 
                                        <span class="badge bg-{{ $datos['ocupacion_actual'] < 50 ? 'success' : ($datos['ocupacion_actual'] > 80 ? 'danger' : 'warning') }}">
                                            {{ $datos['ocupacion_actual'] ?? 'N/A' }}%
                                        </span>
                                    </p>
                                    <p class="mb-1"><strong>Límite:</strong> {{ $datos['ocupacion_limite'] ?? 'N/A' }}%</p>
                                    <p class="mb-1">
                                        <strong>Acción:</strong> 
                                        <span class="badge bg-{{ $datos['accion'] === 'descuento' ? 'success' : 'warning' }}">
                                            {{ strtoupper($datos['accion'] ?? 'N/A') }}
                                        </span>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-calendar-check me-2"></i>Verificación</h6>
                                    <div class="alert alert-{{ $verificacion['cumplidos'] ? 'success' : 'warning' }} mb-0">
                                        <strong>Estado:</strong> {{ $verificacion['cumplidos'] ? 'REQUISITOS CUMPLIDOS' : 'REQUISITOS NO CUMPLIDOS' }}
                                        <br>
                                        <small>{{ $verificacion['razon'] }}</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-primary"><i class="fas fa-clock me-2"></i>Análisis</h6>
                                    <p class="mb-1"><strong>Fecha:</strong> {{ $datos['fecha_analisis'] ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Días Libres:</strong> {{ $datos['total_dias'] ?? 'N/A' }}</p>
                                </div>

                                <button class="btn btn-outline-info btn-sm w-100" onclick="verDatosCompletos({{ $historial->id }})">
                                    <i class="fas fa-expand me-2"></i>Ver Datos Completos
                                </button>
                            @else
                                <div class="text-center py-3">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                    <p class="text-muted">No hay datos del momento disponibles</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Información de Tarifa -->
                    @if($historial->tarifa)
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-tag me-2"></i>Tarifa Aplicada
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Nombre:</strong> {{ $historial->tarifa->nombre }}</p>
                                <p><strong>Precio Base:</strong> {{ $historial->tarifa->precio }}€</p>
                                <p><strong>ID:</strong> {{ $historial->tarifa->id }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Información Técnica -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-cogs me-2"></i>Información Técnica
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">Timestamps</h6>
                                    <p><strong>Creado:</strong> {{ $historial->created_at->format('d/m/Y H:i:s') }}</p>
                                    <p><strong>Actualizado:</strong> {{ $historial->updated_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary">IDs de Referencia</h6>
                                    <p><strong>ID Historial:</strong> {{ $historial->id }}</p>
                                    <p><strong>ID Apartamento:</strong> {{ $historial->apartamento_id }}</p>
                                    <p><strong>ID Tarifa:</strong> {{ $historial->tarifa_id ?? 'N/A' }}</p>
                                    <p><strong>ID Configuración:</strong> {{ $historial->configuracion_descuento_id ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<script>
function verDatosCompletos(historialId) {
    console.log('Función verDatosCompletos llamada con ID:', historialId);
    
    // Mostrar loading con SweetAlert2
    Swal.fire({
        title: 'Cargando datos completos...',
        text: 'Obteniendo información detallada del momento',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Cargar datos
    fetch(`/admin/historial-descuentos/${historialId}/datos-momento`)
        .then(response => {
            console.log('Respuesta del servidor:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (data.error) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin datos disponibles',
                    text: data.error
                });
                return;
            }
            
            const datos = data.datos;
            const verificacion = data.verificacion;
            
            let html = `
                <div style="text-align: left; font-size: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h6 style="color: #007bff; margin-bottom: 10px;"><i class="fas fa-building me-2"></i>Información del Edificio</h6>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Nombre:</strong> ${datos.edificio.nombre}</li>
                                <li><strong>Total Apartamentos:</strong> ${datos.edificio.total_apartamentos}</li>
                            </ul>
                            
                            <h6 style="color: #007bff; margin: 15px 0 10px 0;"><i class="fas fa-cog me-2"></i>Configuración Aplicada</h6>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Nombre:</strong> ${datos.configuracion.nombre}</li>
                                <li><strong>Descuento:</strong> ${datos.configuracion.porcentaje_descuento}%</li>
                                <li><strong>Incremento:</strong> ${datos.configuracion.porcentaje_incremento}%</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h6 style="color: #007bff; margin-bottom: 10px;"><i class="fas fa-home me-2"></i>Información del Apartamento</h6>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Nombre:</strong> ${datos.apartamento.nombre}</li>
                                <li><strong>ID Channex:</strong> ${datos.apartamento.id_channex || 'N/A'}</li>
                            </ul>
                            
                            <h6 style="color: #007bff; margin: 15px 0 10px 0;"><i class="fas fa-calendar me-2"></i>Fechas</h6>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Análisis:</strong> ${datos.fecha_analisis}</li>
                                <li><strong>Rango:</strong> ${datos.fecha_inicio} - ${datos.fecha_fin}</li>
                                <li><strong>Días Libres:</strong> ${datos.total_dias}</li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0;">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h6 style="color: #007bff; margin-bottom: 10px;"><i class="fas fa-chart-line me-2"></i>Datos de Ocupación</h6>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Ocupación Actual:</strong> <span style="background: ${datos.ocupacion_actual < 50 ? '#28a745' : datos.ocupacion_actual > 80 ? '#dc3545' : '#ffc107'}; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">${datos.ocupacion_actual}%</span></li>
                                <li><strong>Límite Aplicado:</strong> ${datos.ocupacion_limite}%</li>
                                <li><strong>Acción:</strong> <span style="background: ${datos.accion === 'descuento' ? '#28a745' : '#ffc107'}; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">${datos.accion.toUpperCase()}</span></li>
                                <li><strong>Porcentaje:</strong> ${datos.porcentaje}%</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h6 style="color: #007bff; margin-bottom: 10px;"><i class="fas fa-euro-sign me-2"></i>Información Financiera</h6>
                            <ul style="list-style: none; padding: 0;">
                                <li><strong>Precio Original:</strong> ${datos.precio_original}€</li>
                                <li><strong>Precio con Ajuste:</strong> ${datos.precio_con_ajuste}€</li>
                                <li><strong>Ahorro por Día:</strong> ${datos.ahorro_por_dia}€</li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0;">
                    
                    <div style="background: ${verificacion.cumplidos ? '#d4edda' : '#fff3cd'}; border: 1px solid ${verificacion.cumplidos ? '#c3e6cb' : '#ffeaa7'}; padding: 15px; border-radius: 5px;">
                        <h6 style="color: ${verificacion.cumplidos ? '#155724' : '#856404'}; margin-bottom: 10px;">
                            <i class="fas fa-${verificacion.cumplidos ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                            Verificación de Requisitos
                        </h6>
                        <p style="margin: 5px 0;"><strong>Estado:</strong> ${verificacion.cumplidos ? 'REQUISITOS CUMPLIDOS' : 'REQUISITOS NO CUMPLIDOS'}</p>
                        <p style="margin: 5px 0;"><strong>Razón:</strong> ${verificacion.razon}</p>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Datos Completos del Momento',
                html: html,
                width: '900px',
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#007bff'
            });
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al cargar datos',
                text: `Error al cargar los datos completos: ${error.message}`
            });
        });
}
</script>
