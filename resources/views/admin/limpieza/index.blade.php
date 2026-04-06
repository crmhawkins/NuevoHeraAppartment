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
    <h2 class="mb-3">{{ __('Limpieza Apartamento') }}</h2>
    <a href="{{route('admin.limpiezaFondo.create')}}" class="btn bg-color-quinto">Crear limpieza</a>
    <hr class="mb-5">
    <div class="row justify-content-center">

        <div class="col-md-12">
            @if (session('status'))
                 <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <!-- Formulario de búsqueda -->
            <form action="{{ route('admin.limpiezaFondo.index') }}" method="GET" class="mb-4">
                <div class="input-group mb-5">
                    <input type="text" class="form-control" name="search" placeholder="Buscar Apartamento" value="{{ request()->get('search') }}">
                    <button type="submit" class="btn bg-color-primero">Buscar</button>
                </div>
            </form>

            <table class="table table-striped table-hover">
                <thead>

                    <tr>
                        <th scope="col">
                            <a href="{{ route('admin.limpiezaFondo.index', ['sort' => 'apartamento_id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                               class="{{ request('sort') == 'apartamento_id' ? 'active-sort' : 'inactive-sort' }}">
                                Apartamento
                                @if (request('sort') == 'apartamento_id')
                                    <i class="fa {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col">
                            <a href="{{ route('admin.limpiezaFondo.index', ['sort' => 'fecha', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                               class="{{ request('sort') == 'fecha' ? 'active-sort' : 'inactive-sort' }}">
                               Fecha
                                @if (request('sort') == 'fecha')
                                    <i class="fa {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th scope="col" class="inactive-sort" style="width: 200px;">Acción</th>
                    </tr>
                </thead>


                <tbody>
                    @if (count($apartamentos) >0 )
                        @foreach ($apartamentos as $apartamento)
                            <tr>
                                <td>{{$apartamento->apartamentoName->titulo}}</td>
                                <td>{{$apartamento->fecha}}</td>
                                <td style="width:auto;">
                                    <a href="{{route('admin.limpiezaFondo.edit', $apartamento->id)}}" class="btn btn-secundario">Editar</a>
                                    <form action="{{ route('admin.limpiezaFondo.destroy', $apartamento->id) }}" method="POST" style="display: inline;" class="delete-form">
                                        @csrf
                                        <button type="button" class="btn btn-danger delete-btn">Eliminar</button>
                                    </form>
                                    {{-- <a href="{{route('clientes.destroy', $cliente->id)}}" class="btn btn-danger">Eliminar</a> --}}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <h5>No hay apartamentos para limpieza a fondo</h5>
                    @endif
                </tbody>
            </table>
            <!-- Paginación links -->
            {{-- {!! $apartamentos->appends(['search' => request()->get('search')])->links('pagination::bootstrap-5') !!} --}}

        </div>
    </div>
</div>
@endsection

{{-- @include('sweetalert::alert') --}}

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Verificar si SweetAlert2 está definido
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 is not loaded');
            return;
        }

        // Botones de eliminar
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const form = this.closest('form');
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection

