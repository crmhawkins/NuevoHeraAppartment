@extends('layouts.appAdmin')

@section('content')
@php
    use Carbon\Carbon;

    // Rango del mes según tu variable $date ("YYYY-MM")
    $startOfMonth = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
    $endOfMonth   = $startOfMonth->copy()->endOfMonth();
    $daysInMonth  = $startOfMonth->daysInMonth;

    // Diseño
    $dayWidth     = 80;     // px por día
    $sidebarWidth = 200;    // px para la columna de apartamentos
@endphp

<style>
/* Prevenir scroll vertical en padres, usar el del navegador */
.container, .container-fluid, .content, .card-body {
  overflow-y: visible !important;
  height: auto !important;
}

/* Corregir z-index del modal backdrop para que esté por encima de todo */
.modal-backdrop {
  z-index: 999998 !important;
}

/* Asegurar que el modal esté por encima del backdrop */
.modal {
  z-index: 999999 !important;
}

/* Asegurar que el listado de apartamentos no esté por encima del modal */
.gantt-row-label {
  z-index: 1 !important;
}

.gantt-header {
  z-index: 2 !important;
}

/* .gantt-container:
   solo scroll horizontal, sin altura fija */
.gantt-container {
  width: 100%;
  border: 1px solid #ccc;
  background: #fff;
  overflow-x: auto;
  overflow-y: visible;
  box-sizing: border-box;
  position: relative;
  margin-bottom: 1rem;
}

/* Cabecera días (sticky) */
.gantt-header {
  position: sticky;
  top: 0;
  left: 0;
  z-index: 10;
  width: {{ $sidebarWidth + $daysInMonth * $dayWidth }}px;
  background: #fbfbfb;
  border-bottom: 2px solid #ccc;
  display: flex;       /* para poner la spacer y los días en una misma línea */
  align-items: center;
  box-sizing: border-box;
  padding: 8px 0;      /* algo de espacio vertical */
}

/* Espacio para alinear con la columna de aptos */
.header-sidebar-spacer {
  width: {{ $sidebarWidth }}px;
  border-right: 1px solid #ddd;
  box-sizing: border-box;
}

/* Día en la cabecera */
.gantt-header-day {
  width: {{ $dayWidth }}px;
  border-right: 1px solid #ddd;
  flex-shrink: 0;
  text-align: center;
  font-weight: bold;
  box-sizing: border-box;
  padding: 0;
    font-size: 12px;
}

/* FILA de apartamento:
   usamos flex en una sola línea: [label][days] */
.gantt-row {
  width: {{ $sidebarWidth + $daysInMonth * $dayWidth }}px;
  border-bottom: 1px solid #ddd;
  box-sizing: border-box;
  position: relative;
  display: flex;           /* un contenedor flex */
  flex-direction: row;     /* horizontal */
  align-items: stretch;    /* ambos subcontenedores misma altura de fila */
  /* margin: 0; min-height: algo si deseas */
}

/* Columna del apartamento:
   ancho fijo, sticky */
.gantt-row-label {
  position: sticky;
  left: 0;
  z-index: 2;
  width: {{ $sidebarWidth }}px;
  background: #fafafa;
  border-right: 1px solid #ddd;
  padding: 8px;
  box-sizing: border-box;
  white-space: nowrap;
  font-weight: bold;
  display: flex;
  align-items: center;
  z-index: 5000;
  font-size: 11px;
}

/* Contenedor de días:
   Ocupa el resto, con ancho = daysInMonth * dayWidth.
   No salte a la siguiente línea, pues es un flex item. */
.gantt-row-days {
  position: relative;
  box-sizing: border-box;
  width: {{ $daysInMonth * $dayWidth }}px;
  /* sin "margin-left" ahora, pues lo ubicamos con flex */
}

/* Línea vertical (1 por día), cubre toda la altura de la fila */
.gantt-day-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #d4d4d4;
  z-index: 0;
}

