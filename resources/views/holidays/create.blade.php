@extends('layouts.appPersonal')

@section('title', 'Crear Petición de Vacaciones')

@section('content')
<div class="holiday-create-container">
    <!-- Header de la Página -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div class="header-text">
                <h1>Crear Petición de Vacaciones</h1>
                <p>Solicita tus días de vacaciones</p>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-section">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('holiday.index') }}" class="breadcrumb-link">
                        <i class="fas fa-arrow-left"></i>
                        Mis Vacaciones
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-plus"></i>
                    Nueva Petición
                </li>
            </ol>
        </nav>
    </div>

    @if ($userHolidaysQuantity != null)
        <!-- Información de Vacaciones Disponibles -->
        <div class="holiday-info-section">
            <div class="info-cards">
                <div class="info-card primary">
                    <div class="card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-number">{{ $userHolidaysQuantity->quantity ?? 0 }}</div>
                        <div class="card-label">Días Disponibles</div>
                    </div>
                </div>

                <div class="info-card warning">
                    <div class="card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-number">{{ $numberOfHolidayPetitions ?? 0 }}</div>
                        <div class="card-label">Peticiones Pendientes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estados de Vacaciones -->
        <div class="status-section">
            <h3 class="section-title">
                <i class="fas fa-info-circle"></i>
                Estados de las Peticiones
            </h3>
            <div class="status-legend">
                <div class="status-item">
                    <div class="status-color pending"></div>
                    <span>Pendiente</span>
                </div>
                <div class="status-item">
                    <div class="status-color approved"></div>
                    <span>Aceptada</span>
                </div>
                <div class="status-item">
                    <div class="status-color denied"></div>
                    <span>Denegada</span>
                </div>
            </div>
        </div>

        <!-- Formulario de Petición -->
        <div class="form-section">
            <div class="form-header">
                <h3 class="section-title">
                    <i class="fas fa-edit"></i>
                    Nueva Petición de Vacaciones
                </h3>
            </div>

            <form method="POST" action="{{ route('holiday.store') }}" enctype="multipart/form-data" class="holiday-form">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label for="from_date" class="form-label">
                            <i class="fas fa-calendar"></i>
                            Fecha de Inicio
                        </label>
                        <input type="date" name="from_date" class="form-control" id="from_date" required />
                    </div>

                    <div class="form-group">
                        <label for="to_date" class="form-label">
                            <i class="fas fa-calendar"></i>
                            Fecha de Fin
                        </label>
                        <input type="date" name="to_date" class="form-control" id="to_date" required />
                    </div>

                    <div class="form-group checkbox-group">
                        <div class="checkbox-wrapper">
                            <input class="form-check-input" type="checkbox" id="half_day" name="half_day" value="1">
                            <label class="form-check-label" for="half_day">
                                <i class="fas fa-clock"></i>
                                Incluir medio día
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        <span>Enviar Petición</span>
                    </button>
                </div>
            </form>
        </div>

    @else
        <!-- Estado Vacío -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>No tienes días de vacaciones disponibles</h3>
            <p>Contacta con administración para configurar tus días de vacaciones</p>
            <a href="{{ route('holiday.index') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Volver a Mis Vacaciones</span>
            </a>
        </div>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/holiday-create.css') }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fromDate = document.getElementById('from_date');
    const toDate = document.getElementById('to_date');
    
    if (fromDate && toDate) {
        fromDate.addEventListener('change', function() {
            toDate.min = this.value;
            if (toDate.value && toDate.value < this.value) {
                toDate.value = this.value;
            }
        });
        
        toDate.addEventListener('change', function() {
            fromDate.max = this.value;
            if (fromDate.value && fromDate.value > this.value) {
                fromDate.value = this.value;
            }
        });
    }
});
</script>
@endpush

@section('scripts')
    @include('partials.toast')
@endsection
