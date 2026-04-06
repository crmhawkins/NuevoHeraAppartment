@extends('layouts.appAdmin')

@section('content')
<div class="container">
    <h1>Lista de Room Types</h1>

    <!-- Botón para Crear Room Type -->
    <a href="{{ route('channex.roomTypes.create') }}" class="btn btn-primary mb-3">Crear Room Type</a>

    @if ($roomTypes->isEmpty())
        <p class="text-muted">No hay Room Types registrados.</p>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Propiedad</th>
                    <th>Cantidad de Habitaciones</th>
                    <th>Ocupación Adultos</th>
                    <th>Ocupación Niños</th>
                    <th>Ocupación Bebés</th>
                    <th>Ocupación Predeterminada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roomTypes as $roomType)
                    <tr>
                        <td>{{ $roomType->title }}</td>
                        <td>{{ $roomType->property->nombre ?? 'No asignada' }}</td>
                        <td>{{ $roomType->count_of_rooms }}</td>
                        <td>{{ $roomType->occ_adults }}</td>
                        <td>{{ $roomType->occ_children }}</td>
                        <td>{{ $roomType->occ_infants }}</td>
                        <td>{{ $roomType->default_occupancy }}</td>
                        <td>
                            <!-- Botones de Acciones -->
                            <a href="{{ route('channex.roomTypes.edit', $roomType->id) }}" class="btn btn-warning btn-sm">Editar</a>
                            <form action="{{ route('channex.roomTypes.destroy', $roomType->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este Room Type?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
