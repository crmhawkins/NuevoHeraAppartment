@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Crear Plantilla WhatsApp
                    </h1>
                    <p class="text-muted mb-0">Crea una nueva plantilla de mensaje para WhatsApp Business</p>
                </div>
                <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                </a>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.whatsapp-templates.store') }}" method="POST" id="templateForm">
                @csrf

                <!-- Informacion General -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Informacion General
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">
                                    Nombre de la Plantilla <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
                                       maxlength="255"
                                       placeholder="Ej: confirmacion_reserva"
                                       pattern="[a-z0-9_]+"
                                       title="Solo letras minusculas, numeros y guiones bajos"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Solo letras minusculas, numeros y guiones bajos (sin espacios)
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="language" class="form-label fw-semibold">
                                    Idioma <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('language') is-invalid @enderror"
                                        id="language"
                                        name="language"
                                        required>
                                    <option value="">Seleccionar</option>
                                    <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>Espanol (es)</option>
                                    <option value="es_ES" {{ old('language') == 'es_ES' ? 'selected' : '' }}>Espanol - Espana (es_ES)</option>
                                    <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English (en)</option>
                                    <option value="en_US" {{ old('language') == 'en_US' ? 'selected' : '' }}>English - US (en_US)</option>
                                    <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>Francais (fr)</option>
                                    <option value="de" {{ old('language') == 'de' ? 'selected' : '' }}>Deutsch (de)</option>
                                    <option value="it" {{ old('language') == 'it' ? 'selected' : '' }}>Italiano (it)</option>
                                    <option value="pt_BR" {{ old('language') == 'pt_BR' ? 'selected' : '' }}>Portugues - BR (pt_BR)</option>
                                </select>
                                @error('language')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="category" class="form-label fw-semibold">
                                    Categoria <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('category') is-invalid @enderror"
                                        id="category"
                                        name="category"
                                        required>
                                    <option value="">Seleccionar</option>
                                    <option value="MARKETING" {{ old('category') == 'MARKETING' ? 'selected' : '' }}>Marketing</option>
                                    <option value="UTILITY" {{ old('category') == 'UTILITY' ? 'selected' : '' }}>Utilidad</option>
                                    <option value="AUTHENTICATION" {{ old('category') == 'AUTHENTICATION' ? 'selected' : '' }}>Autenticacion</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenido del Mensaje -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-file-alt me-2 text-primary"></i>
                            Contenido del Mensaje
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="header_text" class="form-label fw-semibold">Encabezado (Header)</label>
                                <input type="text"
                                       class="form-control @error('header_text') is-invalid @enderror"
                                       id="header_text"
                                       name="header_text"
                                       value="{{ old('header_text') }}"
                                       maxlength="60"
                                       placeholder="Texto del encabezado (opcional, max 60 caracteres)">
                                @error('header_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Maximo 60 caracteres. Aparece en negrita arriba del mensaje.
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="body_text" class="form-label fw-semibold">
                                    Cuerpo del Mensaje <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('body_text') is-invalid @enderror"
                                          id="body_text"
                                          name="body_text"
                                          rows="6"
                                          maxlength="1024"
                                          placeholder="Escribe el contenido del mensaje. Usa {{1}}, {{2}}, etc. para variables."
                                          required>{{ old('body_text') }}</textarea>
                                @error('body_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-lightbulb me-1 text-warning"></i>
                                    Usa <code>@{{1}}</code>, <code>@{{2}}</code>, etc. para insertar variables dinamicas. Max 1024 caracteres.
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="footer_text" class="form-label fw-semibold">Pie de Pagina (Footer)</label>
                                <input type="text"
                                       class="form-control @error('footer_text') is-invalid @enderror"
                                       id="footer_text"
                                       name="footer_text"
                                       value="{{ old('footer_text') }}"
                                       maxlength="60"
                                       placeholder="Texto del pie de pagina (opcional, max 60 caracteres)">
                                @error('footer_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones (Opcional) -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-semibold text-dark">
                                <i class="fas fa-mouse-pointer me-2 text-primary"></i>
                                Botones (Opcional)
                            </h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addButton()">
                                <i class="fas fa-plus me-1"></i> Agregar Boton
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="buttonsContainer">
                            <!-- Dynamic buttons will be added here -->
                        </div>
                        <div id="noBtnsMsg" class="text-center text-muted py-3">
                            <i class="fas fa-mouse-pointer me-2"></i>
                            No hay botones agregados. Haz clic en "Agregar Boton" para anadir uno.
                        </div>
                    </div>
                </div>

                <!-- Botones de accion -->
                <div class="d-flex justify-content-between mb-4">
                    <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">
                        <i class="fas fa-paper-plane me-2"></i>Crear y Enviar a WhatsApp
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Panel -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-eye me-2 text-primary"></i>
                        Vista Previa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="whatsapp-preview p-3 rounded" style="background-color: #e5ddd5;">
                        <div class="message-bubble bg-white p-3 rounded shadow-sm" style="max-width: 100%;">
                            <div id="previewHeader" class="fw-bold mb-1" style="display:none;"></div>
                            <div id="previewBody" class="mb-1 text-dark" style="white-space: pre-wrap; font-size: 0.9rem;">
                                El contenido del mensaje aparecera aqui...
                            </div>
                            <div id="previewFooter" class="text-muted small" style="display:none;"></div>
                            <div class="text-end mt-1">
                                <small class="text-muted" style="font-size: 0.7rem;">12:00</small>
                            </div>
                        </div>
                        <div id="previewButtons" class="mt-2" style="display:none;">
                            <!-- Preview buttons will appear here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let buttonIndex = 0;

function addButton() {
    const container = document.getElementById('buttonsContainer');
    document.getElementById('noBtnsMsg').style.display = 'none';

    const html = `
        <div class="border rounded p-3 mb-3 position-relative" id="button_${buttonIndex}">
            <button type="button" class="btn btn-outline-danger btn-sm position-absolute" style="top: 8px; right: 8px;"
                    onclick="removeButton(${buttonIndex})">
                <i class="fas fa-times"></i>
            </button>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Tipo</label>
                    <select class="form-select form-select-sm" name="buttons[${buttonIndex}][type]"
                            onchange="toggleButtonFields(${buttonIndex}, this.value)">
                        <option value="URL">URL</option>
                        <option value="PHONE_NUMBER">Telefono</option>
                        <option value="QUICK_REPLY">Respuesta rapida</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Texto del boton</label>
                    <input type="text" class="form-control form-control-sm" name="buttons[${buttonIndex}][text]"
                           maxlength="25" placeholder="Texto" onkeyup="updatePreview()">
                </div>
                <div class="col-md-6 btn-url-field-${buttonIndex}">
                    <label class="form-label fw-semibold small">URL</label>
                    <input type="text" class="form-control form-control-sm" name="buttons[${buttonIndex}][url]"
                           placeholder="https://ejemplo.com">
                </div>
                <div class="col-md-6 btn-phone-field-${buttonIndex}" style="display:none;">
                    <label class="form-label fw-semibold small">Telefono</label>
                    <input type="text" class="form-control form-control-sm" name="buttons[${buttonIndex}][phone_number]"
                           placeholder="34612345678">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    buttonIndex++;
    updatePreview();
}

function removeButton(index) {
    const el = document.getElementById(`button_${index}`);
    if (el) el.remove();
    const container = document.getElementById('buttonsContainer');
    if (container.children.length === 0) {
        document.getElementById('noBtnsMsg').style.display = 'block';
    }
    updatePreview();
}

function toggleButtonFields(index, type) {
    const urlField = document.querySelector(`.btn-url-field-${index}`);
    const phoneField = document.querySelector(`.btn-phone-field-${index}`);
    if (urlField) urlField.style.display = type === 'URL' ? 'block' : 'none';
    if (phoneField) phoneField.style.display = type === 'PHONE_NUMBER' ? 'block' : 'none';
}

function updatePreview() {
    const header = document.getElementById('header_text').value;
    const body = document.getElementById('body_text').value;
    const footer = document.getElementById('footer_text').value;

    const previewHeader = document.getElementById('previewHeader');
    const previewBody = document.getElementById('previewBody');
    const previewFooter = document.getElementById('previewFooter');
    const previewButtons = document.getElementById('previewButtons');

    previewHeader.style.display = header ? 'block' : 'none';
    previewHeader.textContent = header;

    previewBody.textContent = body || 'El contenido del mensaje aparecera aqui...';

    previewFooter.style.display = footer ? 'block' : 'none';
    previewFooter.textContent = footer;

    // Update buttons preview
    const btnTexts = document.querySelectorAll('[name$="[text]"]');
    let buttonsHtml = '';
    btnTexts.forEach(input => {
        if (input.closest('#buttonsContainer') && input.value) {
            buttonsHtml += `<div class="bg-white text-center p-2 rounded shadow-sm mb-1">
                <a href="#" class="text-primary text-decoration-none small fw-semibold">${input.value}</a>
            </div>`;
        }
    });
    previewButtons.innerHTML = buttonsHtml;
    previewButtons.style.display = buttonsHtml ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('header_text').addEventListener('input', updatePreview);
    document.getElementById('body_text').addEventListener('input', updatePreview);
    document.getElementById('footer_text').addEventListener('input', updatePreview);

    const form = document.getElementById('templateForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor, completa todos los campos obligatorios.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        const btnSubmit = document.getElementById('btnSubmit');
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
        btnSubmit.disabled = true;
        form.submit();
    });

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Exito',
            text: '{{ session('success') }}',
            confirmButtonColor: '#3085d6'
        });
    @endif

    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Errores de validacion',
            text: '{{ $errors->first() }}',
            confirmButtonColor: '#d33'
        });
    @endif
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 12px 12px 0 0 !important;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
    padding: 0.75rem 1rem;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-control-sm, .form-select-sm {
    padding: 0.4rem 0.75rem;
}

.form-label {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.whatsapp-preview {
    min-height: 150px;
}

.message-bubble {
    border-radius: 8px 8px 8px 0 !important;
}

@media (max-width: 768px) {
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }

    .d-flex.justify-content-between .btn {
        width: 100%;
    }
}
</style>
@endsection
