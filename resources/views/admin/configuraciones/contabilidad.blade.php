@extends('admin.configuraciones.layout')

@section('config-title', 'Contabilidad')
@section('config-subtitle', 'Gestiona la configuración contable: años, saldos iniciales y formas de pago')

@section('config-content')
<!-- Saldo Inicial -->
<div class="config-card">
    <div class="config-card-header">
        <h5>
            <i class="fas fa-money-bill-wave"></i>
            Saldo Inicial
        </h5>
    </div>
    <div class="config-card-body">
        <form action="{{ route('configuracion.contabilidad.saldo-inicial') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">
                        <i class="fas fa-euro-sign"></i>
                        Saldo Inicial
                    </label>
                    <input type="text" name="saldo_inicial" id="saldo_inicial" class="form-control" value="{{$saldo->saldo_inicial ?? 0}}" placeholder="0.00"/>
                    <small class="form-text text-muted">Saldo inicial del año contable actual</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Actualizar Saldo Inicial
            </button>
        </form>
    </div>
</div>

<!-- Año de Gestión -->
<div class="config-card">
    <div class="config-card-header">
        <h5>
            <i class="fas fa-calendar-alt"></i>
            Año de Gestión
        </h5>
    </div>
    <div class="config-card-body">
        <form action="{{ route('configuracion.contabilidad.update-anio') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i>
                        Año de Gestión
                    </label>
                    <select name="anio" id="anio" class="form-select">
                        <option value="">Selecciona año</option>
                        @foreach ($anios as $item)
                            <option @if($item == $anio) selected @endif value="{{$item}}">{{$item}}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Selecciona el año contable activo</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Actualizar Año
            </button>
        </form>
    </div>
</div>

<!-- Formas de Pago -->
<div class="config-card">
    <div class="config-card-header d-flex justify-content-between align-items-center">
        <h5>
            <i class="fas fa-credit-card"></i>
            Formas de Pago
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createForma">
            <i class="fas fa-plus me-2"></i>Crear Método
        </button>
    </div>
    <div class="config-card-body">
        @if (count($formasPago) > 0)
            <div class="list-group">
                @foreach ($formasPago as $forma)
                    <div class="list-item-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <input id="input_formas" data-id="{{$forma->id}}" type="text" value="{{$forma->nombre}}" class="form-control me-3" style="flex: 1;"/>
                            <button id="delete_btn" data-id="{{$forma->id}}" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-credit-card" style="font-size: 48px; color: #C7C7CC; margin-bottom: 16px;"></i>
                <p class="text-muted">No hay métodos de pago configurados</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createForma">
                    <i class="fas fa-plus me-2"></i>Crear Primer Método
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Modal Crear Forma de Pago -->
<div class="modal fade" id="createForma" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-semibold">
                    <i class="fas fa-credit-card me-2" style="color: #007AFF;"></i>
                    Crear Forma de Pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('formaPago.store')}}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-tag"></i>
                            Nombre de la Forma de Pago
                        </label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Tarjeta de Crédito"/>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear
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
    // Formas de Pago - Actualizar
    const inputsFormasPago = document.querySelectorAll('#input_formas');
    inputsFormasPago.forEach(function(nodo){
        $(nodo).on('change', function(){
            var nuevoValor = this.value;
            var id = $(this).attr('data-id');
            var baseUrl = "{{ route('formaPago.update', ['id' => ':id']) }}";
            var url = baseUrl.replace(':id', id);

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('nombre', nuevoValor);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    });
                    Toast.fire({
                        icon: "success",
                        title: "Forma de pago actualizada correctamente"
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo actualizar la forma de pago'
                    });
                }
            });
        });
    });

    // Formas de Pago - Eliminar
    const deleteBtns = document.querySelectorAll('#delete_btn');
    deleteBtns.forEach(function(nodo){
        $(nodo).on('click', function(){
            var id = $(this).attr('data-id');
            var baseUrl = "{{ route('formaPago.delete', ['id' => ':id']) }}";
            var url = baseUrl.replace(':id', id);

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
                    var formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: 'Forma de pago eliminada correctamente',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar la forma de pago'
                            });
                        }
                    });
                }
            });
        });
    });
});
</script>
@endsection

