@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header con navegación -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="d-flex align-items-center mb-2">
                        <a href="{{ route('admin.checklists.index') }}" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left me-2"></i>Volver a Checklists
                        </a>
                        <h1 class="h2 mb-0 text-dark fw-bold">
                            <i class="fas fa-list-check me-2 text-primary"></i>
                            Items del Checklist
                        </h1>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-clipboard-check text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-semibold text-dark">{{ $checklist->nombre }}</h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-building me-1"></i>
                                {{ $checklist->edificio->nombre }}
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.itemsChecklist.create', ['id' => $checklist->id]) }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Item
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.itemsChecklist.index') }}" method="GET" class="row g-3">
                <input type="hidden" name="id" value="{{ $checklist->id }}">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0 ps-0" 
                               name="search" 
                               placeholder="Buscar items del checklist..." 
                               value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        @if($search)
                            <a href="{{ route('admin.itemsChecklist.index', ['id' => $checklist->id]) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de items -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Items ({{ $items->total() }})
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="fas fa-check-square me-1"></i>
                        {{ $items->count() }} mostrados
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-info" id="toggle-order-mode">
                        <i class="fas fa-sort me-2"></i>Ordenar
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            @if($items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="items-table">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 py-3 px-4" style="width: 60px;">
                                    <a href="{{ route('admin.itemsChecklist.index', ['id' => $checklist->id, 'sort' => 'orden', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <span class="fw-semibold">#</span>
                                        @if(request('sort') == 'orden')
                                            <i class="fas fa-sort-{{ request('order', 'asc') == 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                        @else
                                            <i class="fas fa-sort ms-2 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <a href="{{ route('admin.itemsChecklist.index', ['id' => $checklist->id, 'sort' => 'nombre', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <span class="fw-semibold">Nombre</span>
                                        @if(request('sort') == 'nombre')
                                            <i class="fas fa-sort-{{ request('order', 'asc') == 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                        @else
                                            <i class="fas fa-sort ms-2 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Tipo</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Estado</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Obligatorio</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Artículo Asociado</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Cantidad Requerida</span>
                                </th>
                                <th class="border-0 py-3 px-4 text-center" style="width: 200px;">
                                    <span class="fw-semibold">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="sortable-items">
                            @foreach ($items as $item)
                                <tr class="align-middle" data-id="{{ $item->id }}">
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-light text-dark fw-semibold me-2">{{ $item->orden ?? $loop->iteration }}</span>
                                            <i class="fas fa-grip-vertical text-muted drag-handle" style="cursor: move;"></i>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-check-square text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">{{ $item->nombre }}</h6>
                                                @if($item->descripcion)
                                                    <small class="text-muted">{{ Str::limit($item->descripcion, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $tipoColors = [
                                                'simple' => 'success',
                                                'multiple' => 'warning',
                                                'texto' => 'info',
                                                'foto' => 'danger'
                                            ];
                                            $tipoColor = $tipoColors[$item->tipo ?? 'simple'] ?? 'secondary';
                                            $tipoIcon = [
                                                'simple' => 'check-square',
                                                'multiple' => 'list-check',
                                                'texto' => 'font',
                                                'foto' => 'camera'
                                            ];
                                            $tipoIconName = $tipoIcon[$item->tipo ?? 'simple'] ?? 'question';
                                        @endphp
                                        <span class="badge bg-{{ $tipoColor }}-subtle text-{{ $tipoColor }}">
                                            <i class="fas fa-{{ $tipoIconName }} me-1"></i>
                                            {{ ucfirst($item->tipo ?? 'simple') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->activo ?? true)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Activo
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="fas fa-pause-circle me-1"></i>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->obligatorio ?? false)
                                            <span class="badge bg-danger-subtle text-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Obligatorio
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="fas fa-minus-circle me-1"></i>
                                                Opcional
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->tiene_stock && $item->articulo)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="fas fa-box text-success"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-semibold text-success">{{ $item->articulo->nombre }}</span>
                                                    <br>
                                                    <small class="text-muted">Stock: {{ $item->articulo->stock_actual }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="fas fa-minus-circle me-1"></i>
                                                Sin artículo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->tiene_stock && $item->cantidad_requerida)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="fas fa-hashtag text-info"></i>
                                                </div>
                                                <span class="fw-semibold text-info">{{ $item->cantidad_requerida }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.itemsChecklist.show', $item->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver item">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.itemsChecklist.edit', $item->id) }}" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               title="Editar item">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.itemsChecklist.toggle-status', $item->id) }}" 
                                                  method="POST" 
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-{{ $item->activo ?? true ? 'warning' : 'success' }}" 
                                                        title="{{ $item->activo ?? true ? 'Desactivar' : 'Activar' }}">
                                                    <i class="fas fa-{{ $item->activo ?? true ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->nombre }}"
                                                    title="Eliminar item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Estado vacío -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-list-check fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted mb-2">No hay items</h5>
                    <p class="text-muted mb-3">
                        @if($search)
                            No hay resultados para "{{ $search }}". Intenta con otros términos.
                        @else
                            Este checklist aún no tiene items registrados.
                        @endif
                    </p>
                    @if(!$search)
                        <a href="{{ route('admin.itemsChecklist.create', ['id' => $checklist->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Primer Item
                        </a>
                    @endif
                </div>
            @endif
        </div>
        
        @if($items->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="text-muted mb-0">
                        Mostrando {{ $items->firstItem() }} a {{ $items->lastItem() }} de {{ $items->total() }} resultados
                    </p>
                    {{ $items->appends(['id' => $checklist->id, 'search' => $search, 'sort' => $sort, 'order' => $order])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Formulario oculto para eliminar -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
console.log('🚀 SCRIPT INICIADO - Checklist Items');

document.addEventListener('DOMContentLoaded', function () {
    console.log('📋 DOM CARGADO - Buscando botones de eliminar...');
    
    // Buscar botones de eliminar con múltiples selectores
    const deleteButtons = document.querySelectorAll('.delete-btn, .btn-outline-danger[data-id]');
    console.log('🔍 Botones encontrados:', deleteButtons.length);
    console.log('📝 Botones:', deleteButtons);
    
    if (deleteButtons.length === 0) {
        console.error('❌ NO SE ENCONTRARON BOTONES DE ELIMINAR');
        // Buscar todos los botones rojos para debug
        const allRedButtons = document.querySelectorAll('.btn-danger, .btn-outline-danger');
        console.log('🔴 Todos los botones rojos encontrados:', allRedButtons.length);
        allRedButtons.forEach((btn, index) => {
            console.log(`Botón ${index}:`, btn, 'Classes:', btn.className, 'Data-id:', btn.dataset.id);
        });
    }
    
    // Event delegation - capturar TODOS los clicks
    document.addEventListener('click', function(e) {
        console.log('👆 CLICK DETECTADO en:', e.target);
        console.log('📍 Clases del elemento:', e.target.className);
        console.log('🏷️ Dataset:', e.target.dataset);
        
        // Verificar si es un botón de eliminar
        const isDeleteBtn = e.target.classList.contains('delete-btn') || 
                           e.target.classList.contains('btn-outline-danger') ||
                           e.target.closest('.delete-btn') ||
                           e.target.closest('.btn-outline-danger[data-id]');
        
        if (isDeleteBtn) {
            console.log('🗑️ BOTÓN DE ELIMINAR DETECTADO!');
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.classList.contains('delete-btn') || e.target.classList.contains('btn-outline-danger') 
                          ? e.target 
                          : e.target.closest('.delete-btn, .btn-outline-danger[data-id]');
            
            const itemId = button.dataset.id;
            const itemName = button.dataset.name || 'Item sin nombre';
            
            console.log('📊 Datos del item:', { itemId, itemName });
            
            if (!itemId) {
                console.error('❌ No se encontró ID del item');
                alert('Error: No se puede eliminar el item (ID no encontrado)');
                return;
            }
            
            console.log('🎯 Mostrando SweetAlert...');
            
            Swal.fire({
                title: '⚠️ Eliminar Item',
                text: `¿Estás seguro de eliminar "${itemName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                console.log('📋 Resultado SweetAlert:', result);
                if (result.isConfirmed) {
                    console.log('✅ Usuario confirmó eliminación');
                    
                    // Crear formulario dinámicamente si no existe
                    let form = document.getElementById('delete-form');
                    if (!form) {
                        console.log('📝 Creando formulario de eliminación...');
                        form = document.createElement('form');
                        form.id = 'delete-form';
                        form.method = 'POST';
                        form.style.display = 'none';
                        
                        // CSRF Token
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        form.appendChild(csrfInput);
                        
                        // Method DELETE
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        form.appendChild(methodInput);
                        
                        document.body.appendChild(form);
                    }
                    
                    const deleteUrl = `{{ route('admin.itemsChecklist.destroy', '') }}/${itemId}`;
                    console.log('🔗 URL de eliminación:', deleteUrl);
                    
                    form.action = deleteUrl;
                    console.log('📤 Enviando formulario...');
                    form.submit();
                }
            });
        }
    });
    
    // También agregar event listeners directos como backup
    deleteButtons.forEach((button, index) => {
        console.log(`🔗 Agregando listener al botón ${index}:`, button);
        button.addEventListener('click', function(e) {
            console.log('🎯 LISTENER DIRECTO ACTIVADO');
            // El event delegation ya maneja esto, pero por si acaso
        });
    });

    // Variables para el modo ordenación
    let isOrderMode = false;
    let sortable = null;
    
    // Toggle modo ordenación
    const toggleOrderBtn = document.getElementById('toggle-order-mode');
    const sortableItems = document.getElementById('sortable-items');
    const dragHandles = document.querySelectorAll('.drag-handle');

    if (toggleOrderBtn) {
        toggleOrderBtn.addEventListener('click', function() {
            isOrderMode = !isOrderMode;
        
        if (isOrderMode) {
            // Activar modo ordenación
            this.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Orden';
            this.classList.remove('btn-outline-info');
            this.classList.add('btn-success');
            
            // Mostrar handles de arrastre
            dragHandles.forEach(handle => handle.style.display = 'block');
            
            // Inicializar Sortable
            if (!sortable) {
                sortable = Sortable.create(sortableItems, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag'
                });
            }
        } else {
            // Desactivar modo ordenación
            this.innerHTML = '<i class="fas fa-sort me-2"></i>Ordenar';
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-info');
            
            // Ocultar handles de arrastre
            dragHandles.forEach(handle => handle.style.display = 'none');
            
            // Guardar orden
            saveOrder();
        }
        });
    }

    // Función para guardar el orden
    function saveOrder() {
        const items = Array.from(sortableItems.querySelectorAll('tr')).map((row, index) => ({
            id: row.dataset.id,
            orden: index + 1
        }));

        fetch('{{ route('admin.itemsChecklist.reorder') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ items: items })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Orden actualizado!',
                    text: data.message,
                    timer: 2000,
                    timerProgressBar: true,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
                
                // Actualizar números de orden en la tabla
                items.forEach((item, index) => {
                    const row = sortableItems.querySelector(`tr[data-id="${item.id}"]`);
                    if (row) {
                        const badge = row.querySelector('.badge');
                        if (badge) {
                            badge.textContent = item.orden;
                        }
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo actualizar el orden',
                confirmButtonColor: '#dc3545'
            });
        });
    }

    // Ocultar handles de arrastre por defecto
    dragHandles.forEach(handle => handle.style.display = 'none');

    // Mostrar mensajes de SweetAlert
    @if(session('swal_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('swal_success') }}',
            timer: 3000,
            timerProgressBar: true,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    @endif

    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('swal_error') }}',
            timer: 5000,
            timerProgressBar: true,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    @endif
});
</script>

<style>
.sortable-ghost {
    opacity: 0.5;
    background: #f8f9fa;
}

.sortable-chosen {
    background: #e3f2fd;
}

.sortable-drag {
    background: #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.drag-handle {
    opacity: 0.6;
    transition: opacity 0.2s;
}

.drag-handle:hover {
    opacity: 1;
}
</style>
@endsection

