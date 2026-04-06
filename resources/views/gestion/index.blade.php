@extends('layouts.appPersonal')

@section('bienvenido')
    {{-- <h5 class="navbar-brand mb-0 w-auto text-center">Bienvenid@ {{Auth::user()->name}}</h5> --}}
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/gestion-buttons.css') }}">
<style>
/* Estilos del Modal y otros elementos */

/* Modal de Amenities */
.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    color: #FFFFFF;
    border-bottom: none;
    padding: 24px;
}

.modal-title-content {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.title-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.title-icon i {
    font-size: 20px;
    color: #FFFFFF;
}

.title-text h5 {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
}

.title-text p {
    font-size: 14px;
    margin: 0;
    opacity: 0.8;
    font-weight: 400;
}

.btn-close {
    filter: invert(1);
    opacity: 0.8;
}

.btn-close:hover {
    opacity: 1;
}

.modal-body {
    padding: 32px;
    background: #FFFFFF;
}

/* Loading */
.amenities-loading {
    text-align: center;
    padding: 60px 20px;
    color: #6C6C70;
}

/* Amenities Completadas */
.amenities-completada {
    padding: 20px 0;
}

.completada-header {
    text-align: center;
    margin-bottom: 32px;
}

.completada-header h6 {
    font-size: 18px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0 0 8px 0;
}

.completada-header p {
    font-size: 14px;
    color: #6C6C70;
    margin: 0;
}

.completada-content {
    background: #F5F5F7;
    border-radius: 16px;
    padding: 24px;
}

.completada-content .info-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: #FFFFFF;
    border-radius: 12px;
    border: 1px solid #E5E5E7;
}

.completada-content .info-card i {
    font-size: 20px;
    color: #007AFF;
    margin-top: 2px;
}

.completada-content .info-card strong {
    display: block;
    font-size: 16px;
    font-weight: 600;
    color: #1D1D1F;
    margin-bottom: 8px;
}

.completada-content .info-card p {
    font-size: 14px;
    color: #6C6C70;
    margin: 0 0 12px 0;
}

.completada-content .info-card ul {
    margin: 0;
    padding-left: 20px;
    color: #6C6C70;
}

.completada-content .info-card li {
    margin-bottom: 6px;
    font-size: 14px;
}

