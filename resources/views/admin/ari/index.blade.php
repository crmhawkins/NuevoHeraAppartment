@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Actualizar Tarifas, Disponibilidad y Restricciones</h2>
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

    <form action="{{ route('ari.updateRates') }}" method="POST">
        @csrf
        <table class="table table-bordered" id="updatesTable">
            <thead>
                <tr>
                    <th>Propiedad</th>
                    <th>Tipo de Habitación</th>
                    <th>Rate Plan</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Excluir</th>
                    {{-- <th>Excluir Semana</th> --}}
                    <th>Configuracion</th>
                    <th>Estancia Disponible</th>
                    {{-- <th>Restricciones</th> --}}
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="updates[0][property_id]" class="form-select property-select" required>
                            <option value="" disabled selected>Seleccione una propiedad</option>
                            @foreach ($properties as $property)
                                <option value="{{ $property->id_channex }}">{{ $property->nombre }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="updates[0][room_type_id]" class="form-select room-type-select" required>
                            <option value="" disabled selected>Seleccione un tipo de habitación</option>
                        </select>
                    </td>
                    <td>
                        <select name="updates[0][rate_plan_id]" class="form-select rate-plan-select">
                            <option value="" disabled selected>Seleccione un Rate Plan</option>
                        </select>
                    </td>
                    <td><input type="date" name="updates[0][date_from]" class="form-control" required></td>
                    <td><input type="date" name="updates[0][date_to]" class="form-control"></td>
                    <td>
                        <div class="row">
                            <div class="col-md-6 col-sm-12">
                                <!-- Campo oculto para garantizar que siempre se envíe algo -->
                                <input type="hidden" name="updates[0][exclude_weekends]" value="0">
                                <!-- Checkbox real -->
                                <input type="checkbox" name="updates[0][exclude_weekends]" class="form-check-input exclude-weekends" value="1">
                                Excluir Sábados y Domingos
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <!-- Campo oculto para enviar un valor por defecto cuando no se marca -->
                                <input type="hidden" name="updates[0][only_weekends]" value="0">
                                <!-- Checkbox real -->
                                <input type="checkbox" name="updates[0][only_weekends]" class="form-check-input only-weekends" value="1">
                                Solo Fines de Semana

                                <!-- Selector de días específicos de fines de semana -->
                                <select name="updates[0][weekend_days]" class="form-select weekend-days mt-2" style="display: none;">
                                    <option value="both" selected>Ambos días</option>
                                    <option value="saturday">Solo Sábado</option>
                                    <option value="sunday">Solo Domingo</option>
                                </select>
                            </div>
                        </div>
                    </td>

                    <td>
                        <select name="updates[0][update_type]" class="form-select update-type-select">
                            <option value="availability">Basica</option>
                            <option value="restrictions">Avanzada</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="updates[0][value]" class="form-control availability-value" placeholder="Valor de disponibilidad" style="display: none;">
                        <div class="availability-radio">
                            <div class="form-check">
                                <input type="radio" name="updates[0][value]" value="1" class="form-check-input">
                                <label class="form-check-label">Sí</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="updates[0][value]" value="0" class="form-check-input">
                                <label class="form-check-label">No</label>
                            </div>
                        </div>
                    </td>
                    <td class="restrictions-fields" style="display: none; max-width: 300px">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label>Precio</label>
                                <input type="number" name="updates[0][rate]" class="form-control rate-field" placeholder="Ej: 5000 para 50.00">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Min Estancia</label>
                                <input type="number" name="updates[0][min_stay]" class="form-control" placeholder="Min Estancia">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Min Estancia Llegada</label>
                                <input type="number" name="updates[0][min_stay_arrival]" class="form-control" placeholder="Min Estancia Llegada">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Min Estancia Salida</label>
                                <input type="number" name="updates[0][min_stay_through]" class="form-control" placeholder="Min Estancia Salida">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Maz Estancia</label>
                                <input type="number" name="updates[0][max_stay]" class="form-control" placeholder="Min Estancia Salida">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Cerrado a la Venta</label>
                                <div class="form-check">
                                    <input type="radio" name="updates[0][stop_sell]" value="1" class="form-check-input">
                                    <label class="form-check-label">Sí</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="updates[0][stop_sell]" value="0" class="form-check-input">
                                    <label class="form-check-label">No</label>
                                </div>

                            </div>
                            <div class="col-12 mb-3">
                                <label>Cerrado a Llegadas</label>
                                <div class="form-check">
                                    <input type="radio" name="updates[0][closed_to_arrival]" value="1" class="form-check-input">
                                    <label class="form-check-label">Sí</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="updates[0][closed_to_arrival]" value="0" class="form-check-input">
                                    <label class="form-check-label">No</label>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label>Cerrado a Salidas</label>
                                <div class="form-check">
                                    <input type="radio" name="updates[0][closed_to_departure]" value="1" class="form-check-input">
                                    <label class="form-check-label">Sí</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="updates[0][closed_to_departure]" value="0" class="form-check-input">
                                    <label class="form-check-label">No</label>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-row">Eliminar</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="button" id="addRow" class="btn btn-secondary">Añadir Fila</button>
        <button type="submit" class="btn btn-primary mt-4 d-block w-100">Enviar</button>
    </form>
</div>
<style>
    #sidebar {
        display: none
    }
