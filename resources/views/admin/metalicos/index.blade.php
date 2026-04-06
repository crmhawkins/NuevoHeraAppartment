@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-coins text-primary me-2"></i>
            Gestión de Metálicos
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Metálicos</li>
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

<!-- Tarjeta de Acciones -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-tools text-primary me-2"></i>
                Acciones
            </h5>
            <div class="btn-group" role="group">
                <a href="{{ route('metalicos.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Metálico
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta de Saldo Inicial -->
{{-- <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                <i class="fas fa-wallet text-success"></i>
            </div>
            <div>
                <h5 class="card-title mb-0">
                    <i class="fas fa-coins text-success me-2"></i>
                    Saldo Inicial
                </h5>
                <p class="text-muted mb-0">Saldo base de la caja metálica</p>
            </div>
            <div class="ms-auto">
                <h3 class="text-success mb-0">{{ number_format($saldoInicial, 2) }} €</h3>
            </div>
        </div>
    </div>
</div> --}}

<!-- Tarjeta Principal -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Lista de Movimientos Metálicos
        </h5>
    </div>
    <div class="card-body p-0">
        @if($response->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="border-0">
                                <i class="fas fa-hashtag text-primary me-1"></i>ID
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-tag text-primary me-1"></i>Título
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-euro-sign text-primary me-1"></i>Importe
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-exchange-alt text-primary me-1"></i>Tipo
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-calendar text-primary me-1"></i>Fecha Ingreso
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-calculator text-primary me-1"></i>Saldo Acumulado
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-cogs text-primary me-1"></i>Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($response as $metalico)
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">#{{ $metalico->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-coins text-info"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $metalico->titulo }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold {{ $metalico->tipo === 'ingreso' ? 'text-success' : 'text-danger' }}">
                                        {{ $metalico->tipo === 'ingreso' ? '+' : '-' }}{{ number_format($metalico->importe, 2) }} €
                                    </span>
                                </td>
                                <td>
                                    @if($metalico->tipo === 'ingreso')
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-arrow-up me-1"></i>Ingreso
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="fas fa-arrow-down me-1"></i>Gasto
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($metalico->fecha_ingreso)->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">{{ number_format($metalico->saldo, 2) }} €</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                      <a href="{{ route('metalicos.show', $metalico) }}" class="btn btn-outline-info btn-sm" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                      </a>
                                      <a href="{{ route('metalicos.edit', $metalico) }}" class="btn btn-outline-warning btn-sm" title="Editar metálico">
                                        <i class="fas fa-edit"></i>
                                      </a>
                                  
                                      {{-- Borrrado: confirm nativo + upgrade a SweetAlert si está disponible --}}
                                      <form action="{{ route('metalicos.destroy', $metalico) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return window.handleDelete(this, '{{ addslashes($metalico->titulo) }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar metálico">
                                          <i class="fas fa-trash"></i>
                                        </button>
                                      </form>
                                    </div>
                                </td>                       
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay movimientos metálicos</h5>
                <p class="text-muted">No se encontraron movimientos en la caja metálica.</p>
            </div>
        @endif
    </div>
</div>

<script>
    window.handleDelete = function(form, titulo){
      // Si existe SweetAlert2, úsalo
      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({
          title: '¿Eliminar Metálico?',
          html: `
            <div class="text-start">
              <p><strong>Metálico:</strong> ${titulo || ''}</p>
              <p class="text-danger mt-3"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
          `,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, Eliminar',
          cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
          customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary' },
          buttonsStyling: false
        }).then(res => { if (res.isConfirmed) form.submit(); });
        return false; // siempre cancelamos el submit inicial para esperar la respuesta
      }
  
      // Fallback 100% fiable
      return confirm(`¿Estás seguro de que quieres eliminar el metálico "${titulo || ''}"?\n\nEsta acción no se puede deshacer.`);
    }
  </script>
  
@endsection

@include('sweetalert::alert')


