<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\PresupuestoConcepto;
use App\Models\Cliente;
use App\Models\Invoices;
use App\Models\InvoicesReferenceAutoincrement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PresupuestoController extends Controller
{
    /**
     * Mostrar la lista de presupuestos.
     */
    public function index()
    {
        $presupuestos = Presupuesto::with('cliente')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('admin.presupuestos.index', compact('presupuestos'));
    }

    /**
     * Mostrar el formulario para crear un presupuesto.
     */
    public function create()
    {
        $clientes = Cliente::all();
        return view('admin.presupuestos.create', compact('clientes'));
    }

    /**
     * Almacenar un presupuesto en la base de datos.
     * Soporta dos tipos de conceptos por linea:
     *  - tipo='alojamiento': con fecha_entrada, fecha_salida, precio_por_dia, dias_totales
     *  - tipo='servicio':    con unidades, precio_por_dia (usado como precio/unidad)
     */
    public function store(Request $request)
    {
        try {
            // Validacion base (sin los campos tipo-especificos que se validan por concepto)
            $request->validate([
                'cliente_id' => 'nullable|exists:clientes,id',
                'fecha' => 'required|date',
                'conceptos' => 'required|array|min:1',
                'conceptos.*.descripcion' => 'required|string|max:255',
                'conceptos.*.tipo' => 'required|in:alojamiento,servicio',
                'conceptos.*.precio_por_dia' => 'required|numeric|min:0',
                'conceptos.*.precio_total' => 'required|numeric|min:0',
            ]);

            // Validacion adicional por tipo
            foreach ($request->conceptos as $idx => $c) {
                if (($c['tipo'] ?? 'alojamiento') === 'alojamiento') {
                    $request->validate([
                        "conceptos.{$idx}.fecha_entrada" => 'required|date',
                        "conceptos.{$idx}.fecha_salida" => 'required|date|after:conceptos.' . $idx . '.fecha_entrada',
                        "conceptos.{$idx}.dias_totales" => 'required|integer|min:1',
                    ]);
                } else {
                    $request->validate([
                        "conceptos.{$idx}.unidades" => 'required|integer|min:1',
                        // [2026-04-17] IVA solo configurable en servicios: 10 o 21.
                        // Alojamiento es siempre 10% por normativa.
                        "conceptos.{$idx}.iva_porcentaje" => 'nullable|in:10,21',
                    ]);
                }
            }

            $total = collect($request->conceptos)->sum('precio_total');

            $presupuesto = Presupuesto::create([
                'cliente_id' => $request->cliente_id,
                'fecha' => $request->fecha,
                'total' => $total,
                'estado' => 'pendiente',
            ]);

            Log::info('Presupuesto creado', ['presupuesto_id' => $presupuesto->id, 'total' => $total]);

            foreach ($request->conceptos as $c) {
                $tipo = $c['tipo'] ?? 'alojamiento';

                if ($tipo === 'alojamiento') {
                    // Concepto concatenado para visualizacion rapida
                    $conceptoTexto = $c['descripcion']
                        . ' (Del ' . $c['fecha_entrada']
                        . ' al ' . $c['fecha_salida']
                        . ' - ' . $c['dias_totales'] . ' noches)';

                    $presupuesto->conceptos()->create([
                        'concepto' => $conceptoTexto,
                        'tipo' => 'alojamiento',
                        'precio' => $c['precio_por_dia'],
                        // [2026-04-17] iva ahora guarda el PORCENTAJE aplicable.
                        // Alojamiento = 10% fijo. Lo usa facturar() para calcular base/iva.
                        'iva' => 10,
                        'subtotal' => $c['precio_total'],
                        'fecha_entrada' => $c['fecha_entrada'],
                        'fecha_salida' => $c['fecha_salida'],
                        'precio_por_dia' => $c['precio_por_dia'],
                        'dias_totales' => $c['dias_totales'],
                        'precio_total' => $c['precio_total'],
                    ]);
                } else {
                    $conceptoTexto = $c['descripcion']
                        . ' (' . $c['unidades'] . ' x ' . number_format($c['precio_por_dia'], 2, ',', '.') . ' EUR)';

                    // [2026-04-17] Para servicios, el usuario elige IVA 10 o 21 en el form.
                    // Default 21 si no llega (caso mas habitual en consumo de puntales, etc).
                    $ivaPct = in_array((int)($c['iva_porcentaje'] ?? 21), [10, 21], true)
                        ? (int) $c['iva_porcentaje']
                        : 21;

                    $presupuesto->conceptos()->create([
                        'concepto' => $conceptoTexto,
                        'tipo' => 'servicio',
                        'unidades' => $c['unidades'],
                        'precio' => $c['precio_por_dia'],
                        'iva' => $ivaPct,
                        'subtotal' => $c['precio_total'],
                        'precio_por_dia' => $c['precio_por_dia'],
                        'dias_totales' => $c['unidades'], // duplicamos en dias_totales para compatibilidad con exports/listados viejos
                        'precio_total' => $c['precio_total'],
                    ]);
                }
            }

            Log::info('Conceptos del presupuesto creados', ['presupuesto_id' => $presupuesto->id, 'conceptos_count' => count($request->conceptos)]);

            return redirect()->route('presupuestos.index')->with('success', 'Presupuesto creado correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Dejar que Laravel lo maneje normalmente (mostrar errores)
        } catch (\Exception $e) {
            Log::error('Error al crear presupuesto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el presupuesto: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint AJAX para crear un cliente "rapido" desde el modal del formulario
     * de presupuestos. Acepta los campos minimos (nombre, apellidos, email, telefono)
     * y rellena con defaults razonables los obligatorios del modelo Cliente.
     *
     * Devuelve JSON con el cliente creado para que el JS lo anada al select y lo
     * seleccione.
     */
    public function storeClienteRapido(Request $request)
    {
        try {
            $data = $request->validate([
                'nombre'    => 'required|string|max:255',
                'apellido1' => 'required|string|max:255',
                'apellido2' => 'nullable|string|max:255',
                'email'     => 'required|email|max:255',
                'telefono'  => 'required|string|max:30',
            ]);

            // Si ya existe un cliente con ese email, reutilizarlo en vez de duplicar
            $existente = Cliente::where('email', $data['email'])->first();
            if ($existente) {
                return response()->json([
                    'success' => true,
                    'reused'  => true,
                    'cliente' => [
                        'id'        => $existente->id,
                        'nombre'    => $existente->nombre,
                        'apellido1' => $existente->apellido1,
                        'apellido2' => $existente->apellido2,
                        'email'     => $existente->email,
                    ],
                ]);
            }

            $cliente = Cliente::create([
                'nombre'       => $data['nombre'],
                'apellido1'    => $data['apellido1'],
                'apellido2'    => $data['apellido2'] ?? null,
                'email'        => $data['email'],
                'telefono'     => $data['telefono'],
                'telefono_movil' => $data['telefono'],
                // Defaults razonables para los campos obligatorios del modelo
                'sexo'         => 'M',
                'nacionalidad' => 'ES',
                'tipo_cliente' => 'particular',
                'idiomas'      => 'es',
            ]);

            Log::info('Cliente rapido creado desde presupuesto', [
                'cliente_id' => $cliente->id,
                'email' => $cliente->email,
            ]);

            return response()->json([
                'success' => true,
                'reused'  => false,
                'cliente' => [
                    'id'        => $cliente->id,
                    'nombre'    => $cliente->nombre,
                    'apellido1' => $cliente->apellido1,
                    'apellido2' => $cliente->apellido2,
                    'email'     => $cliente->email,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando cliente rapido desde presupuesto', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cliente: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function facturar(Presupuesto $presupuesto)
    {
        // Evitar doble facturación
        if ($presupuesto->estado === 'facturado') {
            return redirect()->back()->with('warning', 'Este presupuesto ya está facturado.');
        }

        // [2026-04-17] Calculamos base+iva por concepto usando el % guardado en
        // cada PresupuestoConcepto.iva (10 o 21). Sumamos los resultados para el
        // total de la factura. Esto respeta presupuestos MIXTOS (alojamiento 10%
        // + servicios 21%) aunque hoy lo normal sea un solo tipo por presupuesto.
        $presupuesto->loadMissing('conceptos');
        $baseAcc = 0.0;
        $ivaAcc  = 0.0;

        foreach ($presupuesto->conceptos as $concepto) {
            $pct = (float) ($concepto->iva ?: 10);
            // Si iva no tiene sentido como porcentaje (valores antiguos 0, o cantidades en
            // euros), caemos a 10% por defecto para no romper facturas historicas.
            if ($pct <= 0 || $pct > 50) {
                $pct = 10.0;
            }
            $bruto  = (float) $concepto->precio_total;
            $bConc  = $bruto / (1 + $pct / 100);
            $iConc  = $bruto - $bConc;
            $baseAcc += $bConc;
            $ivaAcc  += $iConc;
        }

        $total = round($baseAcc + $ivaAcc, 2);
        $base  = round($baseAcc, 2);
        $iva   = round($ivaAcc, 2);

        $invoice = Invoices::create([
            'budget_id'           => $presupuesto->id,
            'cliente_id'          => $presupuesto->cliente_id,
            'reserva_id'          => null,
            'invoice_status_id'   => 1,
            'concepto'            => 'Presupuesto #' . $presupuesto->id,
            'description'         => '',
            'fecha'               => now()->toDateString(),
            'fecha_cobro'         => null,
            'base'                => $base,
            'iva'                 => $iva,
            'descuento'           => null,
            'total'               => $total,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Si tienes lógica de referencia
        $referencia = $this->generateBudgetReference($invoice);
        $invoice->reference                 = $referencia['reference'];
        $invoice->reference_autoincrement_id = $referencia['id'];
        $invoice->invoice_status_id         = 3;
        $invoice->save();

        // Marcar el presupuesto
        $presupuesto->estado = 'facturado';
        $presupuesto->save();

        return redirect()->route('presupuestos.index')
                        ->with('success', 'Presupuesto facturado correctamente.');
    }


    public function generateBudgetReference(Invoices $invoices)
    {
        // Obtener la fecha de salida de la reserva para usarla en la generación de la referencia
        $budgetCreationDate = $invoices->reserva->fecha_salida ?? now(); // Usar la fecha de salida de la reserva
        $datetimeBudgetCreationDate = new \DateTime($budgetCreationDate);

        // Formatear la fecha para obtener los componentes necesarios
        $year = $datetimeBudgetCreationDate->format('Y');
        $monthNum = $datetimeBudgetCreationDate->format('m');

        // Buscar la última referencia autoincremental para el año y mes correspondiente a la fecha de salida de la reserva
        $latestReference = InvoicesReferenceAutoincrement::where('year', $year)
                                ->where('month_num', $monthNum)
                                ->orderBy('id', 'desc')
                                ->first();

        // Si no existe, empezamos desde 1, de lo contrario, incrementamos
        $newReferenceAutoincrement = $latestReference ? $latestReference->reference_autoincrement + 1 : 1;

        // Formatear el número autoincremental a 6 dígitos
        $formattedAutoIncrement = str_pad($newReferenceAutoincrement, 6, '0', STR_PAD_LEFT);

        // Crear la referencia
        $reference = $year . '/' . $monthNum . '/' . $formattedAutoIncrement;

        // Guardar o actualizar la referencia autoincremental en BudgetReferenceAutoincrement
        $referenceToSave = new InvoicesReferenceAutoincrement([
            'reference_autoincrement' => $newReferenceAutoincrement,
            'year' => $year,
            'month_num' => $monthNum,
            // Otros campos pueden ser asignados si son necesarios
        ]);
        $referenceToSave->save();

        // Devolver el resultado
        return [
            'id' => $referenceToSave->id,
            'reference' => $reference,
            'reference_autoincrement' => $newReferenceAutoincrement,
            'budget_reference_autoincrements' => [
                'year' => $year,
                'month_num' => $monthNum,
                // Añade aquí más si es necesario
            ],
        ];
    }

    /**
     * Mostrar el detalle de un presupuesto.
     */
    public function show($id)
    {
        $presupuesto = Presupuesto::with('cliente', 'conceptos')->findOrFail($id);

        // Buscar la factura asociada a este presupuesto
        $factura = Invoices::where('budget_id', $id)->first();

        return view('admin.presupuestos.show', compact('presupuesto', 'factura'));
    }

    /**
     * Mostrar el formulario para editar un presupuesto.
     */
    public function edit($id)
    {
        $presupuesto = Presupuesto::with('conceptos')->findOrFail($id);
        $clientes = Cliente::all();

        // Buscar la factura asociada a este presupuesto
        $factura = Invoices::where('budget_id', $id)->first();

        return view('admin.presupuestos.edit', compact('presupuesto', 'clientes', 'factura'));
    }

    /**
     * Actualizar un presupuesto en la base de datos.
     * Soporta conceptos mixtos (alojamiento + servicio) como store().
     */
    public function update(Request $request, $id)
    {
        $presupuesto = Presupuesto::findOrFail($id);

        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'fecha' => 'nullable|date',
            'conceptos' => 'required|array|min:1',
            'conceptos.*.descripcion' => 'required|string|max:255',
            'conceptos.*.tipo' => 'required|in:alojamiento,servicio',
            'conceptos.*.precio_por_dia' => 'required|numeric|min:0',
            'conceptos.*.precio_total' => 'required|numeric|min:0',
        ]);

        foreach ($request->conceptos as $idx => $c) {
            if (($c['tipo'] ?? 'alojamiento') === 'alojamiento') {
                $request->validate([
                    "conceptos.{$idx}.fecha_entrada" => 'required|date',
                    "conceptos.{$idx}.fecha_salida" => 'required|date|after:conceptos.' . $idx . '.fecha_entrada',
                    "conceptos.{$idx}.dias_totales" => 'required|integer|min:1',
                ]);
            } else {
                $request->validate([
                    "conceptos.{$idx}.unidades" => 'required|integer|min:1',
                    "conceptos.{$idx}.iva_porcentaje" => 'nullable|in:10,21',
                ]);
            }
        }

        $clienteId = $request->cliente_id;

        $presupuesto->update([
            'cliente_id' => $clienteId,
            'descripcion' => $request->descripcion,
            'fecha' => $request->fecha ?? $presupuesto->fecha,
            'total' => collect($request->conceptos)->sum('precio_total'),
        ]);

        // Eliminar conceptos existentes y volver a crearlos
        $presupuesto->conceptos()->delete();

        foreach ($request->conceptos as $c) {
            $tipo = $c['tipo'] ?? 'alojamiento';

            if ($tipo === 'alojamiento') {
                $conceptoTexto = $c['descripcion']
                    . ' (Del ' . $c['fecha_entrada']
                    . ' al ' . $c['fecha_salida']
                    . ' - ' . $c['dias_totales'] . ' noches)';

                PresupuestoConcepto::create([
                    'presupuesto_id' => $presupuesto->id,
                    'concepto' => $conceptoTexto,
                    'tipo' => 'alojamiento',
                    'precio' => $c['precio_por_dia'],
                    // [2026-04-17] Mismo criterio que store(): iva guarda el porcentaje.
                    'iva' => 10,
                    'subtotal' => $c['precio_total'],
                    'fecha_entrada' => $c['fecha_entrada'],
                    'fecha_salida' => $c['fecha_salida'],
                    'precio_por_dia' => $c['precio_por_dia'],
                    'dias_totales' => $c['dias_totales'],
                    'precio_total' => $c['precio_total'],
                ]);
            } else {
                $conceptoTexto = $c['descripcion']
                    . ' (' . $c['unidades'] . ' x ' . number_format($c['precio_por_dia'], 2, ',', '.') . ' EUR)';

                $ivaPct = in_array((int)($c['iva_porcentaje'] ?? 21), [10, 21], true)
                    ? (int) $c['iva_porcentaje']
                    : 21;

                PresupuestoConcepto::create([
                    'presupuesto_id' => $presupuesto->id,
                    'concepto' => $conceptoTexto,
                    'tipo' => 'servicio',
                    'unidades' => $c['unidades'],
                    'precio' => $c['precio_por_dia'],
                    'iva' => $ivaPct,
                    'subtotal' => $c['precio_total'],
                    'precio_por_dia' => $c['precio_por_dia'],
                    'dias_totales' => $c['unidades'],
                    'precio_total' => $c['precio_total'],
                ]);
            }
        }

        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto actualizado correctamente.');
    }

    /**
     * Eliminar un presupuesto.
     */
    public function destroy($id)
    {
        $presupuesto = Presupuesto::findOrFail($id);
        $presupuesto->delete();

        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto eliminado correctamente.');
    }
}
