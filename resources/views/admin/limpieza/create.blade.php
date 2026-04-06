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
    <h2 class="mb-3">{{ __('Limpieza a Fondo de un Apartamento') }}</h2>
    <hr class="mb-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
                 <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <!-- Formulario de búsqueda -->
            <form action="{{ route('admin.limpiezaFondo.store') }}" method="POST" class="mb-4">
              @csrf
                <div class="form-grup mb-5">
                  <label for="form-label">Apartamento</label>
                  <select class="form-select @error('apartamento_id') is-invalid @enderror" name="apartamento_id" id="apartamento_id">
                    <option value="">-- Selecione Apartamento --</option>
                    @foreach ($apartamentos as $apartamento)
                        <option value="{{ $apartamento->id }}" {{ old('apartamento_id') == $apartamento->id ? 'selected' : '' }}>
                            @if (isset($apartamento->edificioName->nombre))
                                {{ $apartamento->edificioName->nombre }}
                            @endif - {{ $apartamento->titulo }}
                        </option>
                    @endforeach
                  </select>
                  @error('apartamento_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-grup mb-5">
                  <label for="form-label">Fecha</label>
                    <input type="date" class="form-control @error('fecha') is-invalid @enderror" name="fecha" value="{{ old('fecha') }}">
                    @error('fecha')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                  <button type="submit" class="btn bg-color-primero">Crear Limpieza</button>
              </form>

        </div>
    </div>
</div>
@endsection

@include('sweetalert::alert')
