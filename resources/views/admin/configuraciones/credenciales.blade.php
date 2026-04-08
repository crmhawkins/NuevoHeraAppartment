@extends('admin.configuraciones.layout')

@section('config-title', 'Credenciales')
@section('config-subtitle', 'Gestiona todas las credenciales y claves de acceso a servicios externos desde un solo lugar')

@section('config-content')
<style>
    .credential-accordion .accordion-item {
        background: #FFFFFF;
        border-radius: 16px !important;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        border: none !important;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .credential-accordion .accordion-button {
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        border: none;
        border-radius: 16px 16px 0 0 !important;
        padding: 20px 24px;
        font-size: 17px;
        font-weight: 600;
        color: #1D1D1F;
        box-shadow: none !important;
        gap: 12px;
    }
    .credential-accordion .accordion-button:not(.collapsed) {
        color: #007AFF;
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
    }
    .credential-accordion .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23007AFF'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        transition: transform 0.3s ease;
    }
    .credential-accordion .accordion-button.collapsed::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%238E8E93'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
    .credential-accordion .accordion-body {
        padding: 24px;
    }
    .credential-accordion .accordion-button i.section-icon {
        font-size: 20px;
        color: #007AFF;
        width: 28px;
        text-align: center;
    }
    .credential-accordion .accordion-button.collapsed i.section-icon {
        color: #8E8E93;
    }
    .section-badge {
        font-size: 12px;
        font-weight: 500;
        padding: 3px 10px;
        border-radius: 12px;
        margin-left: auto;
        margin-right: 8px;
    }
    .btn-save-section {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-size: 15px;
        font-weight: 600;
        color: #FFFFFF;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.25);
    }
    .btn-save-section:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(0, 122, 255, 0.35);
        color: #FFFFFF;
    }
    .btn-save-section:disabled {
        opacity: 0.6;
        transform: none;
    }
    .password-toggle-wrapper {
        position: relative;
    }
    .password-toggle-wrapper .btn-toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #8E8E93;
        cursor: pointer;
        padding: 4px 8px;
        font-size: 14px;
        z-index: 5;
    }
    .password-toggle-wrapper .btn-toggle-password:hover {
        color: #007AFF;
    }
    .password-toggle-wrapper .form-control {
        padding-right: 42px;
    }
    .prompt-toggle-btn {
        background: #F2F2F7;
        border: 1px solid #E5E5EA;
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 600;
        font-size: 14px;
        color: #1D1D1F;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .prompt-toggle-btn:hover {
        background: #E5E5EA;
        border-color: #D1D1D6;
    }
    .prompt-editor {
        font-family: 'SF Mono', Monaco, Consolas, monospace;
        font-size: 14px;
        line-height: 1.6;
        border: 1px solid #E5E5EA;
        border-radius: 8px;
        padding: 16px;
        min-height: 200px;
        resize: vertical;
        width: 100%;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .prompt-editor:focus {
        outline: none;
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
    }
    .prompt-preview {
        background: #F9F9F9;
        border: 1px solid #E5E5EA;
        border-radius: 8px;
        padding: 16px;
        min-height: 100px;
        max-height: 300px;
        overflow-y: auto;
        font-size: 14px;
        line-height: 1.6;
    }
    .prompt-preview h1, .prompt-preview h2, .prompt-preview h3 {
        color: #1D1D1F;
    }
    .prompt-preview code {
        background: #E5E5EA;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 13px;
    }
</style>

<div class="accordion credential-accordion" id="credencialesAccordion">

    {{-- ================================================================== --}}
    {{-- SECTION 1: Plataformas de Reserva --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secPlataformas">
                <i class="fas fa-calendar-check section-icon"></i>
                Plataformas de Reserva
                <span class="section-badge bg-light text-secondary">Booking, Airbnb, Channex</span>
            </button>
        </h2>
        <div id="secPlataformas" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <form id="formPlataformas" action="{{ route('configuracion.credenciales.update', $configuraciones[0]->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_section" value="plataformas">

                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-hotel me-2" style="color: #007AFF;"></i>Booking.com
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Usuario Booking</label>
                            <input class="form-control" name="user_booking" value="{{ $configuraciones[0]->user_booking }}" placeholder="Usuario de Booking.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-lock"></i> Contrasena Booking</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="password_booking" value="{{ $configuraciones[0]->password_booking }}" placeholder="Contrasena de Booking.com">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fab fa-airbnb me-2" style="color: #FF5A5F;"></i>Airbnb
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Usuario Airbnb</label>
                            <input class="form-control" name="user_airbnb" value="{{ $configuraciones[0]->user_airbnb }}" placeholder="Usuario de Airbnb">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-lock"></i> Contrasena Airbnb</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="password_airbnb" value="{{ $configuraciones[0]->password_airbnb }}" placeholder="Contrasena de Airbnb">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-exchange-alt me-2" style="color: #007AFF;"></i>Channex (Channel Manager)
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> API Token</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="channex_api_token" value="{{ $channex['api_token'] }}" placeholder="Token de la API de Channex">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                            <small class="text-muted">Se obtiene desde el panel de Channex</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-link"></i> Webhook URL</label>
                            <input class="form-control" name="channex_webhook_url" value="{{ $channex['webhook_url'] }}" placeholder="https://tu-dominio.com/webhook-handler">
                            <small class="text-muted">URL donde Channex enviara las notificaciones</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-save-section">
                        <i class="fas fa-save me-2"></i>Guardar Plataformas
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 2: WhatsApp Business API --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secWhatsapp">
                <i class="fab fa-whatsapp section-icon" style="color: #25D366 !important;"></i>
                WhatsApp Business API
            </button>
        </h2>
        <div id="secWhatsapp" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <form id="formWhatsapp" action="{{ route('configuracion.credenciales.update', $configuraciones[0]->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_section" value="whatsapp">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> Token de Acceso (Meta Business)</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="whatsapp_token" value="{{ $whatsapp['token'] }}" placeholder="EAAxxxxxxx... (token permanente de Meta Business)">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                            <small class="text-muted">Se obtiene desde Meta Business Suite > WhatsApp > Configuracion de la API</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-phone"></i> Phone Number ID</label>
                            <input class="form-control" name="whatsapp_phone_id" value="{{ $whatsapp['phone_id'] }}" placeholder="102360642838173">
                            <small class="text-muted">ID del numero de telefono en la API de WhatsApp</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-code-branch"></i> Version API</label>
                            <input class="form-control" name="whatsapp_api_version" value="{{ $whatsapp['api_version'] }}" placeholder="v16.0">
                            <small class="text-muted">Version de la Graph API de Meta (ej: v16.0, v20.0)</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-save-section mt-2">
                        <i class="fas fa-save me-2"></i>Guardar WhatsApp
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 3: Inteligencia Artificial --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secIA">
                <i class="fas fa-brain section-icon"></i>
                Inteligencia Artificial
                <span class="section-badge bg-light text-secondary">Hawkins IA, Ollama, OpenAI, Anthropic</span>
            </button>
        </h2>
        <div id="secIA" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <form id="formIA" action="{{ route('configuracion.credenciales.update', $configuraciones[0]->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_section" value="ia">

                    {{-- Hawkins IA --}}
                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-robot me-2" style="color: #007AFF;"></i>Hawkins IA
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-globe"></i> URL</label>
                            <input class="form-control" name="hawkins_ai_url" value="{{ $ia['hawkins']['url'] }}" placeholder="https://aiapi.hawkins.es/chat/chat">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> API Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="hawkins_ai_api_key" value="{{ $ia['hawkins']['api_key'] }}" placeholder="API Key de Hawkins IA">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-cog"></i> Modelo</label>
                            <input class="form-control" name="hawkins_ai_model" value="{{ $ia['hawkins']['model'] }}" placeholder="gpt-oss:120b-cloud">
                        </div>
                    </div>

                    {{-- Ollama --}}
                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-server me-2" style="color: #8E8E93;"></i>Ollama (IA Local)
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-globe"></i> URL</label>
                            <input class="form-control" name="ollama_url" value="{{ $ia['ollama']['url'] }}" placeholder="https://192.168.1.45/chat">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> API Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="ollama_api_key" value="{{ $ia['ollama']['api_key'] }}" placeholder="API Key de Ollama">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-cog"></i> Modelo</label>
                            <input class="form-control" name="ollama_model" value="{{ $ia['ollama']['model'] }}" placeholder="qwen2.5vl:latest">
                        </div>
                    </div>

                    {{-- OpenAI --}}
                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-bolt me-2" style="color: #10A37F;"></i>OpenAI
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> API Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="openai_api_key" value="{{ $ia['openai']['api_key'] }}" placeholder="sk-...">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-cog"></i> Modelo</label>
                            <input class="form-control" name="openai_model" value="{{ $ia['openai']['model'] }}" placeholder="gpt-4o">
                        </div>
                    </div>

                    {{-- Anthropic --}}
                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-atom me-2" style="color: #D4A574;"></i>Anthropic
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> API Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="anthropic_api_key" value="{{ $ia['anthropic']['api_key'] }}" placeholder="sk-ant-...">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-save-section">
                        <i class="fas fa-save me-2"></i>Guardar Credenciales IA
                    </button>
                </form>

                <hr class="my-4" style="border-color: #E5E5EA;">

                {{-- Prompt del Asistente IA (sub-section) --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0" style="color: #1D1D1F;">
                        <i class="fas fa-comment-dots me-2" style="color: #007AFF;"></i>
                        Prompt del Asistente IA
                    </h6>
                    <button class="prompt-toggle-btn" type="button" data-bs-toggle="collapse" data-bs-target="#promptEditorCollapse" aria-expanded="false">
                        <i class="fas fa-edit"></i> Editar Prompt
                    </button>
                </div>

                <div class="collapse" id="promptEditorCollapse">
                    <form id="formPrompt" action="{{ route('configuracion.prompt-ia.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-code"></i> Editar Prompt (Markdown)</label>
                            <textarea name="prompt" id="promptTextarea" class="prompt-editor" rows="12" placeholder="Escribe aqui el prompt del asistente...">@if(count($prompt) > 0){{ $prompt[0]->prompt }}@endif</textarea>
                            <small class="form-text text-muted">Puedes usar formato Markdown para formatear el texto</small>
                        </div>
                        <button type="submit" class="btn btn-save-section mb-3">
                            <i class="fas fa-save me-2"></i>Actualizar Prompt
                        </button>
                    </form>

                    <div class="mb-3">
                        <h6 class="fw-semibold d-flex align-items-center gap-2">
                            <i class="fas fa-eye" style="color: #007AFF;"></i>
                            Vista Previa (formato Markdown)
                        </h6>
                        <small class="text-muted">Actualiza el prompt para ver la vista previa</small>
                    </div>
                    <div class="prompt-preview" id="promptPreview">
                        {!! \Illuminate\Support\Str::markdown(count($prompt) > 0 ? $prompt[0]->prompt : 'No hay contenido para mostrar.') !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 4: MIR - Ministerio del Interior --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secMIR">
                <i class="fas fa-shield-alt section-icon"></i>
                MIR - Ministerio del Interior
            </button>
        </h2>
        <div id="secMIR" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <p class="text-muted mb-4">Credenciales para el envio de reservas al Servicio Web del Ministerio del Interior (MIR) segun el Real Decreto 933/2021.</p>
                <form id="formMIR" action="{{ route('configuracion.mir.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-barcode"></i> Codigo Arrendador</label>
                            <input type="text" class="form-control" name="mir_codigo_arrendador" value="{{ $mir['codigo_arrendador'] }}" placeholder="0000004735">
                            <small class="form-text text-muted">Codigo unico asignado al registrarte en la Sede Electronica del Ministerio del Interior</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-building"></i> Codigo Establecimiento</label>
                            <input type="text" class="form-control" name="mir_codigo_establecimiento" value="{{ $mir['codigo_establecimiento'] }}" placeholder="0000003984">
                            <small class="form-text text-muted">Codigo del establecimiento asignado por el Sistema de Hospedajes</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-tag"></i> Nombre de la Aplicacion</label>
                            <input type="text" class="form-control" name="mir_aplicacion" value="{{ $mir['aplicacion'] }}" placeholder="Hawkins Suite">
                            <small class="form-text text-muted">Nombre de tu sistema para identificacion en MIR</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-user"></i> Usuario</label>
                            <input type="text" class="form-control" name="mir_usuario" value="{{ $mir['usuario'] }}" placeholder="Usuario de la Sede Electronica">
                            <small class="form-text text-muted">Usuario obtenido tras registrarte en la Sede Electronica</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-lock"></i> Contrasena</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="mir_password" value="{{ $mir['password'] }}" placeholder="Contrasena">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                            <small class="form-text text-muted">Contrasena de acceso a la API MIR</small>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-server"></i> Entorno</label>
                        <select class="form-select" name="mir_entorno">
                            <option value="sandbox" {{ $mir['entorno'] === 'sandbox' ? 'selected' : '' }}>Pruebas (Sandbox)</option>
                            <option value="production" {{ $mir['entorno'] === 'production' ? 'selected' : '' }}>Produccion</option>
                        </select>
                        <small class="form-text text-muted">Selecciona el entorno: Sandbox para pruebas o Produccion para el entorno real</small>
                    </div>

                    <div class="alert alert-info" style="border-radius: 10px; border: none; background: #E8F4FD;">
                        <i class="fas fa-info-circle me-2" style="color: #007AFF;"></i>
                        <strong>Endpoints:</strong><br>
                        <small><strong>Sandbox:</strong> https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion</small><br>
                        <small><strong>Produccion:</strong> https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion</small>
                    </div>

                    <button type="submit" class="btn btn-save-section">
                        <i class="fas fa-save me-2"></i>Guardar Configuracion MIR
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 5: Pagos --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secPagos">
                <i class="fas fa-credit-card section-icon"></i>
                Pagos
                <span class="section-badge bg-light text-secondary">Stripe</span>
            </button>
        </h2>
        <div id="secPagos" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <form id="formPagos" action="{{ route('configuracion.credenciales.update', $configuraciones[0]->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_section" value="pagos">

                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fab fa-stripe me-2" style="color: #635BFF;"></i>Stripe
                    </h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> Publishable Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="stripe_key" value="{{ $pagos['stripe']['key'] }}" placeholder="pk_live_...">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-lock"></i> Secret Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="stripe_secret" value="{{ $pagos['stripe']['secret'] }}" placeholder="sk_live_...">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-shield-alt"></i> Webhook Secret</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="stripe_webhook_secret" value="{{ $pagos['stripe']['webhook_secret'] }}" placeholder="whsec_...">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-save-section mt-2">
                        <i class="fas fa-save me-2"></i>Guardar Pagos
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 6: Cerraduras Inteligentes --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secCerraduras">
                <i class="fas fa-door-open section-icon"></i>
                Cerraduras Inteligentes
                <span class="section-badge bg-light text-secondary">Tuya App</span>
            </button>
        </h2>
        <div id="secCerraduras" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <form id="formCerraduras" action="{{ route('configuracion.credenciales.update', $configuraciones[0]->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_section" value="cerraduras">

                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-lock me-2" style="color: #FF9500;"></i>Tuya App
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-globe"></i> URL</label>
                            <input class="form-control" name="tuya_app_url" value="{{ $cerraduras['tuya']['url'] }}" placeholder="http://localhost:8002">
                            <small class="text-muted">URL del servicio Tuya App</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> API Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="tuya_app_api_key" value="{{ $cerraduras['tuya']['api_key'] }}" placeholder="API Key de Tuya">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-save-section mt-2">
                        <i class="fas fa-save me-2"></i>Guardar Cerraduras
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 7: Otros --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secOtros">
                <i class="fas fa-ellipsis-h section-icon"></i>
                Otros
                <span class="section-badge bg-light text-secondary">Recaptcha, Registro Visitantes</span>
            </button>
        </h2>
        <div id="secOtros" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <form id="formOtros" action="{{ route('configuracion.credenciales.update', $configuraciones[0]->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_section" value="otros">

                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-shield-alt me-2" style="color: #4285F4;"></i>Google Recaptcha
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-globe"></i> Site Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="recaptcha_site_key" value="{{ $otros['recaptcha']['site_key'] }}" placeholder="6Lc...">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-key"></i> Secret Key</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" class="form-control" name="recaptcha_secret_key" value="{{ $otros['recaptcha']['secret_key'] }}" placeholder="6Lc...">
                                <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                        <i class="fas fa-id-card me-2" style="color: #34C759;"></i>Registro de Visitantes
                    </h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-link"></i> URL</label>
                            <input class="form-control" name="registro_visitantes_url" value="{{ $otros['registro_visitantes_url'] }}" placeholder="https://registro.tudominio.com">
                            <small class="text-muted">URL del servicio de registro de visitantes</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-save-section">
                        <i class="fas fa-save me-2"></i>Guardar Otros
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 7b: Bankinter (credenciales gestionadas en BD) --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secBankinter">
                <i class="fas fa-university section-icon"></i>
                Bankinter
                <span class="section-badge bg-light text-secondary">Cuentas bancarias del scraper</span>
            </button>
        </h2>
        <div id="secBankinter" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">
                <p class="text-muted mb-3" style="font-size: 14px;">
                    Estas credenciales las usa el scraper Bankinter (PC Windows externo) para descargar los movimientos diariamente.
                    La password se almacena cifrada en la base de datos con la APP_KEY del CRM.
                </p>

                @if($bankinterCredenciales->isEmpty())
                    <div class="p-3 rounded-3 mb-3" style="background: #FFF8E1; border: 1px solid #FFE082;">
                        <i class="fas fa-exclamation-triangle me-2" style="color: #F59E0B;"></i>
                        <span style="font-size: 14px; color: #1D1D1F;">
                            No hay credenciales Bankinter en la base de datos. El scraper usara las del <code>.env</code> (fallback).
                            Puedes migrarlas ejecutando <code>php artisan bankinter:migrar-credenciales</code>.
                        </span>
                    </div>
                @else
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle" style="background: #FFFFFF;">
                            <thead>
                                <tr style="background: #F2F2F7;">
                                    <th style="font-size: 13px;">Alias</th>
                                    <th style="font-size: 13px;">Etiqueta</th>
                                    <th style="font-size: 13px;">Usuario</th>
                                    <th style="font-size: 13px;">IBAN</th>
                                    <th style="font-size: 13px;">Banco</th>
                                    <th style="font-size: 13px; text-align: center;">Estado</th>
                                    <th style="font-size: 13px; text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bankinterCredenciales as $cred)
                                    @php
                                        $ibanMasked = '';
                                        if (!empty($cred->iban)) {
                                            $last4 = substr($cred->iban, -4);
                                            $ibanMasked = '****' . $last4;
                                        }
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $cred->alias }}</strong></td>
                                        <td>{{ $cred->label ?? '-' }}</td>
                                        <td>{{ $cred->user }}</td>
                                        <td><code>{{ $ibanMasked ?: '-' }}</code></td>
                                        <td>{{ $cred->bank?->nombre ?? '-' }}</td>
                                        <td style="text-align: center;">
                                            <form action="{{ route('configuracion.credenciales.bankinter.toggle', $cred->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @if($cred->enabled)
                                                    <button type="submit" class="btn btn-sm" style="background: #34C759; color: white; border-radius: 8px; font-size: 12px;" title="Activada - Click para desactivar">
                                                        <i class="fas fa-check-circle me-1"></i>Activa
                                                    </button>
                                                @else
                                                    <button type="submit" class="btn btn-sm" style="background: #8E8E93; color: white; border-radius: 8px; font-size: 12px;" title="Desactivada - Click para activar">
                                                        <i class="fas fa-times-circle me-1"></i>Inactiva
                                                    </button>
                                                @endif
                                            </form>
                                        </td>
                                        <td style="text-align: right;">
                                            <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;"
                                                    onclick="bankinterEdit({{ $cred->id }}, {{ json_encode([
                                                        'alias' => $cred->alias,
                                                        'label' => $cred->label,
                                                        'user' => $cred->user,
                                                        'iban' => $cred->iban,
                                                        'bank_id' => $cred->bank_id,
                                                        'enabled' => (bool)$cred->enabled,
                                                    ]) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('configuracion.credenciales.bankinter.destroy', $cred->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Eliminar credencial {{ $cred->alias }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <button type="button" class="prompt-toggle-btn mb-3" onclick="bankinterToggleForm(); return false;">
                    <i class="fas fa-plus-circle"></i>
                    <span id="bankinterFormToggleLabel">Anadir nueva cuenta</span>
                </button>

                {{-- Formulario create/edit inline --}}
                <div id="bankinterFormWrapper" style="display: none;">
                    <form id="bankinterForm" method="POST"
                          action="{{ route('configuracion.credenciales.bankinter.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="bankinterMethod" value="POST">
                        <div class="p-3 rounded-3" style="background: #F9F9F9; border: 1px solid #E5E5EA;">
                            <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                                <i class="fas fa-university me-2" style="color: #007AFF;"></i>
                                <span id="bankinterFormTitle">Nueva credencial Bankinter</span>
                            </h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-tag"></i> Alias <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="alias" id="bankinterAlias" required maxlength="64" placeholder="hawkins">
                                    <small class="text-muted">Solo letras, numeros, guiones. Identificador unico.</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-font"></i> Etiqueta</label>
                                    <input type="text" class="form-control" name="label" id="bankinterLabel" maxlength="255" placeholder="Hawkins S.L.">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-building"></i> Banco</label>
                                    <select class="form-control" name="bank_id" id="bankinterBankId">
                                        <option value="">-- Sin asignar --</option>
                                        @foreach($bancosDisponibles as $banco)
                                            <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-user"></i> Usuario <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="user" id="bankinterUser" required maxlength="255" autocomplete="off">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-key"></i> Password <span class="text-danger" id="bankinterPasswordRequired">*</span></label>
                                    <div class="password-toggle-wrapper">
                                        <input type="password" class="form-control" name="password" id="bankinterPassword" autocomplete="new-password" placeholder="">
                                        <button type="button" class="btn-toggle-password" title="Mostrar/Ocultar"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <small class="text-muted" id="bankinterPasswordHelp" style="display:none;">Dejar vacio para no cambiar</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><i class="fas fa-hashtag"></i> IBAN</label>
                                    <input type="text" class="form-control" name="iban" id="bankinterIban" maxlength="34" placeholder="ES91 2100 ...">
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" name="enabled" id="bankinterEnabled" value="1" checked>
                                <label class="form-check-label" for="bankinterEnabled">Activa (el scraper la usara)</label>
                            </div>

                            <button type="submit" class="btn btn-save-section">
                                <i class="fas fa-save me-2"></i><span id="bankinterSubmitLabel">Crear credencial</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" style="border-radius: 10px;" onclick="bankinterResetForm(); bankinterToggleForm();">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SECTION 8: Datos Contables --}}
    {{-- ================================================================== --}}
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secDatosContables">
                <i class="fas fa-calculator section-icon"></i>
                Datos Contables
                <span class="section-badge bg-light text-secondary">Bancos, Categorias, Estados, Bankinter</span>
            </button>
        </h2>
        <div id="secDatosContables" class="accordion-collapse collapse" data-bs-parent="#credencialesAccordion">
            <div class="accordion-body">

                <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                    <i class="fas fa-cogs me-2" style="color: #007AFF;"></i>Configuracion Contable
                </h6>
                <p class="text-muted mb-3" style="font-size: 14px;">Accede a la gestion de los datos maestros de contabilidad desde aqui.</p>

                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('admin.bancos.index') }}" class="text-decoration-none">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background: #F2F2F7; transition: all 0.2s ease;" onmouseover="this.style.background='#E5E5EA'" onmouseout="this.style.background='#F2F2F7'">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: linear-gradient(135deg, #007AFF, #0056CC);">
                                    <i class="fas fa-university text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color: #1D1D1F;">Bancos</div>
                                    <small class="text-muted">Gestionar cuentas bancarias</small>
                                </div>
                                <i class="fas fa-chevron-right ms-auto text-muted"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('admin.categoriaIngresos.index') }}" class="text-decoration-none">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background: #F2F2F7; transition: all 0.2s ease;" onmouseover="this.style.background='#E5E5EA'" onmouseout="this.style.background='#F2F2F7'">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: linear-gradient(135deg, #34C759, #248A3D);">
                                    <i class="fas fa-folder-plus text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color: #1D1D1F;">Categorias de Ingresos</div>
                                    <small class="text-muted">Clasificacion de ingresos</small>
                                </div>
                                <i class="fas fa-chevron-right ms-auto text-muted"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('admin.categoriaGastos.index') }}" class="text-decoration-none">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background: #F2F2F7; transition: all 0.2s ease;" onmouseover="this.style.background='#E5E5EA'" onmouseout="this.style.background='#F2F2F7'">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: linear-gradient(135deg, #FF3B30, #D70015);">
                                    <i class="fas fa-folder-minus text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color: #1D1D1F;">Categorias de Gastos</div>
                                    <small class="text-muted">Clasificacion de gastos</small>
                                </div>
                                <i class="fas fa-chevron-right ms-auto text-muted"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('admin.estadosDiario.index') }}" class="text-decoration-none">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background: #F2F2F7; transition: all 0.2s ease;" onmouseover="this.style.background='#E5E5EA'" onmouseout="this.style.background='#F2F2F7'">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: linear-gradient(135deg, #FF9500, #C93400);">
                                    <i class="fas fa-chart-bar text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color: #1D1D1F;">Estados del Diario</div>
                                    <small class="text-muted">Estados de movimientos contables</small>
                                </div>
                                <i class="fas fa-chevron-right ms-auto text-muted"></i>
                            </div>
                        </a>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-semibold mb-3" style="color: #1D1D1F;">
                    <i class="fas fa-university me-2" style="color: #007AFF;"></i>Sincronizacion Bankinter
                </h6>
                <div class="p-3 rounded-3 mb-3" style="background: #F0F7FF; border: 1px solid #D1E5FF;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2" style="color: #007AFF;"></i>
                        <span style="font-size: 14px; color: #1D1D1F;">La sincronizacion automatica se ejecuta diariamente a las 06:00</span>
                    </div>
                </div>
                <a href="{{ route('admin.bankinter.index') }}" class="text-decoration-none">
                    <div class="d-flex align-items-center p-3 rounded-3" style="background: #F2F2F7; transition: all 0.2s ease;" onmouseover="this.style.background='#E5E5EA'" onmouseout="this.style.background='#F2F2F7'">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; background: linear-gradient(135deg, #5856D6, #3634A3);">
                            <i class="fas fa-sync-alt text-white"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="color: #1D1D1F;">Gestionar cuentas y ver historial</div>
                            <small class="text-muted">Configuracion de cuentas Bankinter, sincronizacion manual e historial</small>
                        </div>
                        <i class="fas fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </a>

            </div>
        </div>
    </div>

</div>{{-- end accordion --}}

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.btn-toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = this.parentElement.querySelector('input');
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Prompt preview (live on blur)
    var promptTextarea = document.getElementById('promptTextarea');
    var promptPreview = document.getElementById('promptPreview');
    if (promptTextarea && promptPreview) {
        promptTextarea.addEventListener('blur', function() {
            var content = this.value || 'No hay contenido para mostrar.';
            fetch('/markdown-preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ content: content })
            }).then(function(r) { return r.text(); })
            .then(function(html) { promptPreview.innerHTML = html; })
            .catch(function() { promptPreview.innerHTML = content.replace(/\n/g, '<br>'); });
        });
    }

    // Open section from URL hash (e.g. #secIA)
    var hash = window.location.hash;
    if (hash && document.querySelector(hash)) {
        var collapseEl = document.querySelector(hash);
        if (collapseEl) {
            var bsCollapse = new bootstrap.Collapse(collapseEl, { show: true });
        }
    }
});

