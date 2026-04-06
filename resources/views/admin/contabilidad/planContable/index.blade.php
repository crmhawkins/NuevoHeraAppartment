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
    <h2 class="mb-3">Plan General Contable</h2>
    <hr class="mb-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <table class="table table-striped table-hover">
              <thead>
                  <tr>
                      <th>Número</th>
                      <th>Nombre</th>
                      {{-- <th>Descripción</th> --}}
                      <th>Nivel</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach ($grupos as $grupo)
                      <tr>
                        
                          <td><strong>{{ $grupo->numero }}</strong></td>
                          <td><strong>{{ $grupo->nombre }}</strong></td>
                          {{-- <td>{{ $grupo->descripcion }}</td> --}}
                          <td>Grupo</td>
                      </tr>
                      @foreach ($grupo->subGrupos as $subGrupo)
                          <tr>
                              <td>{{ $subGrupo->numero }}</td>
                              <td>{{ $subGrupo->nombre }}</td>
                              {{-- <td>{{ $subGrupo->nombre }}</td> --}}
                              <td>SubGrupo</td>
                          </tr>
                          @foreach ($subGrupo->cuentas as $cuenta)
                              <tr>
                                  <td>{{ $cuenta->numero }}</td>
                                  <td>{{ $cuenta->nombre }}</td>
                                  {{-- <td>{{ $cuenta->nombre }}</td> --}}
                                  <td>Cuenta</td>
                              </tr>
                              @foreach ($cuenta->subCuentas as $subCuenta)
                                  <tr>
                                      <td>{{ $subCuenta->numero }}</td>
                                      <td>{{ $subCuenta->nombre }}</td>
                                      {{-- <td>{{ $subCuenta->nombre }}</td> --}}
                                      <td>SubCuenta</td>
                                  </tr>
                                  @foreach ($subCuenta->cuentasHijas as $cuentaHija)
                                      <tr>
                                          <td>{{ $cuentaHija->numero }}</td>
                                          <td>{{ $cuentaHija->nombre }}</td>
                                          {{-- <td>{{ $cuentaHija->nombre }}</td> --}}
                                          <td>SubCuenta Hija</td>
                                      </tr>
                                  @endforeach
                              @endforeach
                          @endforeach
                      @endforeach
                  @endforeach
              </tbody>
          </table>
            {{-- {{ $response->appends(request()->query())->links() }} --}}
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
