@extends('layouts.appAdmin')

@section('title', 'Editar Estado del Diario')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit me-2 text-warning"></i>
                Editar Estado del Diario
            </h1>
            <p class="text-muted mb-0">Modifica la información del estado: <strong>{{ $estado->nombre }}</strong></p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.estadosDiario.index') }}">Estados del Diario</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar Estado</li>
            </ol>
        </nav>
    </div>

    <!-- Alertas de Sesión -->
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Formulario de Edición -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-edit me-2 text-primary"></i>
                Información del Estado
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.estadosDiario.update', $estado->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-4">
                            <label for="nombre" class="form-label fw-semibold">
                                <i class="fas fa-tag me-2 text-success"></i>
                                Nombre del Estado
                            </label>
                            <input type="text" class="form-control form-control-lg" name="nombre" 
                                   value="{{ $estado->nombre }}" 
                                   placeholder="Ej: Pendiente, Aprobado, Rechazado..." required>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-info"></i>
                                Define un nombre descriptivo para el estado contable
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones del Formulario -->
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-warning btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Actualizar Estado
                    </button>
                    <a href="{{ route('admin.estadosDiario.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver al Listado
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
