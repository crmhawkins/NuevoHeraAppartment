@extends('layouts.appAdmin')

@section('content')
<style>
    .subcontrataciones-container {
        padding: 24px;
        background: #F2F2F7;
        min-height: calc(100vh - 80px);
    }
    .subcontrataciones-header {
        margin-bottom: 32px;
    }
    .subcontrataciones-header h1 {
        font-size: 34px;
        font-weight: 700;
        color: #1D1D1F;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }
    .subcontrataciones-header p {
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
    }
    .config-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #E5E5EA;
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        border-radius: 16px 16px 0 0;
    }
    .config-card-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #1D1D1F;
        margin: 0;
    }
    .config-card-body {
        padding: 24px;
    }
    .nav-tabs .nav-link {
        font-weight: 600;
        color: #8E8E93;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
        transition: all 0.2s ease;
    }
    .nav-tabs .nav-link:hover {
        color: #1D1D1F;
        border-color: #E5E5EA;
    }
    .nav-tabs .nav-link.active {
        color: #007AFF;
        border-color: #007AFF;
        background: transparent;
    }
    .list-item-card {
        background: #F9F9FB;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #E5E5EA;
    }
    .form-label {
        font-weight: 600;
        color: #1D1D1F;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #E5E5EA;
        padding: 10px 12px;
        transition: all 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
    }
</style>

