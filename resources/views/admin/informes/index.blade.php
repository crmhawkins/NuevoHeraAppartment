@extends('layouts.appAdmin')

@section('title', 'Informes AI - Análisis Financieros')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-robot text-primary me-2"></i>
                        Informes AI - Análisis Financieros
                    </h2>
                    <p class="text-muted mb-0">Gestiona y visualiza los informes generados por inteligencia artificial</p>
                </div>
                <div>
                    <a href="{{ route('admin.diarioCaja.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Diario
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-list me-2 text-primary"></i>
                        Lista de Informes Generados
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($informes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 20%;">Período</th>
                                        <th style="width: 35%;">Resumen</th>
                                        <th style="width: 15%;">Generado</th>
                                        <th style="width: 15%;">Tamaño</th>
                                        <th style="width: 10%;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($informes as $informe)
                                        <tr>
                                            <td class="text-muted">{{ $informe->id }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold text-primary">
                                                        {{ \Carbon\Carbon::parse($informe->fecha_inicio)->format('d/m/Y') }}
                                                    </span>
                                                    <span class="text-muted small">
                                                        {{ \Carbon\Carbon::parse($informe->fecha_fin)->format('d/m/Y') }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-alt text-info me-2"></i>
                                                    <div>
                                                        <div class="fw-semibold text-dark">
                                                            {{ $informe->resumen ?? 'Sin resumen' }}
                                                        </div>
                                                        <small class="text-muted">
                                                            {{ \Carbon\Carbon::parse($informe->fecha_inicio)->diffInDays($informe->fecha_fin) + 1 }} días analizados
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-dark">
                                                        {{ $informe->created_at->format('d/m/Y') }}
                                                    </span>
                                                    <small class="text-muted">
                                                        {{ $informe->created_at->format('H:i') }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ number_format(strlen($informe->contenido_md) / 1024, 1) }} KB
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('informe.ai.ver', $informe->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Ver informe">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarInforme({{ $informe->id }})"
                                                            title="Eliminar informe">
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
                        <div class="card-footer bg-white border-0 py-3">
                            {{ $informes->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-robot text-muted" style="font-size: 4rem;"></i>
                            </div>
                            <h5 class="text-muted mb-3">No hay informes generados</h5>
                            <p class="text-muted mb-4">
                                Los informes AI se generan desde el Diario de Caja seleccionando un período específico.
                            </p>
                            <a href="{{ route('admin.diarioCaja.index') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Generar Primer Informe
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres eliminar este informe?</p>
                <p class="text-muted small">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminar">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let informeIdAEliminar = null;

function eliminarInforme(id) {
    informeIdAEliminar = id;
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}

document.getElementById('confirmarEliminar').addEventListener('click', function() {
    if (informeIdAEliminar) {
        // Crear formulario para eliminar
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/informe-ai/${informeIdAEliminar}/eliminar`;
        
        // Añadir token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Añadir método DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        form.submit();
    }
});
</script>
@endsection
