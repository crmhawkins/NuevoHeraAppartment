@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-question-circle me-2 text-primary"></i>
                Preguntas Frecuentes - {{ $apartamento->titulo ?? $apartamento->nombre }}
            </h1>
            <p class="text-muted mb-0">Gestiona las preguntas frecuentes que se mostrarán en la página pública</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Volver al Apartamento
            </a>
            <a href="{{ route('admin.faq-apartamentos.create', $apartamento->id) }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Nueva Pregunta
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

    @if($faqs->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No hay preguntas frecuentes configuradas. <a href="{{ route('admin.faq-apartamentos.create', $apartamento->id) }}">Crea la primera</a>
        </div>
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Orden</th>
                                <th>Pregunta</th>
                                <th>Respuesta (preview)</th>
                                <th style="width: 100px;">Estado</th>
                                <th style="width: 150px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($faqs as $faq)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $faq->orden }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $faq->pregunta }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ Str::limit(strip_tags($faq->respuesta), 80) }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($faq->activo)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.faq-apartamentos.edit', [$apartamento->id, $faq->id]) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.faq-apartamentos.destroy', [$apartamento->id, $faq->id]) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar esta pregunta?');">
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
</div>
@endsection




