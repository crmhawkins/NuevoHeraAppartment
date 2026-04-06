<div class="modal fade modalDelete" id="ModalDelete{{$dataId}}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable"
        role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalCenterTitle">¿Estas seguro que deseas eliminar?
                </h5>
                <button type="button" class="close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    Este processo es irrevocable.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary"
                    data-bs-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">No</span>
                </button>
                <button type="button" class="btn btn-primary ml-1"
                    data-bs-dismiss="modal" id="botonAceptar{{$dataId}}" data-id="{{$dataId}}" onclick="botonAceptar({{$dataId}})">
                    <i class="bx bx-check d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Si</span>
                </button>
            </div>
        </div>
    </div>
</div>

