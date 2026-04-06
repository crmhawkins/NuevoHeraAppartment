<div class="modal fade modalDelete" id="ModalAvatar{{$usuario->id}}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable"
        role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalCenterTitle">Subir Avatar
                </h5>
                <button type="button" class="close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    Puedes pulsa sobre Browse o arrastar la imagen al recuadro gris.
                    <input type="file" class="basic-filepond" id="inputFile">

                </p>
                {{-- <div>
                    <img src="" alt="prueba"  class="preview_image img-fluid">
                </div> --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary"
                    data-bs-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Salir</span>
                </button>
                {{-- <button type="button" class="btn btn-primary ml-1" id="botonAceptarUpload"
                 onclick="aceptarReload()">
                    <i class="bx bx-check d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Aceptar</span>
                </button> --}}
            </div>
        </div>
    </div>
</div>
