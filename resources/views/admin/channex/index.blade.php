@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column mb-3">
        <h2 class="mb-0 me-3 encabezado_top">{{ __('Listado de Propiedades') }}</h2>
    </div>
    <hr>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-12">
            <a href="{{ route('channex.createProperty') }}" class="btn btn-primary">Crear Nueva Propiedad</a>
        </div>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Título</th>
                <th>Moneda</th>
                <th>País</th>
                <th>Estado/Provincia</th>
                <th>Ciudad</th>
                <th>Dirección</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Sitio Web</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($properties as $property)
                <tr>
                    <td>{{ $property->nombre }}</td>
                    <td>{{ $property->currency }}</td>
                    <td>{{ $property->country }}</td>
                    <td>{{ $property->state }}</td>
                    <td>{{ $property->city }}</td>
                    <td>{{ $property->address }}</td>
                    <td>{{ $property->email }}</td>
                    <td>{{ $property->phone }}</td>
                    <td>
                        @if($property->website)
                            <a href="{{ $property->website }}" target="_blank">{{ $property->website }}</a>
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        {{-- <a href="{{ route('channex.editProperty', $property->id) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('channex.deleteProperty', $property->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form> --}}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No se encontraron propiedades.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