<div class="subcontrataciones-container">
    <div class="subcontrataciones-header">
        <h1><i class="fas fa-hard-hat me-2 text-primary"></i>Subcontrataciones</h1>
        <p>Gestiona los técnicos, el catálogo de servicios y la asignación de precios desde un solo lugar</p>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="subcontratacionesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tecnicos-tab" data-bs-toggle="tab" data-bs-target="#tecnicos" type="button" role="tab">
                <i class="fas fa-user-hard-hat me-2"></i>Técnicos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="catalogo-tab" data-bs-toggle="tab" data-bs-target="#catalogo" type="button" role="tab">
                <i class="fas fa-wrench me-2"></i>Catálogo de Servicios
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="asignacion-tab" data-bs-toggle="tab" data-bs-target="#asignacion" type="button" role="tab">
                <i class="fas fa-user-cog me-2"></i>Asignación y Precios
            </button>
        </li>
    </ul>

    <div class="tab-content" id="subcontratacionesTabContent">

        {{-- ============================================================ --}}
        {{-- TAB 1: TÉCNICOS --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade show active" id="tecnicos" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-tools me-2"></i>Técnicos</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTecnico">
                        <i class="fas fa-plus me-2"></i>Añadir Técnico
                    </button>
                </div>
                <div class="config-card-body">
                    @if (count($tecnicos) > 0)
                        @foreach ($tecnicos as $tecnico)
                            <div class="list-item-card mb-3">
                                <form action="{{ route('configuracion.reparaciones.update', $tecnico->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label"><i class="fas fa-user"></i> Nombre</label>
                                            <input class="form-control" name="nombre" value="{{ $tecnico->nombre ?? '' }}"/>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label"><i class="fas fa-phone"></i> Teléfono</label>
                                            <input class="form-control" name="telefono" value="{{ $tecnico->telefono ?? '' }}"/>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label"><i class="fas fa-clock"></i> Hora Inicio</label>
                                            <select class="form-select" name="hora_inicio">
                                                @for ($hour = 0; $hour < 24; $hour++)
                                                    @php
                                                        $h1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                        $h2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                    @endphp
                                                    <option value="{{ $h1 }}" @if(($tecnico->hora_inicio ?? '') == $h1) selected @endif>{{ $h1 }}</option>
                                                    <option value="{{ $h2 }}" @if(($tecnico->hora_inicio ?? '') == $h2) selected @endif>{{ $h2 }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label"><i class="fas fa-clock"></i> Hora Fin</label>
                                            <select class="form-select" name="hora_fin">
                                                @for ($hour = 0; $hour < 24; $hour++)
                                                    @php
                                                        $h1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                        $h2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                    @endphp
                                                    <option value="{{ $h1 }}" @if(($tecnico->hora_fin ?? '') == $h1) selected @endif>{{ $h1 }}</option>
                                                    <option value="{{ $h2 }}" @if(($tecnico->hora_fin ?? '') == $h2) selected @endif>{{ $h2 }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-12">
                                            <label class="form-label"><i class="fas fa-calendar-week"></i> Días</label>
                                            <div class="d-flex flex-wrap gap-2">
                                                @php $dias = ['lunes' => 'L', 'martes' => 'M', 'miercoles' => 'X', 'jueves' => 'J', 'viernes' => 'V', 'sabado' => 'S', 'domingo' => 'D']; @endphp
                                                @foreach($dias as $diaKey => $diaLabel)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="{{ $diaKey }}" id="{{ $diaKey }}_{{ $tecnico->id }}" value="{{ $loop->iteration }}" @if($tecnico->$diaKey) checked @endif>
                                                        <label class="form-check-label" for="{{ $diaKey }}_{{ $tecnico->id }}">{{ $diaLabel }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-12">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                                                <i class="fas fa-save me-1"></i>Actualizar
                                            </button>
                                            <button data-id="{{ $tecnico->id }}" type="button" class="btn btn-danger btn-sm w-100 btn-eliminar-tecnico">
                                                <i class="fas fa-trash me-1"></i>Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tools" style="font-size: 48px; color: #C7C7CC; margin-bottom: 16px;"></i>
                            <p class="text-muted">No hay técnicos configurados</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTecnico">
                                <i class="fas fa-plus me-2"></i>Añadir Primer Técnico
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Añadir Técnico -->
            <div class="modal fade" id="addTecnico" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius: 16px; border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                            <h5 class="modal-title fw-semibold">
                                <i class="fas fa-user-plus me-2" style="color: #007AFF;"></i>Añadir Técnico
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{ route('configuracion.reparaciones.store') }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-user"></i> Nombre</label>
                                        <input type="text" class="form-control" name="nombre" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-phone"></i> Teléfono</label>
                                        <input type="text" class="form-control" name="telefono" placeholder="34600600600">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-clock"></i> Hora Inicio</label>
                                        <select class="form-select" name="hora_inicio" required>
                                            @for ($hour = 0; $hour < 24; $hour++)
                                                @php
                                                    $h1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                    $h2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                @endphp
                                                <option value="{{ $h1 }}">{{ $h1 }}</option>
                                                <option value="{{ $h2 }}">{{ $h2 }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-clock"></i> Hora Fin</label>
                                        <select class="form-select" name="hora_fin" required>
                                            @for ($hour = 0; $hour < 24; $hour++)
                                                @php
                                                    $h1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                    $h2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                @endphp
                                                <option value="{{ $h1 }}">{{ $h1 }}</option>
                                                <option value="{{ $h2 }}">{{ $h2 }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label"><i class="fas fa-calendar-week"></i> Días Disponibles</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            @php $diasFull = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo']; @endphp
                                            @foreach($diasFull as $diaKey => $diaLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="{{ $diaKey }}" id="new_{{ $diaKey }}" value="{{ $loop->iteration }}">
                                                    <label class="form-check-label" for="new_{{ $diaKey }}">{{ $diaLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-end mt-4">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Añadir Técnico</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 2: CATÁLOGO DE SERVICIOS --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="catalogo" role="tabpanel">

            <!-- Botones superiores -->
            <div class="d-flex justify-content-end gap-2 mb-4">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria" onclick="resetearFormularioCategoria()">
                    <i class="fas fa-folder-plus me-2"></i>Nueva Categoría
                </button>
                <a href="{{ route('admin.servicios-tecnicos.create') }}" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Nuevo Servicio
                </a>
            </div>

            <!-- Categorías -->
            @if($categorias->isNotEmpty())
                <div class="config-card mb-4">
                    <div class="config-card-header">
                        <h5><i class="fas fa-folder me-2"></i>Categorías</h5>
                    </div>
                    <div class="config-card-body">
                        <div class="row">
                            @foreach($categorias as $categoria)
                                <div class="col-md-4 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">
                                                        @if($categoria->icono)
                                                            <span class="me-2">{!! $categoria->icono !!}</span>
                                                        @endif
                                                        {{ $categoria->nombre }}
                                                    </h6>
                                                    <small class="text-muted">{{ $categoria->servicios_count }} servicios</small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                            onclick="editarCategoria({{ $categoria->id }}, '{{ addslashes($categoria->nombre) }}', '{{ addslashes($categoria->descripcion) }}', '{{ addslashes($categoria->icono) }}', {{ $categoria->orden }}, {{ $categoria->activo ? 'true' : 'false' }})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('admin.servicios-tecnicos.destroyCategoria', $categoria->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Servicios agrupados -->
            @if($servicios->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay servicios técnicos configurados. <a href="{{ route('admin.servicios-tecnicos.create') }}">Crea el primero</a>
                </div>
            @else
                @foreach($serviciosPorCategoria as $categoriaId => $serviciosCat)
                    @php $cat = $categorias->firstWhere('id', $categoriaId); @endphp
                    <div class="config-card mb-4">
                        <div class="config-card-header" style="background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%); border-radius: 16px 16px 0 0;">
                            <h5 style="color: #fff;">
                                <i class="fas fa-folder me-2"></i>
                                {{ $cat ? $cat->nombre : 'Sin categoría' }}
                                <span class="badge bg-light text-dark ms-2">{{ $serviciosCat->count() }}</span>
                            </h5>
                        </div>
                        <div class="config-card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;">Orden</th>
                                            <th>Nombre</th>
                                            <th>Unidad</th>
                                            <th>Precio Base</th>
                                            <th style="width: 100px;">Estado</th>
                                            <th style="width: 150px;" class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($serviciosCat as $servicio)
                                            <tr>
                                                <td><span class="badge bg-secondary">{{ $servicio->orden }}</span></td>
                                                <td>
                                                    <strong>{{ $servicio->nombre }}</strong>
                                                    @if($servicio->descripcion)
                                                        <br><small class="text-muted">{{ Str::limit($servicio->descripcion, 60) }}</small>
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-info">{{ $servicio->unidad_medida ?: '—' }}</span></td>
                                                <td>
                                                    @if($servicio->precio_base)
                                                        <strong class="text-success">{{ number_format($servicio->precio_base, 2, ',', '.') }} €</strong>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($servicio->activo)
                                                        <span class="badge bg-success">Activo</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('admin.servicios-tecnicos.edit', $servicio->id) }}" class="btn btn-sm btn-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.servicios-tecnicos.destroy', $servicio->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este servicio?')" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Modal Categoría -->
            <div class="modal fade" id="modalCategoria" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content" style="border-radius: 16px; border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                            <h5 class="modal-title fw-semibold" id="modalCategoriaTitle">Nueva Categoría</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formCategoria" method="POST" action="{{ route('admin.servicios-tecnicos.storeCategoria') }}">
                            @csrf
                            <div id="methodField"></div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombre" id="categoria_nombre" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" name="descripcion" id="categoria_descripcion" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Icono (HTML/Font Awesome)</label>
                                    <input type="text" class="form-control" name="icono" id="categoria_icono" placeholder='<i class="fas fa-tools"></i>'>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Orden</label>
                                        <input type="number" class="form-control" name="orden" id="categoria_orden" value="0" min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="activo" id="categoria_activo">
                                            <option value="1">Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- TAB 3: ASIGNACIÓN Y PRECIOS --}}
        {{-- ============================================================ --}}
        <div class="tab-pane fade" id="asignacion" role="tabpanel">

            @if($tecnicosConServicios->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay técnicos registrados. Añade uno en la pestaña "Técnicos" primero.
                </div>
            @else
                <!-- Selector de técnico -->
                <div class="config-card mb-4">
                    <div class="config-card-header">
                        <h5><i class="fas fa-user-cog me-2"></i>Seleccionar Técnico</h5>
                    </div>
                    <div class="config-card-body">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Técnico</label>
                                <select class="form-select" id="selectTecnico">
                                    <option value="">-- Seleccionar técnico --</option>
                                    @foreach($tecnicosConServicios as $tec)
                                        <option value="{{ $tec->id }}">{{ $tec->nombre }} ({{ $tec->servicios_count }} servicios asignados)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary" id="btnGestionarServicios" disabled>
                                    <i class="fas fa-cog me-2"></i>Gestionar Servicios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de técnicos con resumen -->
                <div class="config-card">
                    <div class="config-card-header">
                        <h5><i class="fas fa-list me-2"></i>Resumen de Técnicos</h5>
                    </div>
                    <div class="config-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Técnico</th>
                                        <th>Teléfono</th>
                                        <th>Servicios Asignados</th>
                                        <th style="width: 180px;" class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tecnicosConServicios as $tec)
                                        <tr>
                                            <td>
                                                <strong>{{ $tec->nombre }}</strong>
                                                @if(!$tec->activo)
                                                    <span class="badge bg-secondary ms-2">Inactivo</span>
                                                @endif
                                            </td>
                                            <td>{{ $tec->telefono ?: '—' }}</td>
                                            <td><span class="badge bg-primary">{{ $tec->servicios_count }} servicios</span></td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.tecnicos-servicios.show', $tec->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-cog me-1"></i>Gestionar Servicios
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // Tab persistence via URL hash
    // ==========================================
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
    document.querySelectorAll('#subcontratacionesTabs button').forEach(function(btn) {
        btn.addEventListener('shown.bs.tab', function(e) {
            window.location.hash = e.target.getAttribute('data-bs-target');
        });
    });

    // ==========================================
    // Tab 1: Eliminar técnico
    // ==========================================
    document.querySelectorAll('.btn-eliminar-tecnico').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/configuracion/reparaciones/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success !== false) {
                            Swal.fire('Eliminado', data.message || 'Técnico eliminado correctamente', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'No se pudo eliminar el técnico', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error', error.message || 'No se pudo eliminar el técnico', 'error');
                    });
                }
            });
        });
    });

    // ==========================================
    // Tab 2: Modal Categoría
    // ==========================================
    window.resetearFormularioCategoria = function() {
        document.getElementById('modalCategoriaTitle').textContent = 'Nueva Categoría';
        document.getElementById('formCategoria').action = '{{ route("admin.servicios-tecnicos.storeCategoria") }}';
        document.getElementById('methodField').innerHTML = '';
        document.getElementById('formCategoria').reset();
    };

    window.editarCategoria = function(id, nombre, descripcion, icono, orden, activo) {
        document.getElementById('modalCategoriaTitle').textContent = 'Editar Categoría';
        document.getElementById('formCategoria').action = '{{ route("admin.servicios-tecnicos.updateCategoria", ":id") }}'.replace(':id', id);
        document.getElementById('methodField').innerHTML = '@method("PUT")';
        document.getElementById('categoria_nombre').value = nombre;
        document.getElementById('categoria_descripcion').value = descripcion || '';
        document.getElementById('categoria_icono').value = icono || '';
        document.getElementById('categoria_orden').value = orden || 0;
        document.getElementById('categoria_activo').value = activo ? '1' : '0';

        const modal = new bootstrap.Modal(document.getElementById('modalCategoria'));
        modal.show();
    };

    const modalCategoriaEl = document.getElementById('modalCategoria');
    if (modalCategoriaEl) {
        modalCategoriaEl.addEventListener('hidden.bs.modal', function () {
            resetearFormularioCategoria();
        });
    }

    // ==========================================
    // Tab 3: Selector de técnico
    // ==========================================
    const selectTecnico = document.getElementById('selectTecnico');
    const btnGestionar = document.getElementById('btnGestionarServicios');

    if (selectTecnico && btnGestionar) {
        selectTecnico.addEventListener('change', function() {
            btnGestionar.disabled = !this.value;
        });

        btnGestionar.addEventListener('click', function() {
            const tecnicoId = selectTecnico.value;
            if (tecnicoId) {
                window.location.href = `/admin/tecnicos-servicios/${tecnicoId}`;
            }
        });
    }
});
</script>
@endsection
