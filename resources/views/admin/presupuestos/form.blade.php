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
                        <th style="width:140px;">Tipo</th>
                        <th>Descripción</th>
                        <th class="col-alojamiento">Fecha Entrada</th>
                        <th class="col-alojamiento">Fecha Salida</th>
                        <th class="col-servicio">Unidades</th>
                        <th><span class="label-alojamiento">Precio por Noche</span><span class="label-servicio" style="display:none;">Precio por Unidad</span></th>
                        <th class="col-alojamiento">Noches</th>
                        <th class="col-servicio" style="width:90px;">IVA</th>
                        <th>Precio Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-tipo="alojamiento">
                        <td>
                            <select name="conceptos[0][tipo]" class="form-select tipo-select">
                                <option value="alojamiento" selected>Alojamiento</option>
                                <option value="servicio">Servicio</option>
                            </select>
                        </td>
                        <td><input type="text" name="conceptos[0][descripcion]" class="form-control" /></td>
                        <td class="col-alojamiento"><input type="date" name="conceptos[0][fecha_entrada]" class="form-control fecha-entrada" /></td>
                        <td class="col-alojamiento"><input type="date" name="conceptos[0][fecha_salida]" class="form-control fecha-salida" /></td>
                        <td class="col-servicio" style="display:none;"><input type="number" min="1" name="conceptos[0][unidades]" class="form-control unidades" value="1" /></td>
                        <td><input type="number" step="0.01" name="conceptos[0][precio_por_dia]" class="form-control precio-por-dia" /></td>
                        <td class="col-alojamiento"><input type="number" name="conceptos[0][dias_totales]" class="form-control dias-totales" readonly /></td>
                        <td class="col-servicio" style="display:none;">
                            <select name="conceptos[0][iva_porcentaje]" class="form-select iva-porcentaje">
                                <option value="21" selected>21%</option>
                                <option value="10">10%</option>
                            </select>
                        </td>
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
                        <label for="cliente_rapido_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="cliente_rapido_nombre" name="nombre" required />
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cliente_rapido_apellido1" class="form-label">Primer apellido</label>
                            <input type="text" class="form-control" id="cliente_rapido_apellido1" name="apellido1" required />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cliente_rapido_apellido2" class="form-label">Segundo apellido</label>
                            <input type="text" class="form-control" id="cliente_rapido_apellido2" name="apellido2" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="cliente_rapido_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="cliente_rapido_email" name="email" required />
                    </div>
                    <div class="mb-3">
                        <label for="cliente_rapido_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="cliente_rapido_telefono" name="telefono" required />
                    </div>
                    <div id="crearClienteError" class="alert alert-danger d-none"></div>
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

        function recalcularFila(row) {
            const tipo = row.getAttribute('data-tipo') || 'alojamiento';
            const precio = parseFloat(row.querySelector('.precio-por-dia').value) || 0;

            if (tipo === 'alojamiento') {
                const entrada = row.querySelector('.fecha-entrada').value;
                const salida = row.querySelector('.fecha-salida').value;
                if (entrada && salida) {
                    const dias = Math.ceil((new Date(salida) - new Date(entrada)) / (1000 * 60 * 60 * 24));
                    const diasTotales = dias > 0 ? dias : 0;
                    row.querySelector('.dias-totales').value = diasTotales;
                    row.querySelector('.precio-total').value = (diasTotales * precio).toFixed(2);
                }
            } else {
                const unidades = parseInt(row.querySelector('.unidades').value, 10) || 0;
                row.querySelector('.precio-total').value = (unidades * precio).toFixed(2);
            }

            actualizarTotalGeneral();
        }

        function aplicarVisibilidadTipo(row) {
            const tipo = row.querySelector('.tipo-select').value;
            row.setAttribute('data-tipo', tipo);
            row.querySelectorAll('.col-alojamiento').forEach(td => {
                td.style.display = (tipo === 'alojamiento') ? '' : 'none';
            });
            row.querySelectorAll('.col-servicio').forEach(td => {
                td.style.display = (tipo === 'servicio') ? '' : 'none';
            });
            // Cuando cambio a servicio, limpiar fechas/dias; cuando cambio a alojamiento, recalcular con fechas
            if (tipo === 'servicio') {
                const d = row.querySelector('.dias-totales');
                if (d) d.value = '';
            }
            recalcularFila(row);
        }

        function bindConceptoListeners(row) {
            const tipoSel = row.querySelector('.tipo-select');
            tipoSel.addEventListener('change', () => aplicarVisibilidadTipo(row));

            ['.fecha-entrada', '.fecha-salida', '.precio-por-dia', '.unidades'].forEach(sel => {
                const el = row.querySelector(sel);
                if (el) el.addEventListener('input', () => recalcularFila(row));
                if (el) el.addEventListener('change', () => recalcularFila(row));
            });

            const rm = row.querySelector('.removeConcepto');
            if (rm) {
                rm.addEventListener('click', function () {
                    row.remove();
                    actualizarTotalGeneral();
                });
            }

            aplicarVisibilidadTipo(row);
        }

        function actualizarTotalGeneral() {
            let total = 0;
            document.querySelectorAll('.precio-total').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            const resumenTotal = document.getElementById('resumenTotal');
            if (resumenTotal) resumenTotal.textContent = `Total: ${total.toFixed(2)} €`;
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

        // Inicializa listeners en la fila inicial
        document.querySelectorAll('#conceptosTable tbody tr').forEach(tr => bindConceptoListeners(tr));

        document.getElementById('addConcepto').addEventListener('click', function () {
            const tbody = document.querySelector('#conceptosTable tbody');
            const index = tbody.children.length;
            const tr = document.createElement('tr');
            tr.setAttribute('data-tipo', 'alojamiento');
            tr.innerHTML = `
                <td>
                    <select name="conceptos[${index}][tipo]" class="form-select tipo-select">
                        <option value="alojamiento" selected>Alojamiento</option>
                        <option value="servicio">Servicio</option>
                    </select>
                </td>
                <td><input type="text" name="conceptos[${index}][descripcion]" class="form-control" /></td>
                <td class="col-alojamiento"><input type="date" name="conceptos[${index}][fecha_entrada]" class="form-control fecha-entrada" /></td>
                <td class="col-alojamiento"><input type="date" name="conceptos[${index}][fecha_salida]" class="form-control fecha-salida" /></td>
                <td class="col-servicio" style="display:none;"><input type="number" min="1" name="conceptos[${index}][unidades]" class="form-control unidades" value="1" /></td>
                <td><input type="number" step="0.01" name="conceptos[${index}][precio_por_dia]" class="form-control precio-por-dia" /></td>
                <td class="col-alojamiento"><input type="number" name="conceptos[${index}][dias_totales]" class="form-control dias-totales" readonly /></td>
                <td class="col-servicio" style="display:none;">
                    <select name="conceptos[${index}][iva_porcentaje]" class="form-select iva-porcentaje">
                        <option value="21" selected>21%</option>
                        <option value="10">10%</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="conceptos[${index}][precio_total]" class="form-control precio-total" readonly /></td>
                <td><button type="button" class="btn btn-danger btn-sm removeConcepto">Eliminar</button></td>
            `;
            tbody.appendChild(tr);
            bindConceptoListeners(tr);
        });

        // Crear cliente rapido desde el modal (endpoint especifico de presupuestos)
        document.getElementById('guardarCliente').addEventListener('click', function () {
            const errorBox = document.getElementById('crearClienteError');
            errorBox.classList.add('d-none');
            errorBox.textContent = '';

            const payload = {
                _token: '{{ csrf_token() }}',
                nombre: document.getElementById('cliente_rapido_nombre').value.trim(),
                apellido1: document.getElementById('cliente_rapido_apellido1').value.trim(),
                apellido2: document.getElementById('cliente_rapido_apellido2').value.trim(),
                email: document.getElementById('cliente_rapido_email').value.trim(),
                telefono: document.getElementById('cliente_rapido_telefono').value.trim(),
            };

            if (!payload.nombre || !payload.apellido1 || !payload.email || !payload.telefono) {
                errorBox.textContent = 'Nombre, primer apellido, email y teléfono son obligatorios.';
                errorBox.classList.remove('d-none');
                return;
            }

            fetch('{{ route("presupuestos.clienteRapido") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(payload).toString(),
            })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error al crear el cliente.');
                    throw new Error(msg);
                }
                return data;
            })
            .then(data => {
                const c = data.cliente;
                const label = `${c.nombre}${c.apellido1 ? ' ' + c.apellido1 : ''} - ${c.email}`;
                const select = document.getElementById('cliente_id');
                let opt = Array.from(select.options).find(o => parseInt(o.value, 10) === parseInt(c.id, 10));
                if (!opt) {
                    opt = new Option(label, c.id, true, true);
                    select.appendChild(opt);
                }
                if (window.jQuery) {
                    window.jQuery(select).val(c.id).trigger('change');
                } else {
                    select.value = c.id;
                }
                const modalEl = document.getElementById('crearClienteModal');
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
                document.getElementById('crearClienteForm').reset();
            })
            .catch(err => {
                errorBox.textContent = err.message || 'Error al crear el cliente.';
                errorBox.classList.remove('d-none');
            });
        });

        // Actualiza la información en el paso 3 (solo al entrar al ultimo paso)
        document.querySelectorAll('.next-step').forEach((btn, i, all) => {
            if (i === all.length - 1) {
                btn.addEventListener('click', function () {
                    const clienteSel = document.querySelector('#cliente_id option:checked');
                    const cliente = clienteSel ? clienteSel.textContent : '(sin cliente)';
                    document.getElementById('clienteSeleccionado').textContent = `Cliente: ${cliente}`;

                    let html = '<table class="table"><thead><tr><th>Tipo</th><th>Descripción</th><th>Detalle</th><th>Precio</th><th>Total</th></tr></thead><tbody>';
                    document.querySelectorAll('#conceptosTable tbody tr').forEach(tr => {
                        const tipo = tr.getAttribute('data-tipo') || 'alojamiento';
                        const descripcion = tr.querySelector('[name*="[descripcion]"]').value;
                        const precio = tr.querySelector('.precio-por-dia').value;
                        const total = tr.querySelector('.precio-total').value;

                        let detalle = '';
                        if (tipo === 'alojamiento') {
                            const entrada = tr.querySelector('.fecha-entrada').value;
                            const salida = tr.querySelector('.fecha-salida').value;
                            const dias = tr.querySelector('.dias-totales').value;
                            detalle = `Del ${entrada} al ${salida} (${dias} noches)`;
                        } else {
                            const unidades = tr.querySelector('.unidades').value;
                            detalle = `${unidades} unidades`;
                        }

                        html += `<tr>
                            <td>${tipo === 'alojamiento' ? 'Alojamiento' : 'Servicio'}</td>
                            <td>${descripcion}</td>
                            <td>${detalle}</td>
                            <td>${parseFloat(precio || 0).toFixed(2)} €</td>
                            <td>${parseFloat(total || 0).toFixed(2)} €</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('conceptosResumen').innerHTML = html;

                    actualizarTotalGeneral();
                });
            }
        });
    });
</script>

<style>
    .step { display: none; }
    .step.active { display: block; }
</style>
