@extends('layouts.appAdmin')

@section('title', 'Editar Horario de Empleada')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-user-clock me-2"></i>Editar Horario de Empleada
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.empleada-horarios.show', $empleadaHorario) }}" class="btn btn-secondary">
                            <i class="fas fa-eye me-1"></i>Ver
                        </a>
                        <a href="{{ route('admin.empleada-horarios.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <form action="{{ route('admin.empleada-horarios.update', $empleadaHorario) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Información de la Empleada</h5>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Empleada:</label>
                                    <input type="text" class="form-control" value="{{ $empleadaHorario->user->name }}" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email:</label>
                                    <input type="email" class="form-control" value="{{ $empleadaHorario->user->email }}" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                                               {{ $empleadaHorario->activo ? 'checked' : '' }}>
                                        <input type="hidden" name="activo" value="0">
                                        <label class="form-check-label fw-bold" for="activo">
                                            Estado Activo
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Configuración del Horario</h5>
                                
                                <div class="mb-3">
                                    <label for="horas_contratadas_dia" class="form-label fw-bold">Horas Contratadas por Día:</label>
                                    <select class="form-select" id="horas_contratadas_dia" name="horas_contratadas_dia" required>
                                        <option value="4" {{ $empleadaHorario->horas_contratadas_dia == 4 ? 'selected' : '' }}>4 horas</option>
                                        <option value="6" {{ $empleadaHorario->horas_contratadas_dia == 6 ? 'selected' : '' }}>6 horas</option>
                                        <option value="8" {{ $empleadaHorario->horas_contratadas_dia == 8 ? 'selected' : '' }}>8 horas</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="dias_libres_semana" class="form-label fw-bold">Días Libres por Semana:</label>
                                    <input type="number" class="form-control" id="dias_libres_semana" name="dias_libres_semana" 
                                           value="{{ $empleadaHorario->dias_libres_semana }}" min="0" max="7" required>
                                    <small class="form-text text-muted">Número de días libres por semana (0-7)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Configurar Días Libres para Esta Semana:</label>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Selecciona los días específicos que esta empleada tendrá libres esta semana.
                                        <br><small>Semana actual: {{ now()->startOfWeek()->format('d/m/Y') }} - {{ now()->endOfWeek()->format('d/m/Y') }}</small>
                                    </div>
                                    
                                    @php
                                        $diasLibresActuales = $empleadaHorario->diasLibres()
                                            ->where('semana_inicio', now()->startOfWeek())
                                            ->first();
                                        $diasSeleccionados = $diasLibresActuales ? $diasLibresActuales->dias_libres : [];
                                    @endphp
                                    
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dia_libre_0" name="dias_libres_semana_actual[]" value="0" 
                                                       {{ in_array(0, $diasSeleccionados) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dia_libre_0">Domingo</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dia_libre_1" name="dias_libres_semana_actual[]" value="1" 
                                                       {{ in_array(1, $diasSeleccionados) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dia_libre_1">Lunes</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dia_libre_2" name="dias_libres_semana_actual[]" value="2" 
                                                       {{ in_array(2, $diasSeleccionados) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dia_libre_2">Martes</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dia_libre_3" name="dias_libres_semana_actual[]" value="3" 
                                                       {{ in_array(3, $diasSeleccionados) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dia_libre_3">Miércoles</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dia_libre_4" name="dias_libres_semana_actual[]" value="4" 
                                                       {{ in_array(4, $diasSeleccionados) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dia_libre_4">Jueves</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dia_libre_5" name="dias_libres_semana_actual[]" value="5" 
                                                       {{ in_array(5, $diasSeleccionados) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dia_libre_5">Viernes</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="dia_libre_6" name="dias_libres_semana_actual[]" value="6" 
                                                       {{ in_array(6, $diasSeleccionados) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="dia_libre_6">Sábado</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="hora_inicio_atencion" class="form-label fw-bold">Hora de Inicio:</label>
                                            <input type="time" class="form-control" id="hora_inicio_atencion" name="hora_inicio_atencion" 
                                                   value="{{ $empleadaHorario->hora_inicio_atencion->format('H:i') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="hora_fin_atencion" class="form-label fw-bold">Hora de Fin:</label>
                                            <input type="time" class="form-control" id="hora_fin_atencion" name="hora_fin_atencion" 
                                                   value="{{ $empleadaHorario->hora_fin_atencion->format('H:i') }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Días de Trabajo</h5>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="card text-center">
                                            <div class="card-body p-2">
                                                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                                <h6>Lunes</h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="lunes" name="lunes" value="1"
                                                           {{ $empleadaHorario->lunes ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="card text-center">
                                            <div class="card-body p-2">
                                                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                                <h6>Martes</h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="martes" name="martes" value="1"
                                                           {{ $empleadaHorario->martes ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="card text-center">
                                            <div class="card-body p-2">
                                                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                                <h6>Miércoles</h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="miercoles" name="miercoles" value="1"
                                                           {{ $empleadaHorario->miercoles ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="card text-center">
                                            <div class="card-body p-2">
                                                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                                <h6>Jueves</h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="jueves" name="jueves" value="1"
                                                           {{ $empleadaHorario->jueves ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="card text-center">
                                            <div class="card-body p-2">
                                                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                                <h6>Viernes</h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="viernes" name="viernes" value="1"
                                                           {{ $empleadaHorario->viernes ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="card text-center">
                                            <div class="card-body p-2">
                                                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                                <h6>Sábado</h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="sabado" name="sabado" value="1"
                                                           {{ $empleadaHorario->sabado ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="card text-center">
                                            <div class="card-body p-2">
                                                <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                                                <h6>Domingo</h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="domingo" name="domingo" value="1"
                                                           {{ $empleadaHorario->domingo ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="observaciones" class="form-label fw-bold">Observaciones:</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                              placeholder="Observaciones adicionales...">{{ $empleadaHorario->observaciones }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.empleada-horarios.show', $empleadaHorario) }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Guardar Cambios
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
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.btn {
    transition: all 0.3s ease;
    border-radius: 8px;
    font-weight: 500;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.form-label {
    color: #495057;
}

.form-control:focus, .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación de horarios
    const horaInicio = document.getElementById('hora_inicio_atencion');
    const horaFin = document.getElementById('hora_fin_atencion');
    
    function validarHorarios() {
        if (horaInicio.value && horaFin.value) {
            if (horaInicio.value >= horaFin.value) {
                horaFin.setCustomValidity('La hora de fin debe ser posterior a la hora de inicio');
            } else {
                horaFin.setCustomValidity('');
            }
        }
    }
    
    horaInicio.addEventListener('change', validarHorarios);
    horaFin.addEventListener('change', validarHorarios);
    
    // Validación de días de trabajo
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="lunes"], input[type="checkbox"][name^="martes"], input[type="checkbox"][name^="miercoles"], input[type="checkbox"][name^="jueves"], input[type="checkbox"][name^="viernes"], input[type="checkbox"][name^="sabado"], input[type="checkbox"][name^="domingo"]');
    
    function validarDiasTrabajo() {
        const diasSeleccionados = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        // Permitir seleccionar de 0 a 7 días (sin restricciones)
        checkboxes.forEach(cb => {
            cb.setCustomValidity('');
        });
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', validarDiasTrabajo);
    });
    
    // Función para calcular días libres por semana
    function calcularDiasLibres() {
        const diasTrabajo = [
            document.getElementById('lunes'),
            document.getElementById('martes'),
            document.getElementById('miercoles'),
            document.getElementById('jueves'),
            document.getElementById('viernes'),
            document.getElementById('sabado'),
            document.getElementById('domingo')
        ];
        
        let diasTrabajados = 0;
        diasTrabajo.forEach(dia => {
            if (dia && dia.checked) {
                diasTrabajados++;
            }
        });
        
        const diasLibres = 7 - diasTrabajados;
        const campoDiasLibres = document.getElementById('dias_libres_semana');
        
        if (campoDiasLibres) {
            campoDiasLibres.value = diasLibres;
        }
    }
    
    // Agregar event listeners a todos los toggles de días de trabajo
    const diasTrabajo = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    diasTrabajo.forEach(dia => {
        const toggle = document.getElementById(dia);
        if (toggle) {
            toggle.addEventListener('change', calcularDiasLibres);
        }
    });
    
    // Validación inicial
    validarHorarios();
    validarDiasTrabajo();
    calcularDiasLibres();
    
    // Mostrar errores de validación si existen
    @if($errors->any())
        console.log('Errores de validación:', @json($errors->all()));
        
        // Mostrar alerta con errores
        Swal.fire({
            title: 'Error de Validación',
            html: `
                <div class="text-start">
                    <p>Por favor corrige los siguientes errores:</p>
                    <ul class="list-unstyled">
                        @foreach($errors->all() as $error)
                            <li class="text-danger">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            `,
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    @endif
});
</script>
@endsection
