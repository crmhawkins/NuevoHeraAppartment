@extends('layouts.appAdmin')

@section('title', 'Editar Ingreso')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit me-2 text-warning"></i>
                Editar Ingreso #{{ $ingreso->id }}
            </h1>
            <p class="text-muted mb-0">Modifica la información del ingreso seleccionado</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ingresos.index') }}">Ingresos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
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
                Información del Ingreso
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.ingresos.update', $ingreso->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    <!-- Estado -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="estado_id" class="form-label fw-semibold">
                                <i class="fas fa-tag me-2 text-warning"></i>Estado
                            </label>
                            <select name="estado_id" id="estado_id" class="form-select form-select-lg">
                                <option value="">Seleccione un estado</option>
                                @foreach ($estados as $estado)
                                    <option value="{{$estado->id}}" {{ $ingreso->estado_id == $estado->id ? 'selected' : '' }}>{{$estado->nombre}}</option>
                                @endforeach
                            </select>
                            @error('estado_id')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Categoría -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="categoria_id" class="form-label fw-semibold">
                                <i class="fas fa-folder me-2 text-info"></i>Categoría
                            </label>
                            <select name="categoria_id" id="categoria_id" class="form-select form-select-lg">
                                <option value="">Seleccione una categoría</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{$categoria->id}}" {{ $ingreso->categoria_id == $categoria->id ? 'selected' : '' }}>{{$categoria->nombre}}</option>
                                @endforeach
                            </select>
                            @error('categoria_id')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Banco -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_id" class="form-label fw-semibold">
                                <i class="fas fa-university me-2 text-primary"></i>Banco
                            </label>
                            <select name="bank_id" id="bank_id" class="form-select form-select-lg">
                                <option value="">Seleccione un banco</option>
                                @foreach ($bancos as $banco)
                                    <option value="{{$banco->id}}" {{ $ingreso->bank_id == $banco->id ? 'selected' : '' }}>{{$banco->nombre}}</option>
                                @endforeach
                            </select>
                            @error('bank_id')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Título -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title" class="form-label fw-semibold">
                                <i class="fas fa-file-text me-2 text-primary"></i>Título
                            </label>
                            <input type="text" class="form-control form-control-lg" name="title" id="title" 
                                   placeholder="Ingrese el título del ingreso" value="{{ old('title', $ingreso->title) }}">
                            @error('title')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Importe -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="quantity" class="form-label fw-semibold">
                                <i class="fas fa-euro-sign me-2 text-success"></i>Importe
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-success-subtle text-success">
                                    <i class="fas fa-euro-sign"></i>
                                </span>
                                <input type="number" class="form-control" name="quantity" id="quantity" 
                                       placeholder="0.00" step="0.01" value="{{ old('quantity', $ingreso->quantity) }}">
                            </div>
                            @error('quantity')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Fecha -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date" class="form-label fw-semibold">
                                <i class="fas fa-calendar me-2 text-success"></i>Fecha del Ingreso
                            </label>
                            <input type="date" class="form-control form-control-lg" name="date" id="date" 
                                   value="{{ old('date', isset($ingreso->date) ? \Carbon\Carbon::parse($ingreso->date)->format('Y-m-d') : null) }}">
                            @error('date')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- Archivo -->
                    <div class="col-12">
                        <div class="form-group">
                            <label for="factura_foto" class="form-label fw-semibold">
                                <i class="fas fa-upload me-2 text-info"></i>Subir Archivo
                            </label>
                            <input type="file" class="form-control form-control-lg" name="factura_foto" id="factura_foto">
                            @error('factura_foto')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror

                            <!-- Botón de descarga si existe archivo -->
                            @if ($ingreso->factura_foto)
                                <div class="mt-3">
                                    <a href="{{ route('ingresos.download', $ingreso->id) }}" 
                                       class="btn btn-outline-info btn-lg">
                                        <i class="fas fa-download me-2"></i>
                                        Descargar Archivo Actual
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                    <a href="{{ route('admin.ingresos.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver al Listado
                    </a>
                    <button type="submit" class="btn btn-warning btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Actualizar Ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
