@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-ticket-alt text-primary me-2"></i>
                Cupones de Descuento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Cupones</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.cupones.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo Cupón
        </a>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.cupones.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por código o nombre..." value="{{ request('buscar') }}">
                </div>
                <div class="col-md-3">
                    <select name="activo" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="tipo_descuento" class="form-select">
                        <option value="">Todos los tipos</option>
                        <option value="porcentaje" {{ request('tipo_descuento') === 'porcentaje' ? 'selected' : '' }}>Porcentaje</option>
                        <option value="fijo" {{ request('tipo_descuento') === 'fijo' ? 'selected' : '' }}>Fijo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de cupones -->
    <div class="card shadow-sm">
        <div class="card-body">
            @if($cupones->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Descuento</th>
                                <th>Usos</th>
                                <th>Validez</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cupones as $cupon)
                                <tr>
                                    <td>
                                        <strong class="text-primary">{{ $cupon->codigo }}</strong>
                                    </td>
                                    <td>{{ $cupon->nombre }}</td>
                                    <td>
                                        @if($cupon->tipo_descuento === 'porcentaje')
                                            <span class="badge bg-info">{{ $cupon->valor_descuento }}%</span>
                                        @else
                                            <span class="badge bg-success">{{ number_format($cupon->valor_descuento, 2) }} €</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $cupon->usos_actuales }}</span>
                                        @if($cupon->usos_maximos)
                                            / {{ $cupon->usos_maximos }}
                                        @else
                                            / ∞
                                        @endif
                                    </td>
                                    <td>
                                        @if($cupon->fecha_inicio || $cupon->fecha_fin)
                                            <small class="text-muted">
                                                {{ $cupon->fecha_inicio ? $cupon->fecha_inicio->format('d/m/Y') : '∞' }}
                                                -
                                                {{ $cupon->fecha_fin ? $cupon->fecha_fin->format('d/m/Y') : '∞' }}
                                            </small>
                                        @else
                                            <small class="text-muted">Sin límite</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($cupon->activo)
                                            @if($cupon->es_vigente && $cupon->tiene_usos_disponibles)
                                                <span class="badge bg-success">Activo</span>
                                            @elseif(!$cupon->es_vigente)
                                                <span class="badge bg-warning">Expirado</span>
                                            @else
                                                <span class="badge bg-secondary">Sin usos</span>
                                            @endif
                                        @else
                                            <span class="badge bg-danger">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.cupones.show', $cupon) }}" class="btn btn-outline-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.cupones.edit', $cupon) }}" class="btn btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.cupones.toggle-activo', $cupon) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-{{ $cupon->activo ? 'secondary' : 'success' }}" title="{{ $cupon->activo ? 'Desactivar' : 'Activar' }}">
                                                    <i class="fas fa-{{ $cupon->activo ? 'ban' : 'check' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.cupones.destroy', $cupon) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este cupón?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Eliminar">
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

                <div class="mt-3">
                    {{ $cupones->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay cupones registrados.</p>
                    <a href="{{ route('admin.cupones.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Crear primer cupón
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
