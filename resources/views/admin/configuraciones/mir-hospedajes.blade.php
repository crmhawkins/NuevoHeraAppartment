@extends('layouts.appAdmin')

@section('title', 'MIR Hospedajes')

@section('content')
<style>
    /* Estilos Apple para Configuración */
    .config-container {
        padding: 24px;
        background: #F2F2F7;
        min-height: calc(100vh - 80px);
    }

    .config-header {
        margin-bottom: 32px;
    }

    .config-header h1 {
        font-size: 34px;
        font-weight: 700;
        color: #1D1D1F;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }

    .config-header p {
        font-size: 15px;
        color: #8E8E93;
        margin: 0;
    }

    .config-card {
        background: #FFFFFF;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .config-card-header {
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        border-bottom: 1px solid #E5E5EA;
        padding: 20px 24px;
    }

    .config-card-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #1D1D1F;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .config-card-header i {
        color: #007AFF;
        font-size: 20px;
    }

    .config-card-body {
        padding: 24px;
    }

    .form-label {
        font-weight: 600;
        color: #1D1D1F;
        margin-bottom: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-label i {
        color: #007AFF;
        font-size: 16px;
    }

    .form-control,
    .form-select {
        border: 1px solid #E5E5EA;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 15px;
        background: #FFFFFF;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        outline: none;
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        transform: translateY(-1px);
    }

    .form-text {
        font-size: 13px;
        color: #8E8E93;
        margin-top: 4px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border: none;
        border-radius: 12px;
        padding: 16px 24px;
        font-size: 17px;
        font-weight: 600;
        color: #FFFFFF;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
        color: #FFFFFF;
    }
</style>

<div class="config-container">
    <!-- Header -->
    <div class="config-header">
        <h1>
            <i class="fas fa-shield-alt me-2" style="color: #007AFF;"></i>
            MIR Hospedajes
        </h1>
        <p>Configuración para el envío de reservas al Servicio Web del Ministerio del Interior (MIR) según el Real Decreto 933/2021</p>
    </div>

    <div class="config-card">
        <div class="config-card-header">
            <h5>
                <i class="fas fa-shield-alt"></i>
                Configuración API MIR - Servicio de Hospedajes
            </h5>
        </div>
        <div class="config-card-body">
            <p class="text-muted mb-4">Configuración para el envío de reservas al Servicio Web del Ministerio del Interior (MIR) según el Real Decreto 933/2021.</p>
            
            <form action="{{ route('configuracion.mir.update') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-barcode"></i>
                            Código Arrendador
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_codigo_arrendador" 
                               value="{{ \App\Models\Setting::get('mir_codigo_arrendador', \App\Models\Setting::get('mir_arrendador', '0000004735')) }}"
                               placeholder="0000004735">
                        <small class="form-text">Código único asignado al registrarte en la Sede Electrónica del Ministerio del Interior</small>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-building"></i>
                            Código Establecimiento
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_codigo_establecimiento" 
                               value="{{ \App\Models\Setting::get('mir_codigo_establecimiento', '0000003984') }}"
                               placeholder="0000003984">
                        <small class="form-text">Código del establecimiento asignado por el Sistema de Hospedajes</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-tag"></i>
                            Nombre de la Aplicación
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_aplicacion" 
                               value="{{ \App\Models\Setting::get('mir_aplicacion', 'Hawkins Suite') }}"
                               placeholder="Hawkins Suite">
                        <small class="form-text">Nombre de tu sistema para identificación en MIR</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Usuario
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="mir_usuario" 
                               value="{{ \App\Models\Setting::get('mir_usuario') }}"
                               placeholder="Usuario de la Sede Electrónica">
                        <small class="form-text">Usuario obtenido tras registrarte en la Sede Electrónica del Ministerio del Interior</small>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            Contraseña
                        </label>
                        <input type="password" 
                               class="form-control" 
                               name="mir_password" 
                               value="{{ \App\Models\Setting::get('mir_password') }}"
                               placeholder="Contraseña">
                        <small class="form-text">Contraseña de acceso a la API MIR</small>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-server"></i>
                        Entorno
                    </label>
                    <select class="form-select" name="mir_entorno">
                        <option value="sandbox" {{ \App\Models\Setting::get('mir_entorno', 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                            Pruebas (Sandbox)
                        </option>
                        <option value="production" {{ \App\Models\Setting::get('mir_entorno') === 'production' ? 'selected' : '' }}>
                            Producción
                        </option>
                    </select>
                    <small class="form-text">Selecciona el entorno: Sandbox para pruebas o Producción para el entorno real</small>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Endpoints:</strong><br>
                    <strong>Sandbox:</strong> https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion<br>
                    <strong>Producción:</strong> https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Configuración MIR
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection




