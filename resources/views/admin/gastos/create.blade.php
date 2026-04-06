@extends('layouts.appAdmin')

@section('content')
<style>
    .inactive-sort {
        color: #0F1739;
        text-decoration: none;
    }
    .active-sort {
        color: #757191;
    }
</style>
<div class="container-fluid">
    <h2 class="mb-3">{{ __('Crear Gasto') }}</h2>
    <hr class="mb-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            
            <form action="{{ route('admin.gastos.store') }}" method="POST" class="mb-4" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="estado_id">Estado</label>
                    <select name="estado_id" id="estado_id" class="form-select">
                        <option value="">Seleccione un estado</option>
                        @foreach ($estados as $estado)
                        <option value="{{$estado->id}}" {{ old('categoria_id') == $estado->id ? 'selected' : '' }}>{{$estado->nombre}}</option>
                        @endforeach
                    </select>
                    @error('estado_id')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="categoria_id">Categoría</label>
                    <select name="categoria_id" id="categoria_id" class="form-select">
                        <option value="">Seleccione una categoría</option>
                        @foreach ($categorias ?? [] as $categoria)
                            <option value="{{$categoria->id}}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{$categoria->nombre}}</option>
                        @endforeach
                    </select>
                    @error('categoria_id')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group mb-3">
                    <label for="bank_id">Banco</label>
                    <select name="bank_id" id="bank_id" class="form-select">
                        <option value="">Seleccione un banco</option>
                        @foreach ($bancos ?? [] as $banco)
                            <option value="{{$banco->id}}" {{ old('bank_id') == $banco->id ? 'selected' : '' }}>{{$banco->nombre}}</option>
                        @endforeach
                    </select>
                    @error('bank_id')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group mb-3">
                    <label for="title">Título</label>
                    <input type="text" class="form-control" name="title" id="title" placeholder="Título" value="{{ old('title') }}">
                    @error('title')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group mb-3">
                    <label for="quantity">Importe</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" placeholder="Cantidad" step="0.01" value="{{ old('quantity') }}">
                    @error('quantity')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="quantity">Fecha del Gasto</label>
                    <input type="date" class="form-control" name="date" id="date" placeholder="Fecha del gasto" value="{{ old('date') }}">
                    @error('date')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group mb-3">
                    <label for="factura_foto">Subir archivo</label>
                    <input type="file" class="form-control" name="factura_foto" id="factura_foto" style="display: none;">
                    <div class="dropzone" id="file-upload"></div>
                    @error('factura_foto')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn bg-color-primero">Crear Gasto</button>
            </form>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/dropzone@5.7.0/dist/min/dropzone.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.7.0/dist/min/dropzone.min.js"></script>

@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    Dropzone.autoDiscover = false;
    var dataTransfer = new DataTransfer();
    
    var myDropzone = new Dropzone("#file-upload", {
        url: "#",
        autoProcessQueue: false,
        clickable: '#factura_foto',
        maxFiles: 1,
        init: function() {
            this.on("addedfile", function(file) {
                dataTransfer.items.clear();
                dataTransfer.items.add(file);
                document.getElementById('factura_foto').files = dataTransfer.files;
            });
            
            this.on("removedfile", function(file) {
                dataTransfer.items.remove(file);
                document.getElementById('factura_foto').files = dataTransfer.files;
            });
        }
    });
</script>
@endsection
