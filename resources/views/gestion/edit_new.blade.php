@extends('layouts.appPersonal')

@section('title')
    {{ __('Realizando el Apartamento - ') . $apartamentoLimpieza->apartamento->nombre }}
@endsection

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Bienvenid@ {{ Auth::user()->name }}</h5>
@endsection

@section('content')
<div class="container" style="padding-right: 2.5rem !important; padding-left: 2.5rem !important;">
    <h2 class="mb-3">{{ __('Editar Limpieza del Apartamento') }}</h2>
    <hr class="mb-2">

    <form id="limpieza-form" action="{{ route('gestion.update', $apartamentoLimpieza->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        @if($checklists->isNotEmpty())
            @foreach($checklists as $checklist)
                <h4 class="bg-color-tercero text-white p-2 mb-3 fw-bold">
                    <i class="fa-solid fa-spray-can-sparkles fs-5 me-2 fw-regular"></i> {{ $checklist->nombre }}
                </h4>

                <!-- Mostrar los items del checklist -->
                @foreach($checklist->items as $item)
                    <div class="form-check form-switch mt-1 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <input data-id="{{ $apartamentoLimpieza->id }}" style="margin-right: 0 !important" class="form-check-input" type="checkbox" id="item_{{ $item->id }}" name="item_{{ $item->id }}" {{ old('item_' . $item->id, $item->pivot->status ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_{{ $item->id }}" style="padding-left: 0.5rem !important;">{{ $item->nombre }}</label>
                        </div>
                        
                        <!-- Botones de acción para items con stock o averías -->
                        @if($item->tiene_stock || $item->tiene_averias)
                            <div class="btn-group btn-group-sm" role="group">
                                @if($item->tiene_stock)
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            onclick="abrirModalReponer({{ $item->id }}, {{ $apartamentoLimpieza->id }}, '{{ $apartamentoLimpieza->tipo_limpieza === 'zona_comun' ? 'zona_comun' : 'apartamento' }}')"
                                            title="Reponer stock">
                                        <i class="fas fa-plus-circle"></i> Reponer
                                    </button>
                                @endif
                                
                                @if($item->tiene_averias)
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            onclick="abrirModalAveria({{ $item->id }}, {{ $apartamentoLimpieza->id }}, '{{ $apartamentoLimpieza->tipo_limpieza === 'zona_comun' ? 'zona_comun' : 'apartamento' }}')"
                                            title="Reportar avería">
                                        <i class="fas fa-exclamation-triangle"></i> Avería
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <!-- Mostrar los requisitos de fotos para este checklist -->
                @if($checklist->photoRequirements->isNotEmpty())
                <br>
                    <h5 class="bg-danger text-white p-2 mb-3 fw-bold pulse-animation">
                        <i class="fa-solid fa-camera-retro me-2 fw-regular fs-5"></i> {{ __('Fotos Requeridas') }}
                    </h5>
                    @foreach($checklist->photoRequirements as $requirement)
                        <div class="mb-3">
                            <label class="fw-bold mb-2">{{ $requirement->nombre }} ({{ $requirement->cantidad }} fotos)</label>
                            <input type="file" name="photos[{{ $requirement->photo_categoria_id }}][]" multiple="multiple" class="form-control photo-upload" data-categoria-id="{{ $requirement->photo_categoria_id }}" data-requirement-id="{{ $requirement->id }}">
                            <div class="preview-container mt-2" id="preview-container-{{ $requirement->photo_categoria_id }}">
                                <!-- Mostrar las fotos ya subidas -->
                                @if(isset($uploadedPhotos[$requirement->photo_categoria_id]))
                                    @foreach($uploadedPhotos[$requirement->photo_categoria_id] as $photo)
                                        <img src="{{ asset($photo->url) }}" alt="{{ $photo->descripcion }}" class="img-thumbnail" style="max-width: 200px; margin: 5px;">
                                    @endforeach
                                @endif
                            </div> <!-- Contenedor para la vista previa -->
                            @if($requirement->descripcion)
                                <small class="form-text text-muted">{{ $requirement->descripcion }}</small>
                            @endif
                        </div>
                    @endforeach
                @endif
                <hr>
            @endforeach
        @else
            <p>{{ __('No hay checklists asociados a este edificio.') }}</p>
        @endif

        <button type="submit" class="btn btn-primary">{{ __('Guardar cambios') }}</button>
    </form>