/* Cada reserva (badge) */
.reserva-badge {
  position: absolute;
  height: 20px;
  background-color: #57628e;
  color: #fff;
  border-radius: 15px;
  font-size: 12px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;

  /* Centrado vertical en la .gantt-row-days.
     Por ejemplo, top: 8px para no pisar la línea de arriba.
     O top: 50% => transform => se centrará.
     Depende de la altura total que adoptes.
     Usa margin si prefieres */
  top: 50%;
  transform: translateY(-50%);

  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
  padding: 0 10px;
}
</style>

<div class="container-fluid">
  <!-- Botonera mes -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.tablaReservas.index') }}?date={{ $startOfMonth->copy()->subMonth()->format('Y-m') }}" class="btn btn-primary">
        Mes Anterior
      </a>
    <h3 class="text-uppercase">{{ $startOfMonth->translatedFormat('F') }} {{ $startOfMonth->year }}</h3>
    <a href="{{ route('admin.tablaReservas.index') }}?date={{ $startOfMonth->copy()->addMonth()->format('Y-m') }}" class="btn btn-primary">
        Mes Siguiente
      </a>
  </div>

  <!-- Botón para cargar precios -->
  <div class="mb-3">
    <button id="loadPricesBtn" class="btn btn-primary">
      <i class="fas fa-euro-sign"></i> Cargar Precios del Mes
    </button>
    <button id="testPricesBtn" class="btn btn-secondary ms-2">
      <i class="fas fa-test"></i> Probar Visualización
    </button>
    <button id="debugBtn" class="btn btn-warning ms-2">
      <i class="fas fa-bug"></i> Debug DOM
    </button>
    <span id="pricesStatus" class="ms-2 text-muted"></span>
  </div>

  <div class="gantt-container">
    <!-- Cabecera -->
    <div class="gantt-header">
      <div class="header-sidebar-spacer"></div>
      @for($d=1; $d<=$daysInMonth; $d++)
        <div class="gantt-header-day">
          Día {{ $d }}
        </div>
      @endfor
    </div>

    @php
    // Lista de apartamentos a excluir
    $apartamentosExcluir = ['16', '17','18','19','20','23','22']; // O usa los IDs
    @endphp
    {{-- {{dd($apartamentos[1])}} --}}
    <!-- Filas de apartamentos (flex) -->
    @foreach($apartamentos as $apt)


        @php
            // Comprobar si el apartamento está en la lista de exclusión
            if (in_array($apt->id, $apartamentosExcluir)) {
                    continue; // Excluir apartamento
            }
            $colors = [
                '1' => '#FF5733',  // Rojo
                '2' => '#33FF57',  // Verde
                '3' => '#3357FF',  // Azul
                '4' => '#FF33A1',  // Rosa
                '5' => '#FFD700',  // Amarillo
                '6' => '#7B68EE',  // Azul morado
                '7' => '#FF6347',  // Tomate
                '8' => '#00FA9A',  // Verde mar
                '9' => '#8A2BE2',  // Azul violeta
                '10' => '#FF1493', // Rosa profundo
                '11' => '#00BFFF', // Azul profundo
                '12' => '#32CD32', // Verde lima
                '13' => '#FF8C00', // Naranja oscuro
                '14' => '#D2691E', // Chocolate
                '15' => '#A52A2A', // Marrón
                '21' => '#5F9EA0', // Azul pálido
            ];

            $color = $colors[$apt->id] ?? '#';  // Color por defecto si no se encuentra
        @endphp

      <div class="gantt-row">
        <!-- Etiqueta apto (sticky) -->
        <div class="gantt-row-label" id="apt{{ $apt->id }}">
          {{ $apt->titulo }}
        </div>

        <!-- Días -->
        <div class="gantt-row-days">
          <!-- Líneas verticales -->
          @for($d=1; $d<=$daysInMonth; $d++)
            @php
              $leftPos = ($d-1) * $dayWidth;
            @endphp
            <div class="gantt-day-line" style="left: {{ $leftPos }}px;"></div>
          @endfor

          <!-- Reservas -->
          @foreach($apt->reservas as $reserva)
            @php
              // Compara las fechas completas (no solo el día)
              $startFecha = $reserva->fecha_entrada;
              $endFecha   = $reserva->fecha_salida;

              // Asegúrate de que las fechas están dentro del rango
              if ($startFecha->lt($startOfMonth) && $endFecha->lt($startOfMonth)) {
                  continue; // Si la reserva empieza antes de este mes y termina antes, la excluimos
              }

              if ($startFecha->gt($endOfMonth) && $endFecha->gt($endOfMonth)) {
                  continue; // Si la reserva empieza después de este mes y termina después, la excluimos
              }

              // Ajusta las fechas si es necesario
              $startFecha = max($startFecha, $startOfMonth); // Si la entrada es antes del inicio del mes, ajusta al inicio
              $endFecha = min($endFecha, $endOfMonth); // Si la salida es después del final del mes, ajusta al final

              $startDay = $startFecha->day;
              $endDay   = $endFecha->day;

              $pxPerHour = $dayWidth / 24.0;
              $startOffsetH = (($startDay - 1) * 24) + 12;
              $endOffsetH = (($endDay - 1) * 24) + 12;

              $durationH = $endOffsetH - $startOffsetH;
              if ($durationH < 0) continue;

              $leftPx = $startOffsetH * $pxPerHour;
              $widthPx = $durationH * $pxPerHour;

              // gap de 8px total
              $gap = 8;
              $adjLeft = $leftPx + $gap / 2;
              $adjWidth = max(0, $widthPx - $gap);

              // flecha
              $arrow = '';
              if ($endFecha->gt($endOfMonth)) {
                  $arrow = ' →';
              }
              $badgeText = ($reserva->cliente->nombre ?? $reserva->cliente->alias) . $arrow;
            @endphp

            <div class="reserva-badge"
                 style="left:{{ $adjLeft }}px; width:{{ $adjWidth }}px;background-color: {{ $color }}"
                 title="Reserva #{{ $reserva->id }}"
            >
              <span id="badgeText">
                {{ $badgeText }}
              </span>
            </div>
          @endforeach
        </div><!-- .gantt-row-days -->
      </div><!-- .gantt-row -->
    @endforeach
  </div><!-- .gantt-container -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
      var badgeText = document.querySelectorAll('#badgeText');
      console.log(badgeText);
      for (var i = 0; i < badgeText.length; i++) {
        if (badgeText[i].innerText.length > 6) {
          badgeText[i].innerText = badgeText[i].innerText.substring(0, 6) + '...';
        }
      }
    });
