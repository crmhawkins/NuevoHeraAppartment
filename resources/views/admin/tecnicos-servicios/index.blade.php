@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-user-cog me-2 text-primary"></i>
                Técnicos y Servicios
            </h1>
            <p class="text-muted mb-0">Asigna servicios y establece precios para cada técnico</p>
        </div>
    </div>

    <hr class="mb-4">

    @if(session('swal_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('swal_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($tecnicos->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No hay técnicos registrados. <a href="{{ route('configuracion.reparaciones.index') }}">Crea uno primero</a>
        </div>
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Técnico</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Servicios Asignados</th>
                                <th style="width: 150px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tecnicos as $tecnico)
                                <tr>
                                    <td>
                                        <strong>{{ $tecnico->nombre }}</strong>
                                        @if(!$tecnico->activo)
                                            <span class="badge bg-secondary ms-2">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>{{ $tecnico->telefono ?: '—' }}</td>
                                    <td>{{ $tecnico->email ?: '—' }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $tecnico->servicios_count }} servicios</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.tecnicos-servicios.show', $tecnico->id) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-cog me-1"></i>
                                            Gestionar Servicios
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

