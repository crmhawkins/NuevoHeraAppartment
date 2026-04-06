@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-file-alt me-2 text-primary"></i>
                Páginas Legales
            </h1>
            <p class="text-muted mb-0">Gestiona las páginas de información legal</p>
        </div>
        <a href="{{ route('admin.paginas-legales.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Nueva Página
        </a>
    </div>

    <hr class="mb-4">

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($paginas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Título</th>
                                <th>Slug</th>
                                <th>Fecha Actualización</th>
                                <th>Estado</th>
                                <th>Mostrar en Sidebar</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paginas as $pagina)
                                <tr>
                                    <td>{{ $pagina->orden }}</td>
                                    <td>
                                        <strong>{{ $pagina->titulo }}</strong>
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $pagina->slug }}</code>
                                    </td>
                                    <td>
                                        @if($pagina->fecha_actualizacion)
                                            {{ $pagina->fecha_actualizacion->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pagina->activo)
                                            <span class="badge bg-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pagina->mostrar_en_sidebar)
                                            <span class="badge bg-info">Sí</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('web.pagina-legal.show', $pagina->slug) }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="Ver página pública">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.paginas-legales.edit', $pagina->id) }}" 
                                               class="btn btn-sm btn-warning" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.paginas-legales.destroy', $pagina->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar esta página?');">
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
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay páginas legales creadas aún.</p>
                    <a href="{{ route('admin.paginas-legales.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Crear Primera Página
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

