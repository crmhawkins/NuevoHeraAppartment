@extends('layouts.appAdmin')

@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<!-- Incluir el CSS de Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Incluir Flatpickr y la localización en español -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-plus-circle text-primary me-2"></i>
            Crear Nueva Reserva
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reservas.index') }}">Reservas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Crear</li>
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
            <i class="fas fa-calendar-plus text-primary me-2"></i>
            Información de la Reserva
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('reservas.store') }}" method="POST">
            @csrf
            
            <div class="row g-3">
                <!-- Cliente -->
                <div class="col-md-6">
                    <label for="cliente_id" class="form-label fw-semibold">
                        <i class="fas fa-user text-primary me-1"></i>
                        Cliente
                    </label>
                    <select class="form-select form-select-lg select2 {{ $errors->has('cliente_id') ? 'is-invalid' : '' }}" name="cliente_id" id="cliente_id">
                        <option value="">Seleccione un cliente</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                {{ $cliente->alias }} - {{$cliente->num_identificacion}}
                            </option>
                        @endforeach
                    </select>
                    @error('cliente_id')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Apartamento -->
                <div class="col-md-6">
                    <label for="apartamento_id" class="form-label fw-semibold">
                        <i class="fas fa-building text-primary me-1"></i>
                        Apartamento
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('apartamento_id') ? 'is-invalid' : '' }}" name="apartamento_id" id="apartamento_id">
                        <option value="">Seleccione un apartamento</option>
                        @foreach($apartamentos as $apartamento)
                            <option value="{{ $apartamento->id }}" {{ old('apartamento_id') == $apartamento->id ? 'selected' : '' }}>{{ $apartamento->titulo }}</option>
                        @endforeach
                    </select>
                    @error('apartamento_id')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Tipo de Habitación -->
                <div class="col-md-6">
                    <label for="room_type_id" class="form-label fw-semibold">
                        <i class="fas fa-bed text-primary me-1"></i>
                        Tipo de Habitación
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('room_type_id') ? 'is-invalid' : '' }}" name="room_type_id" id="room_type_id">
                        <option value="">Seleccione un tipo de habitación</option>
                    </select>
                    @error('room_type_id')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Origen -->
                <div class="col-md-6">
                    <label for="origen" class="form-label fw-semibold">
                        <i class="fas fa-globe text-primary me-1"></i>
                        Origen
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('origen') ? 'is-invalid' : '' }}" name="origen" id="origen">
                        <option value="">Seleccione el origen</option>
                        <option value="Airbnb" {{ old('origen') == 'Airbnb' ? 'selected' : '' }}>Airbnb</option>
                        <option value="Booking" {{ old('origen') == 'Booking' ? 'selected' : '' }}>Booking</option>
                        <option value="Web" {{ old('origen') == 'Web' ? 'selected' : '' }}>Web</option>
                        <option value="Presencial" {{ old('origen') == 'Presencial' ? 'selected' : '' }}>Presencial</option>
                    </select>
                    @error('origen')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Estado de la Reserva -->
                <div class="col-md-6">
                    <label for="estado_id" class="form-label fw-semibold">
                        <i class="fas fa-toggle-on text-primary me-1"></i>
                        Estado de la Reserva
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('estado_id') ? 'is-invalid' : '' }}" name="estado_id" id="estado_id">
                        <option value="">Seleccione un estado</option>
                        @foreach($estados as $estado)
                            <option value="{{ $estado->id }}" {{ old('estado_id') == $estado->id ? 'selected' : '' }}>{{ $estado->nombre }}</option>
                        @endforeach
                    </select>
                    @error('estado_id')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Código de Reserva -->
                <div class="col-md-6">
                    <label for="codigo_reserva" class="form-label fw-semibold">
                        <i class="fas fa-barcode text-primary me-1"></i>
                        Código de Reserva
                    </label>
                    <input type="text" class="form-control form-control-lg {{ $errors->has('codigo_reserva') ? 'is-invalid' : '' }}" name="codigo_reserva" value="{{ old('codigo_reserva') }}" placeholder="Ingrese el código de reserva">
                    @error('codigo_reserva')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Fecha de Entrada -->
                <div class="col-md-6">
                    <label for="fecha_entrada" class="form-label fw-semibold">
                        <i class="fas fa-calendar-plus text-primary me-1"></i>
                        Fecha de Entrada
                    </label>
                    <input type="text" class="form-control form-control-lg {{ $errors->has('fecha_entrada') ? 'is-invalid' : '' }}" id="fecha_entrada" name="fecha_entrada" value="{{ old('fecha_entrada') }}" placeholder="dd/mm/yyyy" required>
                    @error('fecha_entrada')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Fecha de Salida -->
                <div class="col-md-6">
                    <label for="fecha_salida" class="form-label fw-semibold">
                        <i class="fas fa-calendar-minus text-primary me-1"></i>
                        Fecha de Salida
                    </label>
                    <input type="text" class="form-control form-control-lg {{ $errors->has('fecha_salida') ? 'is-invalid' : '' }}" id="fecha_salida" name="fecha_salida" value="{{ old('fecha_salida') }}" placeholder="dd/mm/yyyy" required>
                    @error('fecha_salida')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Precio -->
                <div class="col-md-6">
                    <label for="precio" class="form-label fw-semibold">
                        <i class="fas fa-euro-sign text-primary me-1"></i>
                        Precio
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">€</span>
                        <input type="text" class="form-control {{ $errors->has('precio') ? 'is-invalid' : '' }}" name="precio" value="{{ old('precio') }}" placeholder="0.00">
                    </div>
                    @error('precio')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Información para Plataforma del Estado -->
            <div class="row g-3 mt-4">
                <div class="col-12">
                    <h6 class="text-primary mb-3 fw-semibold">
                        <i class="fas fa-government me-2"></i>Plataforma del Estado
                    </h6>
                    <p class="text-muted small mb-3">Información adicional requerida para la subida de viajeros.</p>
                </div>
                
                <!-- Referencia del Contrato -->
                <div class="col-md-6">
                    <label for="referencia_contrato" class="form-label fw-semibold">
                        <i class="fas fa-file-contract text-primary me-1"></i>
                        Referencia del Contrato
                    </label>
                    <input type="text" class="form-control form-control-lg {{ $errors->has('referencia_contrato') ? 'is-invalid' : '' }}" 
                           name="referencia_contrato" id="referencia_contrato" value="{{ old('referencia_contrato') }}" 
                           placeholder="Referencia del contrato">
                    @error('referencia_contrato')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Fecha del Contrato -->
                <div class="col-md-6">
                    <label for="fecha_contrato" class="form-label fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i>
                        Fecha del Contrato
                    </label>
                    <input type="date" class="form-control form-control-lg {{ $errors->has('fecha_contrato') ? 'is-invalid' : '' }}" 
                           name="fecha_contrato" id="fecha_contrato" value="{{ old('fecha_contrato') }}">
                    @error('fecha_contrato')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Fecha y Hora de Entrada -->
                <div class="col-md-6">
                    <label for="fecha_hora_entrada" class="form-label fw-semibold">
                        <i class="fas fa-sign-in-alt text-primary me-1"></i>
                        Fecha y Hora de Entrada
                    </label>
                    <input type="datetime-local" class="form-control form-control-lg {{ $errors->has('fecha_hora_entrada') ? 'is-invalid' : '' }}" 
                           name="fecha_hora_entrada" id="fecha_hora_entrada" value="{{ old('fecha_hora_entrada') }}">
                    @error('fecha_hora_entrada')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Fecha y Hora de Salida -->
                <div class="col-md-6">
                    <label for="fecha_hora_salida" class="form-label fw-semibold">
                        <i class="fas fa-sign-out-alt text-primary me-1"></i>
                        Fecha y Hora de Salida
                    </label>
                    <input type="datetime-local" class="form-control form-control-lg {{ $errors->has('fecha_hora_salida') ? 'is-invalid' : '' }}" 
                           name="fecha_hora_salida" id="fecha_hora_salida" value="{{ old('fecha_hora_salida') }}">
                    @error('fecha_hora_salida')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Número de Habitaciones -->
                <div class="col-md-6">
                    <label for="numero_habitaciones" class="form-label fw-semibold">
                        <i class="fas fa-bed text-primary me-1"></i>
                        Número de Habitaciones
                    </label>
                    <input type="number" class="form-control form-control-lg {{ $errors->has('numero_habitaciones') ? 'is-invalid' : '' }}" 
                           name="numero_habitaciones" id="numero_habitaciones" value="{{ old('numero_habitaciones') }}" 
                           min="1" placeholder="1">
                    @error('numero_habitaciones')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Conexión a Internet -->
                <div class="col-md-6">
                    <label for="conexion_internet" class="form-label fw-semibold">
                        <i class="fas fa-wifi text-primary me-1"></i>
                        Conexión a Internet
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('conexion_internet') ? 'is-invalid' : '' }}" 
                            name="conexion_internet" id="conexion_internet">
                        <option value="1" {{ old('conexion_internet', '1') == '1' ? 'selected' : '' }}>Sí, disponible</option>
                        <option value="0" {{ old('conexion_internet') == '0' ? 'selected' : '' }}>No disponible</option>
                    </select>
                    @error('conexion_internet')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Opciones de Facturación -->
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="no_facturar" name="no_facturar" value="1" 
                               {{ old('no_facturar') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="no_facturar">
                            <i class="fas fa-ban text-warning me-2"></i>
                            No facturar automáticamente
                        </label>
                        <div class="form-text text-muted">
                            Marca esta opción si esta reserva no debe ser facturada por el sistema automático.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Checkboxes -->
                <div class="col-12">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input form-check-input-lg {{ $errors->has('verificado') ? 'is-invalid' : '' }}" type="checkbox" name="verificado" id="verificado" value="1" {{ old('verificado') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="verificado">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Verificado
                                </label>
                                @error('verificado')
                                    <div class="alert alert-danger alert-dismissible fade show mt-2">
                                        <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input form-check-input-lg {{ $errors->has('dni_entregado') ? 'is-invalid' : '' }}" type="checkbox" name="dni_entregado" id="dni_entregado" value="1" {{ old('dni_entregado') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="dni_entregado">
                                    <i class="fas fa-id-card text-info me-1"></i>
                                    DNI Entregado
                                </label>
                                @error('dni_entregado')
                                    <div class="alert alert-danger alert-dismissible fade show mt-2">
                                        <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input form-check-input-lg {{ $errors->has('enviado_webpol') ? 'is-invalid' : '' }}" type="checkbox" name="enviado_webpol" id="enviado_webpol" value="1" {{ old('enviado_webpol') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="enviado_webpol">
                                    <i class="fas fa-paper-plane text-warning me-1"></i>
                                    Enviado Webpol
                                </label>
                                @error('enviado_webpol')
                                    <div class="alert alert-danger alert-dismissible fade show mt-2">
                                        <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('reservas.index') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al Listado
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Crear Reserva
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('#apartamento_id').change(function () {
            var apartamentoId = $(this).val();
            $('#room_type_id').html('<option value="">Cargando...</option>'); // Mensaje temporal

            if (apartamentoId) {
                $.ajax({
                    url: '/get-room-types/' + apartamentoId,
                    type: 'GET',
                    dataType: 'json',
                    success: function (data) {
                        $('#room_type_id').html('<option value="">Seleccione un tipo de habitación</option>');
                        $.each(data, function (key, value) {
                            $('#room_type_id').append('<option value="' + value.id + '">' + value.title + '</option>');
                        });
                    }
                });
            } else {
                $('#room_type_id').html('<option value="">Seleccione un apartamento primero</option>');
            }
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Inicializar Select2 para el campo cliente
        $('#cliente_id').select2({
            placeholder: "Seleccione un cliente",
            allowClear: true,
            width: '100%' // Asegura que el select ocupa el ancho completo del contenedor
        });
    });
</script>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Flatpickr en los campos de fecha con localización en español
        flatpickr("#fecha_entrada", {
            dateFormat: "Y-m-d",
            locale: "es", // Configurar el idioma español usando "es"
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('fecha_entrada').value = dateStr; // Actualizar el valor del input
            }
        });

        flatpickr("#fecha_salida", {
            dateFormat: "Y-m-d",
            locale: "es", // Configurar el idioma español usando "es"
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('fecha_salida').value = dateStr; // Actualizar el valor del input
            }
        });
    });
    </script>
@endsection
