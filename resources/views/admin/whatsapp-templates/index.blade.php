@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fab fa-whatsapp me-2 text-success"></i>
                        Plantillas WhatsApp
                    </h1>
                    <p class="text-muted mb-0">Gestiona las plantillas de mensajes de WhatsApp Business</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.whatsapp-templates.sync') }}" class="btn btn-outline-info btn-lg">
                        <i class="fas fa-sync-alt me-2"></i>Sincronizar
                    </a>
                    <a href="{{ route('admin.whatsapp-templates.create') }}" class="btn btn-success btn-lg">
                        <i class="fas fa-plus me-2"></i>Nueva Plantilla
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadisticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fab fa-whatsapp fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $templates->count() }}</h3>
                            <small class="opacity-75">Total Plantillas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $templates->where('status', 'APPROVED')->count() }}</h3>
                            <small class="opacity-75">Aprobadas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-clock fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $templates->where('status', 'PENDING')->count() }}</h3>
                            <small class="opacity-75">Pendientes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-danger text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-times-circle fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $templates->where('status', 'REJECTED')->count() }}</h3>
                            <small class="opacity-75">Rechazadas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Plantillas -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Lista de Plantillas ({{ $templates->count() }})
                </h5>
            </div>
        </div>
        <div class="card-body p-0">
            @if($templates->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tablaTemplates">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 py-3 px-4"><span class="fw-semibold">Nombre</span></th>
                                <th class="border-0 py-3 px-4"><span class="fw-semibold">Categoria</span></th>
                                <th class="border-0 py-3 px-4"><span class="fw-semibold">Idioma</span></th>
                                <th class="border-0 py-3 px-4"><span class="fw-semibold">Variables</span></th>
                                <th class="border-0 py-3 px-4"><span class="fw-semibold">Estado</span></th>
                                <th class="border-0 py-3 px-4"><span class="fw-semibold">Actualizado</span></th>
                                <th class="border-0 py-3 px-4"><span class="fw-semibold">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                @php
                                    $bodyComponent = collect($template->components)->firstWhere('type', 'BODY');
                                    $bodyText = $bodyComponent['text'] ?? '';
                                    preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $matches);
                                    $variableCount = count($matches[1]);
                                @endphp
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <i class="fab fa-whatsapp text-success"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $template->name }}</h6>
                                                <small class="text-muted">{{ Str::limit($bodyText, 60) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4">
                                        <span class="badge bg-primary-subtle text-primary px-2 py-1">
                                            {{ $template->category ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4">
                                        <span class="badge bg-info-subtle text-info px-2 py-1">
                                            <i class="fas fa-language me-1"></i>{{ $template->language }}
                                        </span>
                                    </td>
                                    <td class="px-4">
                                        @if($variableCount > 0)
                                            <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                                <i class="fas fa-code me-1"></i>{{ $variableCount }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4">
                                        @switch($template->status)
                                            @case('APPROVED')
                                                <span class="badge bg-success px-2 py-1">
                                                    <i class="fas fa-check-circle me-1"></i> Aprobada
                                                </span>
                                                @break
                                            @case('PENDING')
                                                <span class="badge bg-warning px-2 py-1">
                                                    <i class="fas fa-clock me-1"></i> Pendiente
                                                </span>
                                                @break
                                            @case('REJECTED')
                                                <span class="badge bg-danger px-2 py-1">
                                                    <i class="fas fa-times-circle me-1"></i> Rechazada
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary px-2 py-1">
                                                    <i class="fas fa-question-circle me-1"></i> {{ $template->status ?? 'Desconocido' }}
                                                </span>
                                        @endswitch
                                    </td>
                                    <td class="px-4">
                                        <small class="text-muted">{{ $template->updated_at ? $template->updated_at->format('d/m/Y H:i') : '-' }}</small>
                                    </td>
                                    <td class="px-4">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.whatsapp-templates.check-status', $template->id) }}"
                                               class="btn btn-outline-info btn-sm" title="Verificar estado">
                                                <i class="fas fa-sync-alt"></i>
                                            </a>
                                            @if($template->status === 'APPROVED')
                                                <button type="button" class="btn btn-outline-success btn-sm"
                                                        onclick="abrirModalTest({{ $template->id }}, '{{ $template->name }}', {{ $variableCount }})"
                                                        title="Enviar prueba">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            @endif
                                            <a href="{{ route('admin.whatsapp-templates.edit', $template->id) }}"
                                               class="btn btn-outline-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="confirmarEliminacion({{ $template->id }}, '{{ $template->name }}')"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fab fa-whatsapp fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron plantillas</h5>
                    <p class="text-muted">Sincroniza desde WhatsApp o crea una nueva plantilla.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('admin.whatsapp-templates.sync') }}" class="btn btn-outline-info">
                            <i class="fas fa-sync-alt me-2"></i>Sincronizar desde WhatsApp
                        </a>
                        <a href="{{ route('admin.whatsapp-templates.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Crear Plantilla
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal de Prueba -->
<div class="modal fade" id="modalTest" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane text-success me-2"></i>
                    Enviar Mensaje de Prueba
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTest" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Plantilla: <strong id="testTemplateName"></strong>
                    </p>
                    <div class="mb-3">
                        <label for="test_phone" class="form-label fw-semibold">
                            Numero de telefono <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="test_phone" name="phone"
                               required placeholder="Ej: 34612345678"
                               pattern="[0-9]+"
                               title="Solo numeros, incluye prefijo de pais">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1 text-info"></i>
                            Incluye el prefijo del pais sin + ni espacios
                        </div>
                    </div>
                    <div id="testParamsContainer">
                        <!-- Dynamic parameter fields will be inserted here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i> Enviar Prueba
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#tablaTemplates').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 25,
        order: [[5, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Exito',
            text: '{{ session('success') }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ $errors->first() }}',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Aceptar'
        });
    @endif
});

function abrirModalTest(templateId, templateName, variableCount) {
    document.getElementById('formTest').action = `/admin/whatsapp-templates/${templateId}/test`;
    document.getElementById('testTemplateName').textContent = templateName;

    const container = document.getElementById('testParamsContainer');
    container.innerHTML = '';

    for (let i = 1; i <= variableCount; i++) {
        container.innerHTML += `
            <div class="mb-3">
                <label class="form-label fw-semibold">Variable @{{${i}}}</label>
                <input type="text" class="form-control" name="param_${i}"
                       placeholder="Valor para la variable ${i}" value="test_value_${i}">
            </div>
        `;
    }

    $('#modalTest').modal('show');
}

function confirmarEliminacion(templateId, nombre) {
    Swal.fire({
        title: 'Estas seguro?',
        text: `Quieres eliminar la plantilla "${nombre}"? Se eliminara tambien de WhatsApp Business.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/whatsapp-templates/${templateId}`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.btn-group .btn {
    margin-right: 2px;
    border-radius: 6px !important;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.table td {
    vertical-align: middle;
}

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

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
}
</style>
@endsection
