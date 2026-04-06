@extends('layouts.appAdmin')

@section('content')
<!-- Fancybox CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0.27/dist/fancybox.min.css">

<!-- Fancybox JS -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0.27/dist/fancybox.umd.js"></script>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-edit text-primary me-2"></i>
            Editar Reserva: <span class="text-primary">{{ $reserva->codigo_reserva }}</span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reservas.index') }}">Reservas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
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
            <i class="fas fa-calendar-edit text-primary me-2"></i>
            Información de la Reserva
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('reservas.updateReserva', $reserva->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <!-- Apartamento -->
                <div class="col-md-6">
                    <label for="apartamento_id" class="form-label fw-semibold">
                        <i class="fas fa-building text-primary me-1"></i>
                        Apartamento
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('apartamento_id') ? 'is-invalid' : '' }}" name="apartamento_id" id="apartamento_id">
                        <option value="">Seleccione un apartamento</option>
                        @foreach($apartamentos as $apartamento)
                            <option value="{{ $apartamento->id }}"
                                {{ (old('apartamento_id', $reserva->apartamento_id) == $apartamento->id) ? 'selected' : '' }}>
                                {{ $apartamento->titulo }}
                            </option>
                        @endforeach
                    </select>
                    @error('apartamento_id')
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
                        @foreach(['Airbnb', 'Booking', 'Web', 'Presencial'] as $origen)
                            <option value="{{ $origen }}"
                                {{ (old('origen', $reserva->origen) == $origen) ? 'selected' : '' }}>
                                {{ $origen }}
                            </option>
                        @endforeach
                    </select>
                    @error('origen')
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
                    <input type="date" class="form-control form-control-lg" id="fecha_entrada" name="fecha_entrada" value="{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('Y-m-d') }}" required>
                </div>

                <!-- Fecha de Salida -->
                <div class="col-md-6">
                    <label for="fecha_salida" class="form-label fw-semibold">
                        <i class="fas fa-calendar-minus text-primary me-1"></i>
                        Fecha de Salida
                    </label>
                    <input type="date" class="form-control form-control-lg" id="fecha_salida" name="fecha_salida" value="{{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('Y-m-d') }}" required>
                </div>

                <!-- Precio -->
                <div class="col-md-6">
                    <label for="precio" class="form-label fw-semibold">
                        <i class="fas fa-euro-sign text-primary me-1"></i>
                        Precio
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">€</span>
                        <input type="number" step="0.01" class="form-control" id="precio" name="precio" value="{{ $reserva->precio }}" required>
                    </div>
                </div>
            </div>

            <!-- Opciones de Facturación -->
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="no_facturar" name="no_facturar" value="1" 
                               {{ old('no_facturar', $reserva->no_facturar) ? 'checked' : '' }}>
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

            <!-- Botones de Acción -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('reservas.index') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al Listado
                        </a>
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Reserva
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        $('#facturar').on('click', function() {
            let reservaId = $(this).data('reserva-id'); // Obtener el ID de la reserva

            // Confirmación opcional
            if (!confirm('¿Estás seguro de que deseas facturar esta reserva?')) {
                return;
            }

            // Enviar la solicitud POST usando Fetch
            fetch(`{{ route('admin.facturas.facturar') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // Incluye el token CSRF
                },
                body: JSON.stringify({ reserva_id: reservaId }) // Enviar el ID de la reserva
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Factura generada correctamente.');
                    location.reload(); // Recargar la página para actualizar el estado
                } else {
                    alert(data.message || 'Error al generar la factura.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un error al procesar la solicitud.');
            });
        });
    });
</script>

@endsection