/* Resumen de estadísticas */
.resumen-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.stat-card {
    background: #FFFFFF;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #E5E5E7;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 12px;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #1D1D1F;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #6C6C70;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Estados de amenities */
.estado-completo {
    border-left: 4px solid #34C759;
}

.estado-incompleto {
    border-left: 4px solid #FF9500;
}

.estado-faltante {
    border-left: 4px solid #FF3B30;
}

/* Categorías de amenities */
.amenity-category {
    margin-bottom: 32px;
}

.category-title {
    font-size: 16px;
    font-weight: 600;
    color: #1D1D1F;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-title i {
    color: #007AFF;
}

.amenities-grid {
    display: grid;
    gap: 16px;
}

.amenity-item {
    background: #FFFFFF;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #E5E5E7;
    transition: all 0.3s ease;
}

.amenity-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.amenity-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.amenity-icon {
    width: 40px;
    height: 40px;
    background: rgba(0, 122, 255, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.amenity-icon i {
    color: #007AFF;
    font-size: 16px;
}

.amenity-name {
    flex: 1;
    font-weight: 600;
    color: #1D1D1F;
}

.estado-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

.estado-badge.estado-completo {
    background: rgba(52, 199, 89, 0.1);
    color: #34C759;
}

.estado-badge.estado-incompleto {
    background: rgba(255, 149, 0, 0.1);
    color: #FF9500;
}

.estado-badge.estado-faltante {
    background: rgba(255, 59, 48, 0.1);
    color: #FF3B30;
}

.amenity-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-label {
    font-size: 12px;
    color: #6C6C70;
    font-weight: 500;
}

.detail-value {
    font-size: 14px;
    font-weight: 600;
    color: #1D1D1F;
}

.text-success {
    color: #34C759 !important;
}

.text-danger {
    color: #FF3B30 !important;
}

.amenity-observaciones {
    margin-top: 16px;
    padding: 12px;
    background: #F5F5F7;
    border-radius: 8px;
    font-size: 13px;
    color: #6C6C70;
}

.amenity-observaciones strong {
    color: #1D1D1F;
}

.loading-spinner {
    margin-bottom: 24px;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(0, 122, 255, 0.2);
    border-top: 4px solid #007AFF;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.amenities-loading p {
    font-size: 16px;
    margin: 0;
    font-weight: 500;
}

/* Contenido de Amenities */
.amenities-preview {
    text-align: center;
}

.preview-header h6 {
    font-size: 18px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0 0 12px 0;
}

.preview-header p {
    font-size: 14px;
    color: #6C6C70;
    margin: 0 0 32px 0;
}

.preview-content {
    max-width: 400px;
    margin: 0 auto;
}

.info-card {
    background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    text-align: left;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.info-card i {
    font-size: 24px;
    color: #007AFF;
    flex-shrink: 0;
}

.info-card strong {
    font-size: 14px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0 0 4px 0;
    display: block;
}

.info-card p {
    font-size: 13px;
    color: #6C6C70;
    margin: 0;
    line-height: 1.4;
}

/* Modal Footer */
.modal-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    padding: 20px 24px;
    background: #F8F9FA;
}

.modal-footer .btn {
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 500;
}

/* Responsive Mejorado */
@media (max-width: 768px) {
    .apple-list-actions {
        flex-direction: row;
        gap: 8px;
        align-items: center;
        margin-top: 12px;
        justify-content: flex-end;
    }
    
    .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
    
    .modal-header {
        padding: 20px;
    }
    
    .modal-body {
        padding: 24px 20px;
    }
    
    .modal-footer {
        padding: 16px 20px;
    }
    
    .title-icon {
        width: 40px;
        height: 40px;
    }
    
    .title-icon i {
        font-size: 18px;
    }
    
    .title-text h5 {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .apple-list-actions {
        flex-direction: column;
        gap: 8px;
        align-items: stretch;
        margin-top: 16px;
    }
}

/* Estilos para modal nativo como fallback */
.modal.show {
    display: block !important;
}

.modal-open {
    overflow: hidden;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}

.modal.show + .modal-backdrop {
    display: block;
}

/* Estilos para Amenities Reales */
.amenities-real {
    padding: 20px 0;
}

.reserva-info {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    justify-content: center;
}

.info-badge {
    background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
    border: 1px solid #D1D5DB;
    border-radius: 20px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #374151;
    font-weight: 500;
}

.info-badge i {
    color: #6366F1;
    font-size: 16px;
}

.amenities-categories {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.amenity-category {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.category-title {
    font-size: 16px;
    font-weight: 600;
    color: #1F2937;
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 2px solid #F3F4F6;
}

.category-title i {
    color: #6366F1;
    font-size: 14px;
}

.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.amenity-item {
    background: #FFFFFF;
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
    position: relative;
}

.amenity-item:hover {
    border-color: #6366F1;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.1);
    transform: translateY(-1px);
}

.amenity-item.stock-bajo {
    border-color: #EF4444;
    background: #FEF2F2;
}

.amenity-item.stock-ok {
    border-color: #10B981;
    background: #F0FDF4;
}

.amenity-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #EEF2FF 0%, #E0E7FF 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.amenity-icon i {
    color: #6366F1;
    font-size: 20px;
}

.amenity-details {
    flex: 1;
    min-width: 0;
}

.amenity-name {
    font-size: 16px;
    font-weight: 600;
    color: #1F2937;
    margin: 0 0 4px 0;
    line-height: 1.3;
}

.amenity-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.amenity-info .cantidad {
    font-size: 14px;
    font-weight: 500;
    color: #6366F1;
}

.amenity-info .tipo {
    font-size: 12px;
    color: #6B7280;
    background: #F3F4F6;
    padding: 2px 8px;
    border-radius: 8px;
    display: inline-block;
}

.amenity-stock {
    text-align: right;
    flex-shrink: 0;
}

.stock-label {
    font-size: 12px;
    color: #6B7280;
    display: block;
    margin-bottom: 4px;
}

.stock-value {
    font-size: 16px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 8px;
    display: inline-block;
}

.stock-value.stock-ok {
    background: #D1FAE5;
    color: #065F46;
}

.stock-value.stock-bajo {
    background: #FEE2E2;
    color: #991B1B;
}

/* Estilos para Error */
.amenities-error {
    text-align: center;
    padding: 40px 20px;
}

.error-icon {
    margin-bottom: 16px;
}

.error-icon i {
    font-size: 48px;
    color: #EF4444;
}

.error-message h6 {
    font-size: 18px;
    font-weight: 600;
    color: #1F2937;
    margin: 0 0 8px 0;
}

.error-message p {
    font-size: 14px;
    color: #6B7280;
    margin: 0;
}

/* Responsive para Amenities */
@media (max-width: 768px) {
    .reserva-info {
        flex-direction: column;
        gap: 12px;
    }
    
    .amenities-grid {
        grid-template-columns: 1fr;
    }
    
    .amenity-item {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .amenity-stock {
        text-align: center;
    }
}

/* Forzar estilos de botones */
.amenities-btn {
    background: linear-gradient(135deg, #8B5CF6 0%, #A855F7 100%) !important;
    color: #FFFFFF !important;
    border: none !important;
    padding: 12px !important;
    border-radius: 50% !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3) !important;
    width: 44px !important;
    height: 44px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.amenities-btn i {
    color: #FFFFFF !important;
    font-size: 18px !important;
}

.action-btn {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%) !important;
    color: #FFFFFF !important;
    border: none !important;
    padding: 12px !important;
    border-radius: 50% !important;
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3) !important;
    width: 44px !important;
    height: 44px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.action-btn i {
    color: #FFFFFF !important;
    font-size: 18px !important;
}
        /* Estilos para amenities de consumo */
        .amenity-category {
            margin-bottom: 30px;
        }

        .amenity-category .category-title {
            color: #007AFF;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #F2F2F7;
            display: flex;
            align-items: center;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .amenity-item {
            display: flex;
            align-items: flex-start;
            padding: 20px;
            background: #F8F9FA;
            border-radius: 16px;
            border: 1px solid #E9ECEF;
            transition: all 0.3s ease;
        }

        .amenity-item:hover {
            background: #E9ECEF;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        /* Estados de amenities */
        .amenity-puesto {
            background: #F0F8FF;
            border-color: #28A745;
        }

        .amenity-no-puesto {
            background: #FFF8F0;
            border-color: #FFC107;
        }

        .amenity-icon {
            margin-right: 16px;
            padding: 12px;
            background: #007AFF;
            border-radius: 12px;
            color: white;
            font-size: 18px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-puesto {
            background: #28A745;
        }

        .icon-no-puesto {
            background: #FFC107;
        }

        .amenity-content {
            flex: 1;
        }

        .amenity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .amenity-name {
            color: #1D1D1F;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            flex: 1;
        }

        .amenity-status {
            margin-left: 12px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-puesto {
            background: #28A745;
            color: white;
        }

        .status-no-puesto {
            background: #FFC107;
            color: #1D1D1F;
        }

        .amenity-type {
            margin: 0 0 12px 0;
        }

        .badge-info {
            background: #007AFF;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .amenity-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .detail-item {
            color: #6E6E73;
            font-size: 13px;
        }

        .detail-item strong {
            color: #1D1D1F;
        }

        .detail-puesto {
            color: #28A745;
        }

        .warning-text {
            color: #FFC107;
            font-weight: 500;
            margin-left: 8px;
        }

        .success-text {
            color: #28A745;
            font-weight: 500;
            margin-left: 8px;
        }
</style>
</style>
</style>
@endsection

@section('scripts')
<script>
    // Función para manejar colapsables
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar todos los colapsables de Bootstrap
        const collapseElements = document.querySelectorAll('.collapse');
        collapseElements.forEach(collapse => {
            collapse.addEventListener('show.bs.collapse', function() {
                const header = this.previousElementSibling;
                const toggleIcon = header.querySelector('.apple-card-toggle i');
                if (toggleIcon) {
                    toggleIcon.className = 'fa-solid fa-chevron-up';
                }
            });
            
            collapse.addEventListener('hide.bs.collapse', function() {
                const header = this.previousElementSibling;
                const toggleIcon = header.querySelector('.apple-card-toggle i');
                if (toggleIcon) {
                    toggleIcon.className = 'fa-solid fa-chevron-down';
                }
            });
        });
        
        // Manejar clics en headers para toggle manual si es necesario
        const cardHeaders = document.querySelectorAll('.apple-card-header');
        cardHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const targetId = this.getAttribute('data-bs-target');
                const target = document.querySelector(targetId);
                const toggleIcon = this.querySelector('.apple-card-toggle i');
                
                if (target) {
                    if (target.classList.contains('show')) {
                        // Está abierto, cerrarlo
                        target.classList.remove('show');
                        if (toggleIcon) {
                            toggleIcon.className = 'fa-solid fa-chevron-down';
                        }
                    } else {
                        // Está cerrado, abrirlo
                        target.classList.add('show');
                        if (toggleIcon) {
                            toggleIcon.className = 'fa-solid fa-chevron-up';
                        }
                    }
                }
            });
        });
    });

    // Función para ver amenities de una reserva pendiente
    function verAmenitiesReserva(reservaId, apartamentoTitulo, numeroPersonas, fechaEntrada, fechaSalida, numeroNinos, edadesNinos) {
        try {
            const modal = document.getElementById('amenitiesModal');
            const modalTitle = document.getElementById('amenitiesModalLabel');
            const modalSubtitle = document.getElementById('amenitiesModalSubtitle');
            const amenitiesLoading = document.getElementById('amenitiesLoading');
            const amenitiesContent = document.getElementById('amenitiesContent');
            
            if (!modal || !modalTitle || !modalSubtitle || !amenitiesLoading || !amenitiesContent) {
                console.error('Elementos del modal no encontrados');
                return;
            }
            
            // Configurar el modal
            modalTitle.textContent = `Amenities - ${apartamentoTitulo}`;
            
            let subtitle = `Reserva #${reservaId}`;
            if (numeroPersonas > 0) {
                subtitle += ` • ${numeroPersonas} adulto${numeroPersonas > 1 ? 's' : ''}`;
            }
            if (numeroNinos > 0) {
                subtitle += ` • ${numeroNinos} niño${numeroNinos > 1 ? 's' : ''}`;
                if (edadesNinos && Array.isArray(edadesNinos) && edadesNinos.length > 0) {
                    const edadesFormateadas = edadesNinos.map(edad => {
                        if (edad <= 2) return `bebé (${edad} años)`;
                        if (edad <= 12) return `niño (${edad} años)`;
                        return `adolescente (${edad} años)`;
                    }).join(', ');
                    subtitle += ` • Edades: ${edadesFormateadas}`;
                }
            }
            if (fechaEntrada && fechaSalida) {
                try {
                    const entrada = new Date(fechaEntrada).toLocaleDateString('es-ES');
                    const salida = new Date(fechaSalida).toLocaleDateString('es-ES');
                    subtitle += ` • ${entrada} - ${salida}`;
                } catch (e) {
                    console.warn('Error al formatear fechas:', e);
                }
            }
            modalSubtitle.textContent = subtitle;
            
            // Mostrar loading
            amenitiesLoading.style.display = 'block';
            amenitiesContent.style.display = 'none';
            
            // Verificar si Bootstrap está disponible
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            } else {
                // Fallback: mostrar modal nativo
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
            
            // Cargar amenities
            cargarAmenitiesReserva(reservaId, numeroNinos, edadesNinos);
            
        } catch (error) {
            console.error('Error al abrir modal de amenities:', error);
            alert('Error al abrir el modal. Por favor, recarga la página.');
        }
    }
    
    // Función para ver amenities de una limpieza en proceso
    function verAmenitiesLimpieza(limpiezaId, apartamentoNombre) {
        try {
            const modal = document.getElementById('amenitiesModal');
            const modalTitle = document.getElementById('amenitiesModalLabel');
            const modalSubtitle = document.getElementById('amenitiesModalSubtitle');
            const amenitiesLoading = document.getElementById('amenitiesLoading');
            const amenitiesContent = document.getElementById('amenitiesContent');
            
            if (!modal || !modalTitle || !modalSubtitle || !amenitiesLoading || !amenitiesContent) {
                console.error('Elementos del modal no encontrados');
                return;
            }
            
            // Configurar el modal
            modalTitle.textContent = `Amenities - ${apartamentoNombre}`;
            modalSubtitle.textContent = `Limpieza #${limpiezaId} en proceso`;
            
            // Mostrar loading
            amenitiesLoading.style.display = 'block';
            amenitiesContent.style.display = 'none';
            
            // Verificar si Bootstrap está disponible
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            } else {
                // Fallback: mostrar modal nativo
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
            
            // Cargar amenities de la limpieza
            cargarAmenitiesLimpieza(limpiezaId);
            
        } catch (error) {
            console.error('Error al abrir modal de amenities:', error);
            alert('Error al abrir el modal. Por favor, recarga la página.');
        }
    }
    
    // Cargar amenities de una reserva
    function cargarAmenitiesReserva(reservaId, numeroNinos, edadesNinos) {
        const amenitiesLoading = document.getElementById('amenitiesLoading');
        const amenitiesContent = document.getElementById('amenitiesContent');
        
        // Realizar llamada AJAX para obtener amenities reales
        fetch(`/amenities-reserva/${reservaId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ numero_ninos: numeroNinos, edades_ninos: edadesNinos })
        })
            .then(response => response.json())
            .then(data => {
                amenitiesLoading.style.display = 'none';
                amenitiesContent.style.display = 'block';
                
                if (data.success) {
                    renderizarAmenities(data.amenities, data.reserva);
                } else {
                    mostrarError('Error al cargar amenities: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                amenitiesLoading.style.display = 'none';
                amenitiesContent.style.display = 'block';
                mostrarError('Error de conexión al cargar amenities');
            });
    }
    
    // Cargar amenities de una limpieza
    function cargarAmenitiesLimpieza(limpiezaId) {
        const amenitiesLoading = document.getElementById('amenitiesLoading');
        const amenitiesContent = document.getElementById('amenitiesContent');
        
        // Simular carga de amenities (aquí iría la llamada AJAX real)
        setTimeout(() => {
            amenitiesLoading.style.display = 'none';
            amenitiesContent.style.display = 'block';
            
            // Por ahora, mostrar un mensaje de ejemplo
            amenitiesContent.innerHTML = `
                <div class="amenities-preview">
                    <div class="preview-header">
                        <h6>Vista previa de Amenities</h6>
                        <p>Esta funcionalidad mostrará los amenities gestionados en la limpieza #${limpiezaId}</p>
                    </div>
                    <div class="preview-content">
                        <div class="info-card">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Funcionalidad en desarrollo</strong>
                                <p>Los amenities se cargarán dinámicamente desde la base de datos</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }, 1500);
    }
    
    // Ver amenities de una limpieza completada
    function verAmenitiesLimpiezaCompletada(limpiezaId, nombreApartamento) {
        try {
            // Actualizar título y subtítulo del modal
            document.getElementById('amenitiesModalLabel').textContent = 'Amenities de Limpieza Completada';
            document.getElementById('amenitiesModalSubtitle').textContent = `Limpieza #${limpiezaId} - ${nombreApartamento}`;
            
            // Mostrar modal
            const modal = document.getElementById('amenitiesModal');
            
            // Verificar si Bootstrap está disponible
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            } else {
                // Fallback: mostrar modal nativo
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.classList.add('modal-open');
            }
            
            // Cargar amenities de la limpieza completada
            cargarAmenitiesLimpiezaCompletada(limpiezaId);
            
        } catch (error) {
            console.error('Error al abrir modal de amenities completada:', error);
            alert('Error al abrir el modal. Por favor, recarga la página.');
        }
    }
    
    // Cargar amenities de una limpieza completada
    function cargarAmenitiesLimpiezaCompletada(limpiezaId) {
        const amenitiesLoading = document.getElementById('amenitiesLoading');
        const amenitiesContent = document.getElementById('amenitiesContent');
        
        // Realizar llamada AJAX para obtener amenities reales
        fetch(`/amenities-limpieza-completada/${limpiezaId}`)
            .then(response => response.json())
            .then(data => {
                amenitiesLoading.style.display = 'none';
                amenitiesContent.style.display = 'block';
                
                if (data.success) {
                    renderizarAmenitiesLimpiezaCompletada(data.amenities, data.limpieza, data.resumen);
                } else {
                    mostrarError('Error al cargar amenities: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                amenitiesLoading.style.display = 'none';
                amenitiesContent.style.display = 'block';
                mostrarError('Error de conexión al cargar amenities');
            });
    }
    
    // Renderizar amenities de limpieza completada
    function renderizarAmenitiesLimpiezaCompletada(amenities, limpieza, resumen) {
        const amenitiesContent = document.getElementById('amenitiesContent');
        
        let html = `
            <div class="amenities-completada">
                <div class="completada-header">
                    <h6>Estado de Amenities - Limpieza #${limpieza.id}</h6>
                    <p>${limpieza.apartamento} - ${limpieza.empleado}</p>
                </div>
                
                <div class="resumen-stats">
                    <div class="stat-card stat-completo">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-number">${resumen.proporcionados}</div>
                            <div class="stat-label">Proporcionados</div>
                        </div>
                    </div>
                    <div class="stat-card stat-faltante">
                        <div class="stat-icon">❌</div>
                        <div class="stat-content">
                            <div class="stat-number">${resumen.faltantes}</div>
                            <div class="stat-label">Faltantes</div>
                        </div>
                    </div>
                    <div class="stat-card stat-costo">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-number">€${resumen.costo_total}</div>
                            <div class="stat-label">Costo Total</div>
                        </div>
                    </div>
                </div>
                
                <div class="amenities-categories">`;
        
        Object.keys(amenities).forEach(categoria => {
            html += `
                <div class="amenity-category">
                    <h6 class="category-title">
                        <i class="fas fa-tag"></i>
                        ${categoria}
                    </h6>
                    <div class="amenities-grid">`;
            
            amenities[categoria].forEach(item => {
                const amenity = item.amenity;
                const estadoClass = item.estado === 'completo' ? 'estado-completo' : 
                                  item.estado === 'incompleto' ? 'estado-incompleto' : 'estado-faltante';
                const estadoIcon = item.estado === 'completo' ? '✅' : 
                                 item.estado === 'incompleto' ? '⚠️' : '❌';
                
                html += `
                    <div class="amenity-item ${estadoClass}">
                        <div class="amenity-header">
                            <div class="amenity-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="amenity-name">${amenity.nombre}</div>
                            <div class="estado-badge ${estadoClass}">
                                ${estadoIcon}
                            </div>
                        </div>
                        <div class="amenity-details">
                            <div class="detail-row">
                                <span class="detail-label">Recomendado:</span>
                                <span class="detail-value">${item.cantidad_recomendada} ${amenity.unidad_medida || 'unidad'}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Real:</span>
                                <span class="detail-value ${item.cantidad_real > 0 ? 'text-success' : 'text-danger'}">${item.cantidad_real} ${amenity.unidad_medida || 'unidad'}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Tipo:</span>
                                <span class="detail-value">${getTipoConsumoText(item.tipo_consumo)}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Costo:</span>
                                <span class="detail-value">€${item.costo_total}</span>
                            </div>
                        </div>
                        ${item.observaciones ? `
                        <div class="amenity-observaciones">
                            <strong>Observaciones:</strong> ${item.observaciones}
                        </div>
                        ` : ''}
                    </div>`;
            });
            
            html += `
                    </div>
                </div>`;
        });
        
        html += `
                </div>
            </div>`;
        
        amenitiesContent.innerHTML = html;
    }
    
    // Renderizar amenities en el modal
    function renderizarAmenities(amenities, reserva) {
        const amenitiesContent = document.getElementById('amenitiesContent');
        
        let html = `
            <div class="amenities-real">
                <div class="reserva-info">
                    <div class="info-badge">
                        <i class="fas fa-users"></i>
                        <span>${reserva.numero_personas} persona${reserva.numero_personas > 1 ? 's' : ''}</span>
                    </div>
                    <div class="info-badge">
                        <i class="fas fa-calendar"></i>
                        <span>${reserva.dias} día${reserva.dias > 1 ? 's' : ''}</span>
                    </div>
                </div>
                
                <div class="amenities-categories">`;
        
        Object.keys(amenities).forEach(categoria => {
            html += `
                <div class="amenity-category">
                    <h6 class="category-title">
                        <i class="fas fa-tag"></i>
                        ${categoria}
                    </h6>
                    <div class="amenities-grid">`;
            
            amenities[categoria].forEach(item => {
                const amenity = item.amenity;
                const stockClass = item.stock_disponible < item.cantidad_recomendada ? 'stock-bajo' : 'stock-ok';
                
                html += `
                    <div class="amenity-item ${stockClass}">
                        <div class="amenity-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="amenity-details">
                            <div class="amenity-name">${amenity.nombre}</div>
                            <div class="amenity-info">
                                <span class="cantidad">${item.cantidad_recomendada} ${amenity.unidad_medida || 'unidad'}</span>
                                <span class="tipo">${getTipoConsumoText(item.tipo_consumo)}</span>
                            </div>
                        </div>
                        <div class="amenity-stock">
                            <span class="stock-label">Stock:</span>
                            <span class="stock-value ${stockClass}">${item.stock_disponible}</span>
                        </div>
                    </div>`;
            });
            
            html += `
                    </div>
                </div>`;
        });
        
        html += `
                </div>
            </div>`;
        
        amenitiesContent.innerHTML = html;
    }
    
    // Obtener texto descriptivo del tipo de consumo
    function getTipoConsumoText(tipo) {
        switch(tipo) {
            case 'por_reserva': return 'Por reserva';
            case 'por_tiempo': return 'Por tiempo';
            case 'por_persona': return 'Por persona';
            default: return 'Estándar';
        }
    }
    
    // Mostrar mensaje de error
    function mostrarError(mensaje) {
        const amenitiesContent = document.getElementById('amenitiesContent');
        amenitiesContent.innerHTML = `
            <div class="amenities-error">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="error-message">
                    <h6>Error</h6>
                    <p>${mensaje}</p>
                </div>
            </div>
        `;
    }

    function mostrarAmenities(apartamentoId, limpiezaId, nombreApartamento, tipoSeccion) {
        const modal = document.getElementById('amenitiesModal');
        const modalLabel = document.getElementById('amenitiesModalLabel');
        const modalSubtitle = document.getElementById('amenitiesModalSubtitle');
        const content = document.getElementById('amenitiesContent');
        
        // Actualizar título del modal
        modalLabel.textContent = `Amenities de Consumo - ${nombreApartamento}`;
        
        // Determinar el subtítulo según la sección
        let subtitulo = '';
        switch(tipoSeccion) {
            case 'pendiente':
                subtitulo = 'Amenities que debes preparar para la limpieza';
                break;
            case 'en_proceso':
                subtitulo = 'Amenities que debes añadir durante la limpieza';
                break;
            case 'completada':
                subtitulo = 'Amenities que se añadieron en la limpieza';
                break;
            case 'manana':
                subtitulo = 'Amenities que necesitarás mañana';
                break;
            default:
                subtitulo = 'Gestiona los amenities para este apartamento';
        }
        modalSubtitle.textContent = subtitulo;
        
        // Mostrar loading
        content.innerHTML = `
            <div class="amenities-loading">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
                <p>Cargando amenities...</p>
            </div>
        `;
        
        // Mostrar modal
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        // Cargar amenities desde el controlador
        cargarAmenities(limpiezaId, tipoSeccion);
    }
    
    function cargarAmenities(limpiezaId, tipoSeccion) {
        const content = document.getElementById('amenitiesContent');
        
        // Obtener amenities del controlador (ya están en la vista)
        const amenities = @json($amenities);
        const consumosExistentes = @json($consumosExistentes);
        
        if (!amenities || Object.keys(amenities).length === 0) {
            content.innerHTML = `
                <div class="text-center py-5">
                    <i class="fa fa-info-circle fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No hay amenities configurados</h6>
                    <p class="text-muted">No se han configurado amenities de consumo para este edificio.</p>
                </div>
            `;
            return;
        }
        
        // Generar HTML de amenities
        let html = '';
        
        Object.keys(amenities).forEach(categoria => {
            const amenitiesCategoria = amenities[categoria];
            
            html += `
                <div class="amenity-category mb-4">
                    <h6 class="category-title">
                        <i class="fa fa-tag me-2"></i>
                        ${categoria.charAt(0).toUpperCase() + categoria.slice(1)} (${amenitiesCategoria.length} amenities)
                    </h6>
                    <div class="amenities-grid">
            `;
            
            amenitiesCategoria.forEach(amenity => {
                const consumosLimpieza = consumosExistentes[limpiezaId] || [];
                const consumoExistente = consumosLimpieza.find(c => c.amenity_id === amenity.id);
                const sePuso = !!consumoExistente;
                const cantidadPuesta = consumoExistente ? consumoExistente.cantidad_consumida : 0;
                
                // Calcular cantidad recomendada
                let cantidadRecomendada = 1;
                if (amenity.tipo_consumo === 'por_reserva') {
                    cantidadRecomendada = amenity.consumo_por_reserva || 1;
                } else if (amenity.tipo_consumo === 'por_persona') {
                    cantidadRecomendada = (amenity.consumo_por_persona || 1);
                }
                
                const estadoClase = sePuso ? 'amenity-puesto' : 'amenity-no-puesto';
                const iconoClase = sePuso ? 'icon-puesto' : 'icon-no-puesto';
                const icono = sePuso ? 'fa-check' : 'fa-gift';
                const estado = sePuso ? 'PUESTO' : 'NO PUESTO';
                const estadoColor = sePuso ? 'status-puesto' : 'status-no-puesto';
                
                html += `
                    <div class="amenity-item ${estadoClase}">
                        <div class="amenity-icon ${iconoClase}">
                            <i class="fa ${icono}"></i>
                        </div>
                        <div class="amenity-content">
                            <div class="amenity-header">
                                <h6 class="amenity-name">${amenity.nombre}</h6>
                                <div class="amenity-status">
                                    <span class="status-badge ${estadoColor}">
                                        <i class="fa fa-${sePuso ? 'check' : 'times'} me-1"></i>${estado}
                                    </span>
                                </div>
                            </div>
                            
                            <p class="amenity-type">
                                <span class="badge badge-info">${amenity.tipo_consumo.replace('_', ' ').charAt(0).toUpperCase() + amenity.tipo_consumo.replace('_', ' ').slice(1)}</span>
                            </p>
                            
                            <div class="amenity-details">
                                <span class="detail-item">
                                    <strong>Recomendado:</strong> ${cantidadRecomendada}
                                </span>
                                ${sePuso ? `
                                    <span class="detail-item detail-puesto">
                                        <strong>Puesto:</strong> ${cantidadPuesta}
                                        ${cantidadPuesta < cantidadRecomendada ? 
                                            '<span class="warning-text">⚠️ Menos del recomendado</span>' : 
                                            cantidadPuesta > cantidadRecomendada ? 
                                            '<span class="warning-text">⚠️ Más del recomendado</span>' : 
                                            '<span class="success-text">✅ Cantidad correcta</span>'
                                        }
                                    </span>
                                ` : ''}
                                <span class="detail-item">
                                    <strong>Stock:</strong> ${amenity.stock_actual}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        });
        
        content.innerHTML = html;
    }

    // Función para controlar la jornada (fichaje/desfichaje)
    function controlarJornada(event) {
        // Prevenir que el evento se propague
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Verificar si hay un fichaje activo
        fetch('/fichajes/estado')
            .then(response => response.json())
            .then(data => {
                if (data.fichaje_activo) {
                    // Si hay fichaje activo, mostrar opciones de pausa o finalizar
                    mostrarOpcionesJornada(data);
                } else {
                    // Si no hay fichaje activo, iniciar jornada
                    iniciarJornada();
                }
            })
            .catch(error => {
                console.error('Error al verificar estado del fichaje:', error);
                // En caso de error, intentar iniciar jornada
                iniciarJornada();
            });
    }

    // Función para iniciar jornada
    function iniciarJornada() {
        fetch('/fichajes/iniciar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Jornada iniciada correctamente', 'success');
                // Actualizar el botón para mostrar opciones de pausa/finalizar
                actualizarBotónJornada(true);
            } else {
                mostrarNotificacion(data.message || 'Error al iniciar la jornada', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión al iniciar jornada', 'error');
        });
    }

    // Función para mostrar opciones de jornada activa
    function mostrarOpcionesJornada(data) {
        console.log('Mostrando opciones de jornada:', data);
        
        // Remover modal existente si existe
        const modalExistente = document.getElementById('jornadaModal');
        if (modalExistente) {
            modalExistente.remove();
        }
        
        const opciones = `
            <div class="modal fade" id="jornadaModal" tabindex="-1" aria-labelledby="jornadaModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="jornadaModalLabel">
                                <i class="fas fa-clock me-2"></i>
                                Control de Jornada
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-clock fa-3x text-success mb-3"></i>
                                <h6>Jornada Activa</h6>
                                <p class="text-muted">Iniciada: ${data.hora_inicio}</p>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-warning" onclick="iniciarPausa()">
                                    <i class="fas fa-pause me-2"></i>
                                    Iniciar Pausa
                                </button>
                                <button class="btn btn-danger" onclick="finalizarJornada()">
                                    <i class="fas fa-stop me-2"></i>
                                    Finalizar Jornada
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar nuevo modal
        document.body.insertAdjacentHTML('beforeend', opciones);
        
        // Mostrar modal con verificación de Bootstrap
        const modalElement = document.getElementById('jornadaModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('Modal mostrado correctamente');
        } else {
            console.error('Error: Bootstrap no está disponible o el modal no se creó');
            // Fallback: mostrar alert
            alert(`Jornada activa desde las ${data.hora_inicio}\n\nOpciones:\n- Iniciar Pausa\n- Finalizar Jornada`);
        }
    }

    // Función para actualizar el botón de jornada
    function actualizarBotónJornada(jornadaActiva) {
        const botonJornada = document.querySelector('.apple-btn[onclick="controlarJornada()"]');
        if (jornadaActiva) {
            botonJornada.innerHTML = '<i class="fa-solid fa-clock"></i><span>Jornada Activa</span>';
            botonJornada.classList.remove('apple-btn-primary');
            botonJornada.classList.add('apple-btn-success');
        } else {
            botonJornada.innerHTML = '<i class="fa-solid fa-clock"></i><span>Jornada</span>';
            botonJornada.classList.remove('apple-btn-success');
            botonJornada.classList.add('apple-btn-primary');
        }
    }

    // Función para iniciar pausa
    function iniciarPausa() {
        fetch('/fichajes/pausa/iniciar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Pausa iniciada correctamente', 'success');
                // Cerrar modal y actualizar estado
                const modal = bootstrap.Modal.getInstance(document.getElementById('jornadaModal'));
                if (modal) modal.hide();
                // Actualizar el estado de la jornada
                setTimeout(() => {
                    controlarJornada();
                }, 1000);
            } else {
                mostrarNotificacion(data.message || 'Error al iniciar la pausa', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión al iniciar pausa', 'error');
        });
    }

    // Función para finalizar jornada
    function finalizarJornada() {
        // Confirmar antes de finalizar
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Finalizar jornada?',
                text: '¿Estás seguro de que quieres finalizar la jornada?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarFinalizarJornada();
                }
            });
        } else {
            if (confirm('¿Estás seguro de que quieres finalizar la jornada?')) {
                ejecutarFinalizarJornada();
            }
        }
    }

    // Función auxiliar para ejecutar la finalización de jornada
    function ejecutarFinalizarJornada() {
        fetch('/fichajes/finalizar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion(data.message, 'success');
                // Cerrar modal y actualizar estado
                const modal = bootstrap.Modal.getInstance(document.getElementById('jornadaModal'));
                if (modal) modal.hide();
                // Actualizar el botón de jornada
                actualizarBotónJornada(false);
            } else {
                mostrarNotificacion(data.message || 'Error al finalizar la jornada', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión al finalizar jornada', 'error');
        });
    }

    // Función para verificar estado de jornada al cargar la página
    function verificarEstadoJornada() {
        fetch('/fichajes/estado')
            .then(response => response.json())
            .then(data => {
                if (data.fichaje_activo) {
                    // Actualizar botón para mostrar jornada activa
                    actualizarBotónJornada(true);
                    // Mostrar información de tiempo transcurrido
                    mostrarTiempoJornada(data);
                } else {
                    actualizarBotónJornada(false);
                }
            })
            .catch(error => {
                console.error('Error al verificar estado de jornada:', error);
            });
    }

    // Función para mostrar tiempo de jornada activa
    function mostrarTiempoJornada(data) {
        // Crear o actualizar indicador de tiempo
        let indicadorTiempo = document.getElementById('indicadorTiempoJornada');
        if (!indicadorTiempo) {
            indicadorTiempo = document.createElement('div');
            indicadorTiempo.id = 'indicadorTiempoJornada';
            indicadorTiempo.className = 'apple-alert apple-alert-info mt-2';
            indicadorTiempo.innerHTML = `
                <i class="fa-solid fa-clock"></i>
                <span>Jornada activa desde las ${data.hora_inicio}</span>
                <span id="tiempoTranscurrido" class="ms-2 fw-bold"></span>
            `;
            
            // Insertar después del botón de jornada
            const botonJornada = document.querySelector('.apple-btn[onclick="controlarJornada()"]');
            if (botonJornada && botonJornada.parentNode) {
                botonJornada.parentNode.appendChild(indicadorTiempo);
            }
        }
        
        // Actualizar tiempo transcurrido cada segundo
        actualizarTiempoTranscurrido(data.hora_inicio);
    }

    // Función para actualizar tiempo transcurrido
    function actualizarTiempoTranscurrido(horaInicio) {
        const elemento = document.getElementById('tiempoTranscurrido');
        if (!elemento) return;
        
        const inicio = new Date();
        const [horas, minutos] = horaInicio.split(':');
        inicio.setHours(parseInt(horas), parseInt(minutos), 0, 0);
        
        function actualizar() {
            const ahora = new Date();
            const diferencia = ahora - inicio;
            
            const horas = Math.floor(diferencia / (1000 * 60 * 60));
            const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);
            
            elemento.textContent = `(${horas}h ${minutos}m ${segundos}s)`;
        }
        
        // Actualizar inmediatamente
        actualizar();
        
        // Actualizar cada segundo
        setInterval(actualizar, 1000);
    }

    // Función para mostrar notificaciones
    function mostrarNotificacion(mensaje, tipo) {
        // Usar SweetAlert2 si está disponible
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: tipo === 'success' ? 'Éxito' : 'Error',
                text: mensaje,
                icon: tipo,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            // Fallback a alert nativo
            alert(mensaje);
        }
    }

    // Verificar estado de jornada al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        verificarEstadoJornada();
    });

    // Lista de tareas del día para depuración en consola
    @if(isset($tareasParaConsola))
    (function() {
        var datos = @json($tareasParaConsola);
        console.log('[Gestión] Tareas del día:', datos);
        console.log('[Gestión] Fecha consultada:', datos.fecha);
        console.log('[Gestión] ¿Tiene turno hoy?', datos.tieneTurnoHoy);
        if (datos.tareas && datos.tareas.length) {
            console.log('[Gestión] Lista de tareas (' + datos.tareas.length + '):', datos.tareas);
        }
        if (datos.mensaje) {
            console.log('[Gestión] Mensaje:', datos.mensaje);
        }
    })();
    @endif