</script>

<!-- Modal ARI para Actualizar Disponibilidad y Tarifas -->
<div class="modal fade" id="modalARI" tabindex="-1" aria-labelledby="modalARILabel" aria-hidden="true" style="z-index: 999999;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalARILabel">
                    <i class="fas fa-edit me-2"></i>Actualizar Disponibilidad y Tarifas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <form id="ariForm" action="{{ route('ari.updateRates') }}" method="POST">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="ari_property_id" class="form-label fw-bold">Apartamento:</label>
                            <select id="ari_property_id" name="updates[0][property_id]" class="form-select" required>
                                <option value="">Seleccione un apartamento</option>
                                @foreach($apartamentos as $apartamento)
                                    @if($apartamento->id_channex)
                                        <option value="{{ $apartamento->id_channex }}">{{ $apartamento->titulo }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ari_room_type_id" class="form-label fw-bold">Tipo de Habitación:</label>
                            <select id="ari_room_type_id" name="updates[0][room_type_id]" class="form-select" required>
                                <option value="">Seleccione un tipo de habitación</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="ari_rate_plan_id" class="form-label fw-bold">Rate Plan:</label>
                            <select id="ari_rate_plan_id" name="updates[0][rate_plan_id]" class="form-select">
                                <option value="">Seleccione un Rate Plan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="ari_date_from" class="form-label fw-bold">Fecha Inicio:</label>
                            <input type="date" id="ari_date_from" name="updates[0][date_from]" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label for="ari_date_to" class="form-label fw-bold">Fecha Fin:</label>
                            <input type="date" id="ari_date_to" name="updates[0][date_to]" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="updates[0][exclude_weekends]" value="0">
                                <input type="checkbox" id="ari_exclude_weekends" name="updates[0][exclude_weekends]" class="form-check-input" value="1">
                                <label class="form-check-label" for="ari_exclude_weekends">
                                    Excluir Sábados y Domingos
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="hidden" name="updates[0][only_weekends]" value="0">
                                <input type="checkbox" id="ari_only_weekends" name="updates[0][only_weekends]" class="form-check-input" value="1">
                                <label class="form-check-label" for="ari_only_weekends">
                                    Solo Fines de Semana
                                </label>
                                <select id="ari_weekend_days" name="updates[0][weekend_days]" class="form-select mt-2" style="display: none;">
                                    <option value="both" selected>Ambos días</option>
                                    <option value="saturday">Solo Sábado</option>
                                    <option value="sunday">Solo Domingo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="ari_update_type" class="form-label fw-bold">Configuración:</label>
                            <select id="ari_update_type" name="updates[0][update_type]" class="form-select">
                                <option value="availability">Básica</option>
                                <option value="restrictions">Avanzada</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="ari_availability_section">
                            <label class="form-label fw-bold">Estancia Disponible:</label>
                            <div class="form-check">
                                <input type="radio" name="updates[0][value]" value="1" class="form-check-input" checked>
                                <label class="form-check-label">Sí</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="updates[0][value]" value="0" class="form-check-input">
                                <label class="form-check-label">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4" id="ari_restrictions_section" style="display: none;">
                        <div class="col-md-12">
                            <h6 class="mb-3">Restricciones y Tarifas:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="ari_rate" class="form-label">Precio:</label>
                                    <input type="number" id="ari_rate" name="updates[0][rate]" class="form-control" placeholder="Ej: 5000 para 50.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="ari_min_stay" class="form-label">Min Estancia:</label>
                                    <input type="number" id="ari_min_stay" name="updates[0][min_stay]" class="form-control" placeholder="Min Estancia">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="ari_min_stay_arrival" class="form-label">Min Estancia Llegada:</label>
                                    <input type="number" id="ari_min_stay_arrival" name="updates[0][min_stay_arrival]" class="form-control" placeholder="Min Estancia Llegada">
                                </div>
                                <div class="col-md-6">
                                    <label for="ari_min_stay_through" class="form-label">Min Estancia Salida:</label>
                                    <input type="number" id="ari_min_stay_through" name="updates[0][min_stay_through]" class="form-control" placeholder="Min Estancia Salida">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="ari_max_stay" class="form-label">Max Estancia:</label>
                                    <input type="number" id="ari_max_stay" name="updates[0][max_stay]" class="form-control" placeholder="Max Estancia">
                                </div>
                                <div class="col-md-6">
                                    <label for="ari_stop_sell" class="form-label">Cerrado a la Venta:</label>
                                    <div class="form-check">
                                        <input type="radio" name="updates[0][stop_sell]" value="1" class="form-check-input">
                                        <label class="form-check-label">Sí</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="updates[0][stop_sell]" value="0" class="form-check-input" checked>
                                        <label class="form-check-label">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="ari_closed_to_arrival" class="form-label">Cerrado a Llegadas:</label>
                                    <div class="form-check">
                                        <input type="radio" name="updates[0][closed_to_arrival]" value="1" class="form-check-input">
                                        <label class="form-check-label">Sí</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="updates[0][closed_to_arrival]" value="0" class="form-check-input" checked>
                                        <label class="form-check-label">No</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="ari_closed_to_departure" class="form-label">Cerrado a Salidas:</label>
                                    <div class="form-check">
                                        <input type="radio" name="updates[0][closed_to_departure]" value="1" class="form-check-input">
                                        <label class="form-check-label">Sí</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="updates[0][closed_to_departure]" value="0" class="form-check-input" checked>
                                        <label class="form-check-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales para el modal ARI
    let selectedApartamento = null;
    let selectedFecha = null;
    
    // Función para abrir el modal ARI
    window.openARIModal = function(element) {
        const apartamentoId = element.dataset.apartamentoId;
        const apartamentoNombre = element.dataset.apartamentoNombre;
        const apartamentoChannex = element.dataset.apartamentoChannex;
        const fecha = element.dataset.fecha;
        const roomTypeId = element.dataset.roomTypeId;
        
        // Guardar datos seleccionados
        selectedApartamento = {
            id: apartamentoId,
            nombre: apartamentoNombre,
            id_channex: apartamentoChannex,
            room_type_id: roomTypeId
        };
        selectedFecha = fecha;
        
        // Pre-llenar el formulario
        document.getElementById('ari_property_id').value = apartamentoChannex;
        document.getElementById('ari_date_from').value = fecha;
        
        // Cargar tipos de habitación
        loadRoomTypes(apartamentoChannex);
        
        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('modalARI'));
        modal.show();
    };
    
    // Función para cargar tipos de habitación
    function loadRoomTypes(propertyId) {
        if (!propertyId) return;
        
        fetch(`/channex/ari/room-types/${propertyId}`)
            .then(response => response.json())
            .then(data => {
                const roomTypeSelect = document.getElementById('ari_room_type_id');
                roomTypeSelect.innerHTML = '<option value="">Seleccione un tipo de habitación</option>';
                
                data.forEach(roomType => {
                    const option = document.createElement('option');
                    option.value = roomType.id_channex;
                    option.textContent = roomType.title;
                    roomTypeSelect.appendChild(option);
                });
                
                // Seleccionar el primer tipo de habitación por defecto
                if (data.length > 0) {
                    roomTypeSelect.value = data[0].id_channex;
                    loadRatePlans(propertyId, data[0].id_channex);
                }
            })
            .catch(error => {
                console.error('Error al cargar tipos de habitación:', error);
            });
    }
    
    // Función para cargar rate plans
    function loadRatePlans(propertyId, roomTypeId) {
        if (!propertyId || !roomTypeId) return;
        
        fetch(`/channex/rate-plans/${propertyId}/${roomTypeId}`)
            .then(response => response.json())
            .then(data => {
                const ratePlanSelect = document.getElementById('ari_rate_plan_id');
                ratePlanSelect.innerHTML = '<option value="">Seleccione un Rate Plan</option>';
                
                data.forEach(ratePlan => {
                    const option = document.createElement('option');
                    option.value = ratePlan.id_channex;
                    option.textContent = ratePlan.title;
                    ratePlanSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error al cargar rate plans:', error);
            });
    }
    
    // Event listeners para el modal ARI
    document.getElementById('ari_property_id').addEventListener('change', function() {
        const propertyId = this.value;
        if (propertyId) {
            loadRoomTypes(propertyId);
        }
    });
    
    // Event listener para cargar rate plans cuando se selecciona un room type
    document.getElementById('ari_room_type_id').addEventListener('change', function() {
        const propertyId = document.getElementById('ari_property_id').value;
        const roomTypeId = this.value;
        if (propertyId && roomTypeId) {
            loadRatePlans(propertyId, roomTypeId);
        }
    });
    
    document.getElementById('ari_update_type').addEventListener('change', function() {
        const updateType = this.value;
        const availabilitySection = document.getElementById('ari_availability_section');
        const restrictionsSection = document.getElementById('ari_restrictions_section');
        
        if (updateType === 'availability') {
            availabilitySection.style.display = 'block';
            restrictionsSection.style.display = 'none';
        } else {
            availabilitySection.style.display = 'none';
            restrictionsSection.style.display = 'block';
        }
    });
    
    // Manejo de checkboxes de fines de semana
    document.getElementById('ari_exclude_weekends').addEventListener('change', function() {
        const onlyWeekendsCheckbox = document.getElementById('ari_only_weekends');
        const weekendDaysSelect = document.getElementById('ari_weekend_days');
        
        if (this.checked) {
            onlyWeekendsCheckbox.checked = false;
            onlyWeekendsCheckbox.disabled = true;
            weekendDaysSelect.style.display = 'none';
            weekendDaysSelect.value = 'both';
        } else {
            onlyWeekendsCheckbox.disabled = false;
        }
    });
    
    document.getElementById('ari_only_weekends').addEventListener('change', function() {
        const weekendDaysSelect = document.getElementById('ari_weekend_days');
        if (this.checked) {
            weekendDaysSelect.style.display = 'block';
        } else {
            weekendDaysSelect.style.display = 'none';
            weekendDaysSelect.value = 'both';
        }
    });
    
    // Función para abrir modal ARI al hacer clic en celdas libres
    function setupARIModal() {
        // Seleccionar todas las filas de apartamentos
        const rows = document.querySelectorAll('.gantt-row');
        
        rows.forEach(row => {
            const rowDays = row.querySelector('.gantt-row-days');
            const apartamentoLabel = row.querySelector('.gantt-row-label');
            const apartamentoId = apartamentoLabel.id.replace('apt', '');
            const apartamentoNombre = apartamentoLabel.textContent.trim();
            
            // Obtener el id_channex del apartamento desde los datos de la vista
            const apartamentoData = @json($apartamentos->keyBy('id')->map(function($apt) { return $apt->id_channex; }));
            const apartamentoChannexId = apartamentoData[apartamentoId];
            
            rowDays.addEventListener('click', function(e) {
                // Verificar que no se hizo clic en una reserva
                if (e.target.closest('.reserva-badge')) {
                    return; // No hacer nada si se hizo clic en una reserva
                }
                
                // Calcular la posición del clic relativa al contenedor
                const rect = this.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                
                // Calcular qué día corresponde al clic (basado en el ancho de día)
                const dayWidth = 80; // Debe coincidir con la variable PHP $dayWidth
                const dayIndex = Math.floor(clickX / dayWidth);
                
                // Verificar que el clic está dentro del rango válido de días
                if (dayIndex < 0 || dayIndex >= {{ $daysInMonth }}) {
                    return; // No hacer nada si está fuera del rango
                }
                
                // Obtener la fecha correspondiente
                const currentMonth = '{{ $date }}'; // Obtener del PHP
                const year = currentMonth.split('-')[0];
                const month = currentMonth.split('-')[1];
                const day = dayIndex + 1;
                
                // Formatear la fecha
                const date = `${year}-${month.padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                
                // Pre-llenar las fechas en el modal
                document.getElementById('ari_date_from').value = date;
                document.getElementById('ari_date_to').value = date;
                
                // Seleccionar el apartamento correspondiente
                const propertySelect = document.getElementById('ari_property_id');
                propertySelect.value = apartamentoChannexId;
                
                // Cargar los room types para este apartamento
                if (apartamentoChannexId) {
                    loadRoomTypes(apartamentoChannexId);
                }
                
                // Abrir el modal
                const modal = new bootstrap.Modal(document.getElementById('modalARI'));
                modal.show();
                
                console.log('Modal abierto para:', {
                    apartamento: apartamentoNombre,
                    apartamentoId: apartamentoId,
                    apartamentoChannexId: apartamentoChannexId,
                    fecha: date,
                    posicion: dayIndex
                });
            });
        });
    }
    
    // Ejecutar después de que la tabla se haya renderizado
    setTimeout(setupARIModal, 1000);
    
    // Función para cargar precios del mes
    function loadPricesForMonth() {
        const statusElement = document.getElementById('pricesStatus');
        const loadBtn = document.getElementById('loadPricesBtn');
        
        console.log('🔍 Iniciando carga de precios REALES de Channex...');
        statusElement.textContent = 'Cargando precios de Channex...';
        loadBtn.disabled = true;
        
        const dateFrom = '{{ $startOfMonth->format("Y-m-d") }}';
        const dateTo = '{{ $endOfMonth->format("Y-m-d") }}';
        
        console.log('📅 Fechas de búsqueda:', { dateFrom, dateTo });
        
        fetch('/channex/ari/all-daily-prices', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                date_from: dateFrom,
                date_to: dateTo
            })
        })
        .then(response => {
            console.log('📡 Status de respuesta:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Respuesta completa de Channex:', data);
            if (data.success) {
                console.log('✅ Datos de precios obtenidos:', data.data);
                if (Object.keys(data.data).length === 0) {
                    console.warn('⚠️ No se encontraron precios en Channex');
                    statusElement.textContent = 'No se encontraron precios en Channex';
                    statusElement.className = 'ms-2 text-warning';
                    alert('No se encontraron precios en Channex. Verifica la configuración de la API.');
                } else {
                    displayPrices(data.data);
                    statusElement.textContent = 'Precios reales cargados correctamente';
                    statusElement.className = 'ms-2 text-success';
                }
            } else {
                throw new Error(data.message || 'Error al cargar precios');
            }
        })
        .catch(error => {
            console.error('💥 Error en la petición:', error);
            statusElement.textContent = 'Error al cargar precios: ' + error.message;
            statusElement.className = 'ms-2 text-danger';
        })
        .finally(() => {
            loadBtn.disabled = false;
        });
    }
    
    // Función para mostrar precios en la tabla
    function displayPrices(pricesData) {
        console.log('Datos de precios recibidos:', pricesData);
        
        // Limpiar precios anteriores
        document.querySelectorAll('.price-display').forEach(el => el.remove());
        
        // Agrupar precios por apartamento y fecha
        const pricesByApt = {};
        Object.values(pricesData).forEach(price => {
            const aptId = price.apartamento_id;
            if (!pricesByApt[aptId]) {
                pricesByApt[aptId] = {};
            }
            pricesByApt[aptId][price.date] = price;
        });
        
        console.log('Precios agrupados por apartamento:', pricesByApt);
        
        // Mostrar precios en cada celda
        Object.keys(pricesByApt).forEach(aptId => {
            const aptRow = document.getElementById(`apt${aptId}`);
            console.log(`Buscando apartamento ${aptId}:`, aptRow);
            
            if (aptRow) {
                const ganttRow = aptRow.closest('.gantt-row');
                const rowDays = ganttRow.querySelector('.gantt-row-days');
                console.log(`Fila de días para apartamento ${aptId}:`, rowDays);
                
                Object.keys(pricesByApt[aptId]).forEach(date => {
                    const price = pricesByApt[aptId][date];
                    const dayIndex = new Date(date).getDate() - 1;
                    const leftPos = dayIndex * 80; // dayWidth
                    
                    console.log(`Mostrando precio para día ${date} (índice ${dayIndex}):`, price);
                    
                    // Crear elemento de precio
                    const priceElement = document.createElement('div');
                    priceElement.className = 'price-display';
                    
                    // Determinar estilo basado en disponibilidad
                    const isAvailable = price.available !== false;
                    const hasPrice = price.rate !== null && price.rate !== undefined;
                    
                    let backgroundColor = 'rgba(255, 255, 255, 0.95)';
                    let borderColor = '#007bff';
                    let textColor = '#007bff';
                    let displayText = 'N/A';
                    
                    if (hasPrice && isAvailable) {
                        displayText = `${price.rate}€`;
                        borderColor = '#28a745';
                        textColor = '#28a745';
                    } else if (!isAvailable) {
                        displayText = 'Ocupado';
                        backgroundColor = 'rgba(220, 53, 69, 0.1)';
                        borderColor = '#dc3545';
                        textColor = '#dc3545';
                    } else if (!hasPrice) {
                        displayText = 'Sin precio';
                        borderColor = '#ffc107';
                        textColor = '#856404';
                    }
                    
                    priceElement.style.cssText = `
                        position: absolute;
                        left: ${leftPos + 2}px;
                        top: 2px;
                        background: ${backgroundColor};
                        border: 1px solid ${borderColor};
                        border-radius: 3px;
                        padding: 2px 4px;
                        font-size: 11px;
                        font-weight: bold;
                        color: #007bff;
                        z-index: 10;
                        pointer-events: none;
                        min-width: 30px;
                        text-align: center;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                    `;
                    
                    if (price.rate) {
                        priceElement.textContent = `${price.rate}€`;
                        priceElement.style.color = '#28a745';
                        priceElement.style.borderColor = '#28a745';
                    } else {
                        priceElement.textContent = 'N/A';
                        priceElement.style.color = '#6c757d';
                        priceElement.style.borderColor = '#6c757d';
                    }
                    
                    rowDays.appendChild(priceElement);
                    console.log(`Precio añadido para apartamento ${aptId}, día ${date}`);
                });
            } else {
                console.log(`No se encontró el apartamento con ID: ${aptId}`);
            }
        });
    }
    
    // Función para probar la visualización con datos de ejemplo
    function testPriceDisplay() {
        const testData = {
            '1_2025-09-01': {
                apartamento_id: 1,
                apartamento_nombre: 'Apartamento Atico',
                date: '2025-09-01',
                rate: 150,
                currency: 'EUR'
            },
            '1_2025-09-02': {
                apartamento_id: 1,
                apartamento_nombre: 'Apartamento Atico',
                date: '2025-09-02',
                rate: 160,
                currency: 'EUR'
            },
            '2_2025-09-01': {
                apartamento_id: 2,
                apartamento_nombre: 'Apartamentos 2A',
                date: '2025-09-01',
                rate: 120,
                currency: 'EUR'
            }
        };
        
        console.log('Probando visualización con datos de ejemplo:', testData);
        displayPrices(testData);
        document.getElementById('pricesStatus').textContent = 'Prueba de visualización completada';
        document.getElementById('pricesStatus').className = 'ms-2 text-info';
    }
    
    // Función para debug del DOM
    function debugDOM() {
        console.log('=== DEBUG DOM ===');
        
        // Verificar que los botones existen
        console.log('Botón cargar precios:', document.getElementById('loadPricesBtn'));
        console.log('Botón test:', document.getElementById('testPricesBtn'));
        console.log('Botón debug:', document.getElementById('debugBtn'));
        
        // Verificar apartamentos
        const apartamentos = document.querySelectorAll('.gantt-row-label');
        console.log('Apartamentos encontrados:', apartamentos.length);
        apartamentos.forEach((apt, index) => {
            console.log(`Apartamento ${index + 1}:`, apt.id, apt.textContent);
        });
        
        // Verificar filas de días
        const filasDias = document.querySelectorAll('.gantt-row-days');
        console.log('Filas de días encontradas:', filasDias.length);
        
        // Verificar estructura de la primera fila
        if (filasDias.length > 0) {
            const primeraFila = filasDias[0];
            console.log('Primera fila de días:', primeraFila);
            console.log('Posición de la primera fila:', primeraFila.getBoundingClientRect());
        }
        
        console.log('=== FIN DEBUG ===');
    }
    
    // Event listeners
    document.getElementById('loadPricesBtn').addEventListener('click', loadPricesForMonth);
    document.getElementById('testPricesBtn').addEventListener('click', testPriceDisplay);
    document.getElementById('debugBtn').addEventListener('click', debugDOM);
    
    // Función para cargar room types
    function loadRoomTypes(propertyId) {
        fetch(`/channex/ari/room-types/${propertyId}`)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('ari_room_type_id');
                select.innerHTML = '<option value="">Seleccionar tipo de habitación</option>';
                
                data.forEach(roomType => {
                    const option = document.createElement('option');
                    option.value = roomType.id_channex;
                    option.textContent = roomType.title;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error cargando room types:', error);
            });
    }
    
    // Función para cargar rate plans
    function loadRatePlans(propertyId, roomTypeId) {
        fetch(`/channex/rate-plans/${propertyId}/${roomTypeId}`)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('ari_rate_plan_id');
                select.innerHTML = '<option value="">Seleccionar rate plan</option>';
                
                data.forEach(ratePlan => {
                    const option = document.createElement('option');
                    option.value = ratePlan.id_channex;
                    option.textContent = ratePlan.title;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error cargando rate plans:', error);
            });
    }
    
    // Manejo del envío del formulario
    document.getElementById('ariForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Mostrar indicador de carga
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Actualizando...';
        submitBtn.disabled = true;
        
        // Enviar formulario
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                alert('Actualización realizada con éxito');
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalARI'));
                modal.hide();
                
                // Recargar la página para mostrar los cambios
                location.reload();
            } else {
                throw new Error(data.message || 'Error en la actualización');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al realizar la actualización: ' + error.message);
        })
        .finally(() => {
            // Restaurar botón
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });
});
</script>

@endsection
