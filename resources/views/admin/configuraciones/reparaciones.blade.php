@extends('admin.configuraciones.layout')

@section('config-title', 'Reparaciones')
@section('config-subtitle', 'Gestiona los técnicos de reparaciones y sus horarios de disponibilidad')

@section('config-content')
<div class="config-card">
    <div class="config-card-header d-flex justify-content-between align-items-center">
        <h5>
            <i class="fas fa-tools"></i>
            Reparaciones (Técnicos)
        </h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTecnico">
            <i class="fas fa-plus me-2"></i>Añadir Técnico
        </button>
    </div>
    <div class="config-card-body">
        @if (count($reparaciones) > 0)
            @foreach ($reparaciones as $reparacion)
                <div class="list-item-card mb-3">
                    <form action="{{ route('configuracion.reparaciones.update', $reparacion->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_method" value="PUT">
                        <div class="row g-3">
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Nombre
                                </label>
                                <input class="form-control" name="nombre" value="@isset($reparacion->nombre){{$reparacion->nombre}}@endisset"/>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-phone"></i>
                                    Teléfono
                                </label>
                                <input class="form-control" name="telefono" value="@isset($reparacion->telefono){{$reparacion->telefono}}@endisset"/>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-clock"></i>
                                    Hora Inicio
                                </label>
                                <select class="form-select" name="hora_inicio">
                                    @for ($hour = 0; $hour < 24; $hour++)
                                        @php
                                            $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                            $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                        @endphp
                                        <option value="{{ $hora1 }}" @isset($reparacion->hora_inicio) @if($reparacion->hora_inicio == $hora1) selected @endif @endisset>{{ $hora1 }}</option>
                                        <option value="{{ $hora2 }}" @isset($reparacion->hora_inicio) @if($reparacion->hora_inicio == $hora2) selected @endif @endisset>{{ $hora2 }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-clock"></i>
                                    Hora Fin
                                </label>
                                <select class="form-select" name="hora_fin">
                                    @for ($hour = 0; $hour < 24; $hour++)
                                        @php
                                            $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                            $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                        @endphp
                                        <option value="{{ $hora1 }}" @isset($reparacion->hora_fin) @if($reparacion->hora_fin == $hora1) selected @endif @endisset>{{ $hora1 }}</option>
                                        <option value="{{ $hora2 }}" @isset($reparacion->hora_fin) @if($reparacion->hora_fin == $hora2) selected @endif @endisset>{{ $hora2 }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-12">
                                <label class="form-label">
                                    <i class="fas fa-calendar-week"></i>
                                    Días
                                </label>
                                <div class="d-flex flex-wrap gap-2">
                                    @php
                                        $dias = ['lunes' => 'L', 'martes' => 'M', 'miercoles' => 'X', 'jueves' => 'J', 'viernes' => 'V', 'sabado' => 'S', 'domingo' => 'D'];
                                    @endphp
                                    @foreach($dias as $diaKey => $diaLabel)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="{{$diaKey}}" id="{{$diaKey}}_{{$reparacion->id}}" value="{{$loop->iteration}}" @if($reparacion->$diaKey == true) checked @endif>
                                            <label class="form-check-label" for="{{$diaKey}}_{{$reparacion->id}}">{{$diaLabel}}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-12">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-save me-1"></i>Actualizar
                                </button>
                                <button data-id="{{$reparacion->id}}" id="eliminarTecnico" type="button" class="btn btn-danger btn-sm w-100">
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
                <p class="text-muted">No hay técnicos de reparación configurados</p>
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
                    <i class="fas fa-user-plus me-2" style="color: #007AFF;"></i>
                    Añadir Técnico
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('configuracion.reparaciones.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-user"></i>
                                Nombre
                            </label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-phone"></i>
                                Teléfono
                            </label>
                            <input type="text" class="form-control" name="telefono" placeholder="34600600600">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-clock"></i>
                                Hora Inicio
                            </label>
                            <select class="form-select" name="hora_inicio" required>
                                @for ($hour = 0; $hour < 24; $hour++)
                                    @php
                                        $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                        $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                    @endphp
                                    <option value="{{ $hora1 }}">{{ $hora1 }}</option>
                                    <option value="{{ $hora2 }}">{{ $hora2 }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-clock"></i>
                                Hora Fin
                            </label>
                            <select class="form-select" name="hora_fin" required>
                                @for ($hour = 0; $hour < 24; $hour++)
                                    @php
                                        $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                        $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                    @endphp
                                    <option value="{{ $hora1 }}">{{ $hora1 }}</option>
                                    <option value="{{ $hora2 }}">{{ $hora2 }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-calendar-week"></i>
                                Días Disponibles
                            </label>
                            <div class="d-flex flex-wrap gap-3">
                                @php
                                    $dias = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
                                @endphp
                                @foreach($dias as $diaKey => $diaLabel)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="{{$diaKey}}" id="new_{{$diaKey}}" value="{{$loop->iteration}}">
                                        <label class="form-check-label" for="new_{{$diaKey}}">{{$diaLabel}}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Añadir Técnico
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Eliminar técnico
    const deleteBtns = document.querySelectorAll('#eliminarTecnico');
    deleteBtns.forEach(function(btn) {
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
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.message || 'Error al eliminar');
                            });
                        }
                        return response.json();
                    })
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
});
</script>
@endsection

