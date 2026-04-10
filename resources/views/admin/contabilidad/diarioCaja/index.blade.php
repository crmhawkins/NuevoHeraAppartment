@extends('layouts.appAdmin')

@section('title', 'Diario de Caja')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-book me-2 text-primary"></i>
                Diario de Caja
            </h1>
            <p class="text-muted mb-0">Gestiona los movimientos contables y financieros</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Diario de Caja</li>
            </ol>
        </nav>
    </div>

    <!-- Barra de Sincronizacion y Accesos Rapidos -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #007AFF, #0056CC);">
                            <i class="fas fa-sync-alt text-white"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Ultima sincronizacion bancaria</small>
                            <span class="fw-semibold">
                                @if($ultimaSync)
                                    {{ $ultimaSync->fecha_sync->format('d/m/Y H:i') }}
                                    <span class="badge bg-success-subtle text-success ms-1">OK</span>
                                @else
                                    <span class="text-muted">Sin sincronizaciones</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <a href="{{ route('admin.ingresos.index') }}" class="btn btn-outline-success btn-sm me-2">
                        <i class="fas fa-arrow-up me-1"></i>Ver Ingresos
                    </a>
                    <a href="{{ route('admin.gastos.index') }}" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-arrow-down me-1"></i>Ver Gastos
                    </a>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalSubirExcel">
                        <i class="fas fa-file-excel me-1"></i>Subir Excel Bankinter
                    </button>
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-robot me-1"></i>Automatico: 08:00 y 12:00
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-filter me-2 text-primary"></i>
                Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.diarioCaja.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="start_date" class="form-label fw-semibold">Fecha Inicio</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label fw-semibold">Fecha Fin</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="estado_id" class="form-label fw-semibold">Estado</label>
                        <select name="estado_id" class="form-select">
                            <option value="">Todos los Estados</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado->id }}" {{ request('estado_id') == $estado->id ? 'selected' : '' }}>{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="cuenta_id" class="form-label fw-semibold">Cuenta</label>
                        <select name="cuenta_id" class="form-select">
                            <option value="">Todas las Cuentas</option>
                            @foreach ($cuentas as $cuenta)
                                <option value="{{ $cuenta->id }}" {{ request('cuenta_id') == $cuenta->id ? 'selected' : '' }}>{{ $cuenta->numero .' - '. $cuenta->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="concepto" class="form-label fw-semibold">Concepto</label>
                        <input type="text" name="concepto" class="form-control" value="{{ request('concepto') }}" placeholder="Buscar concepto...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 me-2">
                            <i class="fas fa-search me-2"></i>Filtrar
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-success w-100" onclick="generarInforme()">
                            <i class="fas fa-robot me-2"></i>Generar Informe
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Movimientos -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-table me-2 text-primary"></i>
                Movimientos del Diario
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="cuentas" class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold text-dark">
                                <i class="fas fa-hashtag me-2 text-primary"></i>Asiento
                            </th>
                            <th class="fw-semibold text-dark" style="min-width: 180px;">
                                <i class="fas fa-calendar me-2 text-info"></i>Fecha
                            </th>
                            <th class="fw-semibold text-dark">
                                <i class="fas fa-file-text me-2 text-success"></i>Concepto
                            </th>
                            <th class="fw-semibold text-dark">
                                <i class="fas fa-credit-card me-2 text-warning"></i>Forma de Pago
                            </th>
                            <th class="fw-semibold text-dark text-end">
                                <i class="fas fa-arrow-down me-2 text-danger"></i>Debe
                            </th>
                            <th class="fw-semibold text-dark text-end">
                                <i class="fas fa-arrow-up me-2 text-success"></i>Haber
                            </th>
                            <th class="fw-semibold text-dark text-end">
                                <i class="fas fa-balance-scale me-2 text-primary"></i>Saldo
                            </th>
                            <th class="fw-semibold text-dark text-center">
                                <i class="fas fa-cogs me-2 text-secondary"></i>Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Saldo Inicial -->
                      {{--   <tr class="table-info">
                            <td colspan="4" class="fw-semibold">
                                <i class="fas fa-coins me-2 text-warning"></i>Saldo Inicial
                            </td>
                            <td class="text-end fw-bold">{{ number_format($saldoInicial, 2) }} €</td>
                            <td></td>
                            <td class="text-end fw-bold">{{ number_format($saldoInicial, 2) }} €</td>
                            <td></td>
                        </tr> --}}
                        
                        @if (count($response) > 0)
                            @foreach ($response as $linea)
                            <tr>
                                <td class="align-middle">
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $linea->asiento_contable }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <i class="fas fa-calendar-day me-2 text-muted"></i>
                                    {{ $linea->date }}
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-file-text text-muted"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $linea->concepto }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-info-subtle text-info">
                                        {{ $linea->forma_pago }}
                                    </span>
                                </td>
                                <td class="text-end align-middle">
                                    @if($linea->debe !== null)
                                        <span class="text-danger fw-semibold">
                                            {{ number_format($linea->debe, 2) }} €
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end align-middle">
                                    @if($linea->haber > 0)
                                        <span class="text-success fw-semibold">
                                            {{ number_format($linea->haber, 2) }} €
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end align-middle">
                                    <span class="badge bg-primary-subtle text-primary fs-6">
                                        {{ number_format($linea->saldo, 2) }} €
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group" role="group">
                                        @if ($linea->tipo == 'ingreso')
                                            <a href="{{ route('admin.ingresos.edit', $linea->ingreso_id) }}" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @elseif ($linea->tipo == 'gasto')
                                            <a href="{{ route('admin.gastos.edit', $linea->gasto_id) }}" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('admin.diarioCaja.destroyDiarioCaja', $linea->id) }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-outline-danger btn-sm delete-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="fas fa-book fa-4x text-muted"></i>
                                    </div>
                                    <h4 class="text-muted mb-3">No hay movimientos registrados</h4>
                                    <p class="text-muted mb-0">No se encontraron movimientos para los filtros seleccionados.</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
      // Verificar si SweetAlert2 está definido
      if (typeof Swal === 'undefined') {
          console.error('SweetAlert2 is not loaded');
          return;
      }
      const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
      const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

      // Botones de eliminar
      const deleteButtons = document.querySelectorAll('.delete-btn');
      deleteButtons.forEach(button => {
          button.addEventListener('click', function (event) {
              event.preventDefault();
              const form = this.closest('form');
              Swal.fire({
                  title: '¿Estás seguro?',
                  text: "¡No podrás revertir esto!",
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#3085d6',
                  cancelButtonColor: '#d33',
                  confirmButtonText: 'Sí, eliminar!',
                  cancelButtonText: 'Cancelar'
              }).then((result) => {
                  if (result.isConfirmed) {
                      form.submit();
                  }
              });
          });
      });
  });

      // Función para generar informe AI
      function generarInforme() {
          const fechaInicio = document.querySelector('input[name="start_date"]').value;
          const fechaFin = document.querySelector('input[name="end_date"]').value;
          
          console.log('Fechas seleccionadas:', fechaInicio, fechaFin);
          
          if (!fechaInicio || !fechaFin) {
              Swal.fire({
                  icon: 'warning',
                  title: 'Fechas requeridas',
                  text: 'Por favor, selecciona fecha de inicio y fecha de fin para generar el informe.',
                  confirmButtonText: 'Entendido'
              });
              return;
          }
      
      if (new Date(fechaInicio) > new Date(fechaFin)) {
          Swal.fire({
              icon: 'error',
              title: 'Fechas inválidas',
              text: 'La fecha de inicio no puede ser posterior a la fecha de fin.',
              confirmButtonText: 'Entendido'
          });
          return;
      }
      
      Swal.fire({
          title: 'Generando informe...',
          text: 'Esto puede tomar unos momentos',
          icon: 'info',
          allowOutsideClick: false,
          showConfirmButton: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });
      
      // Usar fetch para hacer la petición AJAX
      fetch('{{ route("informe.ai.generar") }}', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
          },
          body: JSON.stringify({
              fecha_inicio: fechaInicio,
              fecha_fin: fechaFin
          })
      })
      .then(response => {
          if (!response.ok) {
              throw new Error('Error en la respuesta del servidor: ' + response.status);
          }
          return response.json();
      })
      .then(data => {
          if (data.success && data.redirect_url) {
              // Abrir el informe en nueva pestaña
              window.open(data.redirect_url, '_blank');
              Swal.fire({
                  icon: 'success',
                  title: '¡Informe generado!',
                  text: 'El informe se ha abierto en una nueva pestaña',
                  timer: 3000,
                  showConfirmButton: false
              });
          } else {
              throw new Error(data.error || 'Error desconocido');
          }
      })
      .catch(error => {
          console.error('Error:', error);
          Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error al generar el informe: ' + error.message,
              confirmButtonText: 'Entendido'
          });
      });
  }

  // --- Subir Excel Bankinter manual ---
  function subirExcelBankinter() {
      var form = document.getElementById('formSubirExcel');
      var fileInput = document.getElementById('excelBankinterFile');
      if (!fileInput.files.length) {
          Swal.fire({ icon: 'warning', title: 'Selecciona un archivo', text: 'Elige un Excel (.xlsx) descargado de Bankinter' });
          return;
      }
      var formData = new FormData(form);
      var btn = document.getElementById('btnSubirExcel');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importando...';

      fetch('{{ route("admin.diarioCaja.importarExcelBankinter") }}', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
          body: formData
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-upload me-1"></i>Importar';
          if (data.success) {
              var msg = 'Procesados: ' + (data.procesados || 0) +
                  '\nDuplicados: ' + (data.duplicados || 0) +
                  '\nIngresos: ' + (data.ingresos_creados || 0) +
                  '\nGastos: ' + (data.gastos_creados || 0) +
                  '\nErrores: ' + (data.errores || 0);
              Swal.fire({ icon: 'success', title: 'Importacion completada', text: msg, confirmButtonText: 'Aceptar' })
                  .then(function() { location.reload(); });
          } else {
              Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Error desconocido', confirmButtonText: 'Entendido' });
          }
      })
      .catch(function(e) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-upload me-1"></i>Importar';
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error: ' + e.message });
      });
  }
</script>

<!-- Modal Subir Excel Bankinter -->
<div class="modal fade" id="modalSubirExcel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-excel me-2 text-success"></i>Subir Excel de Bankinter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSubirExcel" enctype="multipart/form-data">
                <div class="modal-body">
                    <p class="text-muted mb-3">Sube un archivo Excel (.xlsx) descargado manualmente desde Bankinter. Los movimientos duplicados se saltan automaticamente.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Archivo Excel</label>
                        <input type="file" class="form-control" id="excelBankinterFile" name="file" accept=".xls,.xlsx" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cuenta</label>
                        <select class="form-select" name="cuenta_alias" required>
                            <option value="helen">Helen (HAWKINS REAL STATE SL)</option>
                        </select>
                        <small class="text-muted">Selecciona la cuenta asociada al Excel</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSubirExcel" onclick="subirExcelBankinter()">
                        <i class="fas fa-upload me-1"></i>Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@endsection
