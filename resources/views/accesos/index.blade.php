@extends('layouts.app')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Panel de Accesos</h1>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Buscar reserva o huésped..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="estado_codigo" class="form-select">
                <option value="">Todos los estados</option>
                <option value="sin_codigo" @selected(request('estado_codigo')=='sin_codigo')>Sin código generado</option>
                <option value="sin_cerradura" @selected(request('estado_codigo')=='sin_cerradura')>Código sin programar en cerradura</option>
                <option value="ok" @selected(request('estado_codigo')=='ok')>Programado OK</option>
                <option value="sin_datos" @selected(request('estado_codigo')=='sin_datos')>Sin datos DNI</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="mostrar_pasadas" id="mostrar_pasadas" value="1" @checked(request('mostrar_pasadas'))>
                <label class="form-check-label" for="mostrar_pasadas">Mostrar pasadas</label>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
        <div class="col-md-1">
            <a href="{{ route('accesos.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Apartamento</th>
                    <th>Huésped</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Datos DNI</th>
                    <th>Código Acceso</th>
                    <th>Tipo</th>
                    <th>Cerradura</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservas as $reserva)
                <tr>
                    <td>{{ $reserva->id }}</td>
                    <td>{{ $reserva->apartamento->titulo ?? '-' }}</td>
                    <td>{{ $reserva->cliente->alias ?? '-' }}</td>
                    <td>{{ $reserva->fecha_entrada }}</td>
                    <td>{{ $reserva->fecha_salida }}</td>
                    <td>
                        @if($reserva->dni_entregado)
                            <span class="badge bg-success">Entregado</span>
                        @else
                            <span class="badge bg-danger">Pendiente</span>
                        @endif
                    </td>
                    <td>
                        @if($reserva->codigo_acceso)
                            <code class="fs-5 fw-bold">{{ $reserva->codigo_acceso }}</code>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @php $tipoCerr = $reserva->apartamento->tipo_cerradura ?? 'manual'; @endphp
                        @if($tipoCerr === 'ttlock')
                            <span class="badge bg-info text-dark"><i class="fas fa-lock me-1"></i>TTLock</span>
                        @elseif($tipoCerr === 'tuya')
                            <span class="badge bg-primary"><i class="fas fa-wifi me-1"></i>Tuya</span>
                        @else
                            <span class="badge bg-secondary"><i class="fas fa-key me-1"></i>Manual</span>
                        @endif
                    </td>
                    <td>
                        @if(!$reserva->codigo_acceso)
                            <span class="badge bg-secondary">Sin código</span>
                        @elseif($reserva->codigo_enviado_cerradura)
                            <span class="badge bg-success">Programada</span>
                        @else
                            <span class="badge bg-warning text-dark">Sin programar</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning btn-regenerar"
                                data-id="{{ $reserva->id }}"
                                data-codigo="{{ $reserva->codigo_reserva }}">
                            Regenerar
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $reservas->links() }}
</div>

<script>
document.querySelectorAll('.btn-regenerar').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const codigo = this.dataset.codigo;
        if (!confirm(`¿Regenerar código para la reserva ${codigo}? Esto invalidará el código actual en la cerradura.`)) return;

        fetch(`/accesos/${id}/regenerar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(`Nuevo código: ${data.codigo_acceso}`);
                location.reload();
            } else {
                alert('Error al regenerar el código.');
            }
        })
        .catch(() => alert('Error de conexión.'));
    });
});
</script>
@endsection
