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
                    <form action="{{route('dni.store')}}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{$id}}">                       
                        <div class="files mt-3">
                            <input type="file" accept="image/*" class="file-input" capture="camera" name="image_desague" id="image_desague" onchange="previewImage3(event)">
                            <button type="button" class="btn btn-secundario fs-5 w-100" onclick="document.getElementById('image_desague').click()"><i class="fa-solid fa-camera me-2"></i> PASAPORTE</button>
                            <img id="image-preview3" style="max-width: 100%; max-height: auto; margin-top: 10px;"/>
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