@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-edit me-2 text-primary"></i>
                        Editar Plantilla: {{ $template->name }}
                    </h1>
                    <p class="text-muted mb-0">Modifica el contenido de la plantilla de WhatsApp</p>
                </div>
                <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                </a>
            </div>
        </div>
    </div>

    <!-- Info de la plantilla -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <i class="fab fa-whatsapp fa-2x mb-2"></i>
                    <h6 class="mb-1">{{ $template->name }}</h6>
                    <small>Nombre</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-language fa-2x mb-2"></i>
                    <h6 class="mb-1">{{ $template->language }}</h6>
                    <small>Idioma</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-tag fa-2x mb-2"></i>
                    <h6 class="mb-1">{{ $template->category }}</h6>
                    <small>Categoria</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            @php
                $statusColors = [
                    'APPROVED' => 'success',
                    'PENDING' => 'warning',
                    'REJECTED' => 'danger',
                ];
                $statusColor = $statusColors[$template->status] ?? 'secondary';
            @endphp
            <div class="card shadow-sm border-0 bg-gradient-{{ $statusColor }} text-white">
                <div class="card-body text-center">
                    <i class="fas fa-{{ $template->status === 'APPROVED' ? 'check-circle' : ($template->status === 'PENDING' ? 'clock' : 'times-circle') }} fa-2x mb-2"></i>
                    <h6 class="mb-1">{{ $template->status }}</h6>
                    <small>Estado</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.whatsapp-templates.update', $template->id) }}" method="POST" id="templateForm">
                @csrf
                @method('PUT')

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Nota:</strong> Al guardar cambios, la plantilla se reenviara a WhatsApp para revision. El nombre, idioma y categoria no se pueden modificar.
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
                                       value="{{ old('header_text', $headerText) }}"
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
                                          required>{{ old('body_text', $bodyText) }}</textarea>
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
                                       value="{{ old('footer_text', $footerText) }}"
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
                            <!-- Existing buttons will be loaded here -->
                        </div>
                        <div id="noBtnsMsg" class="text-center text-muted py-3" style="{{ count($buttons) > 0 ? 'display:none;' : '' }}">
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
                    <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                        <i class="fas fa-save me-2"></i>Guardar y Reenviar a WhatsApp
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
                            <div id="previewHeader" class="fw-bold mb-1" style="{{ $headerText ? '' : 'display:none;' }}">{{ $headerText }}</div>
                            <div id="previewBody" class="mb-1 text-dark" style="white-space: pre-wrap; font-size: 0.9rem;">{{ $bodyText ?: 'El contenido del mensaje aparecera aqui...' }}</div>
                            <div id="previewFooter" class="text-muted small" style="{{ $footerText ? '' : 'display:none;' }}">{{ $footerText }}</div>
                            <div class="text-end mt-1">
                                <small class="text-muted" style="font-size: 0.7rem;">12:00</small>
                            </div>
                        </div>
                        <div id="previewButtons" class="mt-2" style="{{ count($buttons) > 0 ? '' : 'display:none;' }}">
                            @foreach($buttons as $btn)
                                <div class="bg-white text-center p-2 rounded shadow-sm mb-1">
                                    <a href="#" class="text-primary text-decoration-none small fw-semibold">{{ $btn['text'] ?? '' }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let buttonIndex = 0;

function addButton(type = 'URL', text = '', url = '', phone = '') {
    const container = document.getElementById('buttonsContainer');
    document.getElementById('noBtnsMsg').style.display = 'none';

    const idx = buttonIndex;
    const html = `
        <div class="border rounded p-3 mb-3 position-relative" id="button_${idx}">
            <button type="button" class="btn btn-outline-danger btn-sm position-absolute" style="top: 8px; right: 8px;"
                    onclick="removeButton(${idx})">
                <i class="fas fa-times"></i>
            </button>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Tipo</label>
                    <select class="form-select form-select-sm" name="buttons[${idx}][type]"
                            onchange="toggleButtonFields(${idx}, this.value)">
                        <option value="URL" ${type === 'URL' ? 'selected' : ''}>URL</option>
                        <option value="PHONE_NUMBER" ${type === 'PHONE_NUMBER' ? 'selected' : ''}>Telefono</option>
                        <option value="QUICK_REPLY" ${type === 'QUICK_REPLY' ? 'selected' : ''}>Respuesta rapida</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Texto del boton</label>
                    <input type="text" class="form-control form-control-sm" name="buttons[${idx}][text]"
                           maxlength="25" placeholder="Texto" value="${text}" onkeyup="updatePreview()">
                </div>
                <div class="col-md-6 btn-url-field-${idx}" style="${type === 'URL' ? '' : 'display:none;'}">
                    <label class="form-label fw-semibold small">URL</label>
                    <input type="text" class="form-control form-control-sm" name="buttons[${idx}][url]"
                           placeholder="https://ejemplo.com" value="${url}">
                </div>
                <div class="col-md-6 btn-phone-field-${idx}" style="${type === 'PHONE_NUMBER' ? '' : 'display:none;'}">
                    <label class="form-label fw-semibold small">Telefono</label>
                    <input type="text" class="form-control form-control-sm" name="buttons[${idx}][phone_number]"
                           placeholder="34612345678" value="${phone}">
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
    // Load existing buttons
    @foreach($buttons as $btn)
        addButton(
            '{{ $btn['type'] ?? 'URL' }}',
            '{{ addslashes($btn['text'] ?? '') }}',
            '{{ addslashes($btn['url'] ?? '') }}',
            '{{ addslashes($btn['phone_number'] ?? '') }}'
        );
    @endforeach

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
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
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
            title: 'Error',
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

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
}

.bg-gradient-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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
