@extends('layouts.appAdmin')

@section('title', 'Histórico Revenue')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Histórico de cambios aplicados</h2>
        <a href="{{ route('revenue.matriz') }}" class="btn btn-sm btn-outline-secondary">← Matriz</a>
    </div>

    @if($registros->isEmpty())
        <div class="alert alert-info">Aún no se ha aplicado ningún cambio de precio.</div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Apartamento</th>
                            <th>Fecha noche</th>
                            <th>Precio antes</th>
                            <th>Precio aplicado</th>
                            <th>Δ</th>
                            <th>Aplicado el</th>
                            <th>Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registros as $r)
                            <tr>
                                <td>{{ $r->apartamento?->nombre }}</td>
                                <td>{{ $r->fecha->format('d/m/Y') }}</td>
                                <td class="text-muted">{{ $r->precio_actual ? number_format($r->precio_actual, 2).'€' : '—' }}</td>
                                <td><strong>{{ number_format($r->precio_aplicado, 2) }}€</strong></td>
                                <td>
                                    @if($r->precio_actual && $r->precio_aplicado)
                                        @php $delta = $r->precio_aplicado - $r->precio_actual; @endphp
                                        <span class="@if($delta > 0) text-success @elseif($delta < 0) text-danger @endif">
                                            {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }}€
                                        </span>
                                    @endif
                                </td>
                                <td><small>{{ $r->aplicado_at?->diffForHumans() }}</small></td>
                                <td><small>{{ $r->aplicadoPor?->name ?? 'sistema' }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $registros->links() }}</div>
    @endif
</div>
@endsection