</style>
<script>

document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener("change", function (e) {
        if (e.target.classList.contains("update-type-select")) {
            const row = e.target.closest("tr");

            if (!row) return; // Si no encuentra la fila, termina la ejecución

            const valueCell = row.querySelector(".availability-value").closest("td"); // Celda de disponibilidad
            const restrictionsCell = row.querySelector(".restrictions-fields").closest("td"); // Celda de restricciones
            const restrictionsFields = row.querySelector(".restrictions-fields"); // Contenedor interno
            const availabilityInput = row.querySelector(".availability-value"); // Input de número
            const availabilityRadio = row.querySelector(".availability-radio"); // Radio buttons

            if (!valueCell || !restrictionsCell || !restrictionsFields) return; // Si algún elemento falta, no hace nada

            // Obtener el encabezado de la columna (8ª columna, índice 7)
            const tableHeader = document.querySelector("#updatesTable thead tr th:nth-child(8)");

            if (e.target.value === "availability") {
                valueCell.style.display = "table-cell"; // Mostrar celda de disponibilidad
                restrictionsCell.style.display = "none"; // Ocultar celda de restricciones
                restrictionsFields.style.display = "none"; // Ocultar contenido de restricciones
                
                // Mostrar radio buttons y ocultar input de número
                if (availabilityRadio) availabilityRadio.style.display = "block";
                if (availabilityInput) availabilityInput.style.display = "none";
                
                // Cambiar el encabezado a "Estancia Disponible"
                if (tableHeader) {
                    tableHeader.textContent = "Estancia Disponible";
                }
            } else {
                valueCell.style.display = "none"; // Ocultar celda de disponibilidad
                restrictionsCell.style.display = "table-cell"; // Mostrar celda de restricciones
                restrictionsFields.style.display = "block"; // Asegurar que el contenido interno se muestre
                
                // Ocultar radio buttons y mostrar input de número (aunque no se vea la celda)
                if (availabilityRadio) availabilityRadio.style.display = "none";
                if (availabilityInput) availabilityInput.style.display = "block";
                
                // Cambiar el encabezado a "Información"
                if (tableHeader) {
                    tableHeader.textContent = "Información";
                }
            }
        }
    });
});



    let rowIndex = 1;

    document.getElementById('addRow').addEventListener('click', () => {
    const table = document.getElementById('updatesTable').getElementsByTagName('tbody')[0];
    const newRow = table.rows[0].cloneNode(true);

    // Actualizar los nombres de los inputs para evitar conflictos
    Array.from(newRow.querySelectorAll('input, select')).forEach((input) => {
        const name = input.getAttribute('name');
        input.setAttribute('name', name.replace(/\d+/, rowIndex));

        // Resetear valores para que la nueva fila empiece limpia
        if (input.type === 'text' || input.type === 'number' || input.type === 'date') {
            input.value = '';
        }

        // Desmarcar checkboxes y restablecer hidden inputs a 0
        if (input.type === 'checkbox') {
            input.checked = false;
            input.removeAttribute('checked'); // Evita que se mantenga el estado de la fila anterior
        }
         // **Solución definitiva para los radio buttons**
         if (input.type === 'radio') {
            input.checked = false; // Desmarcar radio button
            input.removeAttribute('checked'); // Asegurar que no se hereda selección
        }

        if (input.type === 'hidden' && input.value === '0') {
            input.value = '0'; // Asegurar que los hidden inputs vuelvan a su valor por defecto
        }

        // Reiniciar selects
        if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });

    // Reiniciar visibilidad para evitar estilos heredados de la fila original
    const valueField = newRow.querySelector(".availability-value");
    const restrictionsFields = newRow.querySelector(".restrictions-fields");
    const updateTypeSelect = newRow.querySelector(".update-type-select");
    const availabilityRadio = newRow.querySelector(".availability-radio");

    if (valueField) valueField.closest("td").style.display = "table-cell"; // Mostrar valor por defecto
    if (restrictionsFields) restrictionsFields.style.display = "none"; // Ocultar restricciones por defecto
    
    // Configurar visibilidad de radio buttons e input
    if (availabilityRadio) availabilityRadio.style.display = "block"; // Mostrar radio buttons por defecto
    if (valueField) valueField.style.display = "none"; // Ocultar input de número por defecto

    // Asegurar que el select de la nueva fila empiece en "Availability"
    if (updateTypeSelect) updateTypeSelect.value = "availability";

    // Agregar la nueva fila a la tabla
    table.appendChild(newRow);
    rowIndex++;
});


    document.getElementById('updatesTable').addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('property-select')) {
            const propertyId = e.target.value;
            const roomTypeSelect = e.target.closest('tr').querySelector('.room-type-select');

            fetch(`/channex/ari/room-types/${propertyId}`)
                .then((response) => response.json())
                .then((data) => {
                    roomTypeSelect.innerHTML = '<option value="" disabled selected>Seleccione un tipo de habitación</option>';
                    data.forEach((roomType) => {
                        roomTypeSelect.innerHTML += `<option value="${roomType.id_channex}">${roomType.title}</option>`;
                    });
                })
                .catch((error) => {
                    console.error('Error al cargar tipos de habitación:', error);
                    roomTypeSelect.innerHTML = '<option value="" disabled>Error al cargar</option>';
                });
        }
    });
