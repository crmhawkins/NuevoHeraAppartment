@extends('layouts.appPersonal')

@section('title', 'Mis Vacaciones')

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Mis Vacaciones</h5>
@endsection

@section('content')
<div class="vacations-dashboard">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="hero-icon">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div class="hero-text">
                <h1 class="hero-title">Mis Vacaciones</h1>
                <p class="hero-subtitle">Gestiona tus solicitudes de vacaciones de forma sencilla</p>
            </div>
        </div>
        <div class="hero-actions">
            <a href="{{ route('holiday.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span>Nueva Solicitud</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $userHolidaysQuantity ? $userHolidaysQuantity->quantity : 0 }}</div>
                <div class="stat-label">Días Disponibles</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $numberOfHolidayPetitions ?? 0 }}</div>
                <div class="stat-label">Pendientes</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $holidays->where('holidays_status_id', 1)->count() }}</div>
                <div class="stat-label">Aprobadas</div>
            </div>
        </div>
    </div>

    <!-- Filtros Compactos -->
    <div class="filters-section">
        <form method="GET" action="{{ route('holiday.index') }}" class="filters-form">
            <div class="filter-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="buscar" placeholder="Buscar solicitudes..." value="{{ request('buscar') }}">
                </div>
                <select name="estado" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="3" {{ request('estado') == '3' ? 'selected' : '' }}>Pendiente</option>
                    <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Aceptada</option>
                    <option value="2" {{ request('estado') == '2' ? 'selected' : '' }}>Denegada</option>
                </select>
                <select name="perPage" onchange="this.form.submit()">
                    <option value="10" {{ request('perPage') == 10 ? 'selected' : '' }}>10 por página</option>
                    <option value="25" {{ request('perPage') == 25 ? 'selected' : '' }}>25 por página</option>
                    <option value="50" {{ request('perPage') == 50 ? 'selected' : '' }}>50 por página</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Lista de Peticiones -->
    @if ($holidays->count())
        <div class="apple-card">
            <div class="apple-card-header">
                <h3 class="apple-title">
                    <i class="fas fa-list me-2"></i>
                    Mis Peticiones de Vacaciones
                </h3>
            </div>
            <div class="apple-card-body">
                <!-- Versión Desktop -->
                <div class="holidays-table desktop-only">
                    <table class="apple-table">
                        <thead>
                            <tr>
                                @foreach ([
                                    'from' => 'Días Pedidos',
                                    'half_day' => 'Medio Día',
                                    'total_days' => 'Total Días',
                                    'holidays_status_id' => 'Estado',
                                    'created_at' => 'Fecha Petición',
                                ] as $field => $label)
                                    <th>
                                        <a href="{{ route('holiday.index', array_merge(request()->all(), ['sortColumn' => $field, 'sortDirection' => request('sortDirection') === 'asc' ? 'desc' : 'asc'])) }}" class="sort-link">
                                            {{ $label }}
                                            @if (request('sortColumn') === $field)
                                                <i class="fas fa-sort-{{ request('sortDirection') === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </a>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($holidays as $holiday)
                                <tr class="holiday-row status-{{ $holiday->holidays_status_id }}">
                                    <td class="date-range">
                                        <i class="fas fa-calendar me-2"></i>
                                        {{ Carbon\Carbon::parse($holiday->from)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($holiday->to)->format('d/m/Y') }}
                                    </td>
                                    <td class="half-day">
                                        @if($holiday->half_day)
                                            <span class="apple-badge apple-badge-success"><i class="fas fa-check me-1"></i>Sí</span>
                                        @else
                                            <span class="apple-badge apple-badge-secondary"><i class="fas fa-times me-1"></i>No</span>
                                        @endif
                                    </td>
                                    <td class="total-days">
                                        <span class="days-number">{{ $holiday->total_days }}</span>
                                    </td>
                                    <td class="status">
                                        @if($holiday->holidays_status_id == 1)
                                            <span class="apple-badge apple-badge-success">Aceptada</span>
                                        @elseif($holiday->holidays_status_id == 2)
                                            <span class="apple-badge apple-badge-danger">Denegada</span>
                                        @elseif($holiday->holidays_status_id == 3)
                                            <span class="apple-badge apple-badge-warning">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="created-date">
                                        <i class="fas fa-clock me-2"></i>
                                        {{ Carbon\Carbon::parse($holiday->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Versión Móvil -->
                <div class="holidays-cards mobile-only">
                    @foreach ($holidays as $holiday)
                        <div class="apple-list-item holiday-card status-{{ $holiday->holidays_status_id }}">
                            <div class="apple-list-item-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="apple-list-item-content">
                                <div class="apple-list-item-title">
                                    {{ Carbon\Carbon::parse($holiday->from)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($holiday->to)->format('d/m/Y') }}
                                </div>
                                <div class="apple-list-item-subtitle">
                                    {{ $holiday->total_days }} días • {{ $holiday->half_day ? 'Medio día' : 'Día completo' }}
                                </div>
                                <div class="apple-list-item-detail">
                                    <i class="fas fa-paper-plane me-1"></i>
                                    {{ Carbon\Carbon::parse($holiday->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <div class="apple-list-item-value">
                                @if($holiday->holidays_status_id == 1)
                                    <span class="apple-badge apple-badge-success">Aceptada</span>
                                @elseif($holiday->holidays_status_id == 2)
                                    <span class="apple-badge apple-badge-danger">Denegada</span>
                                @elseif($holiday->holidays_status_id == 3)
                                    <span class="apple-badge apple-badge-warning">Pendiente</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div class="pagination-section">
            {{ $holidays->appends(request()->all())->links() }}
        </div>

    @else
        <!-- Estado Vacío -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <h3 class="empty-title">No tienes solicitudes de vacaciones</h3>
            <p class="empty-subtitle">Crea tu primera solicitud para comenzar a gestionar tus vacaciones</p>
            <a href="{{ route('holiday.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i>
                <span>Crear Primera Solicitud</span>
            </a>
        </div>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/gestion-buttons.css') }}">
<link rel="stylesheet" href="{{ asset('css/holidays-index.css') }}">
@endpush

@section('scripts')
    @include('partials.toast')
@endsection

