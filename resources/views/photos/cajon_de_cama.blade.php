@extends('layouts.appPersonal')

@section('volver')
    <button class="back" type="button" onclick="history.back()"><i class="fa-solid fa-angle-left"></i></button>
@endsection

@section('title')
    {{ __('Subida de foto: Cajón de cama') }}
@endsection

@section('content')
<style>
    .file-input {
        display: none;
    }
</style>

<div class="container-fluid">
    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf
        <div class="filesc card p-2">
            <h3 class="text-center text-uppercase fw-bold">Cajón de cama</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image" id="image_cajon">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_cajon').click()">
                <i class="fa-solid fa-camera me-2"></i> CÁMARA
            </button>
            <img id="image-preview" style="max-width: 100%; margin-top: 10px;" />
        </div>

        <button id="btn_continuar" class="btn btn-terminar mt-3 w-100 text-uppercase fs-4" type="submit">Continuar</button>
    </form>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('image_cajon').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Mostrar preview
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('image-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Enviar automáticamente al seleccionar
        const formData = new FormData();
        formData.append('image', file);
        formData.append('elementId', 'image_cajon');
        formData.append('_token', '{{ csrf_token() }}');

        $.ajax({
            url: "{{ route('fotos.cajonDeCamaStore', $id) }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                Swal.fire({
                    toast: true,
                    icon: 'success',
                    title: 'Imagen subida con éxito',
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo subir la imagen'
                });
            }
        });
    });

    document.getElementById('btn_continuar').addEventListener('click', function (e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        $.ajax({
            url: "{{ route('actualizar.fotos.cajonDeCama', $id) }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                Swal.fire({
                    toast: true,
                    icon: 'success',
                    title: data.message,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    didDestroy: () => {
                        window.location.href = data.redirect_url;
                    }
                });
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo actualizar el estado'
                });
            }
        });
    });

    window.onload = function () {
        const imageUrl = "{{ $imageUrl }}";
        if (imageUrl) {
            document.getElementById('image-preview').src = imageUrl;
        }
    };
</script>
@endsection
