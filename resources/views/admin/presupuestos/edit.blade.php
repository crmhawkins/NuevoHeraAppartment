@extends('layouts.appAdmin')

@section('title', 'Editar Presupuesto')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit me-2 text-warning"></i>
                Editar Presupuesto
            </h1>
            <p class="text-muted mb-0">Modifica la información del presupuesto existente</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if($factura)
                <a href="{{ route('admin.facturas.edit', $factura->id) }}" 
                   class="btn btn-success">
                    <i class="fas fa-file-invoice me-2"></i>
                    Ver Factura Asociada
                </a>
            @endif
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('presupuestos.index') }}">Presupuestos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar Presupuesto</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Formulario de Edición -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-edit me-2 text-primary"></i>
                Edición de Presupuesto
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('presupuestos.update', $presupuesto->id) }}" method="POST" id="formPresupuesto">
                @csrf
                @method('PUT')

                <!-- Paso 1: Cliente y fecha -->
                <div id="step1" class="step active">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="fw-semibold text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Paso 1: Cliente y Fecha
                            </h4>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cliente_id" class="form-label fw-semibold">
                                <i class="fas fa-user me-2 text-success"></i>Cliente
                            </label>
                            <select name="cliente_id" id="cliente_id" class="form-select form-select-lg">
                                <option value="">Seleccionar cliente</option>
                                @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected($cliente->id == old('cliente_id', $presupuesto->cliente_id))>
                                    {{ $cliente->nombre }} - {{ $cliente->email }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="fecha" class="form-label fw-semibold">
                                <i class="fas fa-calendar me-2 text-info"></i>Fecha del Presupuesto
                            </label>
                            <input type="date" name="fecha" id="fecha" class="form-control form-control-lg"
                                value="{{ old('fecha', \Carbon\Carbon::parse($presupuesto->fecha)->toDateString()) }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-primary btn-lg next-step">
                            <i class="fas fa-arrow-right me-2"></i>Siguiente
                        </button>
                    </div>
                </div>

                <!-- Paso 2: Conceptos -->
                <div id="step2" class="step">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="fw-semibold text-primary mb-3">
                                <i class="fas fa-list me-2"></i>Paso 2: Conceptos del Presupuesto
                            </h4>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-hover" id="conceptosTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-semibold text-dark" style="width:140px;">
                                        <i class="fas fa-layer-group me-2 text-primary"></i>Tipo
                                    </th>
                                    <th class="fw-semibold text-dark">
                                        <i class="fas fa-tag me-2 text-success"></i>Descripción
                                    </th>
                                    <th class="fw-semibold text-dark col-alojamiento">
                                        <i class="fas fa-calendar-plus me-2 text-info"></i>Entrada
                                    </th>
                                    <th class="fw-semibold text-dark col-alojamiento">
                                        <i class="fas fa-calendar-minus me-2 text-warning"></i>Salida
                                    </th>
                                    <th class="fw-semibold text-dark col-servicio">
                                        <i class="fas fa-cubes me-2 text-info"></i>Unidades
                                    </th>
                                    <th class="fw-semibold text-dark">
                                        <i class="fas fa-euro-sign me-2 text-primary"></i>€/Unidad
                                    </th>
                                    <th class="fw-semibold text-dark col-alojamiento">
                                        <i class="fas fa-clock me-2 text-secondary"></i>Noches
                                    </th>
                                    <th class="fw-semibold text-dark col-servicio" style="width:90px;">
                                        <i class="fas fa-percent me-2 text-info"></i>IVA
                                    </th>
                                    <th class="fw-semibold text-dark">
                                        <i class="fas fa-calculator me-2 text-success"></i>Total
                                    </th>
                                    <th class="fw-semibold text-dark text-center">
                                        <i class="fas fa-trash me-2 text-danger"></i>Eliminar
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($presupuesto->conceptos as $index => $concepto)
                                    @php
                                        $tipoConcepto = $concepto->tipo ?: 'alojamiento';
                                        // Stripeamos el sufijo auto-generado ("(Del X al Y - N noches)" o "(N x P EUR)")
                                        // para que al guardar no se duplique
                                        $descripcionLimpia = preg_replace('/\s*\(Del .+\)$/u', '', $concepto->concepto);
                                        $descripcionLimpia = preg_replace('/\s*\(\d+\s*x\s*[\d\.,]+\s*EUR\)$/u', '', $descripcionLimpia);
                                    @endphp
                                <tr data-tipo="{{ $tipoConcepto }}">
                                    <td>
                                        <select name="conceptos[{{ $index }}][tipo]" class="form-select tipo-select">
                                            <option value="alojamiento" @selected($tipoConcepto === 'alojamiento')>Alojamiento</option>
                                            <option value="servicio" @selected($tipoConcepto === 'servicio')>Servicio</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="conceptos[{{ $index }}][descripcion]" class="form-control" value="{{ old("conceptos.$index.descripcion", $descripcionLimpia) }}"></td>
                                    <td class="col-alojamiento"><input type="date" name="conceptos[{{ $index }}][fecha_entrada]" class="form-control fecha-entrada" value="{{ old("conceptos.$index.fecha_entrada", optional($concepto->fecha_entrada)->format('Y-m-d')) }}"></td>
                                    <td class="col-alojamiento"><input type="date" name="conceptos[{{ $index }}][fecha_salida]" class="form-control fecha-salida" value="{{ old("conceptos.$index.fecha_salida", optional($concepto->fecha_salida)->format('Y-m-d')) }}"></td>
                                    <td class="col-servicio"><input type="number" min="1" name="conceptos[{{ $index }}][unidades]" class="form-control unidades" value="{{ old("conceptos.$index.unidades", $concepto->unidades ?: 1) }}"></td>
                                    <td><input type="number" name="conceptos[{{ $index }}][precio_por_dia]" class="form-control precio-por-dia" step="0.01" value="{{ old("conceptos.$index.precio_por_dia", $concepto->precio_por_dia ?: $concepto->precio) }}"></td>
                                    <td class="col-alojamiento"><input type="number" name="conceptos[{{ $index }}][dias_totales]" class="form-control dias-totales" value="{{ old("conceptos.$index.dias_totales", $concepto->dias_totales) }}" readonly></td>
                                    @php
                                        $ivaPctActual = (int) old("conceptos.$index.iva_porcentaje", $concepto->iva > 0 && $concepto->iva <= 50 ? $concepto->iva : 21);
                                    @endphp
                                    <td class="col-servicio">
                                        <select name="conceptos[{{ $index }}][iva_porcentaje]" class="form-select iva-porcentaje">
                                            <option value="21" @selected($ivaPctActual === 21)>21%</option>
                                            <option value="10" @selected($ivaPctActual === 10)>10%</option>
                                        </select>
                                    </td>
                                    <td><input type="number" name="conceptos[{{ $index }}][precio_total]" class="form-control precio-total" step="0.01" value="{{ old("conceptos.$index.precio_total", $concepto->precio_total ?: $concepto->subtotal) }}" readonly></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm removeConcepto">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" id="addConcepto" class="btn btn-secondary btn-lg">
                            <i class="fas fa-plus me-2"></i>Añadir Concepto
                        </button>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-outline-secondary btn-lg prev-step">
                                <i class="fas fa-arrow-left me-2"></i>Atrás
                            </button>
                            <button type="button" class="btn btn-primary btn-lg next-step">
                                <i class="fas fa-arrow-right me-2"></i>Siguiente
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Revisión -->
                <div id="step3" class="step">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="fw-semibold text-primary mb-3">
                                <i class="fas fa-check-circle me-2"></i>Paso 3: Revisión Final
                            </h4>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-semibold text-success mb-3">
                                        <i class="fas fa-user me-2"></i>Información del Cliente
                                    </h6>
                                    <p id="clienteSeleccionado" class="mb-0"></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-semibold text-info mb-3">
                                        <i class="fas fa-list me-2"></i>Resumen de Conceptos
                                    </h6>
                                    <div id="conceptosResumen"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card bg-primary-subtle border-0">
                                <div class="card-body text-center">
                                    <p id="resumenTotal" class="fw-bold fs-3 text-primary mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg prev-step">
                            <i class="fas fa-arrow-left me-2"></i>Atrás
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const steps = document.querySelectorAll('.step');
        let currentStep = 0;

        function showStep(index) {
            steps.forEach((step, i) => step.classList.toggle('active', i === index));
        }

        showStep(currentStep);

        document.querySelectorAll('.next-step').forEach(btn => btn.addEventListener('click', () => {
            currentStep++;
            showStep(currentStep);
        }));

        document.querySelectorAll('.prev-step').forEach(btn => btn.addEventListener('click', () => {
            currentStep--;
            showStep(currentStep);
        }));

        function recalcularFila(row) {
            const tipo = row.getAttribute('data-tipo') || 'alojamiento';
            const precio = parseFloat(row.querySelector('.precio-por-dia').value) || 0;

            if (tipo === 'alojamiento') {
                const entradaVal = row.querySelector('.fecha-entrada').value;
                const salidaVal = row.querySelector('.fecha-salida').value;
                if (entradaVal && salidaVal) {
                    const e = new Date(entradaVal);
                    const s = new Date(salidaVal);
                    const d = Math.ceil((s - e) / (1000 * 60 * 60 * 24));
                    const dias = d > 0 ? d : 0;
                    row.querySelector('.dias-totales').value = dias;
                    row.querySelector('.precio-total').value = (dias * precio).toFixed(2);
                }
            } else {
                const unidades = parseInt(row.querySelector('.unidades').value, 10) || 0;
                row.querySelector('.precio-total').value = (unidades * precio).toFixed(2);
            }
            actualizarTotal();
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
            recalcularFila(row);
        }

        function bindConceptoListeners(row) {
            const tipoSel = row.querySelector('.tipo-select');
            if (tipoSel) tipoSel.addEventListener('change', () => aplicarVisibilidadTipo(row));

            ['.fecha-entrada', '.fecha-salida', '.precio-por-dia', '.unidades'].forEach(sel => {
                const el = row.querySelector(sel);
                if (el) {
                    el.addEventListener('input', () => recalcularFila(row));
                    el.addEventListener('change', () => recalcularFila(row));
                }
            });

            const rm = row.querySelector('.removeConcepto');
            if (rm) {
                rm.addEventListener('click', function () {
                    row.remove();
                    actualizarTotal();
                });
            }
        }

        // Aplicar visibilidad y recalcular al cargar cada fila existente
        function actualizarTotal() {
            let total = 0;
            document.querySelectorAll('.precio-total').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('resumenTotal').textContent = `Total: ${total.toFixed(2)} €`;
        }

        document.querySelectorAll('#conceptosTable tbody tr').forEach(tr => {
            bindConceptoListeners(tr);
            aplicarVisibilidadTipo(tr);
        });

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
                <td><input type="text" name="conceptos[${index}][descripcion]" class="form-control"></td>
                <td class="col-alojamiento"><input type="date" name="conceptos[${index}][fecha_entrada]" class="form-control fecha-entrada"></td>
                <td class="col-alojamiento"><input type="date" name="conceptos[${index}][fecha_salida]" class="form-control fecha-salida"></td>
                <td class="col-servicio" style="display:none;"><input type="number" min="1" name="conceptos[${index}][unidades]" class="form-control unidades" value="1"></td>
                <td><input type="number" name="conceptos[${index}][precio_por_dia]" class="form-control precio-por-dia" step="0.01"></td>
                <td class="col-alojamiento"><input type="number" name="conceptos[${index}][dias_totales]" class="form-control dias-totales" readonly></td>
                <td class="col-servicio" style="display:none;">
                    <select name="conceptos[${index}][iva_porcentaje]" class="form-select iva-porcentaje">
                        <option value="21" selected>21%</option>
                        <option value="10">10%</option>
                    </select>
                </td>
                <td><input type="number" name="conceptos[${index}][precio_total]" class="form-control precio-total" step="0.01" readonly></td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm removeConcepto">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            bindConceptoListeners(tr);
            aplicarVisibilidadTipo(tr);
        });

        // Paso 3: resumen
        document.querySelectorAll('.next-step').forEach((btn, i, all) => {
            if (i === all.length - 1) {
                btn.addEventListener('click', () => {
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

                    actualizarTotal();
                });
            }
        });
    });
</script>

<style>
    .step { display: none; }
    .step.active { display: block; }
</style>
@endsection
