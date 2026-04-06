@extends('layouts.appUser')

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center">Seleccion las imagenes del DNI</h5>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 text-center">
            <img src="https://apartamentosalgeciras.com/wp-content/uploads/2022/09/Logo-Hawkins-Suites.svg" alt="" class="img-fluid mb-3 w-50 m-auto">
        </div>
        <div class="col-sm-12">
            
            <div class="card">
                <div class="card-header bg-color-primero">
                    Seleccion las imagenes del DNI
                </div>
                <div class="card-body">
                    <form action="{{route('dni.dniUpload', $id)}}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{$id}}">                       
                        <div class="files mt-3">
                            <input type="file" accept="image/*" class="file-input" capture="camera" name="fontal" id="fontal" onchange="previewImage(event)">
                            <button type="button" class="btn btn-secundario fs-5 w-100" onclick="document.getElementById('fontal').click()"><i class="fa-solid fa-camera me-2"></i> FRONTAL</button>
                            <img id="image-preview" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
                        </div>
                        <div class="files mt-3">
                            <input type="file" accept="image/*" class="file-input" capture="camera" name="trasera" id="trasera" onchange="previewImage2(event)">
                            <button type="button" class="btn btn-secundario fs-5 w-100" onclick="document.getElementById('image_desague').click()"><i class="fa-solid fa-camera me-2"></i> TRASERA</button>
                            <img id="image-preview2" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
                        </div>
                        <div class="mb-3">
                           <button class="btn btn-terminar w-100">Enviar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .file-input {
      display: none;
    }
</style>
@endsection

@section('scripts')
<script>
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('image-preview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
    // Si ya existe una URL de imagen, mostrar la vista previa al cargar la página
    window.onload = function() {
        var imageUrl = "{{ $frontal }}";

        if (imageUrl) {
            var output = document.getElementById('image-preview');
            output.src = imageUrl;
            output.style.display = 'block';
        }

        var imageUrl2 = "{{ $trasera }}";
        if (imageUrl2) {
            var output = document.getElementById('image-preview2');
            output.src = imageUrl2;
            output.style.display = 'block';
        }
    };

    function previewImage2(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('image-preview2');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

</script>
@endsection
