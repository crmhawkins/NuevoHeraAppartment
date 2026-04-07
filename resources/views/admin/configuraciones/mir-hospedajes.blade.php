@extends('layouts.appAdmin')

@section('title', 'MIR Hospedajes')

@section('content')
<style>
    /* Estilos Apple para Configuración */
    .config-container {
        padding: 24px;
        background: #F2F2F7;
        min-height: calc(100vh - 80px);
    }

    .config-header {
        margin-bottom: 32px;
    }

    .config-header h1 {
        font-size: 34px;
        font-weight: 700;
        color: #1D1D1F;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }

    .config-header p {
        font-size: 15px;
        color: #8E8E93;
        margin: 0;
    }

    .config-card {
        background: #FFFFFF;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .config-card-header {
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        border-bottom: 1px solid #E5E5EA;
        padding: 20px 24px;
    }

    .config-card-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #1D1D1F;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .config-card-header i {
        color: #007AFF;
        font-size: 20px;
    }

    .config-card-body {
        padding: 24px;
    }

    .form-label {
        font-weight: 600;
        color: #1D1D1F;
        margin-bottom: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-label i {
        color: #007AFF;
        font-size: 16px;
    }

    .form-control,
    .form-select {
        border: 1px solid #E5E5EA;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 15px;
        background: #FFFFFF;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        outline: none;
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        transform: translateY(-1px);
    }

    .form-text {
        font-size: 13px;
        color: #8E8E93;
        margin-top: 4px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border: none;
        border-radius: 12px;
        padding: 16px 24px;
        font-size: 17px;
        font-weight: 600;
        color: #FFFFFF;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
        color: #FFFFFF;
    }
</style>

<div class="config-container">
    <!-- Header -->
    <div class="config-header">
        <h1>
            <i class="fas fa-shield-alt me-2" style="color: #007AFF;"></i>
            MIR Hospedajes
        </h1>
        <p>Configuración para el envío de reservas al Servicio Web del Ministerio del Interior (MIR) según el Real Decreto 933/2021</p>
    </div>

    <div class="config-card">
        <div class="config-card-header">
            <h5>
                <i class="fas fa-shield-alt"></i>
                Configuración API MIR - Servicio de Hospedajes
            </h5>
        </div>
        <div class="config-card-body">
            <p class="text-muted mb-4">Configuración para el envío de reservas al Servicio Web del Ministerio del Interior (MIR) según el Real Decreto 933/2021.</p>
            
            <form action="{{ route('configuracion.mir.update') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-barcode"></i>
                            Código Arrendador
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_codigo_arrendador" 
                               value="{{ \App\Models\Setting::get('mir_codigo_arrendador', \App\Models\Setting::get('mir_arrendador', '0000004735')) }}"
                               placeholder="0000004735">
                        <small class="form-text">Código único asignado al registrarte en la Sede Electrónica del Ministerio del Interior</small>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-building"></i>
                            Código Establecimiento
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_codigo_establecimiento" 
                               value="{{ \App\Models\Setting::get('mir_codigo_establecimiento', '0000003984') }}"
                               placeholder="0000003984">
                        <small class="form-text">Código del establecimiento asignado por el Sistema de Hospedajes</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-tag"></i>
                            Nombre de la Aplicación
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_aplicacion" 
                               value="{{ \App\Models\Setting::get('mir_aplicacion', 'Hawkins Suite') }}"
                               placeholder="Hawkins Suite">
                        <small class="form-text">Nombre de tu sistema para identificación en MIR</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Usuario
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_usuario" 
                               value="{{ \App\Models\Setting::get('mir_usuario') }}"
                               placeholder="Usuario de la Sede Electrónica">
                        <small class="form-text">Usuario obtenido tras registrarte en la Sede Electrónica del Ministerio del Interior</small>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            Contraseña
                        </label>
                        <input type="password" 
                               class="form-control" 
                               name="mir_password" 
                               value="{{ \App\Models\Setting::get('mir_password') }}"
                               placeholder="Contraseña">
                        <small class="form-text">Contraseña de acceso a la API MIR</small>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-server"></i>
                        Entorno
                    </label>
                    <select class="form-select" name="mir_entorno">
                        <option value="sandbox" {{ \App\Models\Setting::get('mir_entorno', 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                            Pruebas (Sandbox)
                        </option>
                        <option value="production" {{ \App\Models\Setting::get('mir_entorno') === 'production' ? 'selected' : '' }}>
                            Producción
                        </option>
                    </select>
                    <small class="form-text">Selecciona el entorno: Sandbox para pruebas o Producción para el entorno real</small>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Endpoints:</strong><br>
                    <strong>Sandbox:</strong> https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion<br>
                    <strong>Producción:</strong> https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Configuración MIR
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- SECCIÓN: Estado de Envíos MIR --}}
    {{-- ============================================ --}}
    @if(isset($reservas))
    <div class="config-card" style="margin-top: 32px;">
        <div class="config-card-header">
            <h5>
                <i class="fas fa-chart-bar"></i>
                Estado de Envíos MIR
            </h5>
        </div>
        <div class="config-card-body">
            {{-- Summary cards --}}
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="mir-stat-card" style="background: linear-gradient(135deg, #34C759 0%, #28a745 100%);">
                        <div class="mir-stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="mir-stat-number">{{ $contadores['enviado'] ?? 0 }}</div>
                        <div class="mir-stat-label">Enviados OK</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="mir-stat-card" style="background: linear-gradient(135deg, #FF9500 0%, #e68a00 100%);">
                        <div class="mir-stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="mir-stat-number">{{ $contadores['pendiente'] ?? 0 }}</div>
                        <div class="mir-stat-label">Pendientes</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="mir-stat-card" style="background: linear-gradient(135deg, #FF3B30 0%, #cc2f26 100%);">
                        <div class="mir-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="mir-stat-number">{{ $contadores['error'] ?? 0 }}</div>
                        <div class="mir-stat-label">Con Error</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="mir-stat-card" style="background: linear-gradient(135deg, #8E8E93 0%, #6c6c70 100%);">
                        <div class="mir-stat-icon"><i class="fas fa-id-card"></i></div>
                        <div class="mir-stat-number">{{ $contadores['sin_dni'] ?? 0 }}</div>
                        <div class="mir-stat-label">Sin Datos DNI</div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover" id="mirTable">
                    <thead>
                        <tr style="background: #F2F2F7;">
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">Fecha entrada</th>
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">Apartamento</th>
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">Huesped principal</th>
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">N. personas</th>
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">Estado MIR</th>
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">Fecha envio</th>
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">Cod. referencia</th>
                            <th style="font-size:13px; font-weight:600; color:#1D1D1F;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservas as $reserva)
                        @php
                            $estado = $reserva->mir_status_computed ?? 'sin_dni';
                            $badgeClass = match($estado) {
                                'enviado' => 'background: #34C759; color: #fff;',
                                'error' => 'background: #FF3B30; color: #fff;',
                                'pendiente' => 'background: #FF9500; color: #fff;',
                                default => 'background: #8E8E93; color: #fff;',
                            };
                            $badgeText = match($estado) {
                                'enviado' => 'Enviado',
                                'error' => 'Error',
                                'pendiente' => 'Pendiente',
                                default => 'Sin DNI',
                            };
                        @endphp
                        <tr>
                            <td style="font-size:14px;">{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}</td>
                            <td style="font-size:14px;">
                                {{ $reserva->apartamento->nombre ?? '-' }}
                                @if($reserva->apartamento && $reserva->apartamento->edificio)
                                    <small class="text-muted d-block">{{ $reserva->apartamento->edificio->nombre }}</small>
                                @endif
                            </td>
                            <td style="font-size:14px;">{{ $reserva->cliente->nombre ?? '-' }} {{ $reserva->cliente->primer_apellido ?? '' }}</td>
                            <td style="font-size:14px; text-align:center;">{{ $reserva->numero_personas ?? 1 }}</td>
                            <td>
                                <span class="badge" style="{{ $badgeClass }} border-radius:8px; padding:4px 10px; font-size:12px; font-weight:600;">
                                    {{ $badgeText }}
                                </span>
                            </td>
                            <td style="font-size:14px;">{{ $reserva->mir_fecha_envio ? \Carbon\Carbon::parse($reserva->mir_fecha_envio)->format('d/m/Y H:i') : '-' }}</td>
                            <td style="font-size:14px;">{{ $reserva->mir_codigo_referencia ?? '-' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if(in_array($estado, ['pendiente', 'error']))
                                        <button class="btn btn-sm btn-mir-enviar" data-id="{{ $reserva->id }}" title="Enviar a MIR"
                                            style="background:#007AFF; color:#fff; border:none; border-radius:8px; padding:4px 10px; font-size:12px;">
                                            <i class="fas fa-paper-plane"></i> Enviar
                                        </button>
                                    @endif
                                    @if($estado === 'enviado')
                                        <button class="btn btn-sm btn-mir-enviar" data-id="{{ $reserva->id }}" title="Reenviar a MIR"
                                            style="background:#FF9500; color:#fff; border:none; border-radius:8px; padding:4px 10px; font-size:12px;">
                                            <i class="fas fa-redo"></i> Reenviar
                                        </button>
                                    @endif
                                    @if($reserva->mir_respuesta)
                                        <button class="btn btn-sm btn-mir-detalle" data-respuesta="{{ $reserva->mir_respuesta }}" title="Ver detalle"
                                            style="background:#E5E5EA; color:#1D1D1F; border:none; border-radius:8px; padding:4px 10px; font-size:12px;">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay reservas en el rango de fechas seleccionado.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Detalle respuesta MIR --}}
    <div class="modal fade" id="mirDetalleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom:1px solid #E5E5EA;">
                    <h5 class="modal-title" style="font-weight:600; color:#1D1D1F;">
                        <i class="fas fa-file-alt me-2" style="color:#007AFF;"></i>Detalle Respuesta MIR
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="mirDetalleContent" style="background:#F2F2F7; padding:16px; border-radius:8px; font-size:13px; max-height:400px; overflow-y:auto; white-space:pre-wrap; word-break:break-word;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .mir-stat-card {
        border-radius: 16px;
        padding: 20px;
        color: #fff;
        text-align: center;
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    .mir-stat-card:hover {
        transform: translateY(-3px);
    }
    .mir-stat-icon {
        font-size: 24px;
        margin-bottom: 8px;
        opacity: 0.9;
    }
    .mir-stat-number {
        font-size: 32px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }
    .mir-stat-label {
        font-size: 13px;
        font-weight: 500;
        opacity: 0.9;
    }
    #mirTable th {
        border-bottom: 2px solid #E5E5EA;
        padding: 12px 8px;
    }
    #mirTable td {
        padding: 10px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #F2F2F7;
    }
    #mirTable tbody tr:hover {
        background: #F8F8FA;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enviar / Reenviar MIR
    document.querySelectorAll('.btn-mir-enviar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var reservaId = this.getAttribute('data-id');
            var button = this;

            Swal.fire({
                title: 'Enviar a MIR',
                text: 'Se enviaran los datos de esta reserva al Ministerio del Interior.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007AFF',
                cancelButtonColor: '#8E8E93',
                confirmButtonText: 'Enviar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    fetch('/reservas/' + reservaId + '/enviar-mir', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: 'Enviado',
                                text: data.message || 'Reserva enviada correctamente a MIR.',
                                icon: 'success',
                                confirmButtonColor: '#007AFF'
                            }).then(function() {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'No se pudo enviar la reserva a MIR.',
                                icon: 'error',
                                confirmButtonColor: '#007AFF'
                            });
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-paper-plane"></i> Reintentar';
                        }
                    })
                    .catch(function(err) {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error de conexion. Intentalo de nuevo.',
                            icon: 'error',
                            confirmButtonColor: '#007AFF'
                        });
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-paper-plane"></i> Reintentar';
                    });
                }
            });
        });
    });

    // Ver Detalle MIR
    document.querySelectorAll('.btn-mir-detalle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var respuesta = this.getAttribute('data-respuesta');
            try {
                var parsed = JSON.parse(respuesta);
                document.getElementById('mirDetalleContent').textContent = JSON.stringify(parsed, null, 2);
            } catch(e) {
                document.getElementById('mirDetalleContent').textContent = respuesta;
            }
            var modal = new bootstrap.Modal(document.getElementById('mirDetalleModal'));
            modal.show();
        });
    });
});
</script>
@endpush
@endsection




