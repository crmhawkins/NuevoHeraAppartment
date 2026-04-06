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
                           placeholder="Buscar icono por nombre... (ej: wifi, car, tv)">
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