</div>

<!-- CSS para la animación pulse -->
<style>
    @keyframes pulse {
        0% {
            box-shadow: 0 0 5px rgba(255, 0, 0, 0.4);
        }
        50% {
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.7);
        }
        100% {
            box-shadow: 0 0 5px rgba(255, 0, 0, 0.4);
        }
    }

    .pulse-animation {
        animation: pulse 2s infinite;
    }

    .preview-container img {
        max-width: 200px;
        margin: 5px;
        border: 2px solid #ddd;
        border-radius: 5px;
    }
</style>

<!-- Modal para Reponer Stock -->
<div class="modal fade" id="modalReponer" tabindex="-1" aria-labelledby="modalReponerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReponerLabel">
                    <i class="fas fa-plus-circle text-primary me-2"></i>Reponer Stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formReponer">
                    <input type="hidden" id="reponer_item_id" name="item_checklist_id">
                    <input type="hidden" id="reponer_limpieza_id" name="apartamento_limpieza_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Artículo:</label>
                        <p id="reponer_articulo_nombre" class="form-control-plaintext"></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Stock Actual:</label>
                                <p id="reponer_stock_actual" class="form-control-plaintext"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Stock Mínimo:</label>
                                <p id="reponer_stock_minimo" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cantidad a Reponer:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="reponer_cantidad" name="cantidad_reponer" 
                                   step="0.01" min="0.01" required>
                            <span class="input-group-text" id="reponer_unidad_medida"></span>
                        </div>
                        <div class="form-text">
                            <small id="reponer_tipo_descuento" class="text-muted"></small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reponer_observaciones" class="form-label">Observaciones (opcional):</label>
                        <textarea class="form-control" id="reponer_observaciones" name="observaciones" 
                                  rows="3" placeholder="Observaciones sobre la reposición..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarReponer()">
                    <i class="fas fa-check me-1"></i>Confirmar Reposición
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reportar Avería -->
<div class="modal fade" id="modalAveria" tabindex="-1" aria-labelledby="modalAveriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAveriaLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Reportar Avería
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAveria">
                    <input type="hidden" id="averia_item_id" name="item_checklist_id">
                    <input type="hidden" id="averia_limpieza_id" name="apartamento_limpieza_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item:</label>
                        <p id="averia_item_nombre" class="form-control-plaintext"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="averia_descripcion" class="form-label fw-bold">Descripción de la Avería:</label>
                        <textarea class="form-control" id="averia_descripcion" name="descripcion" 
                                  rows="4" required placeholder="Describe detalladamente la avería encontrada..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="averia_prioridad" class="form-label fw-bold">Prioridad:</label>
                        <select class="form-select" id="averia_prioridad" name="prioridad" required>
                            <option value="">Selecciona una prioridad</option>
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarAveria()">
                    <i class="fas fa-exclamation-triangle me-1"></i>Reportar Avería
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Variables globales para los modales
    let modalReponer, modalAveria;
    
    // Inicializar modales cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        modalReponer = new bootstrap.Modal(document.getElementById('modalReponer'));
        modalAveria = new bootstrap.Modal(document.getElementById('modalAveria'));
    });

    // Función para abrir modal de reponer stock
    function abrirModalReponer(itemId, limpiezaId, tipoItem = 'apartamento') {
        // Limpiar formulario
        document.getElementById('formReponer').reset();
        document.getElementById('reponer_item_id').value = itemId;
        document.getElementById('reponer_limpieza_id').value = limpiezaId;
        
        // Obtener información del item
        fetch(`{{ route('gestion.limpieza.item-info') }}?item_checklist_id=${itemId}&tipo_item=${tipoItem}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    document.getElementById('reponer_articulo_nombre').textContent = item.articulo ? item.articulo.nombre : 'Sin artículo asociado';
                    document.getElementById('reponer_stock_actual').textContent = item.articulo ? item.articulo.stock_actual : 'N/A';
                    document.getElementById('reponer_stock_minimo').textContent = item.articulo ? item.articulo.stock_minimo : 'N/A';
                    document.getElementById('reponer_unidad_medida').textContent = item.articulo ? item.articulo.unidad_medida : '';
                    document.getElementById('reponer_tipo_descuento').textContent = item.articulo ? item.articulo.descripcion_tipo_descuento : '';
                    document.getElementById('reponer_cantidad').value = item.cantidad_requerida || 1;
                    
                    modalReponer.show();
                } else {
                    Swal.fire('Error', 'No se pudo cargar la información del item', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar la información del item', 'error');
            });
    }

    // Función para abrir modal de avería
    function abrirModalAveria(itemId, limpiezaId, tipoItem = 'apartamento') {
        // Limpiar formulario
        document.getElementById('formAveria').reset();
        document.getElementById('averia_item_id').value = itemId;
        document.getElementById('averia_limpieza_id').value = limpiezaId;
        document.getElementById('averia_prioridad').value = 'media';
        
        // Obtener información del item
        fetch(`{{ route('gestion.limpieza.item-info') }}?item_checklist_id=${itemId}&tipo_item=${tipoItem}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    document.getElementById('averia_item_nombre').textContent = item.nombre;
                    modalAveria.show();
                } else {
                    Swal.fire('Error', 'No se pudo cargar la información del item', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar la información del item', 'error');
            });
    }

    // Función para confirmar reposición
    function confirmarReponer() {
        const formData = new FormData(document.getElementById('formReponer'));
        
        // Validar formulario
        if (!formData.get('cantidad_reponer') || parseFloat(formData.get('cantidad_reponer')) <= 0) {
            Swal.fire('Error', 'La cantidad debe ser mayor a 0', 'error');
            return;
        }

        // Mostrar confirmación
        Swal.fire({
            title: '¿Confirmar reposición?',
            text: `Se repondrán ${formData.get('cantidad_reponer')} unidades del artículo`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reponer',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar petición
                fetch('{{ route("gestion.limpieza.reponer-stock") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Éxito', data.message, 'success');
                        modalReponer.hide();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al procesar la reposición', 'error');
                });
            }
        });
    }

    // Función para confirmar avería
    function confirmarAveria() {
        const formData = new FormData(document.getElementById('formAveria'));
        
        // Validar formulario
        if (!formData.get('descripcion').trim()) {
            Swal.fire('Error', 'La descripción de la avería es obligatoria', 'error');
            return;
        }

        if (!formData.get('prioridad')) {
            Swal.fire('Error', 'Debe seleccionar una prioridad', 'error');
            return;
        }

        // Mostrar confirmación
        Swal.fire({
            title: '¿Reportar avería?',
            text: 'Se creará una incidencia que será notificada a los administradores',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, reportar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar petición
                fetch('{{ route("gestion.limpieza.reportar-averia") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Éxito', data.message, 'success');
                        modalAveria.hide();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al reportar la avería', 'error');
                });
            }
        });
    }

    // Mostrar vista previa de imágenes seleccionadas
    document.querySelectorAll('.photo-upload').forEach(input => {
    input.addEventListener('change', function(event) {
        const files = event.target.files;
        const categoriaId = event.target.getAttribute('data-categoria-id');
        const requirementId = event.target.getAttribute('data-requirement-id');  // Agregar esta línea
        const previewContainer = document.getElementById('preview-container-' + categoriaId);

        previewContainer.innerHTML = '';  // Limpiar vista previa

        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function(event) {
                const img = new Image();
                img.src = event.target.result;
                previewContainer.appendChild(img);

                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const maxWidth = 900;
                    const scaleSize = maxWidth / img.width;
                    canvas.width = maxWidth;
                    canvas.height = img.height * scaleSize;
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob(function(blob) {
                        const formData = new FormData();
                        formData.append('photo', blob, file.name);
                        formData.append('photo_categoria_id', categoriaId);
                        formData.append('requirement_id', requirementId);  // Usar el requirementId obtenido
                        formData.append('_token', '{{ csrf_token() }}');

                        fetch("{{ route('photo.upload', $apartamentoLimpieza->id) }}", {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Foto subida exitosamente:', data);
                        })
                        .catch(error => {
                            console.error('Error al subir la foto:', error);
                        });
                    }, 'image/jpeg');
                };
            };
        });
    });
});

</script>
@endsection
