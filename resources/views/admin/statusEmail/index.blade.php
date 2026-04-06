@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column mb-3">
        <h2 class="mb-0 me-3 encabezado_top">{{ __('Listado de Estados de Email') }}</h2>
    </div>
    <a href="{{ route('admin.statusMail.create') }}" class="btn bg-color-quinto">Crear Estado de Email</a>
    <hr>
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statuses as $status)
                        <tr>
                            <td>{{ $status->name }}</td>
                            <td>
                                <a href="{{ route('admin.statusMail.edit', $status->id) }}" class="btn btn-warning">Editar</a>
                                <form action="{{ route('admin.statusMail.destroy', $status->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
</div>
@endsection
