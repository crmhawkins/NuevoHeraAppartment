@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-tools me-2 text-primary"></i>
                Servicios Técnicos
            </h1>
            <p class="text-muted mb-0">Gestiona las categorías y servicios técnicos disponibles</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalCategoria" onclick="resetearFormularioCategoria()">
                <i class="fas fa-folder-plus me-2"></i>
                Nueva Categoría
            </button>
            <a href="{{ route('admin.servicios-tecnicos.create') }}" class="btn btn-success btn-lg">
                <i class="fas fa-plus me-2"></i>
                Nuevo Servicio
            </a>
        </div>
    </div>

    <hr class="mb-4">

    @if(session('swal_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('swal_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('swal_error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('swal_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Categorías -->
    @if($categorias->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-folder me-2"></i>
                    Categorías
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($categorias as $categoria)
                        <div class="col-md-4 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                @if($categoria->icono)
                                                    <span class="me-2">{!! $categoria->icono !!}</span>
                                                @endif
                                                {{ $categoria->nombre }}
                                            </h6>
                                            <small class="text-muted">{{ $categoria->servicios_count }} servicios</small>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editarCategoria({{ $categoria->id }}, '{{ $categoria->nombre }}', '{{ $categoria->descripcion }}', '{{ $categoria->icono }}', {{ $categoria->orden }}, {{ $categoria->activo ? 'true' : 'false' }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.servicios-tecnicos.destroyCategoria', $categoria->id) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Servicios -->
    @if($servicios->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No hay servicios técnicos configurados. <a href="{{ route('admin.servicios-tecnicos.create') }}">Crea el primero</a>
        </div>
    @else
        @if(isset($serviciosPorCategoria) && $serviciosPorCategoria->isNotEmpty())
            @foreach($serviciosPorCategoria as $categoriaId => $serviciosCat)
                @php
                    $categoria = $categorias->firstWhere('id', $categoriaId);
                @endphp
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-folder me-2"></i>
                            {{ $categoria ? $categoria->nombre : 'Sin categoría' }}
                            <span class="badge bg-light text-dark ms-2">{{ $serviciosCat->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Orden</th>
                                        <th>Nombre</th>
                                        <th>Unidad</th>
                                        <th>Precio Base</th>
                                        <th style="width: 100px;">Estado</th>
                                        <th style="width: 150px;" class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serviciosCat as $servicio)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">{{ $servicio->orden }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $servicio->nombre }}</strong>
                                                @if($servicio->descripcion)
                                                    <br><small class="text-muted">{{ Str::limit($servicio->descripcion, 60) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $servicio->unidad_medida ?: '—' }}</span>
                                            </td>
                                            <td>
                                                @if($servicio->precio_base)
                                                    <strong class="text-success">{{ number_format($servicio->precio_base, 2, ',', '.') }} €</strong>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($servicio->activo)
                                                    <span class="badge bg-success">Activo</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.servicios-tecnicos.edit', $servicio->id) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.servicios-tecnicos.destroy', $servicio->id) }}" 
                                                          method="POST" 
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este servicio?')"
                                                          class="d-inline">
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
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</div>

<!-- Modal para crear/editar categoría -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCategoriaTitle">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCategoria" method="POST" action="{{ route('admin.servicios-tecnicos.storeCategoria') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="categoria_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="categoria_descripcion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icono (HTML/Font Awesome)</label>
                        <input type="text" class="form-control" name="icono" id="categoria_icono" placeholder='<i class="fas fa-tools"></i>'>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Orden</label>
                            <input type="number" class="form-control" name="orden" id="categoria_orden" value="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="activo" id="categoria_activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetearFormularioCategoria() {
    document.getElementById('modalCategoriaTitle').textContent = 'Nueva Categoría';
    document.getElementById('formCategoria').action = '{{ route("admin.servicios-tecnicos.storeCategoria") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('formCategoria').reset();
}

function editarCategoria(id, nombre, descripcion, icono, orden, activo) {
    document.getElementById('modalCategoriaTitle').textContent = 'Editar Categoría';
    document.getElementById('formCategoria').action = '{{ route("admin.servicios-tecnicos.updateCategoria", ":id") }}'.replace(':id', id);
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    document.getElementById('categoria_nombre').value = nombre;
    document.getElementById('categoria_descripcion').value = descripcion || '';
    document.getElementById('categoria_icono').value = icono || '';
    document.getElementById('categoria_orden').value = orden || 0;
    document.getElementById('categoria_activo').value = activo ? '1' : '0';
    
    const modal = new bootstrap.Modal(document.getElementById('modalCategoria'));
    modal.show();
}

// Resetear formulario al cerrar modal
document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function () {
    resetearFormularioCategoria();
});
</script>
@endsection