</script>
@endsection

@section('content')

    <div class="apple-container">
        <!-- Status Alert -->
        @if(session('status'))
            <div class="apple-alert apple-alert-success">
                <i class="fa-solid fa-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Botón de Jornada - Control de horas de trabajo -->
        <div class="apple-action-section">
            <a href="#" class="apple-btn apple-btn-primary apple-btn-full" onclick="controlarJornada()">
                <i class="fa-solid fa-clock"></i>
                <span>Jornada</span>
            </a>
        </div>

        <!-- Botón de Gestionar Incidencias - Debajo del botón Jornada -->
        <div class="apple-action-section" style="margin-top: 16px;">
            <a href="{{ route('gestion.incidencias.index') }}" class="apple-btn apple-btn-info apple-btn-full">
                <i class="fa-solid fa-exclamation-triangle"></i>
                <span>Gestionar Incidencias</span>
            </a>
        </div>

        <!-- Jornada para hoy - Sección siempre visible -->
        <div class="apple-content" id="accordionExample">
            <div class="apple-card" id="jornadaCard">
                <div class="apple-card-header" data-bs-toggle="collapse" data-bs-target="#collapsePendientes" aria-expanded="false" aria-controls="collapsePendientes">
                    <div class="apple-card-title">
                        <i class="fa-solid fa-broom"></i>
                        <span>Jornada para hoy</span>
                        @if ($reservasPendientes != null)
                            <div class="apple-card-counter">{{ count($reservasPendientes) }}</div>
                        @endif
                    </div>
                    <div class="apple-card-toggle">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
                <div id="collapsePendientes" class="apple-card-body collapse" aria-labelledby="headingPendientes" data-bs-parent="#accordionExample">
                    @if(isset($sinTurnoHoy) && $sinTurnoHoy && (!$reservasPendientes || count($reservasPendientes) === 0))
                        <p class="text-muted mb-0 py-3 px-2">
                            <i class="fa-solid fa-info-circle"></i>
                            No tienes tareas asignadas para hoy. Revisa <a href="{{ route('gestion.mis-turnos') }}">Mis Turnos</a> para ver tus tareas por fecha.
                        </p>
                    @elseif ($reservasPendientes != null)
                        <div class="apple-list">
                            @foreach($reservasPendientes as $reserva)
                            <div class="apple-list-item @if(isset($reserva->limpieza_fondo)) apple-list-item-info @else apple-list-item-warning @endif">
                                <div class="apple-list-content">
                                    <div class="apple-list-title" data-id="{{$reserva->id}}">
                                        @if(isset($reserva->tarea_asignada))
                                            <!-- Nuevo sistema de turnos -->
                                            @if($reserva->apartamento_id)
                                                {{$reserva->apartamento->titulo}}
                                            @elseif($reserva->zona_comun_id)
                                                {{$reserva->zonaComun->nombre}}
                                            @else
                                                {{$reserva->tipo_tarea->nombre}}
                                            @endif
                                        @else
                                            <!-- Sistema antiguo -->
                                            {{$reserva->apartamento->titulo}}
                                        @endif
                                    </div>
                                    <div class="apple-list-subtitle">
                                        @if(isset($reserva->tarea_asignada))
                                            <!-- Información del nuevo sistema -->
                                            @if(isset($reserva->proximaReserva) && $reserva->proximaReserva)
                                                <strong>Próxima Reserva:</strong> Sí<br>
                                                <strong>Adultos:</strong> {{$reserva->proximaReserva->numero_personas ?? 0}}
                                                @if(isset($reserva->proximaReserva->numero_ninos) && $reserva->proximaReserva->numero_ninos > 0)
                                                    <br><strong>Niños:</strong> {{$reserva->proximaReserva->numero_ninos}}
                                                @endif
                                            @else
                                                <strong>Próxima Reserva:</strong> No<br>
                                                <em class="text-muted">No hay reservas programadas después de esta limpieza</em>
                                            @endif
                                            <br><strong>Estado:</strong> 
                                            <span class="badge @if($reserva->estado === 'completada') bg-success @elseif($reserva->estado === 'en_progreso') bg-warning @else bg-primary @endif">
                                                {{ucfirst($reserva->estado)}}
                                            </span>
                                        @elseif($reserva->reservaEntraHoy)
                                            <!-- Si hay reserva que entra hoy, mostrar esa información -->
                                            <strong>Código Reserva:</strong> {{$reserva->reservaEntraHoy->codigo_reserva ?? 'N/A'}}<br>
                                            <strong>Adultos:</strong> {{$reserva->reservaEntraHoy->numero_personas}}
                                            @if($reserva->reservaEntraHoy->numero_ninos > 0)
                                                <br><strong>Niños:</strong> {{$reserva->reservaEntraHoy->numero_ninos}}
                                                @if($reserva->reservaEntraHoy->edades_ninos)
                                                    <br><strong>🎂 Edades:</strong> 
                                                    @foreach($reserva->reservaEntraHoy->edades_ninos as $edad)
                                                        @if($edad <= 2)
                                                            bebé ({{$edad}} años)
                                                        @elseif($edad <= 12)
                                                            niño ({{$edad}} años)
                                                        @else
                                                            adolescente ({{$edad}} años)
                                                        @endif
                                                        @if(!$loop->last), @endif
                                                    @endforeach
                                                @endif
                                            @endif
                                            <br><em>🔄 Entra hoy mismo</em>
                                        @else
                                            <!-- Si no hay reserva que entre hoy, mostrar mensaje -->
                                            <em>No hay entradas para este apartamento</em>
                                        @endif
                                    </div>
                                </div>
                                <div class="apple-list-actions">
                                    @if(isset($reserva->tarea_asignada))
                                        <!-- Botones para el nuevo sistema de turnos -->
                                        @if($reserva->apartamento_id)
                                            <button type="button" 
                                                    class="action-button amenities-btn" 
                                                    onclick="mostrarAmenities({{$reserva->apartamento->id}}, null, '{{$reserva->apartamento->titulo}}', 'pendiente')"
                                                    title="Ver amenities">
                                                <i class="fa fa-gift"></i>
                                            </button>
                                        @endif
                                        <button type="button" 
                                                class="action-button info-btn" 
                                                onclick="mostrarInfoTarea({{$reserva->tarea_asignada->id}})"
                                                title="Ver información de la tarea">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <button type="button" 
                                                class="action-button create-btn" 
                                                onclick="iniciarTarea({{$reserva->tarea_asignada->id}})"
                                                title="Iniciar tarea">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>
                                    @else
                                        <!-- Botones para el sistema antiguo -->
                                        <button type="button" 
                                                class="action-button amenities-btn" 
                                                onclick="mostrarAmenities({{$reserva->apartamento->id}}, null, '{{$reserva->apartamento->titulo}}', 'pendiente')"
                                                title="Ver amenities">
                                            <i class="fa fa-gift"></i>
                                        </button>
                                        @if($reserva->reservaEntraHoy)
                                            <a href="{{ route('gestion.reserva.info', $reserva->reservaEntraHoy->id) }}" 
                                               class="action-button info-btn" 
                                               title="Ver información de la reserva">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        @endif
                                        <a href="{{route('gestion.create', $reserva->id)}}" class="action-button create-btn" title="Iniciar limpieza">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        </div>
                    @else
                        <div class="apple-empty-state">
                            <i class="fa-solid fa-check-circle"></i>
                            <span>No hay apartamentos pendientes</span>
                        </div>
                    @endif

                </div>
            </div>



            <div class="apple-card">
                <div class="apple-card-header" data-bs-toggle="collapse" data-bs-target="#collapseTerminar" aria-expanded="@if(count($reservasEnLimpieza) > 0) true @else false @endif" aria-controls="collapseTerminar">
                    <div class="apple-card-title">
                        <i class="fa-solid fa-clock"></i>
                        <span>{{ __('Apartamentos por Terminar') }}</span>
                        <div class="apple-card-counter">{{ count($reservasEnLimpieza) }}</div>
                    </div>
                    <div class="apple-card-toggle">
                        <i class="fa-solid fa-chevron-@if(count($reservasEnLimpieza) > 0) up @else down @endif"></i>
                    </div>
                </div>
                <div id="collapseTerminar" class="apple-card-body collapse @if(count($reservasEnLimpieza) > 0) show @endif" aria-labelledby="headingTerminar" data-bs-parent="#accordionExample">
                    @if (count($reservasEnLimpieza) > 0)
                        <div class="apple-list">
                            @foreach($reservasEnLimpieza as $reservaEnLimpieza)
                            <div class="apple-list-item apple-list-item-warning">
                                <div class="apple-list-content">
                                    <div class="apple-list-title" data-id="{{$reservaEnLimpieza->id}}">
                                        @if(isset($reservaEnLimpieza->tarea_asignada))
                                            <!-- Nuevo sistema de turnos -->
                                            @if($reservaEnLimpieza->apartamento_id)
                                                {{$reservaEnLimpieza->apartamento->titulo}}
                                            @elseif($reservaEnLimpieza->zona_comun_id)
                                                {{$reservaEnLimpieza->zonaComun->nombre}}
                                            @else
                                                {{$reservaEnLimpieza->tipo_tarea->nombre}}
                                            @endif
                                        @else
                                            <!-- Sistema antiguo -->
                                            {{$reservaEnLimpieza->id}} - 
                                            @if($reservaEnLimpieza->apartamento)
                                                {{$reservaEnLimpieza->apartamento->nombre}}
                                            @elseif($reservaEnLimpieza->zonaComun)
                                                {{$reservaEnLimpieza->zonaComun->nombre}}
                                            @else
                                                Elemento no encontrado
                                            @endif
                                        @endif
                                    </div>
                                    <div class="apple-list-subtitle">
                                        @if(isset($reservaEnLimpieza->tarea_asignada))
                                            <!-- Información del nuevo sistema -->
                                            @if(isset($reservaEnLimpieza->proximaReserva) && $reservaEnLimpieza->proximaReserva)
                                                <strong>Próxima Reserva:</strong> Sí<br>
                                                <strong>Adultos:</strong> {{$reservaEnLimpieza->proximaReserva->numero_personas ?? 0}}
                                                @if(isset($reservaEnLimpieza->proximaReserva->numero_ninos) && $reservaEnLimpieza->proximaReserva->numero_ninos > 0)
                                                    <br><strong>Niños:</strong> {{$reservaEnLimpieza->proximaReserva->numero_ninos}}
                                                @endif
                                            @else
                                                <strong>Próxima Reserva:</strong> No<br>
                                                <em class="text-muted">No hay reservas programadas después de esta limpieza</em>
                                            @endif
                                            <br><strong>Estado:</strong> 
                                            <span class="badge @if($reservaEnLimpieza->estado === 'completada') bg-success @elseif($reservaEnLimpieza->estado === 'en_progreso') bg-warning @else bg-primary @endif">
                                                {{ucfirst($reservaEnLimpieza->estado)}}
                                            </span>
                                        @else
                                            <!-- Sistema antiguo -->
                                            <strong>Fecha Comienzo:</strong> {{ $reservaEnLimpieza->fecha_comienzo ?? 'N/A' }}<br>
                                        @endif
                                        
                                        @if(!isset($reservaEnLimpieza->tarea_asignada) && $reservaEnLimpieza->reserva_entra_hoy)
                                            <!-- Solo para sistema antiguo: Si hay reserva que entra hoy, mostrar esa información -->
                                            <strong>Código Reserva:</strong> {{$reservaEnLimpieza->reserva_entra_hoy->codigo_reserva ?? 'N/A'}}<br>
                                            <strong>Adultos:</strong> {{$reservaEnLimpieza->reserva_entra_hoy->numero_personas}}
                                            @if($reservaEnLimpieza->reserva_entra_hoy->numero_ninos > 0)
                                                <br><strong>Niños:</strong> {{$reservaEnLimpieza->reserva_entra_hoy->numero_ninos}}
                                                @if($reservaEnLimpieza->reserva_entra_hoy->edades_ninos)
                                                    <br><strong>🎂 Edades:</strong> 
                                                    @foreach($reservaEnLimpieza->reserva_entra_hoy->edades_ninos as $edad)
                                                        @if($edad <= 2)
                                                            bebé ({{$edad}} años)
                                                        @elseif($edad <= 12)
                                                            niño ({{$edad}} años)
                                                        @else
                                                            adolescente ({{$edad}} años)
                                                        @endif
                                                        @if(!$loop->last), @endif
                                                    @endforeach
                                                @endif
                                            @endif
                                            <br><em>🔄 Entra hoy mismo</em>
                                        @elseif(!isset($reservaEnLimpieza->tarea_asignada))
                                            <!-- Solo para sistema antiguo: Si no hay reserva que entre hoy, mostrar mensaje -->
                                            <em>No hay entradas para este apartamento</em>
                                        @endif
                                    </div>
                                </div>
                                <div class="apple-list-actions">
                                    @if(isset($reservaEnLimpieza->tarea_asignada))
                                        <!-- Botones para el nuevo sistema de turnos -->
                                        @if($reservaEnLimpieza->apartamento_id)
                                            <button type="button" 
                                                    class="action-button amenities-btn" 
                                                    onclick="mostrarAmenities({{$reservaEnLimpieza->apartamento->id}}, null, '{{$reservaEnLimpieza->apartamento->titulo}}', 'en_progreso')"
                                                    title="Ver amenities">
                                                <i class="fa fa-gift"></i>
                                            </button>
                                        @endif
                                        <button type="button" 
                                                class="action-button info-btn" 
                                                onclick="mostrarInfoTarea({{$reservaEnLimpieza->tarea_asignada->id}})"
                                                title="Ver información de la tarea">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <button type="button" 
                                                class="action-button edit-btn" 
                                                onclick="continuarTarea({{$reservaEnLimpieza->tarea_asignada->id}})"
                                                title="Continuar tarea">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                        <button type="button" 
                                                class="action-button complete-btn" 
                                                onclick="finalizarTarea({{$reservaEnLimpieza->tarea_asignada->id}})"
                                                title="Finalizar tarea">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @else
                                        <!-- Botones para el sistema antiguo -->
                                        @if($reservaEnLimpieza->apartamento)
                                            <button type="button" 
                                                    class="action-button amenities-btn" 
                                                    onclick="mostrarAmenities({{$reservaEnLimpieza->apartamento->id}}, {{$reservaEnLimpieza->id}}, '{{$reservaEnLimpieza->apartamento->nombre}}', 'en_proceso')"
                                                    title="Ver amenities">
                                                <i class="fa fa-gift"></i>
                                            </button>
                                        @endif
                                        @if(isset($reservaEnLimpieza->reserva_entra_hoy) && $reservaEnLimpieza->reserva_entra_hoy)
                                            <a href="{{ route('gestion.reserva.info', $reservaEnLimpieza->reserva_entra_hoy->id) }}" 
                                               class="action-button info-btn" 
                                               title="Ver información de la reserva">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        @endif
                                        @if($reservaEnLimpieza->apartamento)
                                            <a href="{{ route('gestion.edit', $reservaEnLimpieza->id) }}" class="action-button edit-btn" title="Editar limpieza">
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        @elseif($reservaEnLimpieza->zonaComun)
                                            <a href="{{ route('gestion.editZonaComun', $reservaEnLimpieza->id) }}" class="action-button edit-btn" title="Editar limpieza zona común">
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        </div>
                    @else
                        <div class="apple-empty-state">
                            <i class="fa-solid fa-clock"></i>
                            <span>No hay apartamentos en limpieza</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="apple-card">
                <div class="apple-card-header" data-bs-toggle="collapse" data-bs-target="#collapseLimpios" aria-expanded="false" aria-controls="collapseLimpios">
                    <div class="apple-card-title">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>{{ __('Apartamentos Limpiados HOY') }}</span>
                        <div class="apple-card-counter">{{ count($reservasLimpieza) }}</div>
                    </div>
                    <div class="apple-card-toggle">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
                <div id="collapseLimpios" class="apple-card-body collapse" aria-labelledby="headingLimpios" data-bs-parent="#accordionExample">
                    @if (count($reservasLimpieza) > 0)
                        <div class="apple-list">
                            @foreach($reservasLimpieza as $reservaLimpieza)
                            <div class="apple-list-item apple-list-item-success" data-id="{{$reservaLimpieza->id}}">
                                <div class="apple-list-content">
                                    <div class="apple-list-title">
                                        {{$reservaLimpieza->id}} - 
                                        @if($reservaLimpieza->apartamento)
                                            {{$reservaLimpieza->apartamento->nombre}}
                                        @elseif($reservaLimpieza->zonaComun)
                                            {{$reservaLimpieza->zonaComun->nombre}}
                                        @else
                                            {{$reservaLimpieza->tipo_tarea->nombre}}
                                        @endif
                                    </div>
                                    <div class="apple-list-subtitle">
                                        Fecha Salida: @if(isset($reservaLimpieza->origenReserva->fecha_salida)) {{$reservaLimpieza->origenReserva->fecha_salida}} @endif
                                        
                                        @if(isset($reservaLimpieza->reserva_entra_hoy) && $reservaLimpieza->reserva_entra_hoy)
                                            <!-- Si hay reserva que entra hoy, mostrar esa información -->
                                            <br><strong>Código Reserva:</strong> {{$reservaLimpieza->reserva_entra_hoy->codigo_reserva ?? 'N/A'}}<br>
                                            <strong>Adultos:</strong> {{$reservaLimpieza->reserva_entra_hoy->numero_personas}}
                                            @if($reservaLimpieza->reserva_entra_hoy->numero_ninos > 0)
                                                <br><strong>Niños:</strong> {{$reservaLimpieza->reserva_entra_hoy->numero_ninos}}
                                                @if($reservaLimpieza->reserva_entra_hoy->edades_ninos)
                                                    <br><strong>🎂 Edades:</strong> 
                                                    @foreach($reservaLimpieza->reserva_entra_hoy->edades_ninos as $edad)
                                                        @if($edad <= 2)
                                                            bebé ({{$edad}} años)
                                                        @elseif($edad <= 12)
                                                            niño ({{$edad}} años)
                                                        @else
                                                            adolescente ({{$edad}} años)
                                                        @endif
                                                        @if(!$loop->last), @endif
                                                    @endforeach
                                                @endif
                                            @endif
                                            <br><em>🔄 Entra hoy mismo</em>
                                        @else
                                            <!-- Si no hay reserva que entre hoy, mostrar mensaje -->
                                            <br><em>No hay entradas para este apartamento</em>
                                        @endif
                                    </div>
                                </div>
                                <div class="apple-list-actions">
                                    @if(isset($reservaLimpieza->apartamento) && $reservaLimpieza->apartamento)
                                        <button type="button" 
                                                    class="action-button amenities-btn" 
                                                    onclick="mostrarAmenities({{$reservaLimpieza->apartamento->id}}, {{$reservaLimpieza->id}}, '{{$reservaLimpieza->apartamento->nombre}}', 'completada')"
                                                    title="Ver amenities">
                                                <i class="fa fa-gift"></i>
                                            </button>
                                    @endif
                                    @if(isset($reservaLimpieza->reserva_entra_hoy) && $reservaLimpieza->reserva_entra_hoy)
                                        <a href="{{ route('gestion.reserva.info', $reservaLimpieza->reserva_entra_hoy->id) }}" 
                                           class="action-button info-btn" 
                                           title="Ver información de la reserva">
                                            <i class="fas fa-info-circle"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('gestion.limpieza.ver', $reservaLimpieza->id) }}" 
                                       class="action-button calendar-btn" 
                                       title="Ver información de la limpieza">
                                        <i class="fas fa-eye"></i>
                                    </a>
