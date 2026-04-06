@extends('admin.configuraciones.layout')

@section('config-title', 'Portal Público')
@section('config-subtitle', 'Configura la información del host que se mostrará en el portal público de reservas')

@section('config-content')
<div class="config-card">
    <div class="config-card-header">
        <h5>
            <i class="fas fa-globe"></i>
            Información del Host - Portal Público
        </h5>
    </div>
    <div class="config-card-body">
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error de validación:</strong> Por favor, corrige los siguientes errores:
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('configuracion.portal-publico.update') }}" method="POST" id="formPortalPublico">
            @csrf
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-building"></i>
                        Nombre de la Empresa/Host
                    </label>
                    <input type="text" 
                           class="form-control @error('host_nombre') is-invalid @enderror" 
                           name="host_nombre" 
                           value="{{ old('host_nombre', \App\Models\Setting::get('host_nombre', 'Apartamentos Algeciras')) }}"
                           placeholder="Ej: Apartamentos Algeciras">
                    @error('host_nombre')
                        <div class="invalid-feedback">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                    <small class="form-text text-muted">Nombre que aparece en la sección "Gestionado por"</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-font"></i>
                        Iniciales del Logo
                    </label>
                    <input type="text" 
                           class="form-control @error('host_iniciales') is-invalid @enderror" 
                           name="host_iniciales" 
                           value="{{ old('host_iniciales', \App\Models\Setting::get('host_iniciales', 'HA')) }}"
                           placeholder="Ej: HA"
                           maxlength="4">
                    @error('host_iniciales')
                        <div class="invalid-feedback">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                    <small class="form-text text-muted">Letras que aparecen en el círculo azul del logo (máx. 4 caracteres)</small>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-align-left"></i>
                    Descripción
                </label>
                <textarea class="form-control @error('host_descripcion') is-invalid @enderror" 
                          id="host_descripcion"
                          name="host_descripcion" 
                          rows="8"
                          placeholder="Ej: Alojamientos de calidad en el corazón de Algeciras">{{ old('host_descripcion', \App\Models\Setting::get('host_descripcion', 'Alojamientos de calidad en el corazón de Algeciras')) }}</textarea>
                @error('host_descripcion')
                    <div class="invalid-feedback d-block">
                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
                <small class="form-text text-muted">Usa el editor para formatear el texto, añadir enlaces, encabezados, etc. (máx. 5000 caracteres)</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-language"></i>
                    Idiomas que se Hablan
                </label>
                <div class="row">
                    @php
                        $idiomasDisponibles = ['Español', 'Inglés', 'Francés', 'Alemán', 'Italiano', 'Portugués'];
                        $idiomasSeleccionados = json_decode(\App\Models\Setting::get('host_idiomas', '["Español", "Inglés"]'), true) ?: ['Español', 'Inglés'];
                    @endphp
                    @foreach($idiomasDisponibles as $idioma)
                        <div class="col-md-4 col-lg-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="host_idiomas[]" 
                                       value="{{ $idioma }}"
                                       id="idioma_{{ strtolower(str_replace('é', 'e', $idioma)) }}"
                                       {{ in_array($idioma, $idiomasSeleccionados) ? 'checked' : '' }}>
                                <label class="form-check-label" for="idioma_{{ strtolower(str_replace('é', 'e', $idioma)) }}">
                                    {{ $idioma }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <small class="form-text text-muted">Selecciona los idiomas que se mostrarán en la sección "Idiomas que habla"</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-star"></i>
                    Puntuación de los Comentarios (Opcional)
                </label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="number" 
                               class="form-control @error('host_rating') is-invalid @enderror" 
                               name="host_rating" 
                               value="{{ old('host_rating', \App\Models\Setting::get('host_rating', '')) }}"
                               step="0.1"
                               min="0"
                               max="10"
                               placeholder="Ej: 7.4">
                        @error('host_rating')
                            <div class="invalid-feedback">
                                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                        <small class="form-text text-muted">Puntuación promedio (0-10)</small>
                    </div>
                    <div class="col-md-6">
                        <input type="number" 
                               class="form-control @error('host_reviews_count') is-invalid @enderror" 
                               name="host_reviews_count" 
                               value="{{ old('host_reviews_count', \App\Models\Setting::get('host_reviews_count', '')) }}"
                               min="0"
                               placeholder="Ej: 1441">
                        @error('host_reviews_count')
                            <div class="invalid-feedback">
                                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                        <small class="form-text text-muted">Número total de comentarios</small>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-home"></i>
                    Alojamientos Gestionados (Opcional)
                </label>
                <input type="number" 
                       class="form-control @error('host_alojamientos_count') is-invalid @enderror" 
                       name="host_alojamientos_count" 
                       value="{{ old('host_alojamientos_count', \App\Models\Setting::get('host_alojamientos_count', '')) }}"
                       min="0"
                       placeholder="Ej: 20">
                @error('host_alojamientos_count')
                    <div class="invalid-feedback">
                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
                <small class="form-text text-muted">Número de alojamientos gestionados</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Guardar Configuración
                </button>
                <a href="{{ route('configuracion.portal-publico.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<!-- Summernote Editor (GRATUITO - Sin API key) -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-es-ES.min.js"></script>
<script>
    // Inicializar Summernote (100% GRATUITO - Sin API key)
    $(document).ready(function() {
        $('#host_descripcion').summernote({
            height: 400,
            lang: 'es-ES',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            fontNames: ['Arial', 'Helvetica', 'Courier New', 'Times New Roman', 'Nunito'],
            fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '36', '48'],
            styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
            callbacks: {
                onChange: function(contents, $editable) {
                    // Validar longitud máxima
                    const content = $('#host_descripcion').summernote('code');
                    const textLength = content.replace(/<[^>]*>/g, '').length;
                    if (textLength > 5000) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Texto demasiado largo',
                            text: 'La descripción no puede exceder 5000 caracteres. Por favor, reduce el contenido.',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection

