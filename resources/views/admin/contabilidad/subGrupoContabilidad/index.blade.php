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
    <h2 class="mb-3">Sub Grupo Contable</h2>
    <a href="{{ route('admin.cuentasContables.create') }}" class="btn bg-color-quinto">Añadir sub grupo contable</a>
    <hr class="mb-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <div class="mb-3">
                <form action="{{ route('admin.subGrupoContabilidad.index') }}" method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar..." value="{{ request('search') }}">
                        <select name="subGrupo" class="form-control">
                            <option value="">Selecciona Grupo Contable</option>
                            @foreach ($subgrupos as $subgrupo)
                                <option value="{{ $subgrupo->id }}" {{ request('subGrupo') == $subgrupo->id ? 'selected' : '' }}>{{ $subgrupo->numero }} - {{ $subgrupo->nombre }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                    <th scope="col">
                        <a href="{{ route('admin.subGrupoContabilidad.index', array_merge(request()->query(), ['sort' => 'grupo_id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc'])) }}">
                            Grupo
                            @if (request('sort') == 'grupo_id')
                                <i class="fa {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                            @endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ route('admin.subGrupoContabilidad.index', array_merge(request()->query(), ['sort' => 'numero', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc'])) }}">
                            Número
                            @if (request('sort') == 'numero')
                                <i class="fa {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                            @endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ route('admin.subGrupoContabilidad.index', array_merge(request()->query(), ['sort' => 'nombre', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc'])) }}">
                            Nombre
                            @if (request('sort') == 'nombre')
                                <i class="fa {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                            @endif
                        </a>
                    </th>
                    <th scope="col">
                        <a href="{{ route('admin.subGrupoContabilidad.index', array_merge(request()->query(), ['sort' => 'descripcion', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc'])) }}">
                            Descripción
                            @if (request('sort') == 'descripcion')
                                <i class="fa {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                            @endif
                        </a>
                    </th>
                    <th scope="col">Acciones/Editar</th>
                    <th scope="col">Eliminar</th>
                </tr>
              </thead>
              <tbody>
                  @foreach ($response as $item)
                      <tr>
                          <td>{{ $item->grupo->numero }} - {{ $item->grupo->nombre }}</td>
                          <td>{{ $item->numero }}</td>
                          <td>{{ $item->nombre }}</td>
                          <td>{{ $item->descripcion }}</td>
                          <td>
                              <a href="{{ route('admin.subGrupoContabilidad.edit', $item->id) }}" class="btn btn-secundario">Editar</a>
                          </td>
                          <td>
                              <form action="{{ route('admin.subGrupoContabilidad.destroy', $item->id) }}" method="POST">
                                  @csrf
                                  <button type="button" class="btn btn-danger delete-btn">Eliminar</button>
                              </form>
                          </td>
                      </tr>
                  @endforeach
              </tbody>
            </table>
            {{ $response->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@include('sweetalert::alert')

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
@endsection