</script>

<script>
   document.addEventListener('change', (e) => {
        if (e.target.classList.contains('room-type-select')) {
            const roomTypeId = e.target.value;
            const propertyId = e.target.closest('tr').querySelector('.property-select').value;
            const ratePlanSelect = e.target.closest('tr').querySelector('.rate-plan-select');

            if (roomTypeId && propertyId) {
                // Llamada al backend para obtener los Rate Plans desde la base de datos
                fetch(`/channex/rate-plans/${propertyId}/${roomTypeId}`)
                    .then((response) => response.json())
                    .then((data) => {
                        ratePlanSelect.innerHTML = '<option value="" disabled selected>Seleccione un Rate Plan</option>';
                        data.forEach((ratePlan) => {
                            ratePlanSelect.innerHTML += `<option value="${ratePlan.id_channex}">${ratePlan.title}</option>`;
                        });
                    })
                    .catch((error) => {
                        console.error('Error al cargar Rate Plans:', error);
                        ratePlanSelect.innerHTML = '<option value="" disabled>Error al cargar</option>';
                    });
            }
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('only-weekends')) {
            const weekendDaysSelect = e.target.closest('td').querySelector('.weekend-days');
            if (e.target.checked) {
                weekendDaysSelect.style.display = 'block';
            } else {
                weekendDaysSelect.style.display = 'none';
                weekendDaysSelect.value = 'both'; // Reinicia al valor predeterminado
            }
        }

        if (e.target.classList.contains('exclude-weekends')) {
            const onlyWeekendsCheckbox = e.target.closest('td').querySelector('.only-weekends');
            const weekendDaysSelect = e.target.closest('td').querySelector('.weekend-days');

            if (e.target.checked) {
                onlyWeekendsCheckbox.checked = false; // Desmarcar "Solo fines de semana"
                onlyWeekendsCheckbox.disabled = true; // Desactivar checkbox de "Solo fines de semana"
                weekendDaysSelect.style.display = 'none'; // Ocultar selector
                weekendDaysSelect.value = 'both'; // Reinicia al valor predeterminado
            } else {
                onlyWeekendsCheckbox.disabled = false; // Activar checkbox de "Solo fines de semana"
            }
        }
    });

</script>


@endsection
