@extends('layouts.appAdmin')

@section('content')
<div class="container">
    <h1>Rate Plans</h1>
    <a href="{{ route('channex.ratePlans.create') }}" class="btn btn-primary mb-3">Crear Rate Plan</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Título</th>
                <th>Propiedad</th>
                <th>Tipo de Habitación</th>
                <th>Moneda</th>
                <th>Modo de Venta</th>
                <th>Modo de Tarifa</th>
                <th>Opciones</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ratePlans as $ratePlan)
                <tr>
                    <td>{{ $ratePlan->title }}</td>
                    <td>{{ $ratePlan->property->nombre ?? 'N/A' }}</td>
                    <td>{{ $ratePlan->roomType->title ?? 'N/A' }}</td>
                    <td>{{ $ratePlan->currency }}</td>
                    <td>{{ $ratePlan->sell_mode }}</td>
                    <td>{{ $ratePlan->rate_mode }}</td>
                    <td>
                        @if ($ratePlan->options)
                            <pre>{{ json_encode($ratePlan->options, JSON_PRETTY_PRINT) }}</pre>
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('channex.ratePlans.edit', $ratePlan->id) }}" class="btn btn-warning btn-sm">Editar</a>
                        <form action="{{ route('channex.ratePlans.destroy', $ratePlan->id) }}" method="POST" style="display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
