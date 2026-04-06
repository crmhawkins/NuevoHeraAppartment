@extends('layouts.appAdmin')

@section('title', 'Horarios de Empleadas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-clock me-2"></i>Horarios de Empleadas
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info" onclick="mostrarEmpleadasSinHorario()">
                            <i class="fas fa-user-plus me-1"></i>Empleadas sin Horario
                        </button>
                        <a href="{{ route('admin.empleada-horarios.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Nuevo Horario
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Buscar empleada..." value="{{ $search }}" onchange="filtrar()">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="filtrar()">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ $activo === '1' ? 'selected' : '' }}>Activos</option>
                                <option value="0" {{ $activo === '0' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="filtrar()">
                                <option value="">Todas las horas</option>
                                <option value="4" {{ request('horas') == '4' ? 'selected' : '' }}>4 horas</option>
                                <option value="6" {{ request('horas') == '6' ? 'selected' : '' }}>6 horas</option>
                                <option value="8" {{ request('horas') == '8' ? 'selected' : '' }}>8 horas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>

                    @if($horarios->count() > 0)
                        <div class="row">
                            @foreach($horarios as $horario)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-user me-1"></i>{{ $horario->user->name }}
                                                </h5>
                                                @if($horario->user->email)
                                                    <small class="text-muted d-block mt-0">{{ $horario->user->email }}</small>
                                                @endif
                                            </div>
                                            <span class="badge bg-{{ $horario->activo ? 'success' : 'secondary' }}">
                                                {{ $horario->activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">Horas/día:</small><br>
                                                    <strong>{{ $horario->horas_contratadas_dia }}h</strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Días libres/mes:</small><br>
                                                    <strong>{{ $horario->dias_libres_mes }}</strong>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">Horario de atención:</small><br>
                                                <strong>{{ $horario->horario_atencion }}</strong>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">Días de trabajo:</small><br>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'] as $dia)
                                                        <span class="badge bg-{{ $horario->$dia ? 'primary' : 'secondary' }}">
                                                            {{ ucfirst(substr($dia, 0, 3)) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            
                                            @if($horario->observaciones)
                                                <div class="mb-2">
                                                    <small class="text-muted">Observaciones:</small><br>
                                                    <small>{{ Str::limit($horario->observaciones, 100) }}</small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-footer">
                                            <div class="btn-group w-100" role="group">
                                                <a href="{{ route('admin.empleada-horarios.show', $horario) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="{{ route('admin.empleada-horarios.edit', $horario) }}" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                <a href="{{ route('admin.empleada-dias-libres.index', $horario) }}" class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-calendar-alt"></i> Días Libres
                                                </a>
                                                <button type="button" class="btn btn-outline-{{ $horario->activo ? 'warning' : 'success' }} btn-sm" 
                                                        onclick="toggleActive({{ $horario->id }})">
                                                    <i class="fas fa-{{ $horario->activo ? 'pause' : 'play' }}"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="eliminarHorario({{ $horario->id }}, '{{ $horario->user->name }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            {{ $horarios->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay horarios configurados</h4>
                            <p class="text-muted">Configura los horarios de las empleadas para generar turnos automáticamente</p>
                            <a href="{{ route('admin.empleada-horarios.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Configurar Horario
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para empleadas sin horario -->
<div class="modal fade" id="modalEmpleadasSinHorario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Empleadas sin Horario Configurado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="listaEmpleadasSinHorario">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .btn {
        transition: all 0.3s ease;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .badge {
        border-radius: 20px;
        padding: 6px 12px;
        font-weight: 500;
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .schedule-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border-radius: 15px;
        color: white;
        transition: all 0.3s ease;
    }
    
    .schedule-card:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
</style>
@endsection

@section('scripts')
<script>
// Configuración global de SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

function mostrarNotificacion(tipo, titulo, mensaje) {
    Toast.fire({
        icon: tipo,
        title: titulo,
        text: mensaje
    });
}

function filtrar() {
    const search = document.querySelector('input[type="text"]').value;
    const activo = document.querySelectorAll('select')[0].value;
    const horas = document.querySelectorAll('select')[1].value;
    
    mostrarNotificacion('info', 'Filtrando...', 'Aplicando filtros de búsqueda');
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (activo) params.append('activo', activo);
    if (horas) params.append('horas', horas);
    
    window.location.href = `{{ route('admin.empleada-horarios.index') }}?${params.toString()}`;
}

function limpiarFiltros() {
    mostrarNotificacion('info', 'Limpiando filtros...', 'Restableciendo vista');
    window.location.href = '{{ route("admin.empleada-horarios.index") }}';
}

function toggleActive(id) {
    Swal.fire({
        title: '🔄 Cambiar Estado',
        html: `
            <div class="text-center">
                <i class="fas fa-user-clock fa-3x text-primary mb-3"></i>
                <p>¿Estás seguro de que quieres cambiar el estado de este horario?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Esto afectará la disponibilidad de la empleada en la generación de turnos
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-2"></i>Sí, Cambiar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'animate__animated animate__zoomIn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Cambiando estado...',
                html: '<div class="loading-spinner mx-auto"></div><p class="mt-3">Procesando...</p>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            fetch(`/admin/empleada-horarios/${id}/toggle-active`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Estado Actualizado!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Continuar',
                        customClass: {
                            popup: 'animate__animated animate__bounceIn'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error al cambiar el estado',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        }
    });
}

function mostrarEmpleadasSinHorario() {
    const modal = new bootstrap.Modal(document.getElementById('modalEmpleadasSinHorario'));
    modal.show();
    
    // Mostrar loading
    document.getElementById('listaEmpleadasSinHorario').innerHTML = `
        <div class="text-center py-4">
            <div class="loading-spinner mx-auto mb-3"></div>
            <p>Cargando empleadas...</p>
        </div>
    `;
    
    // Cargar empleadas sin horario
    fetch('/admin/empleada-horarios/empleadas-sin-horario')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('listaEmpleadasSinHorario');
            
            if (data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5 class="text-success">¡Perfecto!</h5>
                        <p class="text-muted">Todas las empleadas tienen horario configurado</p>
                    </div>
                `;
            } else {
                let html = '<div class="row">';
                data.forEach(empleada => {
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-2x text-primary mb-3"></i>
                                    <h6 class="card-title">${empleada.name}</h6>
                                    <p class="card-text text-muted">${empleada.email}</p>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="crearHorarioRapido(${empleada.id})">
                                        <i class="fas fa-plus me-1"></i>Crear Horario
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            }
        })
        .catch(error => {
            document.getElementById('listaEmpleadasSinHorario').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar las empleadas
                </div>
            `;
        });
}

function crearHorarioRapido(userId) {
    Swal.fire({
        title: '⚡ Crear Horario Rápido',
        html: `
            <div class="text-center">
                <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                <p>¿Cuántas horas trabaja por día esta empleada?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Se configurará automáticamente de lunes a viernes con horario estándar
                </div>
            </div>
        `,
        input: 'select',
        inputOptions: {
            '4': '4 horas (Media jornada)',
            '6': '6 horas (Jornada reducida)',
            '8': '8 horas (Jornada completa)'
        },
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-2"></i>Crear',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'animate__animated animate__zoomIn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const horas = result.value;
            
            // Mostrar loading
            Swal.fire({
                title: 'Creando horario...',
                html: '<div class="loading-spinner mx-auto"></div><p class="mt-3">Configurando horario automático...</p>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            fetch('/admin/empleada-horarios/crear-horario-rapido', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    horas_contratadas_dia: horas
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Horario Creado!',
                        html: `
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p>${data.message}</p>
                                <div class="alert alert-success">
                                    <strong>Horas configuradas:</strong> ${horas}h por día
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'Continuar',
                        customClass: {
                            popup: 'animate__animated animate__bounceIn'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error al crear el horario',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        }
    });
}

function eliminarHorario(id, nombreEmpleada) {
    Swal.fire({
        title: '🗑️ Eliminar Horario',
        html: `
            <div class="text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <p><strong>¿Estás seguro de que quieres eliminar el horario de <span class="text-primary">${nombreEmpleada}</span>?</strong></p>
                <div class="alert alert-danger">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Esta acción no se puede deshacer.</strong><br>
                    Se eliminará toda la configuración de horarios, días libres y turnos asociados.
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, Eliminar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'animate__animated animate__zoomIn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando horario...',
                html: '<div class="loading-spinner mx-auto"></div><p class="mt-3">Procesando eliminación...</p>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            // Crear formulario para enviar DELETE request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/empleada-horarios/${id}`;
            
            // Agregar token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfToken);
            
            // Agregar método DELETE
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            // Agregar al DOM y enviar
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Añadir animaciones a las tarjetas
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    // Añadir efectos de hover a los botones
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Añadir efectos a las tarjetas de estadísticas
    const statCards = document.querySelectorAll('.card.bg-primary, .card.bg-info, .card.bg-success, .card.bg-warning');
    statCards.forEach(card => {
        card.classList.add('schedule-card');
    });
});
</script>
@endsection
