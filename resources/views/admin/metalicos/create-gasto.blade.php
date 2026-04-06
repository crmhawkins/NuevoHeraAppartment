@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-minus-circle text-danger me-2"></i>
            Crear Nuevo Gasto Metálico
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('metalicos.index') }}">Metálicos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nuevo Gasto</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Session Alerts -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Formulario Principal -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-arrow-down text-danger me-2"></i>
            Información del Gasto Metálico
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('metalicos.storeGasto') }}" method="POST">
            @csrf
            
            <div class="row g-3">
                <!-- Título -->
                <div class="col-12">
                    <label for="titulo" class="form-label fw-semibold">
                        <i class="fas fa-tag text-primary me-1"></i>
                        Título del Gasto
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('titulo') ? 'is-invalid' : '' }}" 
                           name="titulo" 
                           id="titulo"
                           placeholder="Ingrese el título del gasto"
                           value="{{ old('titulo') }}"
                           required>
                    @error('titulo')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Importe -->
                <div class="col-md-6">
                    <label for="importe" class="form-label fw-semibold">
                        <i class="fas fa-euro-sign text-danger me-1"></i>
                        Importe del Gasto
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-danger-subtle text-danger">€</span>
                        <input type="number" 
                               step="0.01" 
                               class="form-control {{ $errors->has('importe') ? 'is-invalid' : '' }}" 
                               name="importe" 
                               id="importe"
                               placeholder="0.00"
                               value="{{ old('importe') }}"
                               required>
                    </div>
                    @error('importe')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Reserva ID -->
                <div class="col-md-6">
                    <label for="reserva_id" class="form-label fw-semibold">
                        <i class="fas fa-calendar-check text-primary me-1"></i>
                        ID de Reserva
                    </label>
                    <input type="number" 
                           class="form-control form-control-lg {{ $errors->has('reserva_id') ? 'is-invalid' : '' }}" 
                           name="reserva_id" 
                           id="reserva_id"
                           placeholder="Ingrese el ID de la reserva"
                           value="{{ old('reserva_id') }}"
                           required>
                    @error('reserva_id')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Fecha de Ingreso -->
                <div class="col-md-6">
                    <label for="fecha_ingreso" class="form-label fw-semibold">
                        <i class="fas fa-calendar text-primary me-1"></i>
                        Fecha del Gasto
                    </label>
                    <input type="date" 
                           class="form-control form-control-lg {{ $errors->has('fecha_ingreso') ? 'is-invalid' : '' }}" 
                           name="fecha_ingreso" 
                           id="fecha_ingreso"
                           value="{{ old('fecha_ingreso', date('Y-m-d')) }}"
                           required>
                    @error('fecha_ingreso')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('metalicos.index') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al Listado
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Crear Gasto
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Establecer fecha actual por defecto si no hay valor
        const fechaInput = document.getElementById('fecha_ingreso');
        if (!fechaInput.value) {
            fechaInput.value = new Date().toISOString().split('T')[0];
        }
    });
</script>
@endsection
