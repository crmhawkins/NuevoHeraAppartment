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
                    <th>Excluir Semana</th>
                    <th>Tipo de Actualización</th>
                    <th>Valor</th>
                    <th>Restricciones</th>
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
                        <!-- Campo oculto para garantizar que siempre se envíe algo -->
                        <input type="hidden" name="updates[0][exclude_weekends]" value="0">
                        <!-- Checkbox real -->
                        <input type="checkbox" name="updates[0][exclude_weekends]" class="form-check-input exclude-weekends" value="1">
                        Excluir Sábados y Domingos
                    </td>
                    <td>
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
                    </td>



                    <td>
                        <select name="updates[0][update_type]" class="form-select update-type-select">
                            <option value="rate">Rate</option>
                            <option value="availability">Availability</option>
                            <option value="min_stay">Min Stay</option>
                            <option value="stop_sell">Stop Sell</option>
                            <option value="restrictions">Restrictions</option>
                        </select>
                    </td>
                    <td><input type="text" name="updates[0][value]" class="form-control"></td>
                    <td style="max-width: 300px">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label>Precio</label>
                                <input type="text" name="updates[0][rate]" class="form-control">
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
                                <div class="form-check">
                                    <input type="radio" name="updates[0][closed_to_arrival]" value="" class="form-check-input" checked>
                                    <label class="form-check-label">No Cambiar</label>
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
                                <div class="form-check">
                                    <input type="radio" name="updates[0][closed_to_departure]" value="" class="form-check-input" checked>
                                    <label class="form-check-label">No Cambiar</label>
                                </div>
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
                                <div class="form-check">
                                    <input type="radio" name="updates[0][stop_sell]" value="" class="form-check-input" checked>
                                    <label class="form-check-label">No Cambiar</label>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <Label>Min Estancia</Label>
                                <input type="number" name="updates[0][min_stay]" class="form-control" placeholder="Min Estancia">
                            </div>
                            <div class="col-12 mb-3">
                                <Label>Min Estancia Llegada</Label>
                                <input type="number" name="updates[0][min_stay_arrival]" class="form-control" placeholder="Min Estancia Llegada">
                            </div>
                            <div class="col-12 mb-3">
                                <Label>Min Estancia Salida</Label>
                                <input type="number" name="updates[0][min_stay_through]" class="form-control" placeholder="Min Estancia Salida">
                            </div>
                            <div class="col-12 mb-3">
                                <Label>Max Estancia</Label>
                                <input type="number" name="updates[0][max_stay]" class="form-control" placeholder="Max Estancia">
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

<script>
    let rowIndex = 1;

    // document.getElementById('addRow').addEventListener('click', () => {
    //     const table = document.getElementById('updatesTable').getElementsByTagName('tbody')[0];
    //     const newRow = table.rows[0].cloneNode(true);

    //     Array.from(newRow.querySelectorAll('input, select')).forEach((input) => {
    //         const name = input.getAttribute('name');
    //         input.setAttribute('name', name.replace(/\d+/, rowIndex));
    //         input.value = '';

    //         if (input.classList.contains('room-type-select')) {
    //             input.innerHTML = '<option value="" disabled selected>Seleccione un tipo de habitación</option>';
    //         }
    //     });

    //     table.appendChild(newRow);
    //     rowIndex++;
    // });

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
        }

        if (input.type === 'hidden' && input.value === '0') {
            input.value = '0'; // Asegurar que los hidden inputs vuelvan a su valor por defecto
        }

        // Reiniciar selects
        if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });

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
