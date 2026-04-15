@extends('layouts.appAdmin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Presupuesto #{{ $presupuesto->id }}</h1>
            <p><strong>Cliente:</strong> {{ $presupuesto->cliente->nombre ?? 'Sin cliente asignado' }}</p>
            <p><strong>Fecha:</strong> {{ $presupuesto->fecha }}</p>
            <p><strong>Total:</strong> {{ number_format($presupuesto->total, 2) }} €</p>
        </div>
        <div>
            @if($factura)
                <a href="{{ route('admin.facturas.edit', $factura->id) }}" 
                   class="btn btn-success btn-lg">
                    <i class="fas fa-file-invoice me-2"></i>
                    Ver Factura Asociada
                </a>
            @endif
            <a href="{{ route('presupuestos.index') }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>
                Volver
            </a>
        </div>
    </div>

    <h4>Conceptos</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Concepto</th>
                <th>Detalle</th>
                <th>Unidades / Noches</th>
                <th>Precio Unitario</th>
                <th>Precio Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($presupuesto->conceptos as $concepto)
                @php
                    $tipoConcepto = $concepto->tipo ?: 'alojamiento';
                @endphp
            <tr>
                <td>
                    @if($tipoConcepto === 'alojamiento')
                        <span class="badge bg-primary">Alojamiento</span>
                    @else
                        <span class="badge bg-info">Servicio</span>
                    @endif
                </td>
                <td>{{ $concepto->concepto }}</td>
                <td>
                    @if($tipoConcepto === 'alojamiento')
                        Del {{ $concepto->fecha_entrada }} al {{ $concepto->fecha_salida }}
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if($tipoConcepto === 'alojamiento')
                        {{ $concepto->dias_totales }} noches
                    @else
                        {{ $concepto->unidades ?: $concepto->dias_totales }} uds
                    @endif
                </td>
                <td>{{ number_format($concepto->precio_por_dia, 2) }} €</td>
                <td>{{ number_format($concepto->precio_total, 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection
