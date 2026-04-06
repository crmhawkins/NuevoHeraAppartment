@extends('layouts.appAdmin')

@section('title', 'Gestión de Checklists - Zonas Comunes')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-list-check"></i> Checklists de Zonas Comunes
                </h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.checklists-zonas-comunes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Checklist
                    </a>
                    <a href="{{ route('admin.zonas-comunes.index') }}" class="btn btn-info">
                        <i class="fas fa-building"></i> Zonas Comunes
                    </a>
                </div>
            </div>

            <!-- Tabla de Checklists -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-table"></i> Checklists ({{ $checklists->total() }})
                    </h6>
                </div>
                <div class="card-body">
                    @if($checklists->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="checklistsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Items</th>
                                        <th>Estado</th>
                                        <th>Orden</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($checklists as $checklist)
                                        <tr>
                                            <td><strong>#{{ $checklist->id }}</strong></td>
                                            <td>
                                                <strong>{{ $checklist->nombre }}</strong>
                                                @if($checklist->descripcion)
                                                    <br><small class="text-muted">{{ Str::limit($checklist->descripcion, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst($checklist->categoria) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $checklist->items->count() }} items</span>
                                            </td>
                                            <td>
                                                @if($checklist->activo)
                                                    <span class="badge bg-success"><i class="fas fa-check"></i> Activo</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Inactivo</span>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-secondary">{{ $checklist->orden }}</span></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.checklists-zonas-comunes.show', $checklist->id) }}" 
                                                       class="btn btn-sm btn-primary" title="Ver Detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.checklists-zonas-comunes.items', $checklist->id) }}" 
                                                       class="btn btn-sm btn-warning" title="Gestionar Items">
                                                        <i class="fas fa-tasks"></i>
                                                    </a>
                                                    <a href="{{ route('admin.checklists-zonas-comunes.edit', $checklist->id) }}" 
                                                       class="btn btn-sm btn-info" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.checklists-zonas-comunes.toggle-status', $checklist->id) }}" 
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-{{ $checklist->activo ? 'warning' : 'success' }}" 
                                                                title="{{ $checklist->activo ? 'Desactivar' : 'Activar' }}">
                                                            <i class="fas fa-{{ $checklist->activo ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.checklists-zonas-comunes.destroy', $checklist->id) }}" 
                                                          method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('¿Estás seguro de que quieres eliminar este checklist?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $checklists->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-list-check fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay checklists disponibles</h5>
                            <p class="text-muted">Crea el primer checklist para comenzar</p>
                            <a href="{{ route('admin.checklists-zonas-comunes.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Checklist
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table thead {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table thead th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    border: none;
    padding: 12px 8px;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
}

.table tbody td {
    padding: 12px 8px;
    vertical-align: middle;
    border-color: #e9ecef;
}

.badge {
    font-size: 0.8em;
    font-weight: 500;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined') {
        $('#checklistsTable').DataTable({
            language: {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ registros",
                "sZeroRecords":    "No se encontraron resultados",
                "sEmptyTable":     "Ningún dato disponible en esta tabla",
                "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar:",
                "sUrl":            "",
                "sInfoThousands":  ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst":    "Primero",
                    "sLast":     "Último",
                    "sNext":     "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
            },
            pageLength: 25,
            order: [[0, 'desc']]
        });
    } else {
        console.warn('jQuery no está disponible, DataTable no se inicializará');
    }
});
</script>
@endsection
