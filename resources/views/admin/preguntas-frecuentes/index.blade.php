@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-question-circle me-2 text-primary"></i>
                Preguntas Frecuentes
            </h1>
            <p class="text-muted mb-0">Gestiona las preguntas frecuentes</p>
        </div>
        <a href="{{ route('admin.preguntas-frecuentes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Nueva Pregunta
        </a>
    </div>

    <hr class="mb-4">

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($preguntas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Pregunta</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preguntas as $pregunta)
                                <tr>
                                    <td>{{ $pregunta->orden }}</td>
                                    <td>
                                        <strong>{{ Str::limit($pregunta->pregunta, 80) }}</strong>
                                    </td>
                                    <td>
                                        @if($pregunta->categoria)
                                            <span class="badge bg-info">{{ $pregunta->categoria }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pregunta->activo)
                                            <span class="badge bg-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.preguntas-frecuentes.edit', $pregunta->id) }}" 
                                               class="btn btn-sm btn-warning" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.preguntas-frecuentes.destroy', $pregunta->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar esta pregunta?');">
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
                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay preguntas frecuentes creadas aún.</p>
                    <a href="{{ route('admin.preguntas-frecuentes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Crear Primera Pregunta
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

