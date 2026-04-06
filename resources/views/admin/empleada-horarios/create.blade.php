@extends('layouts.appAdmin')

@section('title', 'Crear Horario de Empleada')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus me-2"></i>Crear Horario de Empleada
                    </h3>
                </div>
                
                <form action="{{ route('admin.empleada-horarios.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Empleada <span class="text-danger">*</span></label>
                                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                                        <option value="">Seleccionar empleada...</option>
                                        @foreach($empleadas as $empleada)
                                            <option value="{{ $empleada->id }}" {{ old('user_id') == $empleada->id ? 'selected' : '' }}>
                                                {{ $empleada->name }} ({{ $empleada->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="horas_contratadas_dia" class="form-label">Horas Contratadas/Día <span class="text-danger">*</span></label>
                                            <select class="form-select @error('horas_contratadas_dia') is-invalid @enderror" id="horas_contratadas_dia" name="horas_contratadas_dia" required>
                                                <option value="4" {{ old('horas_contratadas_dia', 8) == 4 ? 'selected' : '' }}>4 horas</option>
                                                <option value="6" {{ old('horas_contratadas_dia', 8) == 6 ? 'selected' : '' }}>6 horas</option>
                                                <option value="8" {{ old('horas_contratadas_dia', 8) == 8 ? 'selected' : '' }}>8 horas</option>
                                            </select>
                                            @error('horas_contratadas_dia')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="dias_libres_semana" class="form-label">Días Libres/Semana <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('dias_libres_semana') is-invalid @enderror" 
                                                   id="dias_libres_semana" name="dias_libres_semana" 
                                                   value="{{ old('dias_libres_semana', 2) }}" min="0" max="7" required>
                                            @error('dias_libres_semana')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="hora_inicio_atencion" class="form-label">Hora Inicio Atención <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control @error('hora_inicio_atencion') is-invalid @enderror" 
                                                   id="hora_inicio_atencion" name="hora_inicio_atencion" 
                                                   value="{{ old('hora_inicio_atencion', '08:00') }}" required>
                                            @error('hora_inicio_atencion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="hora_fin_atencion" class="form-label">Hora Fin Atención <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control @error('hora_fin_atencion') is-invalid @enderror" 
                                                   id="hora_fin_atencion" name="hora_fin_atencion" 
                                                   value="{{ old('hora_fin_atencion', '17:00') }}" required>
                                            @error('hora_fin_atencion')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                              id="observaciones" name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Días de Trabajo</h6>
                                <div class="row">
                                    @foreach(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'] as $dia)
                                        <div class="col-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="{{ $dia }}" name="{{ $dia }}" value="1"
                                                       {{ old($dia, in_array($dia, ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="{{ $dia }}">
                                                    {{ ucfirst($dia) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <h6><i class="fas fa-info-circle me-2"></i>Información</h6>
                                    <ul class="mb-0">
                                        <li>Las horas contratadas determinan la carga máxima de trabajo diaria</li>
                                        <li>Los días libres se usan para calcular disponibilidad mensual</li>
                                        <li>El horario de atención define el rango de trabajo diario</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.empleada-horarios.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Crear Horario
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Validación inicial
    validarHorarios();
    
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
    
    // Calcular inicialmente
    calcularDiasLibres();
});
</script>
@endsection
