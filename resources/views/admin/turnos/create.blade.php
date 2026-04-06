@extends('layouts.appAdmin')

@section('title', 'Crear Turno')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus me-2"></i>Crear Turno Manual
                    </h3>
                </div>
                
                <form action="{{ route('gestion.turnos.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('fecha') is-invalid @enderror" 
                                           id="fecha" name="fecha" value="{{ old('fecha', today()->format('Y-m-d')) }}" required>
                                    @error('fecha')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Empleada <span class="text-danger">*</span></label>
                                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                                        <option value="">Seleccionar empleada...</option>
                                        @foreach($empleadas as $empleada)
                                            <option value="{{ $empleada->id }}" {{ old('user_id') == $empleada->id ? 'selected' : '' }}>
                                                {{ $empleada->name }}
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
                                            <label for="hora_inicio" class="form-label">Hora Inicio <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control @error('hora_inicio') is-invalid @enderror" 
                                                   id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', '08:00') }}" required>
                                            @error('hora_inicio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="hora_fin" class="form-label">Hora Fin <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control @error('hora_fin') is-invalid @enderror" 
                                                   id="hora_fin" name="hora_fin" value="{{ old('hora_fin', '17:00') }}" required>
                                            @error('hora_fin')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle me-2"></i>Información</h6>
                                    <p class="mb-0">Los turnos manuales te permiten crear turnos específicos para situaciones especiales. Las tareas se pueden asignar después de crear el turno.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('gestion.turnos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Crear Turno
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
