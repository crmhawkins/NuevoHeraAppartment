{{-- <div class="container">
    <form action="{{ $action }}" method="POST">
        @csrf
        @if($method ?? false)
        @method($method)
        @endif

        <div class="mb-3">
            <label for="cliente_id" class="form-label">Cliente</label>
            <div class="d-flex align-items-center">
                <select name="cliente_id" id="cliente_id" class="form-select w-75">
                    <option value="">Seleccionar cliente</option>
                    @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @if(old('cliente_id', $presupuesto->cliente_id ?? '') == $cliente->id) selected @endif>
                        {{ $cliente->nombre }} - {{ $cliente->email }}
                    </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#crearClienteModal">
                    Crear Cliente
                </button>
            </div>
        </div>

        <div class="mb-3">
            <h4>Conceptos</h4>
            <table class="table" id="conceptosTable">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Fecha Entrada</th>
                        <th>Fecha Salida</th>
                        <th>Precio por Día</th>
                        <th>Días Totales</th>
                        <th>Precio Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($presupuesto) && $presupuesto->conceptos->count())
                    @foreach($presupuesto->conceptos as $concepto)
                    <tr>
                        <td><input type="text" name="conceptos[{{ $loop->index }}][descripcion]" value="{{ $concepto->descripcion }}" class="form-control" /></td>
                        <td><input type="date" name="conceptos[{{ $loop->index }}][fecha_entrada]" value="{{ $concepto->fecha_entrada }}" class="form-control fecha-entrada" /></td>
                        <td><input type="date" name="conceptos[{{ $loop->index }}][fecha_salida]" value="{{ $concepto->fecha_salida }}" class="form-control fecha-salida" /></td>
                        <td><input type="number" step="0.01" name="conceptos[{{ $loop->index }}][precio_por_dia]" value="{{ $concepto->precio_por_dia }}" class="form-control precio-por-dia" /></td>
                        <td><input type="number" name="conceptos[{{ $loop->index }}][dias_totales]" value="{{ $concepto->dias_totales }}" class="form-control dias-totales" readonly /></td>
                        <td><input type="number" step="0.01" name="conceptos[{{ $loop->index }}][precio_total]" value="{{ $concepto->precio_total }}" class="form-control precio-total" readonly /></td>
                        <td><button type="button" class="btn btn-danger btn-sm removeConcepto">Eliminar</button></td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td><input type="text" name="conceptos[0][descripcion]" class="form-control" /></td>
                        <td><input type="date" name="conceptos[0][fecha_entrada]" class="form-control fecha-entrada" /></td>
                        <td><input type="date" name="conceptos[0][fecha_salida]" class="form-control fecha-salida" /></td>
                        <td><input type="number" step="0.01" name="conceptos[0][precio_por_dia]" class="form-control precio-por-dia" /></td>
                        <td><input type="number" name="conceptos[0][dias_totales]" class="form-control dias-totales" readonly /></td>
                        <td><input type="number" step="0.01" name="conceptos[0][precio_total]" class="form-control precio-total" readonly /></td>
                        <td><button type="button" class="btn btn-danger btn-sm removeConcepto">Eliminar</button></td>
                    </tr>
                    @endif
                </tbody>
            </table>
            <button type="button" id="addConcepto" class="btn btn-primary btn-sm">Añadir Concepto</button>
        </div>

        <div class="mb-3">
            <label for="total" class="form-label">Total</label>
            <input type="text" name="total" id="total" value="{{ old('total', $presupuesto->total ?? 0) }}" class="form-control" readonly />
        </div>

        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>

<!-- Modal para crear cliente -->
<div class="modal fade" id="crearClienteModal" tabindex="-1" aria-labelledby="crearClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearClienteModalLabel">Crear Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="crearClienteForm">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required />
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required />
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" required />
                    </div>
                    <button type="button" id="guardarCliente" class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('#cliente_id').select2({
            placeholder: "Seleccionar cliente",
            allowClear: true
        });

        $('#guardarCliente').on('click', function () {
            const formData = $('#crearClienteForm').serialize();
            $.post("{{ route('clientes.store') }}", formData, function (data) {
                const newOption = new Option(`${data.nombre} - ${data.email}`, data.id, true, true);
                $('#cliente_id').append(newOption).trigger('change');
                $('#crearClienteModal').modal('hide');
            }).fail(function () {
                alert('Error al crear el cliente. Por favor, inténtalo de nuevo.');
            });
        });
    });

    function actualizarDiasYTotales(row) {
        const fechaEntrada = row.querySelector('.fecha-entrada').value;
        const fechaSalida = row.querySelector('.fecha-salida').value;
        const precioPorDia = parseFloat(row.querySelector('.precio-por-dia').value) || 0;

        if (fechaEntrada && fechaSalida) {
            const diasTotales = Math.ceil((new Date(fechaSalida) - new Date(fechaEntrada)) / (1000 * 60 * 60 * 24));
            row.querySelector('.dias-totales').value = diasTotales > 0 ? diasTotales : 0;
            row.querySelector('.precio-total').value = diasTotales > 0 ? (diasTotales * precioPorDia).toFixed(2) : 0;
        }

        actualizarTotalGeneral();
    }

    function actualizarTotalGeneral() {
        let total = 0;
        document.querySelectorAll('.precio-total').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('total').value = total.toFixed(2);
    }

    document.getElementById('addConcepto').addEventListener('click', function () {
        const tbody = document.querySelector('#conceptosTable tbody');
        const index = tbody.children.length;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="conceptos[${index}][descripcion]" class="form-control" /></td>
            <td><input type="date" name="conceptos[${index}][fecha_entrada]" class="form-control fecha-entrada" /></td>
            <td><input type="date" name="conceptos[${index}][fecha_salida]" class="form-control fecha-salida" /></td>
            <td><input type="number" step="0.01" name="conceptos[${index}][precio_por_dia]" class="form-control precio-por-dia" /></td>
            <td><input type="number" name="conceptos[${index}][dias_totales]" class="form-control dias-totales" readonly /></td>
            <td><input type="number" step="0.01" name="conceptos[${index}][precio_total]" class="form-control precio-total" readonly /></td>
            <td><button type="button" class="btn btn-danger btn-sm removeConcepto">Eliminar</button></td>
        `;
        tbody.appendChild(tr);

        tr.querySelector('.fecha-entrada, .fecha-salida, .precio-por-dia').addEventListener('change', function () {
            actualizarDiasYTotales(tr);
        });

        tr.querySelector('.removeConcepto').addEventListener('click', function () {
            tr.remove();
            actualizarTotalGeneral();
        });
    });

    document.querySelectorAll('.fecha-entrada, .fecha-salida, .precio-por-dia').forEach(input => {
        input.addEventListener('change', function () {
            actualizarDiasYTotales(this.closest('tr'));
        });
    });

    document.querySelectorAll('.removeConcepto').forEach(button => {
        button.addEventListener('click', function () {
            this.closest('tr').remove();
            actualizarTotalGeneral();
        });
    });
</script> --}}


<div class="container">
    <form action="{{ $action }}" method="POST" id="formPresupuesto">
        @csrf

        <!-- Paso 1: Selección de Cliente -->
        <div id="step1" class="step active">
            <h4>Paso 1: Selección de Cliente</h4>
            <div class="mb-3">
                <label for="cliente_id" class="form-label">Cliente</label>
                <div class="d-flex align-items-center">
                    <select name="cliente_id" id="cliente_id" class="form-select w-75">
                        <option value="">Seleccionar cliente</option>
                        @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}">{{ $cliente->nombre }} - {{ $cliente->email }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#crearClienteModal">
                        Crear Cliente
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-primary next-step">Siguiente</button>
        </div>

        <!-- Paso 2: Conceptos -->
        <div id="step2" class="step">
            <h4>Paso 2: Agregar Conceptos</h4>
            <table class="table" id="conceptosTable">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Fecha Entrada</th>
                        <th>Fecha Salida</th>
                        <th>Precio por Día</th>
                        <th>Días Totales</th>
                        <th>Precio Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="conceptos[0][descripcion]" class="form-control" /></td>
                        <td><input type="date" name="conceptos[0][fecha_entrada]" class="form-control fecha-entrada" /></td>
                        <td><input type="date" name="conceptos[0][fecha_salida]" class="form-control fecha-salida" /></td>
                        <td><input type="number" step="0.01" name="conceptos[0][precio_por_dia]" class="form-control precio-por-dia" /></td>
                        <td><input type="number" name="conceptos[0][dias_totales]" class="form-control dias-totales" readonly /></td>
                        <td><input type="number" step="0.01" name="conceptos[0][precio_total]" class="form-control precio-total" readonly /></td>
                        <td><button type="button" class="btn btn-danger btn-sm removeConcepto">Eliminar</button></td>
                    </tr>
                </tbody>
            </table>
            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha del presupuesto</label>
                <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', \Carbon\Carbon::now()->toDateString()) }}">
            </div>

            <button type="button" id="addConcepto" class="btn btn-primary btn-sm">Añadir Concepto</button>
            <div class="mt-3">
                <button type="button" class="btn btn-secondary prev-step">Atrás</button>
                <button type="button" class="btn btn-primary next-step">Siguiente</button>
            </div>
        </div>

        <!-- Paso 3: Revisión -->
        <div id="step3" class="step">
            <h4>Paso 3: Revisión</h4>
            <p id="clienteSeleccionado"></p>
            <div id="conceptosResumen"></div>
            <p id="resumenTotal" class="fw-bold fs-5"></p>

            <div class="mt-3">
                <button type="button" class="btn btn-secondary prev-step">Atrás</button>
                <button type="submit" class="btn btn-success">Finalizar</button>
            </div>
        </div>
    </form>
</div>

<!-- Modal para crear cliente -->
<div class="modal fade" id="crearClienteModal" tabindex="-1" aria-labelledby="crearClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearClienteModalLabel">Crear Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="crearClienteForm">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required />
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required />
                    </div>
                    <button type="button" id="guardarCliente" class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const steps = document.querySelectorAll('.step');
        let currentStep = 0;

        function bindConceptoListeners(row) {
            const entrada = row.querySelector('.fecha-entrada');
            const salida = row.querySelector('.fecha-salida');
            const precio = row.querySelector('.precio-por-dia');

            [entrada, salida, precio].forEach(input => {
                input.addEventListener('change', () => {
                    const entradaVal = entrada.value;
                    const salidaVal = salida.value;
                    const precioVal = parseFloat(precio.value) || 0;

                    if (entradaVal && salidaVal) {
                        const start = new Date(entradaVal);
                        const end = new Date(salidaVal);
                        const dias = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                        const diasTotales = dias > 0 ? dias : 0;
                        row.querySelector('.dias-totales').value = diasTotales;
                        row.querySelector('.precio-total').value = (diasTotales * precioVal).toFixed(2);
                    }

                    actualizarTotalGeneral();
                });
            });
        }

        function showStep(stepIndex) {
            steps.forEach((step, index) => {
                step.classList.toggle('active', index === stepIndex);
            });
        }

        document.querySelectorAll('.next-step').forEach(button => {
            button.addEventListener('click', () => {
                currentStep++;
                showStep(currentStep);
            });
        });

        document.querySelectorAll('.prev-step').forEach(button => {
            button.addEventListener('click', () => {
                currentStep--;
                showStep(currentStep);
            });
        });

        showStep(currentStep);

        // Inicializa listeners en inputs iniciales
        document.querySelectorAll('#conceptosTable tbody tr').forEach(tr => bindConceptoListeners(tr));


        document.getElementById('addConcepto').addEventListener('click', function () {
            const tbody = document.querySelector('#conceptosTable tbody');
            const index = tbody.children.length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" name="conceptos[${index}][descripcion]" class="form-control" /></td>
                <td><input type="date" name="conceptos[${index}][fecha_entrada]" class="form-control fecha-entrada" /></td>
                <td><input type="date" name="conceptos[${index}][fecha_salida]" class="form-control fecha-salida" /></td>
                <td><input type="number" step="0.01" name="conceptos[${index}][precio_por_dia]" class="form-control precio-por-dia" /></td>
                <td><input type="number" name="conceptos[${index}][dias_totales]" class="form-control dias-totales" readonly /></td>
                <td><input type="number" step="0.01" name="conceptos[${index}][precio_total]" class="form-control precio-total" readonly /></td>
                <td><button type="button" class="btn btn-danger btn-sm removeConcepto">Eliminar</button></td>
            `;
            tbody.appendChild(tr);
            // Agregar eventos dinámicos
            bindConceptoListeners(tr);

            tr.querySelector('.removeConcepto').addEventListener('click', function () {
                tr.remove();
            });
        });

        // Actualiza la información en el paso 3
        document.querySelector('.next-step:last-of-type').addEventListener('click', function () {
            const cliente = document.querySelector('#cliente_id option:checked').textContent;
            document.getElementById('clienteSeleccionado').textContent = `Cliente: ${cliente}`;

            const conceptos = [];
            document.querySelectorAll('#conceptosTable tbody tr').forEach(tr => {
                const descripcion = tr.querySelector('[name*="[descripcion]"]').value;
                const entrada = tr.querySelector('[name*="[fecha_entrada]"]').value;
                const salida = tr.querySelector('[name*="[fecha_salida]"]').value;
                const dias = tr.querySelector('.dias-totales').value;
                const total = tr.querySelector('.precio-total').value;
                conceptos.push({ descripcion, entrada, salida, dias, total });
            });

            let html = '<table class="table"><thead><tr><th>Descripción</th><th>Entrada</th><th>Salida</th><th>Días</th><th>Total</th></tr></thead><tbody>';
            conceptos.forEach(c => {
                html += `<tr>
                    <td>${c.descripcion}</td>
                    <td>${c.entrada}</td>
                    <td>${c.salida}</td>
                    <td>${c.dias}</td>
                    <td>${c.total} €</td>
                </tr>`;
            });
            html += '</tbody></table>';

            document.getElementById('conceptosResumen').innerHTML = html;
        });

        function actualizarTotalGeneral() {
            let total = 0;
            document.querySelectorAll('.precio-total').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            const resumenTotal = document.getElementById('resumenTotal');
            if (resumenTotal) resumenTotal.textContent = `Total: ${total.toFixed(2)} €`;
        }
        document.querySelectorAll('.next-step').forEach((btn, i, all) => {
            if (i === all.length - 1) {
                btn.addEventListener('click', function () {
                    const cliente = document.querySelector('#cliente_id option:checked').textContent;
                    document.getElementById('clienteSeleccionado').textContent = `Cliente: ${cliente}`;

                    const conceptos = [];
                    document.querySelectorAll('#conceptosTable tbody tr').forEach(tr => {
                        const descripcion = tr.querySelector('[name*="[descripcion]"]').value;
                        const entrada = tr.querySelector('[name*="[fecha_entrada]"]').value;
                        const salida = tr.querySelector('[name*="[fecha_salida]"]').value;
                        const dias = tr.querySelector('.dias-totales').value;
                        const total = tr.querySelector('.precio-total').value;
                        conceptos.push({ descripcion, entrada, salida, dias, total });
                    });

                    let html = '<table class="table"><thead><tr><th>Descripción</th><th>Entrada</th><th>Salida</th><th>Días</th><th>Total</th></tr></thead><tbody>';
                    conceptos.forEach(c => {
                        html += `<tr>
                            <td>${c.descripcion}</td>
                            <td>${c.entrada}</td>
                            <td>${c.salida}</td>
                            <td>${c.dias}</td>
                            <td>${c.total} €</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';

                    document.getElementById('conceptosResumen').innerHTML = html;
                });
            }
        });

    });
</script>

<style>
    .step { display: none; }
    .step.active { display: block; }
</style>
