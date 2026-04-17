<?php

namespace App\Http\Controllers;

use App\Exports\InvoicesExport;
use App\Models\Email;
use App\Models\Invoices;
use App\Models\InvoicesReferenceAutoincrement;
use App\Models\Reserva;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Cli\Invoker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webklex\IMAP\Facades\Client;
use ZipArchive;
use App\Models\Cliente;
use App\Models\InvoicesStatus;
use Illuminate\Support\Facades\DB;

class InvoicesController extends Controller
{

    public function regenerateInvoicesForOctober()
    {
        $anio = 2025; // Cambia al año correspondiente
        $mes = 1; // Enero

        // Iniciar una transacción para mantener consistencia
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Filtrar todas las reservas del mes de enero
            $reservasMes = Reserva::whereYear('fecha_entrada', $anio)
                ->whereMonth('fecha_entrada', $mes)
                ->whereNotIn('estado_id', [4]) // Filtrar estado_id diferente de 4
                ->get();

            // Eliminar las facturas existentes del mes de enero
            $facturasMes = Invoices::whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->get();

            foreach ($facturasMes as $factura) {
                $factura->forceDelete(); // Eliminar facturas permanentemente
            }

            // Deshabilitar las restricciones de claves foráneas temporalmente
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Eliminar referencias autoincrementales del mes de enero
            $mesFormateado = str_pad($mes, 2, '0', STR_PAD_LEFT);
            InvoicesReferenceAutoincrement::where('year', $anio)
                ->where('month_num', $mesFormateado)
                ->forceDelete();

            // Habilitar nuevamente las restricciones de claves foráneas
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Verifica si todas las facturas y referencias fueron eliminadas
            if (
                Invoices::whereYear('fecha', $anio)->whereMonth('fecha', $mes)->exists() ||
                InvoicesReferenceAutoincrement::where('year', $anio)->where('month_num', $mesFormateado)->exists()
            ) {
                throw new \Exception("No se pudieron eliminar todas las facturas o referencias del mes de $anio/$mes.");
            }

            // Crear nuevas facturas para las reservas del mes
            foreach ($reservasMes as $reserva) {
                // Cálculo correcto de la base imponible y el IVA
                $total = $reserva->precio;
                $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
                $iva = $total - $base; // Calcular el IVA

                $data = [
                    'budget_id' => null,
                    'cliente_id' => $reserva->cliente_id,
                    'reserva_id' => $reserva->id,
                    'invoice_status_id' => 1,
                    'concepto' => 'Estancia en apartamento: ' . $reserva->apartamento->titulo,
                    'description' => '',
                    'fecha' => $reserva->fecha_entrada, // Fecha de entrada en la reserva
                    'fecha_cobro' => null,
                    'base' => round($base, 2), // Redondear la base a 2 decimales
                    'iva' => round($iva, 2), // Redondear el IVA a 2 decimales
                    'descuento' => null,
                    'total' => round($total, 2), // Asegurarse de que el total también esté redondeado
                    'created_at' => $reserva->fecha_entrada,
                    'updated_at' => $reserva->fecha_entrada,
                ];

                // Crear la factura
                $crearFactura = Invoices::create($data);

                // Generar referencia específica y actualizar la factura
                $referencia = $this->generateSpecificBudgetReference($crearFactura, $anio, $mes);
                $crearFactura->reference = $referencia['reference'];
                $crearFactura->reference_autoincrement_id = $referencia['id'];
                $crearFactura->invoice_status_id = 3;
                $crearFactura->save();

                // Actualizar el estado de la reserva
                $reserva->estado_id = 5;
                $reserva->save();
            }

            // Confirmar transacción
            \Illuminate\Support\Facades\DB::commit();

            // Log para indicar que la tarea se completó
            Log::info("Facturas del mes de enero de $anio regeneradas correctamente.");

            return response()->json(['message' => "Facturas del mes de enero de $anio regeneradas correctamente."]);
        } catch (\Exception $e) { // Corregir el uso del espacio de nombres para Exception
            // Revertir transacción si ocurre algún error
            \Illuminate\Support\Facades\DB::rollBack(); // Corregir el uso del espacio de nombres para DB
            Log::error("Error al regenerar facturas del mes de $anio/$mes: " . $e->getMessage());
            return response()->json(['error' => "Error al regenerar facturas: " . $e->getMessage()], 500);
        }
    }




    /**
     * Generar referencias presupuestarias específicas para un año y mes
     */
    protected function generateSpecificBudgetReference(Invoices $invoices, $anio, $mes)
    {
        // Asegurar que el mes tenga formato de dos dígitos
        $mesFormateado = str_pad($mes, 2, '0', STR_PAD_LEFT);

        // Envolver en transacción con bloqueo para evitar condiciones de carrera
        return DB::transaction(function () use ($anio, $mesFormateado) {
            do {
                // Buscar la última referencia autoincremental con bloqueo
                $latestReference = InvoicesReferenceAutoincrement::where('year', $anio)
                    ->where('month_num', $mesFormateado)
                    ->lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                // Si no existe, empezamos desde 1, de lo contrario, incrementamos
                $newReferenceAutoincrement = $latestReference ? $latestReference->reference_autoincrement + 1 : 1;

                // Formatear el número autoincremental a 6 dígitos
                $formattedAutoIncrement = str_pad($newReferenceAutoincrement, 6, '0', STR_PAD_LEFT);

                // Crear la referencia
                $reference = $anio . '/' . $mesFormateado . '/' . $formattedAutoIncrement;

                // Verificar si ya existe en la tabla de facturas
                $exists = Invoices::where('reference', $reference)->exists();

                if (!$exists) {
                    break;
                }

                // Si existe, incrementar el autoincremento manualmente para evitar colisiones
                $newReferenceAutoincrement++;
            } while (true);

            // Guardar o actualizar la referencia autoincremental
            $referenceToSave = new InvoicesReferenceAutoincrement([
                'reference_autoincrement' => $newReferenceAutoincrement,
                'year' => $anio,
                'month_num' => $mesFormateado,
            ]);
            $referenceToSave->save();

            // Devolver el resultado
            return [
                'id' => $referenceToSave->id,
                'reference' => $reference,
                'reference_autoincrement' => $newReferenceAutoincrement,
            ];
        });
    }





    public function index(Request $request)
    {
        $orderBy = $request->get('order_by', 'fecha');
        $direction = $request->get('direction', 'desc');
        $perPage = $request->get('perPage', 10);
        $searchTerm = $request->get('search', '');
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        // Query inicial para facturas con su cliente y reserva asociados
        $query = Invoices::with(['cliente', 'reserva']); // Asegúrate de incluir las relaciones

        // Filtro de búsqueda por cliente, concepto, total, etc.
        if (!empty($searchTerm)) {
            $query->where(function($subQuery) use ($searchTerm) {
                $subQuery->whereHas('cliente', function($q) use ($searchTerm) {
                    $q->where('alias', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('nombre', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('apellido1', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('facturacion_nombre_razon_social', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('facturacion_nif_cif', 'LIKE', '%' . $searchTerm . '%');
                })
                ->orWhere('reference', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('concepto', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('total', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Filtro por rango de fechas
        if (!empty($fechaInicio) || !empty($fechaFin)) {
            if (!empty($fechaInicio) && !empty($fechaFin)) {
                $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            } elseif (!empty($fechaInicio)) {
                $query->where('fecha', '>=', $fechaInicio);
            } elseif (!empty($fechaFin)) {
                $query->where('fecha', '<=', $fechaFin);
            }
        }

        // Filtro por estado de factura (si es necesario)
        if ($request->has('estado')) {
            $query->where('invoice_status_id', $request->get('estado'));
        }

        // Aplicar orden por columna y dirección
        $facturas = $query->orderBy($orderBy, $direction)
                    ->paginate($perPage)
                    ->appends([
                        'order_by' => $orderBy,
                        'direction' => $direction,
                        'search' => $searchTerm,
                        'perPage' => $perPage,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                    ]);

        $sumatorio = $facturas->sum('total');

        return view('admin.invoices.index', compact('facturas', 'sumatorio'));
    }

    public function downloadInvoicesZip(Request $request)
{
    $fechaInicio = $request->get('fecha_inicio');
    $fechaFin = $request->get('fecha_fin');

    // Validar que las fechas estén presentes
    if (!$fechaInicio || !$fechaFin) {
        return redirect()->back()->with('error', 'Debes seleccionar un rango de fechas.');
    }

    // Obtener las facturas en el rango de fechas
    $facturas = Invoices::whereBetween('fecha', [$fechaInicio, $fechaFin])->get();

    if ($facturas->isEmpty()) {
        return redirect()->back()->with('error', 'No se encontraron facturas en el rango de fechas seleccionado.');
    }

    // Crear un archivo ZIP temporal
    $zipFileName = 'facturas_' . $fechaInicio . '_to_' . $fechaFin . '.zip';
    $zipFilePath = storage_path('app/' . $zipFileName);
    $zip = new ZipArchive;

    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($facturas as $invoice) {
            // Obtener conceptos relacionados con la reserva
            $conceptos = Reserva::where('id', $invoice->reserva_id)->get();
            foreach ($conceptos as $concepto) {
                $apartamento = $concepto->apartamento;
                $edificio = $concepto->apartamento->edificioName;
                $concepto['apartamento'] = $apartamento;
                $concepto['edificio'] = $edificio;
            }

            // Preparar datos para la vista del PDF
            $data = [
                'title' => 'Factura ' . $invoice->reference,
                'invoice' => $invoice,
            ];
            $invoice['conceptos'] = $conceptos;

            // Generar el PDF
            $pdf = PDF::loadView('admin.invoices.previewPDF', compact('data', 'invoice', 'conceptos'));

            // Generar el nombre del archivo PDF
            $fileName = 'factura_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $invoice->reference) . '.pdf';

            // Añadir el PDF al ZIP
            $zip->addFromString($fileName, $pdf->output());
        }

        $zip->close();
    } else {
        return redirect()->back()->with('error', 'No se pudo crear el archivo ZIP.');
    }

    // Descargar el archivo ZIP
    return response()->download($zipFilePath)->deleteFileAfterSend(true);
}




    public function previewPDF($id){
        // Buscar la factura por su ID
        $invoice = Invoices::with(['facturaOriginal'])->findOrFail($id);

        // Datos adicionales para la vista
        $data = [
            'title' => 'Factura ' . $invoice->reference,
        ];
        // Sanear el nombre del archivo para evitar caracteres inválidos
        $safeFileName = preg_replace('/[\/\\\\]/', '-', $invoice->reference);
        // Generar el PDF utilizando la vista 'facturas.pdf'
        $pdf = Pdf::loadView('admin.invoices.previewPDF', compact('invoice', 'data'));

        // Descargar o visualizar el PDF
        return $pdf->stream('factura_' . $safeFileName . '.pdf'); // Para visualizar en el navegador
        // return $pdf->download('factura_' . $invoice->reference . '.pdf'); // Para forzar la descarga


    }

    public function generateInvoicePDF($invoiceId)
{
    $invoice = Invoices::with(['facturaOriginal'])->findOrFail($invoiceId);

    $data = [
        'title' => 'Factura ' . $invoice->reference,
        'invoice' => $invoice,
    ];

    $conceptos = [];

    if ($invoice->reserva_id) {
        $reserva = Reserva::with(['apartamento', 'apartamento.edificioName'])->find($invoice->reserva_id);

        if ($reserva) {
            $reserva->apartamento = $reserva->apartamento;
            $reserva->edificio = $reserva->apartamento->edificioName;
            $conceptos[] = $reserva;
        }

    } elseif ($invoice->budget_id) {
        $presupuesto = \App\Models\Presupuesto::with('conceptos')->find($invoice->budget_id);

        foreach ($presupuesto->conceptos as $c) {
            // Usamos el campo `concepto`, ya contiene: "Aparatas (Del ... al ... - X días)"
            $conceptos[] = (object)[
                'descripcion' => $c->concepto,
                'precio' => $c->subtotal,
            ];
        }
    }

    $invoice['conceptos'] = $conceptos;

    $fileName = 'factura_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $invoice->reference) . '.pdf';

    $pdf = PDF::loadView('admin.invoices.previewPDF', compact('data', 'invoice', 'conceptos'));
    $pdf->setPaper('A4', 'portrait');

    return $pdf->download($fileName);
}


    public function generateInvoicePDF_OLD($invoiceId)
    {
        // Obtener la factura desde la base de datos
        $invoice = Invoices::findOrFail($invoiceId);

        // Aquí puedes definir más datos o preparaciones si lo necesitas
        $data = [
            'title' => 'Factura ' . $invoice->reference,
            'invoice' => $invoice,
        ];
        $conceptos = Reserva::where('id',$invoice->reserva_id)->get();
        foreach($conceptos as $concepto){
            $apartamento = $concepto->apartamento;
            $edificio = $concepto->apartamento->edificioName;
            $concepto['apartamento'] = $apartamento;
            $concepto['edificio'] = $edificio;
        }
        $invoice['conceptos'] = $conceptos;
        // dd($conceptos);
        // Sanitizar el nombre del archivo para eliminar caracteres no válidos
        $fileName = 'factura_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $invoice->reference) . '.pdf';

        // Renderizar la vista y pasarle los datos
        $pdf = PDF::loadView('admin.invoices.previewPDF', compact( 'data', 'invoice', 'conceptos'));

        // Configurar el tamaño de la página y las márgenes si es necesario
        $pdf->setPaper('A4', 'portrait');

        // Descargar el PDF o verlo en el navegador
        return $pdf->download($fileName);
    }


    public function create(Request $request){
        // Cálculo correcto de la base imponible y el IVA
        // El precio recibido YA INCLUYE el IVA al 10%
        // Ejemplo: Si el precio es 180.00 € (con IVA incluido)
        // Base = 180.00 / 1.10 = 163.64 €
        // IVA = 180.00 - 163.64 = 16.36 €
        // Total = 180.00 € (precio original)
        $total = $request->precio; // Precio ya incluye IVA
        $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
        $iva = $total - $base; // Calcular el IVA

        $data = [
            'budget_id' => null,
            'cliente_id' => $request->cliente_id,
            'reserva_id' => $request->reserva_id,
            'reserva_id' => $request->reserva_id,
            'reserva_id' => $request->reserva_id,
            'invoice_status_id ' => 1,
            'concepto' => $request->concepto,
            'description' => $request->descripcion,
            'fecha' => $request->fecha,
            'fecha_cobro' => null,
            'base' => round($base, 2),
            'iva' => round($iva, 2),
            'descuento' => isset($request->descuento) ? $request->descuento : null,
            'total' => round($total, 2),
        ];
        DB::transaction(function () use ($data) {
            $crear = Invoices::create($data);
            $referencia = $this->generateBudgetReference($crear);
            $crear->reference = $referencia['reference'];
            $crear->reference_autoincrement_id = $referencia['id'];
            $crear->budget_status_id = 3;
            $crear->save();
        });

    }

    public function generateReferenceTemp($reference){

        // Extrae los dos dígitos del final de la cadena usando expresiones regulares
        preg_match('/temp_(\d{2})/', $reference, $matches);
       // Incrementa el número primero
       if(count($matches) >= 1){
           $incrementedNumber = intval($matches[1]) + 1;
           // Asegura que el número tenga dos dígitos
           $formattedNumber = str_pad($incrementedNumber, 2, '0', STR_PAD_LEFT);
           // Concatena con la cadena "temp_"
           return "temp_" . $formattedNumber;
       }
   }
   private function generateReferenceDelete($reference){
        // Extrae los dos dígitos del final de la cadena usando expresiones regulares
        preg_match('/delete_(\d{2})/', $reference, $matches);
       // Incrementa el número primero
       if(count($matches) >= 1){
           $incrementedNumber = intval($matches[1]) + 1;
           // Asegura que el número tenga dos dígitos
           $formattedNumber = str_pad($incrementedNumber, 2, '0', STR_PAD_LEFT);
           // Concatena con la cadena "temp_"
           return "delete_" . $formattedNumber;
       }
   }


    public function generateBudgetReference(Invoices $invoices) {
        try {
         // Cargar la relación reserva si no está cargada para evitar N+1 queries
         if (!$invoices->relationLoaded('reserva') && $invoices->reserva_id) {
             try {
                 $invoices->load('reserva');
             } catch (\Exception $e) {
                 Log::warning('No se pudo cargar la relación reserva', [
                     'invoice_id' => $invoices->id,
                     'reserva_id' => $invoices->reserva_id,
                     'error' => $e->getMessage()
                 ]);
             }
         }

         // Obtener la fecha para usar en la generación de la referencia
         // Prioridad: fecha de la factura > fecha de salida de la reserva > fecha actual
         $fechaReserva = null;
         if ($invoices->reserva && isset($invoices->reserva->fecha_salida)) {
             $fechaReserva = $invoices->reserva->fecha_salida;
         }
       $budgetCreationDate = $invoices->fecha ?? $fechaReserva ?? now();
       
       // Validar que la fecha sea válida
       try {
           $datetimeBudgetCreationDate = new \DateTime($budgetCreationDate);
       } catch (\Exception $e) {
           Log::error('Fecha inválida al generar referencia', [
               'invoice_id' => $invoices->id,
               'fecha' => $budgetCreationDate,
               'error' => $e->getMessage()
           ]);
           $datetimeBudgetCreationDate = new \DateTime(); // Usar fecha actual como fallback
       }

       // Formatear la fecha para obtener los componentes necesarios
       $year = $datetimeBudgetCreationDate->format('Y');
       $monthNum = $datetimeBudgetCreationDate->format('m');

       // Envolver en transacción con bloqueo para evitar condiciones de carrera
       return DB::transaction(function () use ($year, $monthNum, $invoices) {
           // Bloquear la última fila de referencia para este año/mes
           $latestReference = InvoicesReferenceAutoincrement::where('year', $year)
                                   ->where('month_num', $monthNum)
                                   ->lockForUpdate()
                                   ->orderBy('id', 'desc')
                                   ->first();

           // Si no existe, empezamos desde 1, de lo contrario, incrementamos
           $newReferenceAutoincrement = $latestReference ? $latestReference->reference_autoincrement + 1 : 1;

           // Buscar la última referencia existente en facturas para este año/mes para evitar colisiones
           $lastInvoiceReference = Invoices::where('reference', 'LIKE', $year . '/' . $monthNum . '/%')
                                   ->whereNotNull('reference')
                                   ->lockForUpdate()
                                   ->orderBy('reference', 'desc')
                                   ->first();

           // Si hay una factura con referencia, extraer el número y usar el siguiente
           if ($lastInvoiceReference && $lastInvoiceReference->reference) {
               $parts = explode('/', $lastInvoiceReference->reference);
               if (count($parts) === 3 && is_numeric($parts[2])) {
                   $lastNumber = (int)$parts[2];
                   if ($lastNumber >= $newReferenceAutoincrement) {
                       $newReferenceAutoincrement = $lastNumber + 1;
                   }
               }
           }

           // Formatear el número autoincremental a 6 dígitos
           $formattedAutoIncrement = str_pad($newReferenceAutoincrement, 6, '0', STR_PAD_LEFT);

           // Crear la referencia
           $reference = $year . '/' . $monthNum . '/' . $formattedAutoIncrement;

           // Verificar una última vez si la referencia existe (por si acaso)
           $exists = Invoices::where('reference', $reference)
                       ->when($invoices->id, function($q) use ($invoices) {
                           return $q->where('id', '!=', $invoices->id);
                       })
                       ->exists();

           // Si existe, simplemente incrementar (esto no debería pasar, pero por seguridad)
           if ($exists) {
               $newReferenceAutoincrement++;
               $formattedAutoIncrement = str_pad($newReferenceAutoincrement, 6, '0', STR_PAD_LEFT);
               $reference = $year . '/' . $monthNum . '/' . $formattedAutoIncrement;
           }

           // Guardar o actualizar la referencia autoincremental en BudgetReferenceAutoincrement
           $referenceToSave = new InvoicesReferenceAutoincrement([
               'reference_autoincrement' => $newReferenceAutoincrement,
               'year' => $year,
               'month_num' => $monthNum,
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
               ],
           ];
       });
       } catch (\Exception $e) {
           Log::error('Error en generateBudgetReference', [
               'invoice_id' => $invoices->id ?? null,
               'error' => $e->getMessage(),
               'file' => $e->getFile(),
               'line' => $e->getLine(),
               'trace' => $e->getTraceAsString()
           ]);
           throw $e; // Re-lanzar la excepción para que se maneje en el método que llama
       }
   }

   public function updateFecha(Request $request, $id)
    {
        $factura = Invoices::find($id);

        if (!$factura) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada.'], 404);
        }

        $request->validate([
            'fecha' => 'required|date',
        ]);

        $factura->fecha = $request->input('fecha');
        $factura->save();

        return response()->json(['success' => true, 'message' => 'Fecha actualizada correctamente.']);
    }

    /**
     * Actualizar fecha de factura y recalcular la referencia basándose en la nueva fecha
     */
    public function updateFechaYRecalcularReferencia(Request $request, $id)
    {
        try {
            Log::info('Iniciando actualización de fecha y recálculo de referencia', ['invoice_id' => $id]);
            
            $factura = Invoices::findOrFail($id);

            $request->validate([
                'fecha' => 'required|date',
            ]);

            $fechaAnterior = $factura->fecha;
            $referenciaAnterior = $factura->reference;
            $nuevaFecha = $request->input('fecha');

            Log::info('Datos antes de actualizar', [
                'invoice_id' => $id,
                'fecha_anterior' => $fechaAnterior,
                'referencia_anterior' => $referenciaAnterior,
                'nueva_fecha' => $nuevaFecha
            ]);

            // Actualizar la fecha
            $factura->fecha = $nuevaFecha;
            $factura->save();

            // Recalcular la referencia basándose en la nueva fecha
            // El método generateBudgetReference usa $invoices->fecha como prioridad
            $referencia = $this->generateBudgetReference($factura);
            
            // Actualizar la referencia en la factura
            $factura->reference = $referencia['reference'];
            $factura->reference_autoincrement_id = $referencia['id'];
            $factura->save();

            Log::info('Fecha y referencia actualizadas correctamente', [
                'invoice_id' => $id,
                'fecha_anterior' => $fechaAnterior,
                'nueva_fecha' => $nuevaFecha,
                'referencia_anterior' => $referenciaAnterior,
                'nueva_referencia' => $referencia['reference']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fecha y referencia actualizadas correctamente.',
                'data' => [
                    'fecha_anterior' => $fechaAnterior,
                    'nueva_fecha' => $nuevaFecha,
                    'referencia_anterior' => $referenciaAnterior,
                    'nueva_referencia' => $referencia['reference']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar fecha y recalcular referencia', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la fecha y recalcular la referencia: ' . $e->getMessage()
            ], 500);
        }
    }


   public function exportInvoices(Request $request)
   {
       $orderBy = $request->get('order_by', 'fecha');
       $direction = $request->get('direction', 'asc');
       $searchTerm = $request->get('search', '');
       $fechaInicio = $request->get('fecha_inicio');
       $fechaFin = $request->get('fecha_fin');

       // Query inicial para facturas con cliente, reserva y estado asociados
       $query = Invoices::with(['cliente', 'reserva']);

       // Filtro de búsqueda por cliente, referencia, concepto, o total
       if (!empty($searchTerm)) {
           $query->where(function($subQuery) use ($searchTerm) {
               $subQuery->whereHas('cliente', function($q) use ($searchTerm) {
                   $q->where('alias', 'LIKE', '%' . $searchTerm . '%');
               })
               ->orWhere('reference', 'LIKE', '%' . $searchTerm . '%')
               ->orWhere('concepto', 'LIKE', '%' . $searchTerm . '%')
               ->orWhere('total', 'LIKE', '%' . $searchTerm . '%');
           });
       }

       // Filtro por rango de fechas
       if (!empty($fechaInicio) || !empty($fechaFin)) {
           if (!empty($fechaInicio) && !empty($fechaFin)) {
               $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
           } elseif (!empty($fechaInicio)) {
               $query->where('fecha', '>=', $fechaInicio);
           } elseif (!empty($fechaFin)) {
               $query->where('fecha', '<=', $fechaFin);
           }
       }

    //    // Filtro por estado de factura
    //    if ($request->has('estado')) {
    //        $query->where('invoice_status_id', $request->get('estado'));
    //    }

       // Aplicar el orden
       $query->orderBy($orderBy, $direction);

       // Obtener los resultados filtrados
       $invoices = $query->get();

       // Exportar el Excel con los datos filtrados
       return Excel::download(new InvoicesExport($invoices), 'invoices.xlsx');
   }

   public function facturar(Request $request)
   {
       try {
           $idReserva = $request->input('reserva_id');
           $reserva = Reserva::with('apartamento')->find($idReserva);

           if (!$reserva) {
               return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
           }

           if (!$reserva->apartamento) {
               return response()->json(['success' => false, 'message' => 'La reserva no tiene apartamento asociado.'], 400);
           }

           $invoice = Invoices::where('reserva_id', $idReserva)->first();

           if ($invoice == null) {
               $apartamentoTitulo = $reserva->apartamento->titulo ?? $reserva->apartamento->nombre ?? 'Apartamento #' . $reserva->apartamento_id;

               // Cálculo correcto de la base imponible y el IVA
               // El precio de la reserva YA INCLUYE el IVA al 10%
               // Ejemplo: Si el precio es 180.00 € (con IVA incluido)
               // Base = 180.00 / 1.10 = 163.64 €
               // IVA = 180.00 - 163.64 = 16.36 €
               // Total = 180.00 € (precio original)
               $total = $reserva->precio; // Precio ya incluye IVA
               $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
               $iva = $total - $base; // Calcular el IVA

               $data = [
                   'budget_id' => null,
                   'cliente_id' => $reserva->cliente_id,
                   'reserva_id' => $reserva->id,
                   'invoice_status_id' => 1,
                   'concepto' => 'Estancia en apartamento: ' . $apartamentoTitulo,
                   'description' => '',
                   'fecha' => $reserva->fecha_salida,
                   'fecha_cobro' => null,
                   'base' => round($base, 2),
                   'iva' => round($iva, 2),
                   'descuento' => null,
                   'total' => round($total, 2),
                   'created_at' => $reserva->fecha_salida,
                   'updated_at' => $reserva->fecha_salida,
               ];

               $crearFactura = DB::transaction(function () use ($data) {
                   $crearFactura = Invoices::create($data);

                   $referencia = $this->generateBudgetReference($crearFactura);
                   $crearFactura->reference = $referencia['reference'];
                   $crearFactura->reference_autoincrement_id = $referencia['id'];
                   $crearFactura->invoice_status_id = 3;
                   $crearFactura->save();

                   return $crearFactura;
               });

               $reserva->estado_id = 5;
               $reserva->save();

               Log::info('Factura generada correctamente', [
                   'reserva_id' => $reserva->id,
                   'invoice_id' => $crearFactura->id,
                   'reference' => $crearFactura->reference
               ]);

               return response()->json(['success' => true, 'message' => 'Factura generada correctamente.']);
           } else {
               return response()->json(['success' => false, 'message' => 'La factura ya estaba generada.']);
           }
       } catch (\Exception $e) {
           Log::error('Error al generar factura', [
               'reserva_id' => $request->input('reserva_id'),
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString()
           ]);

           return response()->json([
               'success' => false,
               'message' => 'Error al generar la factura: ' . $e->getMessage()
           ], 500);
       }
   }

    public function edit($id)
    {
        $invoice = Invoices::with(['cliente', 'reserva', 'estado'])->findOrFail($id);

        // Obtener estados de factura disponibles
        $estados = InvoicesStatus::all();

        // Obtener clientes disponibles
        $clientes = Cliente::all();

        // Obtener reservas disponibles (si es necesario)
        $reservas = Reserva::with(['apartamento', 'cliente'])->get();

        return view('admin.invoices.edit', compact('invoice', 'estados', 'clientes', 'reservas'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'reserva_id' => 'nullable|exists:reservas,id',
            'invoice_status_id' => 'required|exists:invoices_status,id',
            'concepto' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fecha' => 'required|date',
            'fecha_cobro' => 'nullable|date',
            'base' => 'required|numeric|min:0',
            'iva' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);

        $invoice = Invoices::findOrFail($id);

        // Si hay una reserva asociada, recalcular automáticamente desde el precio de la reserva
        if ($request->reserva_id) {
            $reserva = Reserva::find($request->reserva_id);
            if ($reserva && $reserva->precio) {
                // Recalcular correctamente desde el precio de la reserva (que ya incluye IVA)
                $total = $reserva->precio; // Precio ya incluye IVA
                $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
                $iva = $total - $base; // Calcular el IVA
                
                Log::info('Recalculando factura desde precio de reserva', [
                    'invoice_id' => $id,
                    'reserva_id' => $request->reserva_id,
                    'precio_reserva' => $reserva->precio,
                    'base_calculada' => round($base, 2),
                    'iva_calculado' => round($iva, 2),
                    'total_calculado' => round($total, 2)
                ]);

                $invoice->update([
                    'cliente_id' => $request->cliente_id,
                    'reserva_id' => $request->reserva_id,
                    'invoice_status_id' => $request->invoice_status_id,
                    'concepto' => $request->concepto,
                    'description' => $request->description,
                    'fecha' => $request->fecha,
                    'fecha_cobro' => $request->fecha_cobro,
                    'base' => round($base, 2), // Usar valores recalculados
                    'iva' => round($iva, 2),    // Usar valores recalculados
                    'descuento' => $request->descuento,
                    'total' => round($total, 2), // Usar valores recalculados
                ]);
            } else {
                // Si no hay reserva o no tiene precio, usar los valores del formulario
                $invoice->update([
                    'cliente_id' => $request->cliente_id,
                    'reserva_id' => $request->reserva_id,
                    'invoice_status_id' => $request->invoice_status_id,
                    'concepto' => $request->concepto,
                    'description' => $request->description,
                    'fecha' => $request->fecha,
                    'fecha_cobro' => $request->fecha_cobro,
                    'base' => $request->base,
                    'iva' => $request->iva,
                    'descuento' => $request->descuento,
                    'total' => $request->total,
                ]);
            }
        } else {
            // Si no hay reserva, usar los valores del formulario directamente
            $invoice->update([
                'cliente_id' => $request->cliente_id,
                'reserva_id' => $request->reserva_id,
                'invoice_status_id' => $request->invoice_status_id,
                'concepto' => $request->concepto,
                'description' => $request->description,
                'fecha' => $request->fecha,
                'fecha_cobro' => $request->fecha_cobro,
                'base' => $request->base,
                'iva' => $request->iva,
                'descuento' => $request->descuento,
                'total' => $request->total,
            ]);
        }

        return redirect()->route('admin.facturas.index')
                        ->with('success', 'Factura actualizada correctamente');
    }

    /**
     * Recalcular factura basándose en el precio de la reserva
     * Útil para corregir facturas creadas con cálculo incorrecto de IVA
     * Si es rectificativa o tiene rectificativas, solo actualiza la referencia
     */
    public function recalculateFromReserva($id)
    {
        try {
            Log::info('Iniciando recálculo de factura', ['invoice_id' => $id]);
            
            // Cargar la factura con las relaciones necesarias para evitar N+1 queries
            $invoice = Invoices::with(['reserva'])->findOrFail($id);
            
            Log::info('Factura cargada', [
                'invoice_id' => $id,
                'tiene_reserva' => !is_null($invoice->reserva_id),
                'tiene_referencia' => !empty($invoice->reference)
            ]);

            // Valores antiguos para logging
            $valoresAntiguos = [
                'base' => $invoice->base,
                'iva' => $invoice->iva,
                'total' => $invoice->total,
                'reference' => $invoice->reference
            ];

            // Verificar si es rectificativa o tiene rectificativas
            $esRectificativa = (bool)($invoice->es_rectificativa ?? false);
            $tieneRectificativas = false;
            if (!$esRectificativa) {
                $tieneRectificativas = Invoices::where('factura_original_id', $id)->exists();
            }
            
            Log::info('Estado de factura verificado', [
                'invoice_id' => $id,
                'es_rectificativa' => $esRectificativa,
                'tiene_rectificativas' => $tieneRectificativas
            ]);

            // Si es rectificativa o tiene rectificativas, solo actualizar la referencia
            if ($esRectificativa || $tieneRectificativas) {
                Log::info('Factura es rectificativa o tiene rectificativas, solo actualizando referencia', [
                    'invoice_id' => $id,
                    'es_rectificativa' => $esRectificativa,
                    'tiene_rectificativas' => $tieneRectificativas
                ]);

                // Solo generar/actualizar referencia si no tiene
                if (empty($invoice->reference)) {
                    try {
                        Log::info('Generando referencia para factura rectificativa', ['invoice_id' => $id]);
                        $referencia = $this->generateBudgetReference($invoice);
                        
                        $invoice->reference = $referencia['reference'];
                        $invoice->reference_autoincrement_id = $referencia['id'];
                        $invoice->save();

                        Log::info('Referencia generada para factura rectificativa', [
                            'invoice_id' => $id,
                            'reference' => $referencia['reference']
                        ]);

                        $invoice->refresh();

                        return response()->json([
                            'success' => true,
                            'message' => 'Referencia asignada correctamente: ' . $referencia['reference'],
                            'data' => [
                                'valores_antiguos' => $valoresAntiguos,
                                'valores_nuevos' => [
                                    'base' => $invoice->base,
                                    'iva' => $invoice->iva,
                                    'total' => $invoice->total
                                ],
                                'referencia_generada' => true,
                                'referencia_nueva' => $referencia['reference'],
                                'solo_referencia' => true
                            ]
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error al generar referencia para factura rectificativa', [
                            'invoice_id' => $id,
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        throw $e;
                    }
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'La factura ya tiene referencia. No se realizaron cambios.',
                        'data' => [
                            'valores_antiguos' => $valoresAntiguos,
                            'valores_nuevos' => $valoresAntiguos,
                            'referencia_generada' => false,
                            'solo_referencia' => true
                        ]
                    ]);
                }
            }

            // Si no es rectificativa y no tiene rectificativas, proceder con recálculo normal
            if (!$invoice->reserva_id && !$invoice->budget_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta factura no tiene una reserva ni un presupuesto asociado.'
                ], 400);
            }

            $total = null;
            $precioOrigen = null;
            $tipoOrigen = null;

            // Si tiene reserva, usar el precio de la reserva
            if ($invoice->reserva_id) {
                $reserva = Reserva::find($invoice->reserva_id);
                
                if (!$reserva) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La reserva asociada no existe.'
                    ], 404);
                }

                if (!$reserva->precio) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La reserva no tiene precio definido.'
                    ], 400);
                }

                $total = $reserva->precio; // Precio ya incluye IVA
                $precioOrigen = $reserva->precio;
                $tipoOrigen = 'reserva';
            } 
            // Si tiene presupuesto, usar el total de la factura (que ya incluye IVA)
            elseif ($invoice->budget_id) {
                if (!$invoice->total || $invoice->total <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La factura no tiene un total válido para recalcular.'
                    ], 400);
                }

                $total = $invoice->total; // El total de la factura ya incluye IVA
                $precioOrigen = $invoice->total;
                $tipoOrigen = 'presupuesto';
            }

            // Recalcular correctamente desde el total (que ya incluye IVA)
            $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
            $iva = $total - $base; // Calcular el IVA

            // Preparar datos para actualizar
            $updateData = [
                'base' => round($base, 2),
                'iva' => round($iva, 2),
                'total' => round($total, 2),
            ];

            // Verificar si la factura tiene referencia, si no, generarla
            if (empty($invoice->reference)) {
                try {
                    Log::info('Factura sin referencia, generando referencia automáticamente', [
                        'invoice_id' => $id,
                        'reserva_id' => $invoice->reserva_id
                    ]);

                    // Generar referencia usando el mismo método que se usa normalmente
                    $referencia = $this->generateBudgetReference($invoice);
                    $updateData['reference'] = $referencia['reference'];
                    $updateData['reference_autoincrement_id'] = $referencia['id'];

                    Log::info('Referencia generada para factura', [
                        'invoice_id' => $id,
                        'reference' => $referencia['reference']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error al generar referencia en recálculo', [
                        'invoice_id' => $id,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    // Continuar sin referencia si falla, pero loguear el error
                }
            }

            // Actualizar la factura
            Log::info('Actualizando factura con nuevos valores', [
                'invoice_id' => $id,
                'update_data' => $updateData
            ]);
            $invoice->update($updateData);

            // Recargar la factura para obtener los valores actualizados (incluyendo la referencia si se generó)
            $invoice->refresh();

            $referenciaGenerada = false;
            $referenciaNueva = null;
            if (empty($valoresAntiguos['reference'] ?? null) && !empty($invoice->reference)) {
                $referenciaGenerada = true;
                $referenciaNueva = $invoice->reference;
            }

            Log::info('Factura recalculada', [
                'invoice_id' => $id,
                'tipo_origen' => $tipoOrigen,
                'reserva_id' => $invoice->reserva_id,
                'budget_id' => $invoice->budget_id,
                'precio_origen' => $precioOrigen,
                'valores_antiguos' => $valoresAntiguos,
                'valores_nuevos' => [
                    'base' => round($base, 2),
                    'iva' => round($iva, 2),
                    'total' => round($total, 2)
                ],
                'referencia_generada' => $referenciaGenerada,
                'referencia_nueva' => $referenciaNueva
            ]);

            $mensaje = 'Factura recalculada correctamente.';
            if ($referenciaGenerada) {
                $mensaje .= ' Se ha asignado la referencia: ' . $referenciaNueva;
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'valores_antiguos' => $valoresAntiguos,
                    'valores_nuevos' => [
                        'base' => round($base, 2),
                        'iva' => round($iva, 2),
                        'total' => round($total, 2)
                    ],
                    'precio_origen' => $precioOrigen,
                    'tipo_origen' => $tipoOrigen,
                    'referencia_generada' => $referenciaGenerada,
                    'referencia_nueva' => $referenciaNueva
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Factura no encontrada al recalcular', [
                'invoice_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al recalcular factura', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al recalcular la factura: ' . $e->getMessage() . ' (Línea: ' . $e->getLine() . ')'
            ], 500);
        }
    }

    /**
     * Crear una factura rectificativa
     */
    public function createRectificativa($id)
    {
        $facturaOriginal = Invoices::with(['cliente', 'reserva', 'estado'])->findOrFail($id);

        // Verificar que la factura original no sea ya una rectificativa
        if ($facturaOriginal->es_rectificativa) {
            return redirect()->back()
                ->with('swal_error', 'No se puede rectificar una factura rectificativa.');
        }

        // Verificar que la factura original no tenga ya rectificativas
        if ($facturaOriginal->tieneRectificativas()) {
            return redirect()->back()
                ->with('swal_error', 'Esta factura ya tiene una rectificativa asociada.');
        }

        return view('admin.invoices.create-rectificativa', compact('facturaOriginal'));
    }

    /**
     * Guardar la factura rectificativa
     */
    public function storeRectificativa(Request $request, $id)
    {
        $request->validate([
            'motivo_rectificacion' => 'required|string|max:255',
            'observaciones_rectificacion' => 'nullable|string|max:1000',
        ]);

        $facturaOriginal = Invoices::findOrFail($id);

        // Verificar que la factura original no sea ya una rectificativa
        if ($facturaOriginal->es_rectificativa) {
            return redirect()->back()
                ->with('swal_error', 'No se puede rectificar una factura rectificativa.');
        }

        // Verificar que la factura original no tenga ya rectificativas
        if ($facturaOriginal->tieneRectificativas()) {
            return redirect()->back()
                ->with('swal_error', 'Esta factura ya tiene una rectificativa asociada.');
        }

        try {
            // Crear la factura rectificativa con valores negativos
            $facturaRectificativa = Invoices::create([
                'budget_id' => $facturaOriginal->budget_id,
                'cliente_id' => $facturaOriginal->cliente_id,
                'reserva_id' => $facturaOriginal->reserva_id,
                'invoice_status_id' => $facturaOriginal->invoice_status_id,
                'concepto' => 'RECTIFICATIVA - ' . $facturaOriginal->concepto,
                'description' => $facturaOriginal->description,
                'fecha' => now()->toDateString(),
                'fecha_cobro' => null,
                'base' => -$facturaOriginal->base, // Valor negativo
                'iva' => -$facturaOriginal->iva,   // Valor negativo
                'descuento' => $facturaOriginal->descuento ? -$facturaOriginal->descuento : null,
                'total' => -$facturaOriginal->total, // Valor negativo
                'reference' => null, // Se generará después
                'reference_autoincrement_id' => null,
                // Campos específicos de rectificativa
                'es_rectificativa' => true,
                'factura_original_id' => $facturaOriginal->id,
                'motivo_rectificacion' => $request->motivo_rectificacion,
                'observaciones_rectificacion' => $request->observaciones_rectificacion,
            ]);

            // Generar referencia para la factura rectificativa
            $referencia = $this->generateRectificativaReference($facturaOriginal);
            $facturaRectificativa->reference = $referencia['reference'];
            $facturaRectificativa->reference_autoincrement_id = $referencia['id'];
            $facturaRectificativa->save();

            return redirect()->route('admin.facturas.show', $facturaRectificativa->id)
                ->with('swal_success', 'Factura rectificativa creada correctamente. El total neto de la factura original es ahora 0€.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear la factura rectificativa: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar las facturas rectificativas de una factura original
     */
    public function showRectificativas($id)
    {
        $facturaOriginal = Invoices::with(['cliente', 'reserva', 'estado', 'facturasRectificativas'])->findOrFail($id);

        return view('admin.invoices.show-rectificativas', compact('facturaOriginal'));
    }

    /**
     * Generar referencia para factura rectificativa
     * Formato: R + referencia original (ej: R2025/09/000001)
     */
    protected function generateRectificativaReference(Invoices $facturaOriginal)
    {
        // Obtener la referencia original
        $referenciaOriginal = $facturaOriginal->reference;

        // Crear la referencia rectificativa con "R" al principio
        $referenciaRectificativa = 'R' . $referenciaOriginal;

        // Verificar si ya existe una rectificativa con esta referencia
        $existe = Invoices::where('reference', $referenciaRectificativa)->exists();

        if ($existe) {
            // Si ya existe, añadir un sufijo numérico
            $contador = 1;
            do {
                $referenciaRectificativa = 'R' . $referenciaOriginal . '-' . $contador;
                $existe = Invoices::where('reference', $referenciaRectificativa)->exists();
                $contador++;
            } while ($existe);
        }

        // Crear un registro en la tabla de referencias autoincrementales
        // para mantener la consistencia del sistema
        $referenceToSave = new InvoicesReferenceAutoincrement([
            'reference_autoincrement' => 0, // Las rectificativas no usan autoincremento
            'year' => date('Y'),
            'month_num' => date('m'),
        ]);
        $referenceToSave->save();

        return [
            'id' => $referenceToSave->id,
            'reference' => $referenciaRectificativa,
            'reference_autoincrement' => 0,
        ];
    }

    /**
     * [2026-04-17] Generar un token seguro de descarga y enviarselo al cliente
     * por WhatsApp (numero del cliente) y email (facturacion_email o email).
     * El enlace caduca a 30 dias. Queda registrado en invoice_download_tokens.
     *
     * Se dispara manualmente desde el detalle de la factura para no afectar
     * flujos automaticos existentes ni re-enviar facturas antiguas.
     */
    public function enviarAlCliente($id)
    {
        $invoice = Invoices::with('cliente')->findOrFail($id);
        $cliente = $invoice->cliente;

        if (!$cliente) {
            return redirect()->back()->with('error', 'La factura no tiene cliente asignado.');
        }

        $telefonoCliente = $cliente->telefono_movil ?: $cliente->telefono;
        $emailCliente    = $cliente->facturacion_email ?: $cliente->email;

        if (empty($telefonoCliente) && empty($emailCliente)) {
            return redirect()->back()->with('error', 'El cliente no tiene telefono ni email para enviarle la factura.');
        }

        // Generar token unico (64 chars, no predecible)
        $token = bin2hex(random_bytes(24));
        $downloadToken = \App\Models\InvoiceDownloadToken::create([
            'invoice_id'   => $invoice->id,
            'token'        => $token,
            'expires_at'   => now()->addDays(30),
            'downloaded_at' => null,
        ]);

        $url = route('facturas.descargarPublica', ['token' => $token]);
        $referencia = $invoice->reference ?: ('#' . $invoice->id);
        $totalFormateado = number_format($invoice->total, 2, ',', '.') . ' EUR';

        $enviados = [];
        $errores  = [];

        // 1) Email
        if (!empty($emailCliente)) {
            try {
                $asunto = 'Tu factura ' . $referencia;
                $cuerpo = "Hola " . ($cliente->nombre ?: '') . ",\n\n"
                    . "Aqui tienes el enlace seguro para descargar tu factura " . $referencia
                    . " (total: {$totalFormateado}):\n\n" . $url . "\n\n"
                    . "El enlace caduca el " . $downloadToken->expires_at->format('d/m/Y') . ".\n\n"
                    . "Gracias por elegirnos.";
                \Illuminate\Support\Facades\Mail::raw($cuerpo, function ($m) use ($emailCliente, $asunto) {
                    $m->to($emailCliente)->subject($asunto);
                });
                $enviados[] = 'email';
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error enviando email factura al cliente: ' . $e->getMessage(), [
                    'invoice_id' => $invoice->id,
                ]);
                $errores[] = 'email: ' . $e->getMessage();
            }
        }

        // 2) WhatsApp (mensaje simple, sin template, para no depender de templates aprobados)
        if (!empty($telefonoCliente)) {
            try {
                $texto = "Hola " . ($cliente->nombre ?: '')
                    . ", aqui tienes tu factura {$referencia} (total {$totalFormateado}): {$url}"
                    . " (el enlace caduca el " . $downloadToken->expires_at->format('d/m/Y') . ")";

                $token_wa   = \App\Models\Setting::whatsappToken();
                $urlMensajes = \App\Models\Setting::whatsappUrl();

                // Normalizar telefono: solo digitos, anadir prefijo 34 si faltara
                $phone = preg_replace('/\D+/', '', $telefonoCliente);
                if ($phone !== '' && !str_starts_with($phone, '34') && strlen($phone) === 9) {
                    $phone = '34' . $phone;
                }

                $payload = [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => ['body' => $texto],
                ];

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token_wa,
                ])->post($urlMensajes, $payload);

                if ($response->successful()) {
                    $enviados[] = 'whatsapp';
                } else {
                    $errores[] = 'whatsapp: ' . $response->body();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error enviando whatsapp factura al cliente: ' . $e->getMessage(), [
                    'invoice_id' => $invoice->id,
                ]);
                $errores[] = 'whatsapp: ' . $e->getMessage();
            }
        }

        // Registrar por que vias salio
        if (!empty($enviados)) {
            $downloadToken->sent_via = implode('+', $enviados);
            $downloadToken->save();
        }

        \Illuminate\Support\Facades\Log::info('Factura enviada al cliente', [
            'invoice_id' => $invoice->id,
            'token_id' => $downloadToken->id,
            'enviados' => $enviados,
            'errores' => $errores,
        ]);

        if (!empty($enviados)) {
            $msg = 'Factura enviada al cliente via ' . implode(' y ', $enviados) . '.';
            if (!empty($errores)) {
                $msg .= ' Algunos canales fallaron (' . implode('; ', $errores) . ').';
            }
            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back()->with('error', 'No se pudo enviar la factura. Revisa los logs: ' . implode('; ', $errores));
    }

}
