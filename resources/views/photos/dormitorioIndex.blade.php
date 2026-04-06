@extends('layouts.appPersonal')
@section('volver')
    <button class="back" type="button" onclick="history.back()"><i class="fa-solid fa-angle-left"></i></button>
@endsection

@section('title')
{{ __('Subidas de fotos del dormitorio')}}
@endsection

@section('content')
<style>
    .file-input {
      display: none;
    }
</style>
<div class="container-fluid">
    <form action="{{ route('actualizar.fotos.dormitorio', $id) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf
        <div class="filesc card p-2">
            <h3 class="text-center text-uppercase fw-bold">Dormitorio General</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_general" id="image_general">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_general').click()"><i class="fa-solid fa-camera me-2"></i> CÁMARA</button>
            <img id="image-preview" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
            <input type="hidden" name="image_general_resized" id="image_general_resized">
        </div>
        <div class="files mt-4 card p-2">
            <h3 class="text-center text-uppercase fw-bold">Dormitorio Almohada</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_almohada" id="image_almohada">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_almohada').click()"><i class="fa-solid fa-camera me-2"></i> CÁMARA</button>
            <img id="image-preview2" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
            <input type="hidden" name="image_almohada_resized" id="image_almohada_resized">
        </div>
        <div class="files mt-4 card p-2">
            <h3 class="text-center text-uppercase fw-bold">Dormitorio Canape</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_canape" id="image_canape">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_canape').click()"><i class="fa-solid fa-camera me-2"></i> CÁMARA</button>
            <img id="image-preview3" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
            <input type="hidden" name="image_canape_resized" id="image_canape_resized">
        </div>
        
        <button id="btn_continuar" class="btn btn-terminar mt-3 w-100 text-uppercase fs-4" type="button">Continuar</button>
    </form>
</div>
@endsection

{{-- @include('sweetalert::alert') --}}

