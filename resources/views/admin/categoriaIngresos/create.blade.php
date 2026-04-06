@extends('layouts.appAdmin')

@section('title', 'Crear Nueva Categoría de Ingresos')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-plus me-2 text-info"></i>
                Crear Nueva Categoría
            </h1>
            <p class="text-muted mb-0">Añade una nueva categoría para clasificar los ingresos</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.categoriaIngresos.index') }}">Categorías de Ingresos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Crear</li>
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

    <!-- Tarjeta del Formulario -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-edit me-2 text-primary"></i>
                Información de la Categoría
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.categoriaIngresos.store') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="nombre" class="form-label fw-semibold">
                        <i class="fas fa-tag me-2 text-info"></i>Nombre de la Categoría
                    </label>
                    <input type="text" class="form-control form-control-lg" name="nombre" id="nombre" 
                           placeholder="Ingrese el nombre de la categoría" required>
                    @error('nombre')
                        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @enderror
                </div>

                <div class="form-group mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="contabilizar_misma_empresa" 
                               id="contabilizar_misma_empresa" value="1">
                        <label class="form-check-label fw-semibold" for="contabilizar_misma_empresa">
                            <i class="fas fa-building me-2 text-warning"></i>
                            Contabilizar Misma Empresa
                        </label>
                        <small class="form-text text-muted d-block mt-1">
                            Marca esta opción si esta categoría debe contabilizarse por separado (no aparecerá en el dashboard principal)
                        </small>
                    </div>
                    @error('contabilizar_misma_empresa')
                        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @enderror
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                    <a href="{{ route('admin.categoriaIngresos.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver al Listado
                    </a>
                    <button type="submit" class="btn btn-info btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Crear Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')

@endsection

