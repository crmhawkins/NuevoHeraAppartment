@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-edit me-2"></i>Editar Factura: {{ $invoice->reference }}
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.facturas.update', $invoice->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Información básica -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Información Básica</h5>
                                
                                <div class="mb-3">
                                    <label for="reference" class="form-label">Referencia</label>
                                    <input type="text" class="form-control" id="reference" value="{{ $invoice->reference }}" readonly>
                                    <small class="text-muted">La referencia no se puede modificar</small>
                                </div>

                                <div class="mb-3">
                                    <label for="cliente_id" class="form-label">Cliente *</label>
                                    <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id" required>
                                        <option value="">Seleccionar cliente</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}" {{ $invoice->cliente_id == $cliente->id ? 'selected' : '' }}>
                                                {{ $cliente->alias }} - {{ $cliente->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cliente_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="reserva_id" class="form-label">Reserva</label>
                                    <select class="form-select @error('reserva_id') is-invalid @enderror" id="reserva_id" name="reserva_id">
                                        <option value="">Sin reserva</option>
                                        @foreach($reservas as $reserva)
                                            <option value="{{ $reserva->id }}" {{ $invoice->reserva_id == $reserva->id ? 'selected' : '' }}>
                                                {{ $reserva->codigo_reserva }} - {{ $reserva->apartamento->nombre ?? 'Sin apartamento' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('reserva_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="invoice_status_id" class="form-label">Estado *</label>
                                    <select class="form-select @error('invoice_status_id') is-invalid @enderror" id="invoice_status_id" name="invoice_status_id" required>
                                        <option value="">Seleccionar estado</option>
                                        @foreach($estados as $estado)
                                            <option value="{{ $estado->id }}" {{ $invoice->invoice_status_id == $estado->id ? 'selected' : '' }}>
                                                {{ $estado->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('invoice_status_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Fechas -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Fechas</h5>
                                
                                <div class="mb-3">
                                    <label for="fecha" class="form-label">Fecha de Factura *</label>
                                    <input type="date" class="form-control @error('fecha') is-invalid @enderror" id="fecha" name="fecha" value="{{ $invoice->fecha }}" required>
                                    @error('fecha')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="fecha_cobro" class="form-label">Fecha de Cobro</label>
                                    <input type="date" class="form-control @error('fecha_cobro') is-invalid @enderror" id="fecha_cobro" name="fecha_cobro" value="{{ $invoice->fecha_cobro }}">
                                    @error('fecha_cobro')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Concepto y Descripción -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Concepto y Descripción</h5>
                                
                                <div class="mb-3">
                                    <label for="concepto" class="form-label">Concepto *</label>
                                    <input type="text" class="form-control @error('concepto') is-invalid @enderror" id="concepto" name="concepto" value="{{ $invoice->concepto }}" required>
                                    @error('concepto')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ $invoice->description }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Importes -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Importes</h5>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="base" class="form-label">Base Imponible *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input type="number" step="0.01" class="form-control @error('base') is-invalid @enderror" id="base" name="base" value="{{ $invoice->base }}" required>
                                            </div>
                                            @error('base')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="iva" class="form-label">IVA *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input type="number" step="0.01" class="form-control @error('iva') is-invalid @enderror" id="iva" name="iva" value="{{ $invoice->iva }}" required>
                                            </div>
                                            @error('iva')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="descuento" class="form-label">Descuento</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input type="number" step="0.01" class="form-control @error('descuento') is-invalid @enderror" id="descuento" name="descuento" value="{{ $invoice->descuento }}">
                                            </div>
                                            @error('descuento')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="total" class="form-label">Total *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">€</span>
                                                <input type="number" step="0.01" class="form-control @error('total') is-invalid @enderror" id="total" name="total" value="{{ $invoice->total }}" required>
                                            </div>
                                            @error('total')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Botones de acción -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="{{ route('admin.facturas.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Volver
                                        </a>
                                        
                                        <a href="{{ route('admin.facturas.generatePdf', $invoice->id) }}" class="btn btn-info ms-2" target="_blank">
                                            <i class="fas fa-file-pdf me-2"></i>Ver PDF
                                        </a>
                                    </div>
                                    
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calcular total cuando cambien base, IVA o descuento
    const baseInput = document.getElementById('base');
    const ivaInput = document.getElementById('iva');
    const descuentoInput = document.getElementById('descuento');
    const totalInput = document.getElementById('total');

    function calculateTotal() {
        const base = parseFloat(baseInput.value) || 0;
        const iva = parseFloat(ivaInput.value) || 0;
        const descuento = parseFloat(descuentoInput.value) || 0;
        
        const total = base + iva - descuento;
        totalInput.value = total.toFixed(2);
    }

    baseInput.addEventListener('input', calculateTotal);
    ivaInput.addEventListener('input', calculateTotal);
    descuentoInput.addEventListener('input', calculateTotal);
});
</script>
@endsection
