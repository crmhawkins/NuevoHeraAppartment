@extends('layouts.appAdmin')

@section('title', $titulo)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold">
                            @if($tipo === 'whatsapp')
                                <i class="fab fa-whatsapp text-success me-2"></i>
                            @else
                                <i class="fas fa-comments text-primary me-2"></i>
                            @endif
                            {{ $titulo }}
                        </h4>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                    <p class="text-muted mb-0 mt-2">{{ $descripcion }}</p>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <p class="mb-0"><i class="fas fa-exclamation-circle me-1"></i>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('admin.prompt.update', $tipo) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-robot me-1"></i>Instrucciones para la IA
                            </label>
                            <textarea
                                name="prompt"
                                class="form-control"
                                rows="20"
                                style="font-family: monospace; font-size: 13px; line-height: 1.6;"
                                placeholder="Escribe aqui las instrucciones que la IA seguira al responder mensajes..."
                            >{{ old('prompt', $prompt->prompt ?? '') }}</textarea>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-info-circle me-1"></i>
                                Este texto se envia como "system prompt" a la IA en cada conversacion.
                                Define el tono, las reglas, la informacion que debe dar, y lo que NO debe hacer.
                            </small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                @if($prompt && $prompt->updated_at)
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Ultima actualizacion: {{ $prompt->updated_at->format('d/m/Y H:i') }}
                                    </small>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Guardar Prompt
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb text-warning me-2"></i>Consejos para un buen prompt</h6>
                    <ul class="mb-0" style="font-size: 14px;">
                        <li>Define el <strong>nombre</strong> del asistente (ej: "Eres Maria, asistente de Apartamentos Hawkins")</li>
                        <li>Especifica el <strong>tono</strong>: formal, cercano, profesional</li>
                        <li>Indica que <strong>idioma</strong> debe usar (o que detecte el idioma del huesped)</li>
                        <li>Lista la <strong>informacion</strong> que debe dar: direccion, horarios, servicios, normas</li>
                        <li>Define lo que <strong>NO debe hacer</strong>: no inventar precios, no confirmar disponibilidad sin verificar</li>
                        <li>Añade <strong>ejemplos</strong> de preguntas frecuentes y sus respuestas ideales</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
