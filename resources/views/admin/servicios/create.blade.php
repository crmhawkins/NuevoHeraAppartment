@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-plus me-2 text-primary"></i>
                Nuevo Servicio
            </h1>
            <p class="text-muted mb-0">Crea un nuevo servicio que se podrá asignar a los apartamentos</p>
        </div>
        <a href="{{ route('admin.servicios.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.servicios.store') }}" method="POST">
                        @csrf

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
                                       value="{{ old('icono') }}"
                                       placeholder="Selecciona un icono o déjalo vacío"
                                       readonly>
                                <button type="button" 
                                        class="btn btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#iconPickerModal">
                                    <i class="fas fa-icons me-2"></i>Seleccionar Icono
                                </button>
                                @if(old('icono'))
                                    <button type="button" class="btn btn-outline-secondary" id="previewIcono" title="Vista previa">
                                        {!! old('icono') !!}
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
                            <label for="nombre" class="form-label fw-semibold">
                                <i class="fas fa-tag me-1 text-primary"></i>
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="{{ old('nombre') }}"
                                   placeholder="Ej: Wifi gratis, Aire acondicionado, Cocina"
                                   required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="descripcion" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>
                                Descripción
                            </label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="4"
                                      placeholder="Descripción opcional del servicio">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="precio" class="form-label fw-semibold">
                                    <i class="fas fa-euro-sign me-1 text-primary"></i>
                                    Precio (€)
                                </label>
                                <input type="number" 
                                       class="form-control @error('precio') is-invalid @enderror" 
                                       id="precio" 
                                       name="precio" 
                                       value="{{ old('precio') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                                <small class="form-text text-muted">
                                    Precio del servicio extra. Si tiene precio, aparecerá como servicio comprable.
                                </small>
                                @error('precio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="imagen" class="form-label fw-semibold">
                                    <i class="fas fa-image me-1 text-primary"></i>
                                    Imagen
                                </label>
                                <input type="text" 
                                       class="form-control @error('imagen') is-invalid @enderror" 
                                       id="imagen" 
                                       name="imagen" 
                                       value="{{ old('imagen') }}"
                                       placeholder="check-in.jpeg, check-out.jpeg, etc.">
                                <small class="form-text text-muted">
                                    Nombre del archivo de imagen en la carpeta public (ej: check-in.jpeg, mascotas-perro.png).
                                </small>
                                @if(old('imagen'))
                                    <div class="mt-2">
                                        <img src="{{ asset(old('imagen')) }}" alt="Vista previa" style="max-width: 200px; height: auto; border-radius: 4px;">
                                    </div>
                                @endif
                                @error('imagen')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="categoria" class="form-label fw-semibold">
                                <i class="fas fa-folder me-1 text-primary"></i>
                                Categoría
                            </label>
                            <input type="text" 
                                   class="form-control @error('categoria') is-invalid @enderror" 
                                   id="categoria" 
                                   name="categoria" 
                                   value="{{ old('categoria') }}"
                                   list="categoriasList"
                                   placeholder="Ej: Internet, Cocina, Baño, Aparcamiento, Varios">
                            <datalist id="categoriasList">
                                <option value="Servicios más populares">
                                <option value="Aparcamiento">
                                <option value="Internet">
                                <option value="Cocina">
                                <option value="Baño">
                                <option value="Instalaciones de la habitación">
                                <option value="Servicios de recepción">
                                <option value="Varios">
                                <option value="Idiomas que se hablan">
                            </datalist>
                            <small class="form-text text-muted">
                                Categoría del servicio (aparecerá agrupado en la vista).
                            </small>
                            @error('categoria')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label for="orden" class="form-label fw-semibold">
                                    <i class="fas fa-sort-numeric-down me-1 text-primary"></i>
                                    Orden
                                </label>
                                <input type="number" 
                                       class="form-control @error('orden') is-invalid @enderror" 
                                       id="orden" 
                                       name="orden" 
                                       value="{{ old('orden', 0) }}"
                                       min="0">
                                <small class="form-text text-muted">
                                    Menor número = aparece primero.
                                </small>
                                @error('orden')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-star me-1 text-primary"></i>
                                    Servicio Popular
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="es_popular" 
                                           name="es_popular" 
                                           {{ old('es_popular') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="es_popular">
                                        Marcar como popular
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Aparecerá en la sección "Servicios más populares".
                                </small>
                            </div>

                            <div class="col-md-4 mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-toggle-on me-1 text-primary"></i>
                                    Estado
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="activo" 
                                           name="activo" 
                                           {{ old('activo', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="activo">
                                        Servicio activo
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Solo los servicios activos se mostrarán.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.servicios.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Guardar Servicio
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
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información
                    </h5>
                    <hr>
                    <p class="card-text">
                        <strong>¿Qué son los servicios?</strong>
                    </p>
                    <p class="small text-muted">
                        Los servicios son características y comodidades que tiene un apartamento (Wifi, Aire acondicionado, Cocina, etc.). 
                        Puedes crear servicios globales y luego asignarlos a cada apartamento según corresponda.
                    </p>
                    <hr>
                    <p class="card-text">
                        <strong>Ejemplos de servicios:</strong>
                    </p>
                    <ul class="small text-muted">
                        <li>Wifi gratis</li>
                        <li>Aire acondicionado</li>
                        <li>Calefacción</li>
                        <li>Cocina</li>
                        <li>TV</li>
                        <li>Parking</li>
                        <li>Ascensor</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Selector de Iconos (igual que en normas) -->
@include('admin.servicios.partials.icon-picker-modal')

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
<script>
    // Iconos Font Awesome más comunes para servicios
    const iconosServicios = [
        { icon: 'fa-wifi', name: 'WiFi' },
        { icon: 'fa-snowflake', name: 'Aire acondicionado' },
        { icon: 'fa-fire', name: 'Calefacción' },
        { icon: 'fa-utensils', name: 'Cocina' },
        { icon: 'fa-tv', name: 'TV' },
        { icon: 'fa-car', name: 'Parking' },
        { icon: 'fa-swimming-pool', name: 'Piscina' },
        { icon: 'fa-elevator', name: 'Ascensor' },
        { icon: 'fa-bed', name: 'Cama' },
        { icon: 'fa-bath', name: 'Baño' },
        { icon: 'fa-shower', name: 'Ducha' },
        { icon: 'fa-hot-tub', name: 'Jacuzzi' },
        { icon: 'fa-gym', name: 'Gimnasio' },
        { icon: 'fa-spa', name: 'Spa' },
        { icon: 'fa-dumbbell', name: 'Fitness' },
        { icon: 'fa-bicycle', name: 'Bicicleta' },
        { icon: 'fa-wind', name: 'Ventilador' },
        { icon: 'fa-soap', name: 'Lavadora' },
        { icon: 'fa-dryer', name: 'Secadora' },
        { icon: 'fa-thermometer-half', name: 'Temperatura' },
        { icon: 'fa-sun', name: 'Terraza' },
        { icon: 'fa-mountain', name: 'Vista' },
        { icon: 'fa-umbrella-beach', name: 'Playa' },
        { icon: 'fa-building', name: 'Edificio' },
        { icon: 'fa-key', name: 'Seguro' },
        { icon: 'fa-lock', name: 'Caja fuerte' },
        { icon: 'fa-smoking-ban', name: 'No fumar' },
        { icon: 'fa-paw', name: 'Mascotas' },
        { icon: 'fa-wheelchair', name: 'Accesible' },
        { icon: 'fa-baby', name: 'Niños' },
        { icon: 'fa-user-friends', name: 'Huéspedes' },
        { icon: 'fa-couch', name: 'Salón' },
        { icon: 'fa-door-open', name: 'Entrada' },
        { icon: 'fa-window-maximize', name: 'Ventana' }
    ];

    let selectedIcon = '';
    
    // Cargar iconos en el grid
    function loadIcons() {
        const grid = document.getElementById('iconGrid');
        if (!grid) return;
        grid.innerHTML = '';
        
        iconosServicios.forEach(icono => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 col-lg-2';
            
            const item = document.createElement('div');
            item.className = 'icon-item';
            item.dataset.icon = icono.icon;
            item.innerHTML = `
                <i class="fas ${icono.icon}"></i>
                <span>${icono.name}</span>
            `;
            
            item.addEventListener('click', function() {
                document.querySelectorAll('.icon-item').forEach(el => el.classList.remove('selected'));
                this.classList.add('selected');
                selectedIcon = `<i class="fas ${icono.icon}"></i>`;
            });
            
            col.appendChild(item);
            grid.appendChild(col);
        });
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
        } else {
            document.getElementById('icono').value = '';
            const previewBtn = document.getElementById('previewIcono');
            if (previewBtn) previewBtn.remove();
        }
    });
    
    // Cargar iconos cuando se abre el modal
    document.getElementById('iconPickerModal')?.addEventListener('show.bs.modal', function() {
        loadIcons();
        selectedIcon = document.getElementById('icono').value || '';
    });
</script>
@endpush
@endsection

