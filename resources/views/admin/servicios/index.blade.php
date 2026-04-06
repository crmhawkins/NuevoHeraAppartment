@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-concierge-bell me-2 text-primary"></i>
                Servicios
            </h1>
            <p class="text-muted mb-0">Gestiona los servicios disponibles para los apartamentos</p>
        </div>
        <a href="{{ route('admin.servicios.create') }}" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>
            Nuevo Servicio
        </a>
    </div>

    <hr class="mb-4">

    @if(session('swal_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('swal_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($servicios->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No hay servicios configurados. <a href="{{ route('admin.servicios.create') }}">Crea el primero</a>
        </div>
    @else
        @if(isset($serviciosPorCategoria) && $serviciosPorCategoria->isNotEmpty())
            @foreach($serviciosPorCategoria as $categoria => $serviciosCat)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-folder me-2"></i>
                            {{ $categoria ?: 'Sin categoría' }}
                            <span class="badge bg-light text-dark ms-2">{{ $serviciosCat->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Orden</th>
                                        <th style="width: 80px;">Icono</th>
                                        <th>Nombre</th>
                                        <th>Descripción (preview)</th>
                                        <th style="width: 120px;">Popular</th>
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
                                                @if($servicio->icono)
                                                    <span style="font-size: 20px;">{!! $servicio->icono !!}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $servicio->nombre }}</strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ Str::limit($servicio->descripcion, 80) }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($servicio->es_popular)
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-star me-1"></i>Popular
                                                    </span>
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
                                                    <a href="{{ route('admin.servicios.edit', $servicio->id) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.servicios.destroy', $servicio->id) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este servicio?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-danger" 
                                                                title="Eliminar">
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
        @else
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">Orden</th>
                                    <th style="width: 80px;">Icono</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Descripción (preview)</th>
                                    <th style="width: 120px;">Popular</th>
                                    <th style="width: 100px;">Estado</th>
                                    <th style="width: 150px;" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($servicios as $servicio)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $servicio->orden }}</span>
                                        </td>
                                        <td>
                                            @if($servicio->icono)
                                                <span style="font-size: 20px;">{!! $servicio->icono !!}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $servicio->nombre }}</strong>
                                        </td>
                                        <td>
                                            @if($servicio->categoria)
                                                <span class="badge bg-light text-dark">{{ $servicio->categoria }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ Str::limit($servicio->descripcion, 80) }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($servicio->es_popular)
                                                <span class="badge bg-info">
                                                    <i class="fas fa-star me-1"></i>Popular
                                                </span>
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
                                                <a href="{{ route('admin.servicios.edit', $servicio->id) }}" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.servicios.destroy', $servicio->id) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este servicio?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger" 
                                                            title="Eliminar">
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
        @endif
    @endif
</div>
@endsection
