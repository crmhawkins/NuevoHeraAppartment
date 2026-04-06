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
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cliente_id' => 'nullable|exists:clientes,id',
                'fecha' => 'required|date',
                'conceptos' => 'required|array',
                'conceptos.*.descripcion' => 'required|string|max:255',
                'conceptos.*.fecha_entrada' => 'required|date',
                'conceptos.*.fecha_salida' => 'required|date|after:conceptos.*.fecha_entrada',
                'conceptos.*.precio_por_dia' => 'required|numeric|min:0',
                'conceptos.*.dias_totales' => 'required|integer|min:1',
                'conceptos.*.precio_total' => 'required|numeric|min:0',
            ]);

            $total = collect($validated['conceptos'])->sum('precio_total');

            $presupuesto = Presupuesto::create([
                'cliente_id' => $validated['cliente_id'] ?? null,
                'fecha' => $validated['fecha'],
                'total' => $total,
                'estado' => 'pendiente',
            ]);

            Log::info('Presupuesto creado', ['presupuesto_id' => $presupuesto->id, 'total' => $total]);

            foreach ($validated['conceptos'] as $conceptoData) {
                // Concatenar concepto completo (se mantiene para visualización)
                $conceptoTexto = $conceptoData['descripcion']
                    . ' (Del ' . $conceptoData['fecha_entrada']
                    . ' al ' . $conceptoData['fecha_salida']
                    . ' - ' . $conceptoData['dias_totales'] . ' días)';

                $presupuesto->conceptos()->create([
                    'concepto' => $conceptoTexto,
                    'precio' => $conceptoData['precio_por_dia'],
                    'iva' => 0, // Puedes calcularlo si lo deseas
                    'subtotal' => $conceptoData['precio_total'],
                    // Guardar también los campos de detalle para posterior edición
                    'fecha_entrada' => $conceptoData['fecha_entrada'],
                    'fecha_salida' => $conceptoData['fecha_salida'],
                    'precio_por_dia' => $conceptoData['precio_por_dia'],
                    'dias_totales' => $conceptoData['dias_totales'],
                    'precio_total' => $conceptoData['precio_total'],
                ]);
            }

            Log::info('Conceptos del presupuesto creados', ['presupuesto_id' => $presupuesto->id, 'conceptos_count' => count($validated['conceptos'])]);

            return redirect()->route('presupuestos.index')->with('success', 'Presupuesto creado correctamente.');
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

    public function facturar(Presupuesto $presupuesto)
    {
        // Evitar doble facturación
        if ($presupuesto->estado === 'facturado') {
            return redirect()->back()->with('warning', 'Este presupuesto ya está facturado.');
        }

        // Generar factura con lógica análoga a la de reservas:
        $total = $presupuesto->total;
        // Por ejemplo, IVA 10%:
        $base = $total / 1.10;
        $iva  = $total - $base;

        $invoice = Invoices::create([
            'budget_id'           => $presupuesto->id,
            'cliente_id'          => $presupuesto->cliente_id,
            'reserva_id'          => null,
            'invoice_status_id'   => 1,
            'concepto'            => 'Presupuesto #' . $presupuesto->id,
            'description'         => '',
            'fecha'               => now()->toDateString(),
            'fecha_cobro'         => null,
            'base'                => round($base, 2),
            'iva'                 => round($iva, 2),
            'descuento'           => null,
            'total'               => round($total, 2),
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
     */
    public function update(Request $request, $id)
    {
        $presupuesto = Presupuesto::findOrFail($id);

        $request->validate([
            'conceptos.*.concepto' => 'required|string|max:255',
            'conceptos.*.precio' => 'required|numeric|min:0',
            'conceptos.*.iva' => 'nullable|numeric|min:0',
            'conceptos.*.subtotal' => 'required|numeric|min:0',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        // Actualizar cliente si es necesario
        if (!$request->cliente_id) {
            $cliente = Cliente::create([
                'nombre' => $request->nombre,
                'apellido1' => $request->apellido1,
                'apellido2' => $request->apellido2,
                'email' => $request->email,
            ]);

            $clienteId = $cliente->id;
        } else {
            $clienteId = $request->cliente_id;
        }

        // Actualizar presupuesto
        $presupuesto->update([
            'cliente_id' => $clienteId,
            'descripcion' => $request->descripcion,
            'total' => collect($request->conceptos)->sum('subtotal'),
        ]);

        // Eliminar conceptos existentes y volver a crearlos
        $presupuesto->conceptos()->delete();

        foreach ($request->conceptos as $concepto) {
            PresupuestoConcepto::create([
                'presupuesto_id' => $presupuesto->id,
                'concepto' => $concepto['concepto'],
                'precio' => $concepto['precio'],
                'iva' => $concepto['iva'] ?? 0, // Default a 0 si no se proporciona
                'subtotal' => $concepto['subtotal'],
                'fecha_entrada' => $concepto['fecha_entrada'] ?? null,
                'fecha_salida' => $concepto['fecha_salida'] ?? null,
            ]);
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
