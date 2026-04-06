@extends('admin.configuraciones.layout')

@section('config-title', 'Notificaciones')
@section('config-subtitle', 'Gestiona los emails que recibirán las notificaciones del sistema')

@section('config-content')
<div class="config-card">
    <div class="config-card-header d-flex justify-content-between align-items-center">
        <h5>
            <i class="fas fa-bell"></i>
            Notificaciones
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmailModal">
            <i class="fas fa-plus me-2"></i>Añadir Persona
        </button>
    </div>
    <div class="config-card-body">
        @if (count($emailsNotificaciones) > 0)
            @foreach ($emailsNotificaciones as $person)
                <div class="list-item-card mb-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-user"></i>
                                Nombre
                            </label>
                            <input data-id="{{$person->id}}" class="input_persona form-control" name="nombre" type="text" value="{{$person->nombre}}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </label>
                            <input data-id="{{$person->id}}" class="input_persona form-control" name="email" type="email" value="{{$person->email}}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-phone"></i>
                                Teléfono
                            </label>
                            <input data-id="{{$person->id}}" class="input_persona form-control" name="telefono" type="text" value="{{$person->telefono}}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-block">&nbsp;</label>
                            <button id="deletePerson" data-id="{{$person->id}}" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-center py-5">
                <i class="fas fa-bell" style="font-size: 48px; color: #C7C7CC; margin-bottom: 16px;"></i>
                <p class="text-muted">No hay personas configuradas para recibir notificaciones</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                    <i class="fas fa-plus me-2"></i>Añadir Primera Persona
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Modal Añadir Email -->
<div class="modal fade" id="addEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-semibold">
                    <i class="fas fa-user-plus me-2" style="color: #007AFF;"></i>
                    Añadir Persona para Notificaciones
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addEmailForm" method="POST" action="{{ route('configuracion.notificaciones.emails.add') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i>
                            Dirección de Email
                        </label>
                        <input type="email" class="form-control" id="emailAddress" name="email" required placeholder="ejemplo@email.com">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Nombre
                        </label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Nombre completo">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-phone"></i>
                            Teléfono
                        </label>
                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="34600600600">
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Añadir
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
    // Actualizar persona
    const inputsPersona = document.querySelectorAll('.input_persona');
    inputsPersona.forEach(function(input) {
        $(input).on('blur', function() {
            const id = $(this).attr('data-id');
            const field = $(this).attr('name');
            const value = $(this).val();
            
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PUT');
            formData.append(field, value);

            $.ajax({
                url: `/configuracion/notificaciones/emails/${id}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(data) {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: data.message || 'Persona actualizada correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo actualizar la persona'
                        });
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'No se pudo actualizar la persona';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message
                    });
                }
            });
        });
    });

    // Añadir email
    $('#addEmailForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Añadido',
                        text: data.message || 'Persona añadida correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo añadir la persona'
                    });
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'No se pudo añadir la persona';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });

    // Eliminar persona
    const deleteBtns = document.querySelectorAll('#deletePerson');
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
                    fetch(`/configuracion/notificaciones/emails/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire('Eliminado', 'Persona eliminada correctamente', 'success')
                            .then(() => location.reload());
                    })
                    .catch(error => {
                        Swal.fire('Error', 'No se pudo eliminar la persona', 'error');
                    });
                }
            });
        });
    });
});
</script>
@endsection

