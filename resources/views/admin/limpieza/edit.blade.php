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
    <h2 class="mb-3">{{ __('Editar Limpieza') }}</h2>
    {{-- <a href="{{route('apartamentos.admin.create')}}" class="btn bg-color-quinto">Crear banco</a> --}}
    <hr class="mb-5">
    <div class="row justify-content-center">
      <div class="col-md-12">
        @if (session('status'))
              <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif
        <!-- Formulario de búsqueda -->
        <form action="{{ route('admin.limpiezaFondo.update', $limpieza->id) }}" method="POST" class="mb-4">
          @csrf
            <div class="form-grup mb-5">
              <label for="form-label">Apartamento</label>
              <select class="form-select" name="apartamento_id" id="apartamento_id">
                @if ($apartamentos)
                  @foreach ($apartamentos as $apartamento)
                    <option value="{{ $apartamento->id }}" {{ $apartamento->id == $limpieza->apartamento_id ? 'selected' : '' }}>
                      @if (isset($apartamento->edificioName->nombre))
                          {{ $apartamento->edificioName->nombre }}
                      @endif - {{ $apartamento->titulo }}
                    </option>                  
                  @endforeach
                @endif
              </select>
                {{-- <input type="text" class="form-control" name="nombre" placeholder="Nombre banco" value="{{ $limpieza->apartamentoName->edificioName->nombre }} - {{ $limpieza->apartamentoName->nombre }}"> --}}
            </div>
            <div class="form-grup mb-5">
              <label for="form-label">Fecha</label>
                <input type="date" class="form-control" name="clave" value="{{$limpieza->fecha}}">
            </div>
              <button type="submit" class="btn bg-color-primero">Actualizar Edificio</button>
        </form>

        </div>
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')

@endsection

