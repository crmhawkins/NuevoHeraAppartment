@extends('layouts.appAdmin')

@section('title', 'Configurar Revenue · ' . $apartamento->nombre)

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">⚙ Revenue · {{ $apartamento->nombre }}</h2>
        <a href="{{ route('revenue.matriz') }}" class="btn btn-sm btn-outline-secondary">← Matriz</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Settings de pricing --}}
    <div class="card mb-3">
        <div class="card-header">Configuración de pricing</div>
        <div class="card-body">
            <form method="POST" action="{{ route('revenue.updateSettings', $apartamento->id) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Precio mínimo (€)</label>
                        <input type="number" step="0.01" name="revenue_min_precio"
                               class="form-control" value="{{ $apartamento->revenue_min_precio }}"
                               placeholder="ej. 40">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Precio máximo (€)</label>
                        <input type="number" step="0.01" name="revenue_max_precio"
                               class="form-control" value="{{ $apartamento->revenue_max_precio }}"
                               placeholder="ej. 200">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Posicionamiento</label>
                        <select name="revenue_factor_segmento" class="form-select">
                            <option value="premium" @selected($apartamento->revenue_factor_segmento==='premium')>Premium (+10% vs competencia)</option>
                            <option value="match" @selected($apartamento->revenue_factor_segmento==='match')>Match (mismo precio)</option>
                            <option value="budget" @selected($apartamento->revenue_factor_segmento==='budget')>Budget (-10%)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Channex rate_plan_id</label>
                        <input type="text" name="revenue_rate_plan_id"
                               class="form-control" value="{{ $apartamento->revenue_rate_plan_id }}"
                               placeholder="UUID Channex">
                        <small class="text-muted">Necesario para empujar precios</small>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary">Guardar configuración</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Lista de competidores --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between">
            <span>Competidores ({{ $competidores->count() }})</span>
            <small class="text-muted">El scraper visitará estas URLs cada noche</small>
        </div>
        <div class="card-body">
            @if($competidores->isEmpty())
                <div class="text-muted">Aún no hay competidores. Añade abajo URLs de Booking/Airbnb comparables.</div>
            @else
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Plataforma</th>
                            <th>Título</th>
                            <th>URL</th>
                            <th>Último scrape</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($competidores as $c)
                            <tr>
                                <td>
                                    @if($c->plataforma === 'booking')
                                        <span class="badge bg-primary">Booking</span>
                                    @else
                                        <span class="badge bg-danger">Airbnb</span>
                                    @endif
                                </td>
                                <td>{{ $c->titulo ?? '—' }}</td>
                                <td><a href="{{ $c->url }}" target="_blank" class="small">{{ Str::limit($c->url, 50) }}</a></td>
                                <td>
                                    @if($c->ultimo_scrape_at)
                                        <small>{{ $c->ultimo_scrape_at->diffForHumans() }}</small>
                                    @else
                                        <small class="text-muted">nunca</small>
                                    @endif
                                </td>
                                <td>
                                    @if($c->ultimo_error_at && (!$c->ultimo_scrape_at || $c->ultimo_error_at->gt($c->ultimo_scrape_at)))
                                        <span class="badge bg-warning text-dark" title="{{ $c->ultimo_error_msg }}">⚠ error</span>
                                    @else
                                        <span class="badge bg-success">OK</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('revenue.deleteCompetidor', $c->id) }}"
                                          onsubmit="return confirm('¿Eliminar este competidor?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">×</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Añadir competidor --}}
    <div class="card">
        <div class="card-header">Añadir competidor</div>
        <div class="card-body">
            <form method="POST" action="{{ route('revenue.addCompetidor', $apartamento->id) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-2">
                        <label class="form-label">Plataforma</label>
                        <select name="plataforma" class="form-select" required>
                            <option value="booking">Booking</option>
                            <option value="airbnb">Airbnb</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Título (opcional)</label>
                        <input type="text" name="titulo" class="form-control" placeholder="ej. Apt vecino piso 2">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">URL</label>
                        <input type="url" name="url" class="form-control" required
                               placeholder="https://www.booking.com/hotel/... o https://www.airbnb.es/rooms/...">
                    </div>
                </div>
                <div class="mt-2">
                    <textarea name="notas" class="form-control" rows="2" placeholder="Notas (opcional): ej. similar capacidad, mismo barrio..."></textarea>
                </div>
                <div class="mt-3">
                    <button class="btn btn-success">Añadir</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
