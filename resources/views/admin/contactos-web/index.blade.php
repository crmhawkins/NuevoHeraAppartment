@extends('layouts.appAdmin')

@section('title', 'Contactos desde la Web')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-envelope me-2"></i>Contactos desde la Web
        </h1>
        <div>
            @if($noLeidos > 0)
                <span class="badge bg-danger me-2">{{ $noLeidos }} sin leer</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Tarjeta Principal -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">
                <i class="fas fa-list text-primary me-2"></i>
                Lista de Contactos ({{ $contactos->total() }})
            </h5>
        </div>
        <div class="card-body p-0">
            @if($contactos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="border-0">
                                    <i class="fas fa-hashtag text-primary me-1"></i>ID
                                </th>
                                <th scope="col" class="border-0">
                                    <i class="fas fa-user text-primary me-1"></i>Nombre
                                </th>
                                <th scope="col" class="border-0">
                                    <i class="fas fa-envelope text-primary me-1"></i>Email
                                </th>
                                <th scope="col" class="border-0">
                                    <i class="fas fa-tag text-primary me-1"></i>Asunto
                                </th>
                                <th scope="col" class="border-0">
                                    <i class="fas fa-calendar text-primary me-1"></i>Fecha
                                </th>
                                <th scope="col" class="border-0">
                                    <i class="fas fa-eye text-primary me-1"></i>Estado
                                </th>
                                <th scope="col" class="border-0">
                                    <i class="fas fa-cogs text-primary me-1"></i>Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contactos as $contacto)
                                <tr class="{{ !$contacto->leido ? 'table-warning' : '' }}">
                                    <td>{{ $contacto->id }}</td>
                                    <td>
                                        <strong>{{ $contacto->nombre }}</strong>
                                        @if(!$contacto->leido)
                                            <span class="badge bg-danger ms-2">Nuevo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="mailto:{{ $contacto->email }}">{{ $contacto->email }}</a>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $contacto->asunto }}">
                                            {{ $contacto->asunto }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $contacto->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($contacto->leido)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Leído
                                            </span>
                                            @if($contacto->leidoPor)
                                                <br><small class="text-muted">por {{ $contacto->leidoPor->name }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-envelope"></i> Sin leer
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.contactos-web.show', $contacto->id) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('admin.contactos-web.toggle-leido', $contacto->id) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-{{ $contacto->leido ? 'warning' : 'success' }}" 
                                                        title="{{ $contacto->leido ? 'Marcar como no leído' : 'Marcar como leído' }}">
                                                    <i class="fas fa-{{ $contacto->leido ? 'envelope' : 'check' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.contactos-web.destroy', $contacto->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este contacto?');">
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
                
                <!-- Paginación -->
                <div class="card-footer bg-white">
                    {{ $contactos->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay contactos registrados.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

