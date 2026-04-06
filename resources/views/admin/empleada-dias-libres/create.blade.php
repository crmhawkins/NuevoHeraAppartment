@extends('layouts.appAdmin')

@section('title', 'Configurar Días Libres - ' . $empleadaHorario->user->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Configurar Días Libres - {{ $empleadaHorario->user->name }}
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.empleada-dias-libres.index', $empleadaHorario) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Calendario
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Información de la semana -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h5 class="alert-heading">
                                    <i class="fas fa-calendar-week me-2"></i>
                                    Configurando Días Libres
                                </h5>
                                <p class="mb-1">
                                    <strong>Empleada:</strong> {{ $empleadaHorario->user->name }}<br>
                                    <strong>Semana:</strong> {{ $semanaInicio->format('d/m/Y') }} - {{ $semanaInicio->copy()->endOfWeek()->format('d/m/Y') }}<br>
                                    <strong>Jornada:</strong> {{ $empleadaHorario->horas_contratadas_dia }} horas/día
                                </p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.empleada-dias-libres.store', $empleadaHorario) }}" method="POST">
                        @csrf
                        <input type="hidden" name="semana_inicio" value="{{ $semanaInicio->format('Y-m-d') }}">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Seleccionar Días Libres</h5>
                                <p class="text-muted">Marca los días que {{ $empleadaHorario->user->name }} tendrá libres esta semana:</p>
                                
                                <div class="row">
                                    @php
                                        $diasSemana = [
                                            ['num' => 0, 'nombre' => 'Domingo', 'fecha' => $semanaInicio->copy()->addDays(6)],
                                            ['num' => 1, 'nombre' => 'Lunes', 'fecha' => $semanaInicio->copy()],
                                            ['num' => 2, 'nombre' => 'Martes', 'fecha' => $semanaInicio->copy()->addDay()],
                                            ['num' => 3, 'nombre' => 'Miércoles', 'fecha' => $semanaInicio->copy()->addDays(2)],
                                            ['num' => 4, 'nombre' => 'Jueves', 'fecha' => $semanaInicio->copy()->addDays(3)],
                                            ['num' => 5, 'nombre' => 'Viernes', 'fecha' => $semanaInicio->copy()->addDays(4)],
                                            ['num' => 6, 'nombre' => 'Sábado', 'fecha' => $semanaInicio->copy()->addDays(5)]
                                        ];
                                        $diasSeleccionados = $diasLibres ? $diasLibres->dias_libres : [];
                                    @endphp
                                    
                                    @foreach($diasSemana as $dia)
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="card {{ in_array($dia['num'], $diasSeleccionados) ? 'border-primary bg-light' : '' }}">
                                                <div class="card-body text-center">
                                                    <h6 class="card-title">{{ $dia['nombre'] }}</h6>
                                                    <p class="card-text text-muted small">{{ $dia['fecha']->format('d/m') }}</p>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="dia_{{ $dia['num'] }}" 
                                                               name="dias_libres[]" 
                                                               value="{{ $dia['num'] }}"
                                                               {{ in_array($dia['num'], $diasSeleccionados) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="dia_{{ $dia['num'] }}">
                                                            {{ in_array($dia['num'], $diasSeleccionados) ? 'Libre' : 'Trabaja' }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h5>Información Adicional</h5>
                                
                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones:</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="4" 
                                              placeholder="Motivo de los días libres, vacaciones, etc...">{{ $diasLibres->observaciones ?? '' }}</textarea>
                                    <small class="form-text text-muted">Opcional: Explica el motivo de los días libres</small>
                                </div>
                                
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Resumen</h6>
                                        <p class="mb-1"><strong>Días de trabajo:</strong> <span id="dias-trabajo">5</span></p>
                                        <p class="mb-1"><strong>Días libres:</strong> <span id="dias-libres">2</span></p>
                                        <p class="mb-0"><strong>Horas totales:</strong> <span id="horas-totales">{{ $empleadaHorario->horas_contratadas_dia * 5 }}</span>h</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.empleada-dias-libres.index', $empleadaHorario) }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Guardar Configuración
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.border-primary {
    border-color: #007bff !important;
    background-color: #e3f2fd !important;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.form-check-input:checked + .form-check-label {
    color: #28a745;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="dias_libres[]"]');
    const diasTrabajoSpan = document.getElementById('dias-trabajo');
    const diasLibresSpan = document.getElementById('dias-libres');
    const horasTotalesSpan = document.getElementById('horas-totales');
    const horasPorDia = {{ $empleadaHorario->horas_contratadas_dia }};
    
    function actualizarResumen() {
        const diasSeleccionados = Array.from(checkboxes).filter(cb => cb.checked).length;
        const diasTrabajo = 7 - diasSeleccionados;
        const horasTotales = diasTrabajo * horasPorDia;
        
        diasTrabajoSpan.textContent = diasTrabajo;
        diasLibresSpan.textContent = diasSeleccionados;
        horasTotalesSpan.textContent = horasTotales;
        
        // Actualizar etiquetas
        checkboxes.forEach(checkbox => {
            const label = checkbox.nextElementSibling;
            label.textContent = checkbox.checked ? 'Libre' : 'Trabaja';
        });
        
        // Actualizar estilos de las tarjetas
        checkboxes.forEach(checkbox => {
            const card = checkbox.closest('.card');
            if (checkbox.checked) {
                card.classList.add('border-primary', 'bg-light');
            } else {
                card.classList.remove('border-primary', 'bg-light');
            }
        });
    }
    
    // Agregar event listeners
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', actualizarResumen);
        
        // Hacer clic en la tarjeta para cambiar el checkbox
        const card = checkbox.closest('.card');
        card.addEventListener('click', function(e) {
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
                actualizarResumen();
            }
        });
    });
    
    // Actualizar resumen inicial
    actualizarResumen();
});
</script>
@endsection
