@extends('layouts.appAdmin')

@section('title', 'Revenue Management')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">📈 Revenue Management</h2>
            <small class="text-muted">Tus precios vs competencia · Próximos {{ $diasVista }} días</small>
        </div>
        <div>
            <a href="{{ route('revenue.historial') }}" class="btn btn-sm btn-outline-secondary">
                Histórico cambios
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="card card-body mb-3 p-3">
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label">Apartamentos</label>
                <select name="apartamentos[]" class="form-select" multiple size="3">
                    @foreach($apartamentos as $apt)
                        <option value="{{ $apt->id }}" selected>{{ $apt->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Días vista</label>
                <select name="dias" class="form-select">
                    @foreach([7,14,30,45,60] as $d)
                        <option value="{{ $d }}" @selected($diasVista===$d)>{{ $d }} días</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Actualizar</button>
            </div>
        </div>
    </form>

    {{-- Matriz --}}
    @if($apartamentos->isEmpty())
        <div class="alert alert-warning">No hay apartamentos para mostrar.</div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 70vh">
                    <table class="table table-sm table-hover mb-0 align-middle" id="matriz-revenue">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="min-width: 180px">Apartamento</th>
                                @foreach($fechas as $f)
                                    <th class="text-center" style="min-width: 90px">
                                        <small class="d-block text-muted">{{ $f->isoFormat('ddd') }}</small>
                                        <strong>{{ $f->format('d/m') }}</strong>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($apartamentos as $apt)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $apt->nombre }}</div>
                                        <small class="text-muted">
                                            <a href="{{ route('revenue.configurar', $apt->id) }}">⚙ configurar</a>
                                        </small>
                                    </td>
                                    @foreach($fechas as $f)
                                        @php
                                            $key = $apt->id . '_' . $f->toDateString();
                                            $rec = $recomendaciones[$key] ?? null;
                                            $estado = $rec ? $rec->estado : 'sin_datos';
                                        @endphp
                                        <td class="text-center
                                            @if($estado === 'subir') table-success
                                            @elseif($estado === 'bajar') table-danger
                                            @elseif($estado === 'aplicado') table-info
                                            @endif"
                                            data-apt="{{ $apt->id }}"
                                            data-fecha="{{ $f->toDateString() }}"
                                            data-precio-rec="{{ $rec?->precio_recomendado }}"
                                            style="cursor: pointer"
                                            @if($rec)
                                                title="Actual: {{ number_format($rec->precio_actual ?? 0, 0) }}€ → Recomendado: {{ number_format($rec->precio_recomendado ?? 0, 0) }}€&#10;Competencia: {{ number_format($rec->competencia_min ?? 0, 0) }}-{{ number_format($rec->competencia_max ?? 0, 0) }}€ (mediana {{ number_format($rec->competencia_media ?? 0, 0) }}€)&#10;{{ $rec->razonamiento }}"
                                            @endif
                                            >
                                            @if($rec && $rec->precio_recomendado)
                                                <input type="checkbox" class="form-check-input cambio-check"
                                                    data-apt="{{ $apt->id }}"
                                                    data-fecha="{{ $f->toDateString() }}"
                                                    data-precio="{{ $rec->precio_recomendado }}">
                                                <div class="small">
                                                    <span class="text-muted">{{ number_format($rec->precio_actual ?? 0, 0) }}€</span>
                                                    <span>→</span>
                                                    <strong>{{ number_format($rec->precio_recomendado, 0) }}€</strong>
                                                </div>
                                                @if($estado === 'aplicado')
                                                    <div class="small text-info">✓ aplicado</div>
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Footer fijo con acción --}}
        <div class="card mt-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span id="contador-seleccion">0</span> celdas seleccionadas
                </div>
                <div>
                    <button id="btn-simular" class="btn btn-outline-secondary" disabled>
                        Simular (dry-run)
                    </button>
                    <button id="btn-aplicar" class="btn btn-success" disabled>
                        Aplicar precios seleccionados
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
(function() {
    const checks = document.querySelectorAll('.cambio-check');
    const contador = document.getElementById('contador-seleccion');
    const btnAplicar = document.getElementById('btn-aplicar');
    const btnSimular = document.getElementById('btn-simular');

    function actualizarContador() {
        const sel = document.querySelectorAll('.cambio-check:checked');
        contador.textContent = sel.length;
        btnAplicar.disabled = sel.length === 0;
        btnSimular.disabled = sel.length === 0;
    }
    checks.forEach(c => c.addEventListener('change', actualizarContador));

    function recolectarCambios() {
        return Array.from(document.querySelectorAll('.cambio-check:checked')).map(c => ({
            apartamento_id: parseInt(c.dataset.apt),
            fecha: c.dataset.fecha,
            precio: parseFloat(c.dataset.precio),
        }));
    }

    async function lanzar(dryRun) {
        const cambios = recolectarCambios();
        if (cambios.length === 0) return;
        if (!dryRun && !confirm(`¿Aplicar ${cambios.length} cambios de precio en Channex? Esta acción es REAL.`)) return;
        const r = await fetch('{{ route("revenue.aplicar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({cambios, dry_run: dryRun}),
        });
        const data = await r.json();
        let msg = `OK: ${data.ok}/${data.total}`;
        if (data.errores && data.errores.length) {
            msg += '\n\nErrores:\n' + data.errores.slice(0, 5).join('\n');
        }
        if (dryRun) msg = 'SIMULACIÓN — ' + msg;
        alert(msg);
        if (!dryRun && data.ok > 0) location.reload();
    }
    btnAplicar.addEventListener('click', () => lanzar(false));
    btnSimular.addEventListener('click', () => lanzar(true));
})();
</script>
@endsection
