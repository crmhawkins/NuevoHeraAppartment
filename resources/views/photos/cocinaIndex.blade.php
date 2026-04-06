@extends('layouts.appPersonal')

@section('volver')
    <button class="back" type="button" onclick="history.back()"><i class="fa-solid fa-angle-left"></i></button>
@endsection

@section('title')
{{ __('Subidas de fotos de la Cocina Común') }}
@endsection

@section('content')

<div class="apple-container">
    <form action="{{ route('fotos.cocinaStore', $id) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
        @csrf
        
        <!-- Cocina General -->
        <div class="apple-photo-section">
            <div class="apple-photo-header">
                <div class="apple-photo-title">
                    <i class="fa-solid fa-utensils"></i>
                    <span>COCINA GENERAL</span>
                </div>
                <div class="apple-photo-status" id="status-general">
                    <i class="fa-solid fa-circle"></i>
                </div>
            </div>
            <div class="apple-photo-content">
                <input type="file" accept="image/*" class="apple-file-input" capture="camera" name="image_general_cocina" id="image_general_cocina">
                <button type="button" class="apple-camera-btn" onclick="document.getElementById('image_general_cocina').click()">
                    <i class="fa-solid fa-camera"></i>
                    <span>CÁMARA</span>
                </button>
                <div class="apple-preview-container" id="preview-container-general">
                    <img id="image-preview" class="apple-preview-image"/>
                </div>
                <input type="hidden" name="image_general_resized" id="image_general_resized">
            </div>
        </div>

        <!-- Cocina Nevera -->
        <div class="apple-photo-section">
            <div class="apple-photo-header">
                <div class="apple-photo-title">
                    <i class="fa-solid fa-snowflake"></i>
                    <span>COCINA - NEVERA</span>
                </div>
                <div class="apple-photo-status" id="status-nevera">
                    <i class="fa-solid fa-circle"></i>
                </div>
            </div>
            <div class="apple-photo-content">
                <input type="file" accept="image/*" class="apple-file-input" capture="camera" name="image_nevera" id="image_nevera">
                <button type="button" class="apple-camera-btn" onclick="document.getElementById('image_nevera').click()">
                    <i class="fa-solid fa-camera"></i>
                    <span>CÁMARA</span>
                </button>
                <div class="apple-preview-container" id="preview-container-nevera">
                    <img id="image-preview2" class="apple-preview-image"/>
                </div>
                <input type="hidden" name="image_nevera_resized" id="image_nevera_resized">
            </div>
        </div>

        <!-- Cocina Microondas -->
        <div class="apple-photo-section">
            <div class="apple-photo-header">
                <div class="apple-photo-title">
                    <i class="fa-solid fa-microwave"></i>
                    <span>COCINA - MICROONDAS</span>
                </div>
                <div class="apple-photo-status" id="status-microondas">
                    <i class="fa-solid fa-circle"></i>
                </div>
            </div>
            <div class="apple-photo-content">
                <input type="file" accept="image/*" class="apple-file-input" capture="camera" name="image_microondas" id="image_microondas">
                <button type="button" class="apple-camera-btn" onclick="document.getElementById('image_microondas').click()">
                    <i class="fa-solid fa-camera"></i>
                    <span>CÁMARA</span>
                </button>
                <div class="apple-preview-container" id="preview-container-microondas">
                    <img id="image-preview3" class="apple-preview-image"/>
                </div>
                <input type="hidden" name="image_microondas_resized" id="image_microondas_resized">
            </div>
        </div>

        <!-- Cocina Bajos -->
        <div class="apple-photo-section">
            <div class="apple-photo-header">
                <div class="apple-photo-title">
                    <i class="fa-solid fa-drawer"></i>
                    <span>COCINA - BAJOS</span>
                </div>
                <div class="apple-photo-status" id="status-bajos">
                    <i class="fa-solid fa-circle"></i>
                </div>
            </div>
            <div class="apple-photo-content">
                <input type="file" accept="image/*" class="apple-file-input" capture="camera" name="image_bajos" id="image_bajos">
                <button type="button" class="apple-camera-btn" onclick="document.getElementById('image_bajos').click()">
                    <i class="fa-solid fa-camera"></i>
                    <span>CÁMARA</span>
                </button>
                <div class="apple-preview-container" id="preview-container-bajos">
                    <img id="image-preview4" class="apple-preview-image"/>
                </div>
                <input type="hidden" name="image_bajos_resized" id="image_bajos_resized">
            </div>
        </div>

        <!-- Botón Continuar -->
        <div class="apple-continue-section">
            <button id="btn_continuar" class="apple-continue-btn" type="submit" disabled>
                <i class="fa-solid fa-arrow-right"></i>
                <span>CONTINUAR</span>
            </button>
        </div>
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
                
                // Mostrar contenedor de preview
                var containerId = id.replace('image-preview', 'preview-container');
                if (id === 'image-preview') containerId = 'preview-container-general';
                if (id === 'image-preview2') containerId = 'preview-container-nevera';
                if (id === 'image-preview3') containerId = 'preview-container-microondas';
                if (id === 'image-preview4') containerId = 'preview-container-bajos';
                
                var container = document.getElementById(containerId);
                if (container) {
                    container.classList.add('has-image');
                }
                
                // Actualizar estado
                updatePhotoStatus(id);
            }
        }
        
        // Verificar estado inicial del botón continuar
        checkContinueButton();
    };

    // Función para actualizar el estado de las fotos
    function updatePhotoStatus(previewId) {
        var statusId = '';
        if (previewId === 'image-preview') statusId = 'status-general';
        if (previewId === 'image-preview2') statusId = 'status-nevera';
        if (previewId === 'image-preview3') statusId = 'status-microondas';
        if (previewId === 'image-preview4') statusId = 'status-bajos';
        
        var statusElement = document.getElementById(statusId);
        if (statusElement) {
            statusElement.classList.add('completed');
        }
    }

    // Función para verificar si se puede habilitar el botón continuar
    function checkContinueButton() {
        var requiredPhotos = 4; // Total de fotos requeridas
        var completedPhotos = 0;
        
        var statusElements = [
            document.getElementById('status-general'),
            document.getElementById('status-nevera'),
            document.getElementById('status-microondas'),
            document.getElementById('status-bajos')
        ];
        
        statusElements.forEach(function(element) {
            if (element && element.classList.contains('completed')) {
                completedPhotos++;
            }
        });
        
        var continueBtn = document.getElementById('btn_continuar');
        if (continueBtn) {
            if (completedPhotos >= requiredPhotos) {
                continueBtn.disabled = false;
            } else {
                continueBtn.disabled = true;
            }
        }
    }
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
                
                // Mostrar contenedor de preview
                var containerId = previewElementId.replace('image-preview', 'preview-container');
                if (previewElementId === 'image-preview') containerId = 'preview-container-general';
                if (previewElementId === 'image-preview2') containerId = 'preview-container-nevera';
                if (previewElementId === 'image-preview3') containerId = 'preview-container-microondas';
                if (previewElementId === 'image-preview4') containerId = 'preview-container-bajos';
                
                var container = document.getElementById(containerId);
                if (container) {
                    container.classList.add('has-image');
                }
                
                // Actualizar estado y verificar botón continuar
                updatePhotoStatus(previewElementId);
                checkContinueButton();
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