<<<<<<< Updated upstream
=======
                                    @if(isset($reservaLimpieza->status_id) && $reservaLimpieza->status_id == 3)
                                        <button type="button" 
                                                class="action-button warning-btn cambiar-estado-gestion-btn" 
                                                title="Volver a En Limpieza"
                                                data-limpieza-id="{{ $reservaLimpieza->id }}">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    @endif
>>>>>>> Stashed changes
                                    <div class="apple-list-icon">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    @else
                        <div class="apple-empty-state">
                            <i class="fa-solid fa-check-circle"></i>
                            <span>No hay apartamentos finalizados</span>
                        </div>
                    @endif
                </div>
            </div>
            <!-- Apartamentos previstos Mañana -->
            <div class="apple-card">
                <div class="apple-card-header" data-bs-toggle="collapse" data-bs-target="#apartamentosManana" aria-expanded="false" aria-controls="apartamentosManana">
                    <div class="apple-card-title">
                        <i class="fa-solid fa-calendar-day"></i>
                        <span>Apartamentos previstos Mañana</span>
                        <div class="apple-card-counter">{{ count($reservasManana) }}</div>
                    </div>
                    <div class="apple-card-toggle">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
                <div id="apartamentosManana" class="apple-card-body collapse" aria-labelledby="headingManana" data-bs-parent="#accordionExample">
                    @if (count($reservasManana) > 0)
                        <div class="apple-list">
                            @foreach($reservasManana as $reservaManana)
                                <div class="apple-list-item apple-list-item-info">
                                    <div class="apple-list-content">
                                        <div class="apple-list-title" data-id="{{$reservaManana->id}}">
                                            {{$reservaManana->id}} - {{$reservaManana->apartamento->nombre}}
                                        </div>
                                        <div class="apple-list-subtitle">
                                            <strong>Fecha Salida:</strong> {{$reservaManana->fecha_salida}}<br>
                                            
                                            @if($reservaManana->reserva_entra_manana)
                                                <!-- Si hay reserva que entra mañana, mostrar esa información -->
                                                <strong>Código Reserva:</strong> {{$reservaManana->reserva_entra_manana->codigo_reserva ?? 'N/A'}}<br>
                                                <strong>Adultos:</strong> {{$reservaManana->reserva_entra_manana->numero_personas}}
                                                @if($reservaManana->reserva_entra_manana->numero_ninos > 0)
                                                    <br><strong>Niños:</strong> {{$reservaManana->reserva_entra_manana->numero_ninos}}
                                                    @if($reservaManana->reserva_entra_manana->edades_ninos)
                                                        <br><strong>🎂 Edades:</strong> 
                                                        @foreach($reservaManana->reserva_entra_manana->edades_ninos as $edad)
                                                            @if($edad <= 2)
                                                                bebé ({{$edad}} años)
                                                            @elseif($edad <= 12)
                                                                niño ({{$edad}} años)
                                                            @else
                                                                adolescente ({{$edad}} años)
                                                            @endif
                                                            @if(!$loop->last), @endif
                                                        @endforeach
                                                    @endif
                                                @endif
                                                <br><em>📅 Entra mañana mismo</em>
                                            @else
                                                <!-- Si no hay reserva que entre mañana, mostrar mensaje -->
                                                <em>No hay entradas para este apartamento</em>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="apple-list-actions">
                                        <button type="button" 
                                                class="action-button amenities-btn" 
                                                onclick="mostrarAmenities({{$reservaManana->apartamento->id}}, null, '{{$reservaManana->apartamento->nombre}}', 'manana')"
                                                title="Ver amenities">
                                            <i class="fa fa-gift"></i>
                                        </button>
                                        @if($reservaManana->reserva_entra_manana)
                                            <a href="{{ route('gestion.reserva.info', $reservaManana->reserva_entra_manana->id) }}" 
                                               class="action-button info-btn" 
                                               title="Ver información de la reserva">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        @endif
                                        <div class="apple-list-icon">
                                            <i class="fas fa-calendar-day"></i>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="apple-empty-state">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span>No hay apartamentos previstos para mañana</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Amenities de Consumo -->
    <div class="modal fade" id="amenitiesModal" tabindex="-1" aria-labelledby="amenitiesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-content">
                        <div class="title-icon">
                            <i class="fa fa-gift"></i>
                        </div>
                        <div class="title-text">
                            <h5 id="amenitiesModalLabel">Amenities de Consumo</h5>
                            <p id="amenitiesModalSubtitle">Gestiona los amenities para este apartamento</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="amenitiesContent">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Información de Reserva -->

    <!-- Modal para confirmar inicio de tarea -->
    <div class="modal fade" id="confirmarInicioModal" tabindex="-1" aria-labelledby="confirmarInicioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmarInicioModalLabel">
                        <i class="fas fa-play-circle me-2"></i>
                        Confirmar Inicio de Tarea
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h6>¿Desea iniciar esta tarea?</h6>
                        <p class="text-muted mb-0">Una vez iniciada, la tarea cambiará a estado "En Progreso"</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmarInicioBtn">
                        <i class="fas fa-play me-1"></i>
                        Iniciar Tarea
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para información de tarea -->
    <div class="modal fade" id="infoTareaModal" tabindex="-1" aria-labelledby="infoTareaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoTareaModalLabel">
                        <i class="fas fa-info-circle me-2"></i>
                        Información de la Tarea
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="infoTareaContent">
                    <!-- Contenido se carga dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de progreso -->
    <div class="modal fade" id="progresoModal" tabindex="-1" aria-labelledby="progresoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="progresoModalLabel">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        Procesando...
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p id="progresoTexto">Iniciando tarea...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Variables globales
    let tareaActualId = null;
    
    // Funciones para el nuevo sistema de turnos
    function mostrarInfoTarea(tareaId) {
        tareaActualId = tareaId;
        
        // Mostrar modal de progreso
        const progresoModal = new bootstrap.Modal(document.getElementById('progresoModal'));
        progresoModal.show();
        
        // Realizar llamada AJAX al servidor
        fetch(`/gestion/tareas/${tareaId}/info`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            progresoModal.hide();
            
            if (data.success) {
                // Cargar información de la tarea
                cargarInformacionTarea(data.data);
                
                // Mostrar modal de información
                const infoModal = new bootstrap.Modal(document.getElementById('infoTareaModal'));
                infoModal.show();
            } else {
                mostrarError('Error al cargar información de la tarea: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            progresoModal.hide();
            console.error('Error:', error);
            mostrarError('Error de conexión al cargar información de la tarea');
        });
    }
    
    function cargarInformacionTarea(data) {
        const contenido = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-tag me-2"></i>Información General</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>ID Tarea:</strong></td>
                            <td>${data.id}</td>
                        </tr>
                        <tr>
                            <td><strong>Tipo:</strong></td>
                            <td>${data.tipo_tarea}</td>
                        </tr>
                        <tr>
                            <td><strong>Categoría:</strong></td>
                            <td>${data.categoria}</td>
                        </tr>
                        <tr>
                            <td><strong>Prioridad:</strong></td>
                            <td><span class="badge ${data.prioridad >= 8 ? 'bg-danger' : data.prioridad >= 6 ? 'bg-warning' : 'bg-info'}">${data.prioridad}/10</span></td>
                        </tr>
                        <tr>
                            <td><strong>Orden:</strong></td>
                            <td>${data.orden_ejecucion}</td>
                        </tr>
                        <tr>
                            <td><strong>Tiempo Estimado:</strong></td>
                            <td>${data.tiempo_estimado} minutos</td>
                        </tr>
                        <tr>
                            <td><strong>Estado:</strong></td>
                            <td><span class="badge ${data.estado === 'completada' ? 'bg-success' : data.estado === 'en_progreso' ? 'bg-warning' : 'bg-primary'}">${data.estado.charAt(0).toUpperCase() + data.estado.slice(1)}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Empleada:</strong></td>
                            <td>${data.empleada}</td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Asignación:</strong></td>
                            <td>${data.fecha_asignacion}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-map-marker-alt me-2"></i>Elemento</h6>
                    <div class="alert alert-light">
                        <strong>Tipo:</strong> ${data.elemento.tipo}<br>
                        <strong>Nombre:</strong> ${data.elemento.nombre}
                        ${data.elemento.edificio ? '<br><strong>Edificio:</strong> ' + data.elemento.edificio : ''}
                        ${data.elemento.descripcion ? '<br><strong>Descripción:</strong> ' + data.elemento.descripcion : ''}
                    </div>
                    
                    ${data.checklist ? `
                    <h6><i class="fas fa-list-check me-2"></i>Checklist</h6>
                    <div class="alert alert-info">
                        <strong>${data.checklist.nombre}</strong><br>
                        <small>${data.checklist.descripcion}</small>
                        ${data.items_checklist.length > 0 ? `<br><small class="text-muted">${data.items_checklist.length} items en el checklist</small>` : ''}
                    </div>
                    ` : ''}
                    
                    ${data.observaciones ? `
                    <h6><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                    <div class="alert alert-warning">
                        ${data.observaciones}
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        document.getElementById('infoTareaContent').innerHTML = contenido;
    }
    
    function iniciarTarea(tareaId) {
        tareaActualId = tareaId;
        
        // Mostrar modal de confirmación
        const confirmarModal = new bootstrap.Modal(document.getElementById('confirmarInicioModal'));
        confirmarModal.show();
    }
    
    // Event listener para confirmar inicio
    document.getElementById('confirmarInicioBtn').addEventListener('click', function() {
        // Cerrar modal de confirmación
        const confirmarModal = bootstrap.Modal.getInstance(document.getElementById('confirmarInicioModal'));
        confirmarModal.hide();
        
        // Mostrar modal de progreso
        const progresoModal = new bootstrap.Modal(document.getElementById('progresoModal'));
        progresoModal.show();
        
        // Realizar llamada AJAX al servidor
        fetch(`/gestion/tareas/${tareaActualId}/iniciar`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            progresoModal.hide();
            
            if (data.success) {
                // Mostrar modal de éxito
                mostrarModalExito(data.data);
            } else {
                mostrarError('Error al iniciar la tarea: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            progresoModal.hide();
            console.error('Error:', error);
            mostrarError('Error de conexión al iniciar la tarea');
        });
    });
    
    function mostrarModalExito(data) {
        // Crear modal de éxito dinámicamente
        const modalHtml = `
            <div class="modal fade" id="exitoModal" tabindex="-1" aria-labelledby="exitoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="exitoModalLabel">
                                <i class="fas fa-check-circle me-2"></i>
                                Tarea Iniciada
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h5 class="text-success">¡Tarea iniciada correctamente!</h5>
                            <p class="text-muted">La tarea ID ${data.tarea_id} ha sido iniciada y está ahora en estado "${data.estado}".</p>
                            <p class="text-muted"><small>Iniciada el: ${data.fecha_inicio}</small></p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Puede continuar con el checklist de limpieza o iniciar otra tarea.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                                <i class="fas fa-check me-1"></i>
                                Continuar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar modal
        const exitoModal = new bootstrap.Modal(document.getElementById('exitoModal'));
        exitoModal.show();
        
        // Limpiar modal del DOM cuando se cierre
        document.getElementById('exitoModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
        
        // Recargar la página para actualizar el estado
        setTimeout(() => {
            location.reload();
        }, 3000);
    }
    
    function mostrarError(mensaje) {
        // Crear modal de error dinámicamente
        const modalHtml = `
            <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="errorModalLabel">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h5 class="text-danger">¡Error!</h5>
                            <p class="text-muted">${mensaje}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar modal
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
        
        // Limpiar modal del DOM cuando se cierre
        document.getElementById('errorModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
    
    function continuarTarea(tareaId) {
        // Abrir el checklist de la tarea
        window.location.href = `/gestion/tareas/${tareaId}/checklist`;
    }
    
    function finalizarTarea(tareaId) {
        if (confirm('¿Estás seguro de que quieres finalizar esta tarea?')) {
            fetch(`/gestion/tareas/${tareaId}/finalizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        icon: 'success',
                        title: 'Tarea finalizada',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Recargar la página para actualizar la vista
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al finalizar la tarea'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión'
                });
            });
        }
    }
    </script>

@endsection

