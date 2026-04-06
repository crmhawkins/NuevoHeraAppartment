@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <h2 class="mb-3">{{ __('Editar Categoria de Limpieza') }}</h2>
    <hr class="mb-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('status'))
                 <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Formulario de edición -->
            <form action="{{ route('admin.checklists.update', $checklist->id) }}" method="POST" class="mb-4">
              @csrf

              <div class="form-group mb-2">
                  <label for="form-label">Edificio</label>
                  <select name="edificio_id" id="edificio_id" class="form-select">
                      @foreach ($edificios as $edificio)
                          <option value="{{ $edificio->id }}" {{ $checklist->edificio_id == $edificio->id ? 'selected' : '' }}>{{ $edificio->nombre }}</option>
                      @endforeach
                  </select>
              </div>

              <div class="form-group mb-5">
                  <label for="form-label">Nombre de la Categoria</label>
                  <input type="text" class="form-control" name="nombre" value="{{ $checklist->nombre }}">
              </div>

              <!-- Requisitos de Fotos -->
              {{-- <h4>Fotos Requeridas</h4>
              <div id="photo-requirements">
                  @foreach ($checklist->photoRequirements as $requirement)
                  <div class="photo-requirement-wrapper">
                      <div class="form-group mb-3">
                          <label>Nombre de la Foto</label>
                          <input type="text" class="form-control" name="photo_names[]" value="{{ $requirement->nombre }}">
                      </div>
                      <div class="form-group mb-3">
                          <label>Descripción</label>
                          <input type="text" class="form-control" name="photo_descriptions[]" value="{{ $requirement->descripcion }}">
                      </div>
                      <div class="form-group mb-3">
                          <label>Cantidad de fotos</label>
                          <input type="number" class="form-control" name="photo_quantities[]" value="{{ $requirement->cantidad }}">
                      </div>
                      <button type="button" class="btn btn-danger remove-photo-requirement mb-4">Eliminar Foto</button>
                  </div>
                  @endforeach
              </div>
              <button type="button" id="add-photo-requirement" class="btn btn-secondary mb-4 d-block">Añadir Foto</button> --}}

              <button type="submit" class="btn bg-color-primero d-block">Actualizar Categoria</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Añadir nuevo requisito de foto
    document.getElementById('add-photo-requirement').addEventListener('click', function() {
        const photoRequirements = document.getElementById('photo-requirements');
        const newPhotoField = `
            <div class="photo-requirement-wrapper">
                <div class="form-group mb-3">
                    <label>Nombre de la Foto</label>
                    <input type="text" class="form-control" name="photo_names[]" placeholder="Ej: Foto del baño">
                </div>
                <div class="form-group mb-3">
                    <label>Descripción</label>
                    <input type="text" class="form-control" name="photo_descriptions[]" placeholder="Ej: Tomar foto de la limpieza final del baño">
                </div>
                <div class="form-group mb-3">
                    <label>Cantidad de fotos</label>
                    <input type="number" class="form-control" name="photo_quantities[]" value="1">
                </div>
                <button type="button" class="btn btn-danger remove-photo-requirement mb-4">Eliminar Foto</button>
            </div>
        `;
        photoRequirements.insertAdjacentHTML('beforeend', newPhotoField);
    });

    // Eliminar requisito de foto
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-photo-requirement')) {
            const wrapper = event.target.closest('.photo-requirement-wrapper');
            wrapper.remove();
        }
    });
</script>
@endsection
