@extends('layouts.appPersonal')

@section('volver')
    <button class="back" type="button" onclick="history.back()"><i class="fa-solid fa-angle-left"></i></button>
@endsection

@section('title')
{{ __('Subidas de fotos de la Cocina Común') }}
@endsection

@section('content')
<style>
    .file-input {
      display: none;
    }
</style>
<div class="container-fluid">
    <form action="{{ route('fotos.cocinaStore', $id) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf
        <div class="filesc card p-2">
            <h3 class="text-center text-uppercase fw-bold">Cocina General</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_general_cocina" id="image_general_cocina">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_general_cocina').click()"><i class="fa-solid fa-camera me-2"></i> CÁMARA</button>
            <img id="image-preview" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
            <input type="hidden" name="image_general_resized" id="image_general_resized">
        </div>
        <div class="files mt-4 card p-2">
            <h3 class="text-center text-uppercase fw-bold">Cocina Nevera</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_nevera" id="image_nevera">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_nevera').click()"><i class="fa-solid fa-camera me-2"></i> CÁMARA</button>
            <img id="image-preview2" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
            <input type="hidden" name="image_nevera_resized" id="image_nevera_resized">
        </div>
        <div class="files mt-4 card p-2">
            <h3 class="text-center text-uppercase fw-bold">Cocina Microondas</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_microondas" id="image_microondas">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_microondas').click()"><i class="fa-solid fa-camera me-2"></i> CÁMARA</button>
            <img id="image-preview3" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
            <input type="hidden" name="image_microondas_resized" id="image_microondas_resized">
        </div>
        <div class="files mt-4 card p-2">
            <h3 class="text-center text-uppercase fw-bold">Cocina Bajos</h3>
            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_bajos" id="image_bajos">
            <button type="button" class="btn btn-secundario fs-5" onclick="document.getElementById('image_bajos').click()"><i class="fa-solid fa-camera me-2"></i> CÁMARA</button>
            <img id="image-preview4" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
            <input type="hidden" name="image_bajos_resized" id="image_bajos_resized">
        </div>

        <button id="btn_continuar" class="btn btn-terminar mt-3 w-100 text-uppercase fs-4" type="submit">Continuar</button>
    </form>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.onload = function() {
        var previews = {
            'image-preview': "{{ $imageUrl }}",
            'image-preview2': "{{ $imageUrlNevera }}",
            'image-preview3': "{{ $imageUrlMicroondas }}",
            'image-preview4': "{{ $imageUrlBajos }}"
        };

        for (const [id, url] of Object.entries(previews)) {
            if (url) {
                var output = document.getElementById(id);
                output.src = url;
                output.style.display = 'block';
            }
        }
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    console.log('Limpieza de Apartamento by Hawkins.');

    function resizeImage(event, previewElementId, hiddenInputId) {
        var file = event.target.files[0];
        var reader = new FileReader();
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
    document.getElementById('image_general_cocina').addEventListener('change', function(event) {
        resizeImage(event, 'image-preview', 'image_general_resized');
        var inputFile = document.getElementById('image_general_cocina');
        var file = inputFile.files[0]; // Accede al primer archivo

        if (file) {
            var formData = new FormData();
            formData.append('image', file); // Asegúrate de que la clave 'image' coincida con lo que espera tu backend
            formData.append('elementId', 'image_general_cocina'); // Añade el ID del elemento al FormData
            formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
            var url = "{{ route('fotos.cocinaStore', $id) }}";

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

    document.getElementById('image_nevera').addEventListener('change', function(event) {
        resizeImage(event, 'image-preview2', 'image_nevera_resized');
        var inputFile = document.getElementById('image_nevera');
        var file = inputFile.files[0]; // Accede al primer archivo

        if (file) {
            var formData = new FormData();
            formData.append('image', file); // Asegúrate de que la clave 'image' coincida con lo que espera tu backend
            formData.append('elementId', 'image_nevera'); // Añade el ID del elemento al FormData
            formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
            var url = "{{ route('fotos.cocinaStore', $id) }}";

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

    document.getElementById('image_microondas').addEventListener('change', function(event) {
        resizeImage(event, 'image-preview3', 'image_microondas_resized');
        var inputFile = document.getElementById('image_microondas');
        var file = inputFile.files[0]; // Accede al primer archivo

        if (file) {
            var formData = new FormData();
            formData.append('image', file); // Asegúrate de que la clave 'image' coincida con lo que espera tu backend
            formData.append('elementId', 'image_microondas'); // Añade el ID del elemento al FormData
            formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
            var url = "{{ route('fotos.cocinaStore', $id) }}";

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

    document.getElementById('image_bajos').addEventListener('change', function(event) {
        resizeImage(event, 'image-preview4', 'image_bajos_resized');
        var inputFile = document.getElementById('image_bajos');
        var file = inputFile.files[0]; // Accede al primer archivo

        if (file) {
            var formData = new FormData();
            formData.append('image', file); // Asegúrate de que la clave 'image' coincida con lo que espera tu backend
            formData.append('elementId', 'image_bajos'); // Añade el ID del elemento al FormData
            formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
            var url = "{{ route('fotos.cocinaStore', $id) }}";

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

    document.getElementById('btn_continuar').addEventListener('click', function(event) {
        event.preventDefault();
        var url = "{{ route('actualizar.fotos.cocina', $id) }}";
        var formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}'); // Añade el token CSRF aquí
        //console.log('Click')
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
        var imageUrlNevera = "{{ $imageUrlNevera }}";
        if (imageUrlNevera) {
            var output2 = document.getElementById('image-preview2');
            output2.src = imageUrlNevera;
            output2.style.display = 'block';
        }
        var imageUrlMicroondas = "{{ $imageUrlMicroondas }}";
        if (imageUrlMicroondas) {
            var output3 = document.getElementById('image-preview3');
            output3.src = imageUrlMicroondas;
            output3.style.display = 'block';
        }
        var imageUrlBajos = "{{ $imageUrlBajos }}";
        if (imageUrlBajos) {
            var output4 = document.getElementById('image-preview4');
            output4.src = imageUrlBajos;
            output4.style.display = 'block';
        }
    };
</script>
@endsection
