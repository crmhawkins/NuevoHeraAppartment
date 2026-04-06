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
    <h2 class="mb-3">{{ __('Editar Gasto') }}</h2>
    <hr class="mb-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            
            <form action="{{ route('admin.gastos.update', $gasto->id) }}" method="POST" class="mb-4" enctype="multipart/form-data">
                @csrf
                {{-- @method('PUT') <!-- Importante para indicar que es una actualización --> --}}

                <div class="form-group mb-3">
                    <label for="estado_id">Estado</label>
                    <select name="estado_id" id="estado_id" class="form-select">
                        <option value="">Seleccione un estado</option>
                        @foreach ($estados as $estado)
                            <option value="{{$estado->id}}" {{ $gasto->estado_id == $estado->id ? 'selected' : '' }}>{{$estado->nombre}}</option>
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
                        @foreach ($categorias as $categoria)
                            <option value="{{$categoria->id}}" {{ $gasto->categoria_id == $categoria->id ? 'selected' : '' }}>{{$categoria->nombre}}</option>
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
                        @foreach ($bancos as $banco)
                            <option value="{{$banco->id}}" {{ $gasto->bank_id == $banco->id ? 'selected' : '' }}>{{$banco->nombre}}</option>
                        @endforeach
                    </select>
                    @error('bank_id')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group mb-3">
                    <label for="title">Título</label>
                    <input type="text" class="form-control" name="title" id="title" placeholder="Título del gasto" value="{{ old('title', $gasto->title) }}">
                    @error('title')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group mb-3">
                    <label for="quantity">Importe</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" placeholder="Cantidad" step="0.01" value="{{ old('quantity', $gasto->quantity) }}">
                    @error('quantity')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="date">Fecha del Gasto</label>
                    <input type="date" class="form-control" name="date" id="date" value="{{ old('date', isset($gasto->date) ? \Carbon\Carbon::parse($gasto->date)->format('Y-m-d') : null) }}">

                    @error('date')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                  <label for="factura_foto">Subir archivo</label>
                  <input type="file" class="form-control" name="factura_foto" id="factura_foto">
                  @error('factura_foto')
                      <div class="alert alert-danger">{{ $message }}</div>
                  @enderror
              
                  <!-- Botón de descarga si existe archivo -->
                  @if ($gasto->factura_foto)
                      <a href="{{ route('gastos.download', $gasto->id) }}" class="btn btn-primary mt-2">
                          <i class="fas fa-download"></i> Descargar Archivo
                      </a>
                  @endif
                </div>
              
                
                <button type="submit" class="btn bg-color-primero">Actualizar Gasto</button>
            </form>
        </div>
    </div>
</div>

@endsection
