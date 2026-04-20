@extends('layouts.appAdmin')

@section('title', 'Clientes vetados')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="apple-card">
                <div class="apple-card-header">
                    <h3 class="apple-card-title">
                        <i class="fas fa-ban me-2 text-danger"></i>
                        Clientes vetados — derecho de admisión
                    </h3>
                    <div class="apple-card-actions">
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalVetar">
                            <i class="fas fa-user-slash me-1"></i>
                            Vetar cliente
                        </button>
                    </div>
                </div>
                <div class="apple-card-body">

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <h5 class="mt-3 mb-3">Vetos activos ({{ $activos->count() }})</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>DNI</th>
                                    <th>Teléfono</th>
                                    <th>Motivo</th>
                                    <th>Vetado por</th>
                                    <th>Fecha</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activos as $v)
                                    <tr>
                                        <td>{{ $v->id }}</td>
                                        <td>
                                            @if ($v->clienteOriginal)
                                                {{ $v->clienteOriginal->nombre ?? $v->clienteOriginal->alias ?? '-' }}
                                                <small class="text-muted d-block">#{{ $v->clienteOriginal->id }}</small>
                                            @else
                                                <em class="text-muted">(sin cliente vinculado)</em>
                                            @endif
                                        </td>
                                        <td><code>{{ $v->num_identificacion ?? '-' }}</code></td>
                                        <td><code>{{ $v->telefono ?? '-' }}</code></td>
                                        <td style="max-width: 280px;">{{ $v->motivo }}</td>
                                        <td>{{ $v->vetadoPor->name ?? '-' }}</td>
                                        <td><small>{{ optional($v->vetado_at)->format('d/m/Y H:i') }}</small></td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.clientes-vetados.levantar', $v->id) }}"
                                                  onsubmit="return confirm('¿Levantar este veto? El cliente podrá volver a reservar.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-unlock me-1"></i>Levantar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">No hay clientes vetados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($levantados->count())
                        <h5 class="mt-5 mb-3 text-muted">Histórico (vetos levantados)</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>DNI</th>
                                        <th>Teléfono</th>
                                        <th>Motivo</th>
                                        <th>Vetado</th>
                                        <th>Levantado</th>
                                        <th>Por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($levantados as $v)
                                        <tr class="text-muted">
                                            <td>{{ $v->id }}</td>
                                            <td>{{ $v->clienteOriginal->nombre ?? $v->clienteOriginal->alias ?? '-' }}</td>
                                            <td><code>{{ $v->num_identificacion ?? '-' }}</code></td>
                                            <td><code>{{ $v->telefono ?? '-' }}</code></td>
                                            <td>{{ \Illuminate\Support\Str::limit($v->motivo, 80) }}</td>
                                            <td><small>{{ optional($v->vetado_at)->format('d/m/Y') }}</small></td>
                                            <td><small>{{ optional($v->levantado_at)->format('d/m/Y') }}</small></td>
                                            <td>{{ $v->levantadoPor->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: vetar cliente -->
<div class="modal fade" id="modalVetar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.clientes-vetados.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Vetar cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ID del cliente</label>
                    <input type="number" name="cliente_id" class="form-control" required min="1"
                           placeholder="Ej: 5877">
                    <small class="text-muted">Se aplica el veto al DNI y teléfono del cliente. Cualquier reserva futura con ese DNI/telefono quedará bloqueada.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo <span class="text-danger">*</span></label>
                    <textarea name="motivo" class="form-control" rows="3" required minlength="3" maxlength="2000"
                              placeholder="Ej: Daños en el apartamento, ruidos constantes, trato irrespetuoso..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas internas (opcional)</label>
                    <textarea name="notas_internas" class="form-control" rows="2" maxlength="2000"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-ban me-1"></i>Vetar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
