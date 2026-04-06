@extends('layouts.appAdmin')

@section('title', 'Detalle del Horario de Empleada')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-user-clock me-2"></i>Detalle del Horario de Empleada
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.empleada-horarios.edit', $empleadaHorario) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                        <a href="{{ route('admin.empleada-horarios.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Información de la Empleada</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td>{{ $empleadaHorario->user->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $empleadaHorario->user->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $empleadaHorario->activo ? 'success' : 'danger' }}">
                                            {{ $empleadaHorario->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Configuración del Horario</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Horas Contratadas:</strong></td>
                                    <td>{{ $empleadaHorario->horas_contratadas_dia }} horas/día</td>
                                </tr>
                                <tr>
                                    <td><strong>Días Libres/Semana:</strong></td>
                                    <td>{{ $empleadaHorario->dias_libres_semana }} días/semana</td>
                                </tr>
                                <tr>
                                    <td><strong>Horario de Atención:</strong></td>
                                    <td>{{ $empleadaHorario->horario_atencion }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Días de Trabajo</h5>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="card text-center {{ $empleadaHorario->lunes ? 'border-success' : 'border-secondary' }}">
                                        <div class="card-body p-2">
                                            <i class="fas fa-calendar-day fa-2x {{ $empleadaHorario->lunes ? 'text-success' : 'text-muted' }}"></i>
                                            <h6 class="mt-2 {{ $empleadaHorario->lunes ? 'text-success' : 'text-muted' }}">Lunes</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center {{ $empleadaHorario->martes ? 'border-success' : 'border-secondary' }}">
                                        <div class="card-body p-2">
                                            <i class="fas fa-calendar-day fa-2x {{ $empleadaHorario->martes ? 'text-success' : 'text-muted' }}"></i>
                                            <h6 class="mt-2 {{ $empleadaHorario->martes ? 'text-success' : 'text-muted' }}">Martes</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center {{ $empleadaHorario->miercoles ? 'border-success' : 'border-secondary' }}">
                                        <div class="card-body p-2">
                                            <i class="fas fa-calendar-day fa-2x {{ $empleadaHorario->miercoles ? 'text-success' : 'text-muted' }}"></i>
                                            <h6 class="mt-2 {{ $empleadaHorario->miercoles ? 'text-success' : 'text-muted' }}">Miércoles</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center {{ $empleadaHorario->jueves ? 'border-success' : 'border-secondary' }}">
                                        <div class="card-body p-2">
                                            <i class="fas fa-calendar-day fa-2x {{ $empleadaHorario->jueves ? 'text-success' : 'text-muted' }}"></i>
                                            <h6 class="mt-2 {{ $empleadaHorario->jueves ? 'text-success' : 'text-muted' }}">Jueves</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center {{ $empleadaHorario->viernes ? 'border-success' : 'border-secondary' }}">
                                        <div class="card-body p-2">
                                            <i class="fas fa-calendar-day fa-2x {{ $empleadaHorario->viernes ? 'text-success' : 'text-muted' }}"></i>
                                            <h6 class="mt-2 {{ $empleadaHorario->viernes ? 'text-success' : 'text-muted' }}">Viernes</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center {{ $empleadaHorario->sabado ? 'border-success' : 'border-secondary' }}">
                                        <div class="card-body p-2">
                                            <i class="fas fa-calendar-day fa-2x {{ $empleadaHorario->sabado ? 'text-success' : 'text-muted' }}"></i>
                                            <h6 class="mt-2 {{ $empleadaHorario->sabado ? 'text-success' : 'text-muted' }}">Sábado</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($empleadaHorario->observaciones)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Observaciones</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ $empleadaHorario->observaciones }}
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Estadísticas</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <i class="fas fa-clock fa-2x mb-2"></i>
                                            <h4>{{ $empleadaHorario->horas_contratadas_dia }}h</h4>
                                            <p class="mb-0">Horas por día</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <i class="fas fa-calendar-week fa-2x mb-2"></i>
                                            <h4>{{ $empleadaHorario->numero_dias_trabajo }}</h4>
                                            <p class="mb-0">Días de trabajo</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                            <h4>{{ $empleadaHorario->dias_libres_semana }}</h4>
                                            <p class="mb-0">Días libres/semana</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body text-center">
                                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                                            <h4>{{ $empleadaHorario->horas_contratadas_dia * $empleadaHorario->numero_dias_trabajo }}h</h4>
                                            <p class="mb-0">Horas semanales</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

.badge {
    border-radius: 20px;
    padding: 6px 12px;
    font-weight: 500;
}

.table-borderless td {
    border: none;
    padding: 0.5rem 0.75rem;
}

.table-borderless td:first-child {
    font-weight: 600;
    color: #6c757d;
    width: 40%;
}
</style>
@endsection
