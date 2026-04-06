@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-edit me-2 text-primary"></i>
                Editar Norma de la Casa
            </h1>
            <p class="text-muted mb-0">Modifica la norma: {{ $norma->titulo }}</p>
        </div>
        <a href="{{ route('admin.normas-casa.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.normas-casa.update', $norma->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="icono" class="form-label fw-semibold">
                                <i class="fas fa-icons me-1 text-primary"></i>
                                Icono
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control @error('icono') is-invalid @enderror" 
                                       id="icono" 
                                       name="icono" 
                                       value="{{ old('icono', $norma->icono) }}"
                                       placeholder="Selecciona un icono o déjalo vacío"
                                       readonly>
                                <button type="button" 
                                        class="btn btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#iconPickerModal">
                                    <i class="fas fa-icons me-2"></i>Seleccionar Icono
                                </button>
                                @if(old('icono', $norma->icono))
                                    <button type="button" class="btn btn-outline-secondary" id="previewIcono" title="Vista previa">
                                        {!! old('icono', $norma->icono) !!}
                                    </button>
                                @endif
                            </div>
                            <small class="form-text text-muted">
                                Haz clic en "Seleccionar Icono" para elegir visualmente. O déjalo vacío si no quieres icono.
                            </small>
                            @error('icono')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="titulo" class="form-label fw-semibold">
                                <i class="fas fa-heading me-1 text-primary"></i>
                                Título <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('titulo') is-invalid @enderror" 
                                   id="titulo" 
                                   name="titulo" 
                                   value="{{ old('titulo', $norma->titulo) }}"
                                   placeholder="Ej: Entrada, Salida, Cancelación / prepago"
                                   required>
                            @error('titulo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="descripcion" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>
                                Descripción <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="12"
                                      required>{{ old('descripcion', $norma->descripcion) }}</textarea>
                            <small class="form-text text-muted">
                                Usa el editor para formatear el texto, añadir enlaces, encabezados, etc.
                            </small>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="orden" class="form-label fw-semibold">
                                    <i class="fas fa-sort-numeric-down me-1 text-primary"></i>
                                    Orden de visualización
                                </label>
                                <input type="number" 
                                       class="form-control @error('orden') is-invalid @enderror" 
                                       id="orden" 
                                       name="orden" 
                                       value="{{ old('orden', $norma->orden) }}"
                                       min="0">
                                <small class="form-text text-muted">
                                    Menor número = aparece primero. Usa 0, 10, 20, 30... para facilitar el ordenamiento.
                                </small>
                                @error('orden')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-toggle-on me-1 text-primary"></i>
                                    Estado
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="activo" 
                                           name="activo" 
                                           {{ old('activo', $norma->activo) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="activo">
                                        Norma activa
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Solo las normas activas se mostrarán en los apartamentos.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.normas-casa.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Actualizar Norma
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-eye text-primary me-2"></i>
                        Vista Previa
                    </h5>
                    <hr>
                    <div style="border-top: 1px solid #E0E0E0; padding-top: 12px;">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <div style="flex: 0 0 200px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                <span id="preview-icono">{!! old('icono', $norma->icono) !!}</span>
                                <span id="preview-titulo">{{ $norma->titulo }}</span>
                            </div>
                            <div style="flex: 1; color: #333;">
                                <span id="preview-descripcion">{!! old('descripcion', $norma->descripcion) !!}</span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        La vista previa se actualiza automáticamente al editar los campos.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-0 bg-light mt-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información
                    </h5>
                    <hr>
                    <p class="small text-muted">
                        <strong>Creada:</strong> {{ $norma->created_at->format('d/m/Y H:i') }}<br>
                        <strong>Última actualización:</strong> {{ $norma->updated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Selector de Iconos -->
<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-labelledby="iconPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iconPickerModalLabel">
                    <i class="fas fa-icons me-2"></i>Seleccionar Icono Font Awesome
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" 
                           class="form-control" 
                           id="iconSearch" 
                           placeholder="Buscar icono por nombre... (ej: home, user, calendar)">
                </div>
                
                <div class="row" id="iconGrid">
                    <!-- Los iconos se cargarán aquí dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmIcon" data-bs-dismiss="modal">Confirmar Selección</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.2/css/all.min.css">
<style>
    .icon-item {
        padding: 15px;
        text-align: center;
        cursor: pointer;
        border: 2px solid transparent;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin-bottom: 10px;
    }
    .icon-item:hover {
        background-color: #f8f9fa;
        border-color: #007bff;
        transform: scale(1.05);
    }
    .icon-item.selected {
        background-color: #e7f3ff;
        border-color: #007bff;
    }
    .icon-item i {
        font-size: 24px;
        color: #495057;
        margin-bottom: 8px;
    }
    .icon-item span {
        display: block;
        font-size: 11px;
        color: #6c757d;
        word-break: break-word;
    }
    #iconGrid {
        max-height: 500px;
        overflow-y: auto;
    }
</style>
@endpush

@push('scripts')
<!-- Summernote Editor (GRATUITO - Sin API key) -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-es-ES.min.js"></script>
<script>
    // Inicializar Summernote (100% GRATUITO - Sin API key)
    $(document).ready(function() {
        $('#descripcion').summernote({
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
                    // Actualizar vista previa
                    const content = $('#descripcion').summernote('code');
                    document.getElementById('preview-descripcion').innerHTML = content;
                }
            }
        });
    });

    // Iconos Font Awesome más comunes para normas de casa
    const iconosComunes = [
        { icon: 'fa-arrow-right', name: 'Entrada/Salida' },
        { icon: 'fa-info-circle', name: 'Información' },
        { icon: 'fa-child', name: 'Niños' },
        { icon: 'fa-lock', name: 'Seguridad' },
        { icon: 'fa-ban', name: 'Prohibido' },
        { icon: 'fa-calendar-check', name: 'Check-in/out' },
        { icon: 'fa-clock', name: 'Horario' },
        { icon: 'fa-bed', name: 'Camas' },
        { icon: 'fa-paw', name: 'Mascotas' },
        { icon: 'fa-smoking-ban', name: 'No fumar' },
        { icon: 'fa-volume-up', name: 'Ruido' },
        { icon: 'fa-users', name: 'Personas' },
        { icon: 'fa-money-bill', name: 'Precio' },
        { icon: 'fa-credit-card', name: 'Pago' },
        { icon: 'fa-key', name: 'Llaves' },
        { icon: 'fa-home', name: 'Casa' },
        { icon: 'fa-exclamation-triangle', name: 'Advertencia' },
        { icon: 'fa-check-circle', name: 'Permitido' },
        { icon: 'fa-times-circle', name: 'Prohibido' },
        { icon: 'fa-question-circle', name: 'Pregunta' },
        { icon: 'fa-wifi', name: 'WiFi' },
        { icon: 'fa-car', name: 'Parking' },
        { icon: 'fa-swimming-pool', name: 'Piscina' },
        { icon: 'fa-utensils', name: 'Cocina' },
        { icon: 'fa-tv', name: 'TV' },
        { icon: 'fa-snowflake', name: 'Aire acondicionado' },
        { icon: 'fa-fire', name: 'Calefacción' },
        { icon: 'fa-couch', name: 'Salón' },
        { icon: 'fa-door-open', name: 'Entrada' },
        { icon: 'fa-door-closed', name: 'Salida' }
    ];

    let selectedIcon = '';
    
    // Cargar iconos en el grid
    function loadIcons() {
        const grid = document.getElementById('iconGrid');
        grid.innerHTML = '';
        
        // Restaurar selección si existe
        const currentIcon = document.getElementById('icono').value;
        let currentIconClass = '';
        if (currentIcon) {
            const match = currentIcon.match(/fa-[\w-]+/);
            if (match) currentIconClass = match[0];
        }
        
        iconosComunes.forEach(icono => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 col-lg-2';
            
            const item = document.createElement('div');
            item.className = 'icon-item' + (currentIconClass === icono.icon ? ' selected' : '');
            item.dataset.icon = icono.icon;
            item.innerHTML = `
                <i class="fas ${icono.icon}"></i>
                <span>${icono.name}</span>
            `;
            
            item.addEventListener('click', function() {
                // Remover selección anterior
                document.querySelectorAll('.icon-item').forEach(el => el.classList.remove('selected'));
                // Añadir selección actual
                this.classList.add('selected');
                selectedIcon = `<i class="fas ${icono.icon}"></i>`;
            });
            
            col.appendChild(item);
            grid.appendChild(col);
        });
        
        if (currentIconClass) {
            selectedIcon = currentIcon;
        }
    }
    
    // Buscar iconos
    document.getElementById('iconSearch')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.icon-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            const parent = item.closest('.col-6');
            if (text.includes(searchTerm)) {
                parent.style.display = '';
            } else {
                parent.style.display = 'none';
            }
        });
    });
    
    // Confirmar selección de icono
    document.getElementById('confirmIcon')?.addEventListener('click', function() {
        if (selectedIcon) {
            document.getElementById('icono').value = selectedIcon;
            
            // Actualizar preview en el input
            let previewBtn = document.getElementById('previewIcono');
            if (!previewBtn) {
                const inputGroup = document.getElementById('icono').closest('.input-group');
                previewBtn = document.createElement('button');
                previewBtn.type = 'button';
                previewBtn.className = 'btn btn-outline-secondary';
                previewBtn.id = 'previewIcono';
                previewBtn.title = 'Vista previa';
                inputGroup.appendChild(previewBtn);
            }
            previewBtn.innerHTML = selectedIcon;
            
            // Actualizar vista previa
            document.getElementById('preview-icono').innerHTML = selectedIcon;
        } else {
            document.getElementById('icono').value = '';
            const previewBtn = document.getElementById('previewIcono');
            if (previewBtn) previewBtn.remove();
            document.getElementById('preview-icono').innerHTML = '';
        }
    });
    
    // Cargar iconos cuando se abre el modal
    document.getElementById('iconPickerModal')?.addEventListener('show.bs.modal', function() {
        loadIcons();
        selectedIcon = document.getElementById('icono').value || '';
    });
    
    // Actualizar vista previa en tiempo real
    document.getElementById('titulo')?.addEventListener('input', function() {
        document.getElementById('preview-titulo').textContent = this.value || 'Título';
    });
</script>
@endpush
@endsection