// ============================================================================
// Bankinter - gestion del formulario create/edit inline
// ============================================================================
function bankinterToggleForm() {
    var wrapper = document.getElementById('bankinterFormWrapper');
    if (!wrapper) return;
    wrapper.style.display = (wrapper.style.display === 'none') ? 'block' : 'none';
}

function bankinterResetForm() {
    var form = document.getElementById('bankinterForm');
    if (!form) return;
    form.reset();
    form.action = "{{ route('configuracion.credenciales.bankinter.store') }}";
    document.getElementById('bankinterMethod').value = 'POST';
    document.getElementById('bankinterFormTitle').textContent = 'Nueva credencial Bankinter';
    document.getElementById('bankinterSubmitLabel').textContent = 'Crear credencial';
    document.getElementById('bankinterPassword').required = true;
    document.getElementById('bankinterPassword').placeholder = '';
    document.getElementById('bankinterPasswordRequired').style.display = '';
    document.getElementById('bankinterPasswordHelp').style.display = 'none';
    document.getElementById('bankinterEnabled').checked = true;
}

function bankinterEdit(id, data) {
    var form = document.getElementById('bankinterForm');
    if (!form) return;

    // Cambiar a modo edit
    var actionBase = "{{ url('configuracion/credenciales/bankinter') }}";
    form.action = actionBase + '/' + id;
    document.getElementById('bankinterMethod').value = 'PUT';
    document.getElementById('bankinterFormTitle').textContent = 'Editar credencial #' + id;
    document.getElementById('bankinterSubmitLabel').textContent = 'Guardar cambios';

    // Rellenar campos
    document.getElementById('bankinterAlias').value = data.alias || '';
    document.getElementById('bankinterLabel').value = data.label || '';
    document.getElementById('bankinterUser').value = data.user || '';
    document.getElementById('bankinterIban').value = data.iban || '';
    document.getElementById('bankinterBankId').value = data.bank_id || '';
    document.getElementById('bankinterEnabled').checked = !!data.enabled;

    // Password: opcional en edit
    var pw = document.getElementById('bankinterPassword');
    pw.value = '';
    pw.required = false;
    pw.placeholder = 'Dejar vacio para no cambiar';
    document.getElementById('bankinterPasswordRequired').style.display = 'none';
    document.getElementById('bankinterPasswordHelp').style.display = 'block';

    // Mostrar el formulario y hacer scroll
    var wrapper = document.getElementById('bankinterFormWrapper');
    if (wrapper) wrapper.style.display = 'block';
    wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>
@endsection