@section('scripts')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    console.log('Limpieza de Apartamento by Hawkins.')

    function resizeImage(event, previewElementId, hiddenInputId) {
        var file = event.target.files[0];
        var reader = new FileReader();
        console.log('redimensionar')
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                var maxWidth = 800; // Max width for the image
                var maxHeight = 800; // Max height for the image
                var width = img.width;
                var height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height *= maxWidth / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width *= maxHeight / height;
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);

                // Show the resized image in the preview element
                var dataurl = canvas.toDataURL('image/jpeg');
                document.getElementById(previewElementId).src = dataurl;

                // Set the resized image data in the hidden input
                document.getElementById(hiddenInputId).value = dataurl;
            }
            img.src = e.target.result;
        }
        reader.readAsDataURL(file);
    }

    document.getElementById('image_general').addEventListener('change', function(event) {
        resizeImage(event, 'image-preview', 'image_general_resized');
        var inputFile = document.getElementById('image_general');
        var file = inputFile.files[0]; // Accede al primer archivo

        if (file) {
            var formData = new FormData();
            formData.append('image', file); // Asegúrate de que la clave 'image' coincida con lo que espera tu backend
            formData.append('elementId', 'image_general'); // Añade el ID del elemento al FormData
            formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
            var url = "{{ route('fotos.dormitorioStore', $id) }}"; 

            $.ajax({
                url: url, // Reemplaza con la URL de tu servidor
                type: 'POST',
                data: formData,
                processData: false,  // Evita que jQuery procese los datos
                contentType: false,  // Evita que jQuery establezca el tipo de contenido
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
                        title: "Imagen subida con exito"
                    });
                    //window.location.href = data.redirect_url;
                    // console.log('Archivo enviado con éxito:', data);
                },
                error: function(xhr, status, error) {
                    console.error('Error al enviar el archivo:', error);
                }
            });
        } else {
            console.log('No se ha cargado ningún archivo.');
        }
    });

    document.getElementById('image_almohada').addEventListener('change', function(event) {
        resizeImage(event, 'image-preview2', 'image_almohada_resized');
        var inputFile = document.getElementById('image_almohada');
        var file = inputFile.files[0]; // Accede al primer archivo

        if (file) {
            var formData = new FormData();
            formData.append('image', file); // Asegúrate de que la clave 'image' coincida con lo que espera tu backend
            formData.append('elementId', 'image_almohada'); // Añade el ID del elemento al FormData
            formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
            var url = "{{ route('fotos.dormitorioStore', $id) }}"; 

            $.ajax({
                url: url, // Reemplaza con la URL de tu servidor
                type: 'POST',
                data: formData,
                processData: false,  // Evita que jQuery procese los datos
                contentType: false,  // Evita que jQuery establezca el tipo de contenido
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
                        title: "Imagen subida con exito"
                    });
                    //window.location.href = data.redirect_url;
                    // console.log('Archivo enviado con éxito:', data);
                },
                error: function(xhr, status, error) {
                    console.error('Error al enviar el archivo:', error);
                }
            });
        } else {
            console.log('No se ha cargado ningún archivo.');
        }
    });

    document.getElementById('image_canape').addEventListener('change', function(event) {
        resizeImage(event, 'image-preview3', 'image_canape_resized');
        var inputFile = document.getElementById('image_canape');
        var file = inputFile.files[0]; // Accede al primer archivo

        if (file) {
            var formData = new FormData();
            formData.append('image', file); // Asegúrate de que la clave 'image' coincida con lo que espera tu backend
            formData.append('elementId', 'image_canape'); // Añade el ID del elemento al FormData
            formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
            var url = "{{ route('fotos.dormitorioStore', $id) }}"; 

            $.ajax({
                url: url, // Reemplaza con la URL de tu servidor
                type: 'POST',
                data: formData,
                processData: false,  // Evita que jQuery procese los datos
                contentType: false,  // Evita que jQuery establezca el tipo de contenido
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
                        title: "Signed in successfully"
                    });
                    //window.location.href = data.redirect_url;
                    // console.log('Archivo enviado con éxito:', data);
                },
                error: function(xhr, status, error) {
                    console.error('Error al enviar el archivo:', error);
                }
            });
        } else {
            console.log('No se ha cargado ningún archivo.');
        }
    });
    
    document.getElementById('btn_continuar').addEventListener('click', function(event) {
        event.preventDefault();
        var url = "{{ route('actualizar.fotos.dormitorio', $id) }}"; 
        var formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
        console.log('Envio de actualizacion dormitorio')
        $.ajax({
            url: url, // Reemplaza con la URL de tu servidor
            type: 'POST',
            data: formData,
            processData: false,  // Evita que jQuery procese los datos
            contentType: false,  // Evita que jQuery establezca el tipo de contenido
            success: function(data) {

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    },
                    didDestroy: () => {
                        // Aquí puedes colocar la acción que desees realizar después de que el toast desaparezca
                        window.location.href = data.redirect_url;
                    }
                });
                Toast.fire({
                    icon: "success",
                    title: data.message
                });
                // console.log('Archivo enviado con éxito:', data);
            },
            error: function(xhr, status, error) {
                console.error('Error al enviar el archivo:', error);
            }
        });
    });

    window.onload = function() {
        var imageUrl = "{{ $imageUrl }}";
        if (imageUrl) {
            var output = document.getElementById('image-preview');
            output.src = imageUrl;
            output.style.display = 'block';
        }
        var imageUrlAlmohada = "{{ $imageUrlAlmohada }}";
        if (imageUrlAlmohada) {
            var output2 = document.getElementById('image-preview2');
            output2.src = imageUrlAlmohada;
            output2.style.display = 'block';
        }
        var imageUrlCanape = "{{ $imageUrlCanape }}";
        if (imageUrlCanape) {
            var output3 = document.getElementById('image-preview3');
            output3.src = imageUrlCanape;
            output3.style.display = 'block';
        }
    };

    function terminarDormitorio() {

    }
</script>
@endsection
