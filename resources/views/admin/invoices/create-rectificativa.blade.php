@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-undo me-2 text-warning"></i>
                        Crear Factura Rectificativa
                    </h1>
                    <p class="text-muted mb-0">Crear una factura rectificativa para la factura original</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.facturas.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de la Factura Original -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice me-2"></i>
                        Factura Original
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Referencia:</strong><br>
                            <span class="badge bg-primary">{{ $facturaOriginal->reference }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Cliente:</strong><br>
                            {{ $facturaOriginal->cliente->nombre ?? 'N/A' }} {{ $facturaOriginal->cliente->apellido1 ?? '' }}
                        </div>
                        <div class="col-md-3">
                            <strong>Concepto:</strong><br>
                            {{ $facturaOriginal->concepto }}
                        </div>
                        <div class="col-md-3">
                            <strong>Total:</strong><br>
                            <span class="h5 text-success">{{ number_format($facturaOriginal->total, 2) }} €</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de Rectificación -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Datos de la Rectificación
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.facturas.storeRectificativa', $facturaOriginal->id) }}" method="POST" id="rectificativaForm">
                        @csrf
                        
                        <div class="row g-3">
                            <!-- Motivo de Rectificación -->
                            <div class="col-12">
                                <label for="motivo_rectificacion" class="form-label fw-semibold">
                                    Motivo de Rectificación <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('motivo_rectificacion') is-invalid @enderror" 
                                        id="motivo_rectificacion" 
                                        name="motivo_rectificacion" 
                                        required>
                                    <option value="">Seleccionar motivo</option>
                                    <option value="Error en datos del cliente" {{ old('motivo_rectificacion') == 'Error en datos del cliente' ? 'selected' : '' }}>
                                        Error en datos del cliente
                                    </option>
                                    <option value="Error en importes" {{ old('motivo_rectificacion') == 'Error en importes' ? 'selected' : '' }}>
                                        Error en importes
                                    </option>
                                    <option value="Error en concepto" {{ old('motivo_rectificacion') == 'Error en concepto' ? 'selected' : '' }}>
                                        Error en concepto
                                    </option>
                                    <option value="Cancelación de servicio" {{ old('motivo_rectificacion') == 'Cancelación de servicio' ? 'selected' : '' }}>
                                        Cancelación de servicio
                                    </option>
                                    <option value="Devolución" {{ old('motivo_rectificacion') == 'Devolución' ? 'selected' : '' }}>
                                        Devolución
                                    </option>
                                    <option value="Otro" {{ old('motivo_rectificacion') == 'Otro' ? 'selected' : '' }}>
                                        Otro
                                    </option>
                                </select>
                                @error('motivo_rectificacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Selecciona el motivo por el cual se rectifica la factura
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-12">
                                <label for="observaciones_rectificacion" class="form-label fw-semibold">
                                    Observaciones Adicionales
                                </label>
                                <textarea class="form-control @error('observaciones_rectificacion') is-invalid @enderror" 
                                          id="observaciones_rectificacion" 
                                          name="observaciones_rectificacion" 
                                          rows="4"
                                          placeholder="Detalles adicionales sobre la rectificación...">{{ old('observaciones_rectificacion') }}</textarea>
                                @error('observaciones_rectificacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-sticky-note me-1 text-info"></i>
                                    Información adicional sobre la rectificación (opcional)
                                </div>
                            </div>
                        </div>

                        <!-- Resumen de la Rectificación -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Resumen de la Rectificación
                                    </h6>
                                    <p class="mb-2">Se creará una factura rectificativa con los siguientes datos:</p>
                                    <ul class="mb-0">
                                        <li><strong>Concepto:</strong> RECTIFICATIVA - {{ $facturaOriginal->concepto }}</li>
                                        <li><strong>Base Imponible:</strong> -{{ number_format($facturaOriginal->base, 2) }} €</li>
                                        <li><strong>IVA:</strong> -{{ number_format($facturaOriginal->iva, 2) }} €</li>
                                        <li><strong>Total:</strong> -{{ number_format($facturaOriginal->total, 2) }} €</li>
                                        <li><strong>Resultado Neto:</strong> 0,00 € (Original + Rectificativa)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.facturas.index') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-warning btn-lg" id="btnSubmit">
                                <i class="fas fa-undo me-2"></i>Crear Factura Rectificativa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rectificativaForm');
    const btnSubmit = document.getElementById('btnSubmit');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar campos requeridos
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor, completa todos los campos obligatorios.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        // Confirmar creación
        Swal.fire({
            title: '⚠️ Crear Factura Rectificativa',
            html: `
                <div class="text-center">
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h5 class="mb-2"><strong>¡ATENCIÓN!</strong></h5>
                        <p class="mb-0">Se creará una factura rectificativa que anulará la factura original.</p>
                    </div>
                    <div class="card border-warning">
                        <div class="card-body text-start">
                            <h6 class="card-title text-warning">
                                <i class="fas fa-info-circle me-2"></i>Detalles:
                            </h6>
                            <p class="mb-1"><strong>Factura Original:</strong> {{ $facturaOriginal->reference }}</p>
                            <p class="mb-1"><strong>Total Original:</strong> {{ number_format($facturaOriginal->total, 2) }} €</p>
                            <p class="mb-0"><strong>Total Neto Final:</strong> 0,00 €</p>
                        </div>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f0ad4e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-undo me-2"></i>Sí, Crear Rectificativa',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
            customClass: {
                confirmButton: 'btn btn-warning btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            },
            buttonsStyling: false,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
                btnSubmit.disabled = true;
                
                // Enviar formulario
                form.submit();
            }
        });
    });

    // SweetAlert para mensajes de sesión
    @if(session('swal_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('swal_success') }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('swal_error') }}',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Aceptar'
        });
    @endif
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 12px 12px 0 0 !important;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
    padding: 0.75rem 1rem;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.invalid-feedback {
    font-size: 0.875rem;
    color: #dc3545;
}

.text-danger {
    color: #dc3545 !important;
}
</style>
@endsection
