@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-home me-2 text-primary"></i>
                Normas de la Casa
            </h1>
            <p class="text-muted mb-0">Gestiona las normas de la casa para todos los apartamentos</p>
        </div>
        <a href="{{ route('admin.normas-casa.create') }}" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>
            Nueva Norma
        </a>
    </div>

    <hr class="mb-4">

    @if(session('swal_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('swal_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($normas->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No hay normas de la casa configuradas. <a href="{{ route('admin.normas-casa.create') }}">Crea la primera</a>
        </div>
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Orden</th>
                                <th style="width: 80px;">Icono</th>
                                <th>Título</th>
                                <th>Descripción (preview)</th>
                                <th style="width: 100px;">Estado</th>
                                <th style="width: 150px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($normas as $norma)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $norma->orden }}</span>
                                    </td>
                                    <td>
                                        @if($norma->icono)
                                            <span style="font-size: 20px;">{!! $norma->icono !!}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $norma->titulo }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {!! \Illuminate\Support\Str::limit(strip_tags($norma->descripcion), 80) !!}
                                        </small>
                                    </td>
                                    <td>
                                        @if($norma->activo)
                                            <span class="badge bg-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.normas-casa.edit', $norma->id) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.normas-casa.destroy', $norma->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar esta norma?');">
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
    @endif
</div>
@endsection




