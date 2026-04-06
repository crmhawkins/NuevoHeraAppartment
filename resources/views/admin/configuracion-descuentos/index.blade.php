@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-cog mr-2"></i>
                        Configuración de Descuentos
                    </h3>
                    <a href="{{ route('configuracion-descuentos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>
                        Nueva Configuración
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Edificio</th>
                                    <th>Descripción</th>
                                    <th>Descuento</th>
                                    <th>Incremento</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($configuraciones as $configuracion)
                                    <tr>
                                        <td>{{ $configuracion->id }}</td>
                                        <td>
                                            <strong>{{ $configuracion->nombre }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                {{ $configuracion->edificio->nombre ?? 'Sin edificio' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ Str::limit($configuracion->descripcion, 50) }}
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ $configuracion->porcentaje_formateado }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">
                                                {{ $configuracion->porcentaje_incremento_formateado }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($configuracion->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $configuracion->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('configuracion-descuentos.show', $configuracion) }}" 
                                                   class="btn btn-sm btn-info" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <a href="{{ route('configuracion-descuentos.edit', $configuracion) }}" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <form action="{{ route('configuracion-descuentos.toggle-status', $configuracion) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-{{ $configuracion->activo ? 'warning' : 'success' }}" 
                                                            title="{{ $configuracion->activo ? 'Desactivar' : 'Activar' }}">
                                                        <i class="fas fa-{{ $configuracion->activo ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>

                                                <form action="{{ route('configuracion-descuentos.destroy', $configuracion) }}" 
                                                      method="POST" style="display: inline;"
                                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta configuración?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                No hay configuraciones de descuento creadas.
                                                <a href="{{ route('configuracion-descuentos.create') }}" class="alert-link">Crear la primera</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@endpush
