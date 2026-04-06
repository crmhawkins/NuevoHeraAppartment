@extends('layouts.appAdmin')

@section('title', 'Gestión de Apartamentos')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-building me-2 text-primary"></i>
                        Gestión de Apartamentos
                    </h1>
                    <p class="text-muted mb-0">Gestiona todos los apartamentos y propiedades del sistema</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('apartamentos.admin.create') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Crear Apartamento
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-building fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $apartamentos->total() }}</h4>
                    <small>Total Apartamentos</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $apartamentos->where('id_channex', '!=', null)->count() }}</h4>
                    <small>Sincronizados</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $apartamentos->where('id_channex', null)->count() }}</h4>
                    <small>Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-building fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $edificios->count() }}</h4>
                    <small>Edificios</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-filter me-2 text-primary"></i>
                Filtros y Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('apartamentos.admin.index') }}" method="GET" id="search_form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="apartamento_id" class="form-label fw-semibold">
                            <i class="fas fa-home me-1 text-primary"></i>Apartamento
                        </label>
                        <select class="form-select" name="apartamento_id" id="apartamento_id">
                            <option value="">Todos los apartamentos</option>
                            @foreach($apartamentoslist as $apartamento)
                                <option value="{{ $apartamento->id }}"
                                    {{ request()->get('apartamento_id') == $apartamento->id ? 'selected' : '' }}>
                                    {{ $apartamento->titulo ?? $apartamento->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="edificio_id" class="form-label fw-semibold">
                            <i class="fas fa-building me-1 text-primary"></i>Edificio
                        </label>
                        <select name="edificio_id" id="edificio_id" class="form-select">
                            <option value="">Todos los edificios</option>
                            @foreach ($edificios as $edificio)
                                <option value="{{ $edificio->id }}" {{ request()->get('edificio_id') == $edificio->id ? 'selected' : '' }}>
                                    {{ $edificio->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="button" onclick="limpiarFiltros()" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de apartamentos -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Lista de Apartamentos
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary px-3 py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ $apartamentos->count() }} de {{ $apartamentos->total() }} apartamentos
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($apartamentos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">
                                    <a href="{{ route('apartamentos.admin.index', array_merge(request()->query(), ['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc'])) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-hashtag me-1 text-primary"></i>ID
                                        @if(request('sort') == 'id')
                                            <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }} ms-auto"></i>
                                        @else
                                            <i class="fas fa-sort ms-auto text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0">
                                    <a href="{{ route('apartamentos.admin.index', array_merge(request()->query(), ['sort' => 'nombre', 'order' => request('sort') == 'nombre' && request('order') == 'asc' ? 'desc' : 'asc'])) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-home me-1 text-primary"></i>Nombre
                                        @if(request('sort') == 'nombre')
                                            <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }} ms-auto"></i>
                                        @else
                                            <i class="fas fa-sort ms-auto text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0">
                                    <a href="{{ route('apartamentos.admin.index', array_merge(request()->query(), ['sort' => 'edificio', 'order' => request('sort') == 'edificio' && request('order') == 'asc' ? 'desc' : 'asc'])) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-building me-1 text-primary"></i>Edificio
                                        @if(request('sort') == 'edificio')
                                            <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }} ms-auto"></i>
                                        @else
                                            <i class="fas fa-sort ms-auto text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-key me-1 text-primary"></i>ID Booking
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-bed me-1 text-primary"></i>ID Airbnb
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-globe me-1 text-primary"></i>ID Web
                                </th>
                                <th class="border-0">
                                    <a href="{{ route('apartamentos.admin.index', array_merge(request()->query(), ['sort' => 'ingresos', 'order' => request('sort') == 'ingresos' && request('order') == 'asc' ? 'desc' : 'asc'])) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-euro-sign me-1 text-success"></i>Ingresos {{ $añoActual }}
                                        @if(request('sort') == 'ingresos')
                                            <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }} ms-auto"></i>
                                        @else
                                            <i class="fas fa-sort ms-auto text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0">
                                    <a href="{{ route('apartamentos.admin.index', array_merge(request()->query(), ['sort' => 'ocupaciones', 'order' => request('sort') == 'ocupaciones' && request('order') == 'asc' ? 'desc' : 'asc'])) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-calendar-check me-1 text-info"></i>Ocupaciones {{ $añoActual }}
                                        @if(request('sort') == 'ocupaciones')
                                            <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }} ms-auto"></i>
                                        @else
                                            <i class="fas fa-sort ms-auto text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 text-center">
                                    <i class="fas fa-cogs me-1 text-primary"></i>Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($apartamentos as $apartamento)
                                <tr class="align-middle">
                                    <td>
                                        <span class="badge bg-secondary fs-6">#{{ $apartamento->id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-text">
                                                    {{ strtoupper(substr($apartamento->titulo ?? $apartamento->nombre ?? 'A', 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">{{ $apartamento->titulo ?? $apartamento->nombre ?? 'Sin título' }}</h6>
                                                @if($apartamento->property_type)
                                                    <small class="text-muted">
                                                        <span class="badge bg-info">{{ ucfirst($apartamento->property_type) }}</span>
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($apartamento->edificio_id && $apartamento->edificioName)
                                            <span class="fw-semibold">{{ $apartamento->edificioName->nombre }}</span>
                                        @else
                                            <span class="text-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($apartamento->id_booking)
                                            <code class="bg-light px-2 py-1 rounded">{{ $apartamento->id_booking }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($apartamento->id_airbnb)
                                            <code class="bg-light px-2 py-1 rounded">{{ $apartamento->id_airbnb }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($apartamento->id_web)
                                            <code class="bg-light px-2 py-1 rounded">{{ $apartamento->id_web }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $estadisticas = $estadisticasApartamentos[$apartamento->id] ?? ['ingresos_año' => 0, 'ocupaciones_año' => 0];
                                        @endphp
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-success">
                                                €{{ number_format($estadisticas['ingresos_año'], 2, ',', '.') }}
                                            </span>
                                            @if($estadisticas['ingresos_netos'] > 0)
                                                <small class="text-muted">
                                                    Neto: €{{ number_format($estadisticas['ingresos_netos'], 2, ',', '.') }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-info-subtle text-info px-2 py-1 me-2">
                                                {{ $estadisticas['ocupaciones_año'] }}
                                            </span>
                                            @if($estadisticas['ocupaciones_año'] > 0)
                                                <small class="text-muted">
                                                    reservas
                                                </small>
                                            @else
                                                <small class="text-muted">Sin ocupaciones</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('apartamentos.admin.show', $apartamento->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver apartamento">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" 
                                               class="btn btn-sm btn-outline-warning" 
                                               title="Editar apartamento">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($apartamento->id_channex)
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-info" 
                                                        title="Registrar webhooks"
                                                        onclick="registrarWebhooks({{ $apartamento->id }})">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            @endif
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    title="Eliminar apartamento"
                                                    onclick="confirmarEliminacion({{ $apartamento->id }}, '{{ $apartamento->titulo ?? $apartamento->nombre }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-center py-4">
                    {{ $apartamentos->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-building text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 fw-semibold">No hay apartamentos</h4>
                        <p class="text-muted">No se encontraron apartamentos con los filtros aplicados.</p>
                        <a href="{{ route('apartamentos.admin.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Primer Apartamento
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminación -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@section('styles')
<style>
/* Gradientes personalizados */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

/* Avatar pequeño */
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content-center;
    background-color: #f8f9fa;
    border-radius: 50%;
}

.avatar-text {
    font-size: 1.2rem;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
    padding: 1rem;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
    padding: 1rem;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fc;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Estado vacío */
.empty-state {
    padding: 2rem;
}

.empty-state i {
    opacity: 0.7;
}

/* Animaciones */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

/* Paginación personalizada */
.pagination {
    margin-bottom: 0;
}

.page-link {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    color: #667eea;
    margin: 0 2px;
}

.page-link:hover {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
}

.page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between .btn {
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .col-lg-3 {
        margin-bottom: 1rem;
    }
}
</style>
@endsection

@section('scriptHead')
<script>
// Función para limpiar filtros
function limpiarFiltros() {
    window.location.href = '{{ route("apartamentos.admin.index") }}';
}

// Función para confirmar eliminación
function confirmarEliminacion(apartamentoId, apartamentoNombre) {
    Swal.fire({
        title: '¿Estás seguro?',
        html: `¿Deseas eliminar el apartamento <strong>${apartamentoNombre}</strong>?<br><br>Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF3B30',
        cancelButtonColor: '#8E8E93',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('delete-form');
            form.action = `{{ url('apartamentos/admin') }}/${apartamentoId}`;
            form.submit();
        }
    });
}

// Función para registrar webhooks
function registrarWebhooks(apartamentoId) {
    Swal.fire({
        title: 'Registrando Webhooks',
        text: 'Por favor espera mientras se registran los webhooks...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/apartamentos/admin/${apartamentoId}/webhooks`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        let successCount = 0;
        let errorCount = 0;
        
        data.forEach(item => {
            if (item.status === 'success') successCount++;
            else errorCount++;
        });

        Swal.fire({
            title: 'Webhooks Registrados',
            html: `
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success fa-2x"></i>
                    </div>
                    <p><strong>${successCount}</strong> webhooks registrados exitosamente</p>
                    ${errorCount > 0 ? `<p class="text-warning"><strong>${errorCount}</strong> webhooks con errores</p>` : ''}
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    })
    .catch(error => {
        Swal.fire({
            title: 'Error',
            text: 'Error al registrar los webhooks: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}

// Mostrar mensajes de éxito/error con SweetAlert
@if(session('swal_success'))
    Swal.fire({
        title: '¡Éxito!',
        text: '{{ session("swal_success") }}',
        icon: 'success',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#34C759'
    });
@endif

@if(session('swal_error'))
    Swal.fire({
        title: 'Error',
        text: '{{ session("swal_error") }}',
        icon: 'error',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#FF3B30'
    });
@endif
</script>
@endsection
