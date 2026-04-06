@extends('admin.configuraciones.layout')

@section('config-title', 'Plataforma Estado')
@section('config-subtitle', 'Controla el estado de la plataforma (activa/inactiva)')

@section('config-content')
<div class="config-card">
    <div class="config-card-header">
        <h5>
            <i class="fas fa-building"></i>
            Configuración Plataforma del Estado
        </h5>
    </div>
    <div class="config-card-body">
        <p class="text-muted mb-4">Configuración para la subida de viajeros a la plataforma del estado español.</p>
        
        <form action="{{ route('configuracion.plataforma-estado.update') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">
                        <i class="fas fa-barcode"></i>
                        Código del Arrendador
                    </label>
                    <input type="text" 
                           class="form-control" 
                           name="codigo_arrendador" 
                           value="{{ \App\Models\Setting::get('codigo_arrendador') }}"
                           placeholder="Código asignado por la administración">
                    <small class="form-text text-muted">Código único asignado por la administración pública</small>
                </div>
                
                <div class="col-md-6 mb-4">
                    <label class="form-label">
                        <i class="fas fa-tag"></i>
                        Nombre de la Aplicación
                    </label>
                    <input type="text" 
                           class="form-control" 
                           name="aplicacion" 
                           value="{{ \App\Models\Setting::get('aplicacion', 'HAWKINS_SUITES') }}"
                           placeholder="HAWKINS_SUITES">
                    <small class="form-text text-muted">Identificador de la aplicación en la plataforma</small>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-key"></i>
                    Credenciales de Acceso (JSON)
                </label>
                <textarea class="form-control" 
                          name="credenciales" 
                          rows="4"
                          style="font-family: 'SF Mono', Monaco, monospace;"
                          placeholder='{"usuario": "tu_usuario", "password": "tu_password", "endpoint": "https://api.ejemplo.com"}'>{{ \App\Models\Setting::get('credenciales') }}</textarea>
                <small class="form-text text-muted">Credenciales en formato JSON para la conexión con la plataforma</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-certificate"></i>
                    Ruta del Certificado CA
                </label>
                <input type="text" 
                       class="form-control" 
                       name="ca_path" 
                       value="{{ \App\Models\Setting::get('ca_path') }}"
                       placeholder="/path/to/certificate.pem">
                <small class="form-text text-muted">Ruta al certificado CA para conexiones seguras</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Guardar Configuración
                </button>
                <button type="button" class="btn btn-secondary" id="testConnection">
                    <i class="fas fa-plug me-2"></i>Probar Conexión
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

