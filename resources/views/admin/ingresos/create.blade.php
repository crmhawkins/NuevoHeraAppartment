@extends('layouts.appAdmin')

@section('title', 'Crear Nuevo Ingreso')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-plus me-2 text-success"></i>
                Crear Nuevo Ingreso
            </h1>
            <p class="text-muted mb-0">Añade un nuevo ingreso al sistema</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ingresos.index') }}">Ingresos</a></li>
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
                Información del Ingreso
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.ingresos.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
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
                                <option value="{{$estado->id}}" {{ old('estado_id') == $estado->id ? 'selected' : '' }}>{{$estado->nombre}}</option>
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
                                @foreach ($categorias ?? [] as $categoria)
                                    <option value="{{$categoria->id}}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{$categoria->nombre}}</option>
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
                                @foreach ($bancos ?? [] as $banco)
                                    <option value="{{$banco->id}}" {{ old('bank_id') == $banco->id ? 'selected' : '' }}>{{$banco->nombre}}</option>
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
                                   placeholder="Ingrese el título del ingreso" value="{{ old('title') }}">
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
                                       placeholder="0.00" step="0.01" value="{{ old('quantity') }}">
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
                                   value="{{ old('date') }}">
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
                            <input type="file" class="form-control form-control-lg" name="factura_foto" id="factura_foto" style="display: none;">
                            <div class="dropzone border-2 border-dashed border-info rounded-3 p-4 text-center" id="file-upload">
                                <div class="dz-message">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-info mb-3"></i>
                                    <h5 class="text-muted">Arrastra archivos aquí o haz clic para seleccionar</h5>
                                    <p class="text-muted mb-0">Solo se permiten archivos de imagen y PDF</p>
                                </div>
                            </div>
                            @error('factura_foto')
                                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                    <a href="{{ route('admin.ingresos.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver al Listado
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Crear Ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/dropzone@5.7.0/dist/min/dropzone.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.7.0/dist/min/dropzone.min.js"></script>

@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    Dropzone.autoDiscover = false;
    var dataTransfer = new DataTransfer();
    
    var myDropzone = new Dropzone("#file-upload", {
        url: "#",
        autoProcessQueue: false,
        clickable: '#factura_foto',
        maxFiles: 1,
        init: function() {
            this.on("addedfile", function(file) {
                dataTransfer.items.clear();
                dataTransfer.items.add(file);
                document.getElementById('factura_foto').files = dataTransfer.files;
            });
            
            this.on("removedfile", function(file) {
                dataTransfer.items.remove(file);
                document.getElementById('factura_foto').files = dataTransfer.files;
            });
        }
    });
</script>
@endsection
