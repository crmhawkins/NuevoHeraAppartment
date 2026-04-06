@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-edit text-primary me-2"></i>
            Editar Banco: <span class="text-primary">{{ $banco->nombre }}</span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.bancos.index') }}">Bancos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Session Alerts -->
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('status') }}
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
            <i class="fas fa-university text-primary me-2"></i>
            Información del Banco
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.bancos.update', $banco->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <!-- Nombre del Banco -->
                <div class="col-12">
                    <label for="nombre" class="form-label fw-semibold">
                        <i class="fas fa-university text-primary me-1"></i>
                        Nombre del Banco
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('nombre') ? 'is-invalid' : '' }}" 
                           name="nombre" 
                           id="nombre"
                           placeholder="Ingrese el nombre del banco"
                           value="{{ old('nombre', $banco->nombre) }}"
                           required>
                    @error('nombre')
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
                        <a href="{{ route('admin.bancos.index') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al Listado
                        </a>
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Banco
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

@endsection

