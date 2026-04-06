@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                Lugares Cercanos - {{ $apartamento->titulo ?? $apartamento->nombre }}
            </h1>
            <p class="text-muted mb-0">Gestiona los lugares cercanos que se mostrarán en la página pública</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Volver al Apartamento
            </a>
            @if($lugares->isNotEmpty())
                <form action="{{ route('admin.lugares-cercanos.borrar-todos', $apartamento->id) }}" 
                      method="POST" 
                      class="d-inline"
                      onsubmit="return confirm('¿Estás seguro de borrar TODOS los lugares cercanos? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash-alt me-2"></i>Borrar Todos
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.lugares-cercanos.generar-automatico', $apartamento->id) }}" class="btn btn-success btn-lg">
                <i class="fas fa-map-marked-alt me-2"></i>Generar Automáticamente (OSM)
            </a>
            <a href="{{ route('admin.lugares-cercanos.create', $apartamento->id) }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Nuevo Lugar
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

    @php
        $lugaresPorCategoria = $lugares->groupBy('categoria');
    @endphp

    @if($lugares->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No hay lugares cercanos configurados. <a href="{{ route('admin.lugares-cercanos.create', $apartamento->id) }}">Crea el primero</a>
        </div>
    @else
        @foreach($lugaresPorCategoria as $categoria => $lugaresCat)
            @php
                $categoriaLabels = [
                    'que_hay_cerca' => '¿Qué hay cerca?',
                    'restaurantes' => 'Restaurantes y cafeterías',
                    'transporte' => 'Transporte público',
                    'playas' => 'Playas en la zona',
                    'aeropuertos' => 'Aeropuertos más cercanos'
                ];
            @endphp
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-folder me-2"></i>
                        {{ $categoriaLabels[$categoria] ?? ucfirst(str_replace('_', ' ', $categoria)) }}
                        <span class="badge bg-light text-dark ms-2">{{ $lugaresCat->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">Orden</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Distancia</th>
                                    <th style="width: 100px;">Estado</th>
                                    <th style="width: 150px;" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lugaresCat as $lugar)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $lugar->orden }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $lugar->nombre }}</strong>
                                        </td>
                                        <td>
                                            @if($lugar->tipo)
                                                <span class="badge bg-light text-dark">{{ $lugar->tipo }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($lugar->distancia)
                                                <span>{{ number_format($lugar->distancia, 2, ',', '.') }} {{ $lugar->unidad_distancia }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($lugar->activo)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.lugares-cercanos.edit', [$apartamento->id, $lugar->id]) }}" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.lugares-cercanos.destroy', [$apartamento->id, $lugar->id]) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este lugar?');">
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
    @endif
</div>
@endsection

