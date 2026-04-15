<?php

namespace App\Http\Controllers;

use App\Models\CategoriaGastos;
use App\Models\CategoriaIngresos;
use App\Models\DiarioCaja;
use Illuminate\Http\Request;
use App\Models\Ingresos;
use App\Models\Gastos;
use App\Models\Reserva;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MovimientosController extends Controller
{

    public function uploadFiles(){
        return view('admin.movimientos.upload');
    }
    public function uploadBooking(){
        return view('admin.movimientos.uploadBooking');
    }

    public function uploadExcel2(Request $request)
{
    $request->validate([
        'file' => 'required'
    ]);

    // Cargar el archivo Excel y extraer datos relevantes
    $file = $request->file('file');
    $data = Excel::toArray([], $file);
    $rows = $data[0];

    // Procesar el archivo para obtener solo datos relevantes
    $header = array_map('strtolower', $rows[7]);
    $indexFechaOperacion = array_search('fecha operación', $header);
    $indexFechaValor = array_search('fecha valor', $header);
    $indexConcepto = array_search('concepto', $header);
    $indexImporte = array_search('importe', $header);
    //return response()->json(['status' => 'success', 'data' => $rows]);

    $movimientos = [];
    foreach (array_slice($rows, 8) as $row) {
        if (!isset($row[$indexFechaOperacion], $row[$indexImporte])) continue;

        $movimientos[] = [
            'fecha_operacion' => $row[$indexFechaOperacion],
            'fecha_valor' => $row[$indexFechaValor] ?? null,
            'concepto' => $row[$indexConcepto] ?? '',
            'importe' => (float) str_replace(',', '.', $row[$indexImporte]),
            'tipo' => $row[$indexImporte] > 0 ? 'Ingreso' : 'Gasto'
        ];
    }

    // Dividir los movimientos en bloques de 100
    $chunks = array_chunk($movimientos, 100);
    $responses = [];
    return response()->json(['status' => 'success', 'data' => $movimientos]);

    foreach ($chunks as $chunk) {
        $prompt = '
            Aquí tienes un bloque de movimientos bancarios en formato JSON. Por favor:
            1. Asegúrate de que las fechas estén en formato ISO 8601 (YYYY-MM-DD).
            2. Verifica que "importe" sea un número decimal válido.
            3. Indica si el movimiento es un "Ingreso" o "Gasto" basándote en el importe.
            4. Devuelve un JSON con los datos procesados.
        ';
        $jsonInput = json_encode($chunk, JSON_UNESCAPED_UNICODE);

        try {
            $responses[] = app(\App\Services\AIGatewayService::class)->chatCompletion([
                "model" => "gpt-4",
                "messages" => [
                    [
                        "role" => "user",
                        "content" => $prompt . "\n\nDatos:\n" . $jsonInput
                    ]
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Error al realizar la solicitud: ' . $e->getMessage()]);
        }

    }

    // Combinar las respuestas en un solo JSON
    $combinedResponses = [];
    foreach ($responses as $response) {
        if (isset($response['choices'][0]['message']['content'])) {
            $parsed = json_decode($response['choices'][0]['message']['content'], true);
            if ($parsed) {
                $combinedResponses = array_merge($combinedResponses, $parsed);
            }
        }
    }

    // Guardar el JSON combinado
    Storage::disk('public')->put('MovimientosProcesados.json', json_encode($combinedResponses, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return response()->json(['status' => 'success', 'data' => $combinedResponses]);
}


    public function uploadExcel(Request $request)
    {
        // Validación del archivo
        $request->validate([
            'file' => 'required|mimes:xlsx'
        ]);

        // Leer el archivo Excel
        $file = $request->file('file');
        $data = Excel::toArray([], $file);

        // Procesar el primer sheet del archivo Excel
        $rows = $data[0];

        // Filtrar y ordenar las filas por fecha contable
        $filteredRows = array_filter($rows, function ($row, $index) {
            return $index >= 5 && isset($row[0]) && !empty($row[0]) && strtolower($row[0]) !== 'fecha contable' && is_numeric($row[0]);
        }, ARRAY_FILTER_USE_BOTH);

        usort($filteredRows, function ($a, $b) {
            return (float)$a[0] <=> (float)$b[0];
        });

        // Contadores para el reporte
        $procesados = 0;
        $duplicados = [];
        $errores = [];
        $ingresosCreados = 0;
        $gastosCreados = 0;
        $hashesHuérfanosEliminados = 0;

        // Procesar cada fila
        foreach ($filteredRows as $index => $row) {
            $diarioCajaIdParaHash = null;
            try {
                // Convertir el número de fecha de Excel en una fecha válida de Carbon
                $fecha_contable = Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[0])->format('Y-m-d'));
            } catch (\Exception $e) {
                // Si no se puede convertir a fecha, agregar a errores
                $errores[] = [
                    'fila' => $index + 6, // +6 porque empezamos desde la fila 5 del Excel
                    'error' => 'Error al convertir fecha: ' . $e->getMessage(),
                    'datos' => [
                        'fecha' => $row[0] ?? 'N/A',
                        'descripcion' => $row[5] ?? 'N/A',
                        'debe' => $row[7] ?? 'N/A',
                        'haber' => $row[8] ?? 'N/A'
                    ]
                ];
                continue;
            }

            $descripcion = $row[5];
            $debe = $row[7];  // Gastos
            $haber = $row[8]; // Ingresos
            $saldo = $row[10]; // Saldo

            // Generar un hash único basado en la combinación de fecha, descripción, debe y haber
            $hash = md5($fecha_contable->format('Y-m-d') . $descripcion . (float)$debe . (float)$haber . (float)$saldo);

            // Verificar si ya existe un registro con este hash
            $existingHash = DB::table('hash_movimientos')
                ->where('hash', $hash)
                ->first();

            if ($existingHash) {
                // Buscar el registro original que generó este hash
                $registroOriginal = null;
                
                // Buscar en ingresos
                $ingresoOriginal = DB::table('ingresos')
                    ->where('date', $fecha_contable->format('Y-m-d'))
                    ->where('title', $descripcion)
                    ->where('quantity', $haber > 0 ? $haber : $debe)
                    ->first();
                
                if ($ingresoOriginal) {
                    $registroOriginal = [
                        'tipo' => 'ingreso',
                        'id' => $ingresoOriginal->id,
                        'fecha' => $ingresoOriginal->date,
                        'concepto' => $ingresoOriginal->title,
                        'importe' => $ingresoOriginal->quantity,
                        'categoria_id' => $ingresoOriginal->categoria_id,
                        'created_at' => $ingresoOriginal->created_at
                    ];
                } else {
                    // Buscar en gastos
                    $gastoOriginal = DB::table('gastos')
                        ->where('date', $fecha_contable->format('Y-m-d'))
                        ->where('title', $descripcion)
                        ->where('quantity', $debe > 0 ? $debe : $haber)
                        ->first();
                    
                    if ($gastoOriginal) {
                        $registroOriginal = [
                            'tipo' => 'gasto',
                            'id' => $gastoOriginal->id,
                            'fecha' => $gastoOriginal->date,
                            'concepto' => $gastoOriginal->title,
                            'importe' => $gastoOriginal->quantity,
                            'categoria_id' => $gastoOriginal->categoria_id,
                            'created_at' => $gastoOriginal->created_at
                        ];
                    }
                }

                // Si encontramos el registro original, es un duplicado real
                if ($registroOriginal) {
                    $duplicados[] = [
                        'fila' => $index + 6, // +6 porque empezamos desde la fila 5 del Excel
                        'fecha' => $fecha_contable->format('Y-m-d'),
                        'descripcion' => $descripcion,
                        'debe' => $debe,
                        'haber' => $haber,
                        'saldo' => $saldo,
                        'hash' => $hash,
                        'hash_id' => $existingHash->id,
                        'hash_created_at' => $existingHash->created_at,
                        'registro_original' => $registroOriginal,
                        'razon' => 'Registro duplicado (ya existe en la base de datos)'
                    ];
                    continue; // Saltar esta fila para evitar duplicados
                } else {
                    // Hash huérfano: eliminar el hash y continuar con el procesamiento
                    DB::table('hash_movimientos')->where('id', $existingHash->id)->delete();
                    $hashesHuérfanosEliminados++;
                    // Continuar con el procesamiento normal (no hacer continue)
                }
            }

            // Obtener una categoría por defecto (ajustar según tu lógica)
            $categoria_ingreso = CategoriaIngresos::first();
            $categoria_gasto = CategoriaGastos::first();

            $registroProcesado = false;

            // Si es un ingreso (HABER)
            if (!empty($haber) && $haber > 0) {
                // Verificar si el ingreso ya existe en la tabla de ingresos
                $existingIngreso = Ingresos::where('date', $fecha_contable)
                    ->where('title', $descripcion)
                    ->where('quantity', $haber)
                    ->first();

                if (!$existingIngreso) {
                    // Crear el ingreso
                    $ingreso = Ingresos::create([
                        'categoria_id' => $categoria_ingreso->id ?? 1,
                        'bank_id' => 1,      // Ajusta según tu lógica
                        'title' => $descripcion,
                        'quantity' => $haber,
                        'date' => $fecha_contable,
                        'estado_id' => 1
                    ]);

                    // Reflejar el ingreso en el Diario de Caja (guardar instancia para vincular hash)
                    $diarioCaja = DiarioCaja::create([
                        'asiento_contable' => $this->generarAsientoContable(),
                        'cuenta_id' => 1, // Aquí seleccionas la cuenta contable adecuada
                        'ingreso_id' => $ingreso->id,
                        'date' => $fecha_contable,
                        'concepto' => $descripcion,
                        'haber' => $haber,
                        'tipo' => 'ingreso',
                        'estado_id' => 1
                    ]);
                    $diarioCajaIdParaHash = $diarioCaja->id;

                    $ingresosCreados++;
                    $registroProcesado = true;
                }
            }

            // Si es un gasto (DEBE)
            if (!empty($debe) && $debe != 0) {
                // Verificar si el gasto ya existe en la tabla de gastos
                $existingGasto = Gastos::where('date', $fecha_contable)
                    ->where('title', $descripcion)
                    ->where('quantity', $debe)
                    ->first();

                if (!$existingGasto) {
                    // Crear el gasto
                    $gasto = Gastos::create([
                        'categoria_id' => $categoria_gasto->id ?? 1,
                        'bank_id' => 1,
                        'title' => $descripcion,
                        'quantity' => $debe,
                        'date' => $fecha_contable,
                        'estado_id' => 1
                    ]);

                    // Reflejar el gasto en el Diario de Caja (guardar instancia para vincular hash)
                    $diarioCaja = DiarioCaja::create([
                        'asiento_contable' => $this->generarAsientoContable(),
                        'cuenta_id' => 1, // Aquí seleccionas la cuenta contable adecuada
                        'gasto_id' => $gasto->id,
                        'date' => $fecha_contable,
                        'concepto' => $descripcion,
                        'debe' => $debe,
                        'tipo' => 'gasto',
                        'estado_id' => 1
                    ]);
                    $diarioCajaIdParaHash = $diarioCaja->id;

                    $gastosCreados++;
                    $registroProcesado = true;
                }
            }

            // Solo guardar el hash si se procesó algún registro (con diario_caja_id para poder borrarlo al eliminar la línea)
            if ($registroProcesado) {
                $insertHash = [
                    'hash' => $hash,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (isset($diarioCajaIdParaHash) && \Schema::hasColumn('hash_movimientos', 'diario_caja_id')) {
                    $insertHash['diario_caja_id'] = $diarioCajaIdParaHash;
                }
                DB::table('hash_movimientos')->insert($insertHash);
                $procesados++;
            }
        }

        // Preparar respuesta detallada
        $response = [
            'message' => 'Archivo procesado correctamente.',
            'resumen' => [
                'total_filas' => count($filteredRows),
                'procesados' => $procesados,
                'duplicados' => count($duplicados),
                'errores' => count($errores),
                'ingresos_creados' => $ingresosCreados,
                'gastos_creados' => $gastosCreados,
                'hashes_huérfanos_eliminados' => $hashesHuérfanosEliminados
            ]
        ];

        // Agregar detalles si hay duplicados o errores
        if (!empty($duplicados)) {
            $response['duplicados_detalle'] = $duplicados;
        }

        if (!empty($errores)) {
            $response['errores_detalle'] = $errores;
        }

        return response()->json($response);
    }


    public function uploadCSV(Request $request)
    {
        // Validar que el archivo es un CSV
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt',
        ]);

        // Abrir el archivo CSV
        $file = fopen($request->file('csv_file'), 'r');
        fgetcsv($file); // Saltar la primera fila (encabezados)

        $totalNeto = 0;

        while (($data = fgetcsv($file, 1000, ',')) !== FALSE) {
            // Datos del CSV
            $numeroReferencia = $data[1];
            $comision = $data[9];
            $cargoPorPago = $data[10];
            $iva = $data[11];
            $neto = $data[12];
            $estadoPago = $data[8];
            //dd($numeroReferencia);
            // Solo procesar si el estado de pago es 'by_booking' (indica que se pagó a través de la plataforma)
            $reserva = Reserva::where('codigo_reserva', $numeroReferencia)->first();
            //dd($reserva);
            if ($reserva) {
                // Cambiar el estado de la reserva a 'pagada'
                $reserva->estado_id = 6;

                // Actualizar los valores de la reserva
                $reserva->comision = $comision;
                $reserva->cargo_por_pago = $cargoPorPago;
                $reserva->iva = $iva;
                $reserva->neto = $neto;
                $reserva->save();

                // Sumar el neto al total del neto
                $totalNeto += $neto;
            }
        }

        fclose($file);

        // Obtener el último ingreso de categoría 'booking'
        $ultimoIngresoBooking = Ingresos::where('categoria_id', 2)->where('quantity', $totalNeto)->orderBy('created_at', 'desc')->first();

        if ($ultimoIngresoBooking) {
            // $diferencia = $totalNeto - $ultimoIngresoBooking->neto;
            // Comparar la diferencia
        }

        return redirect()->back()->with('status', 'Archivo procesado exitosamente.');
    }


    private function generarAsientoContable()
    {
        // Generar un número de asiento contable único para cada registro
        $asiento = DiarioCaja::orderBy('id', 'desc')->first();
        $anio = Carbon::now()->format('Y');
        $numeroAsiento;

        if ($asiento != null) {
            $asientoTemporal = explode("/", $asiento->asiento_contable);
            $numeroAsientos = $asientoTemporal[0] + 1;
            $numeroConCeros = str_pad($numeroAsientos, 4, "0", STR_PAD_LEFT);
            $numeroAsiento =  $numeroConCeros. '/' . $anio;
        } else {
            $numeroAsiento = '0001' . '/' . $anio;
        }

        return $numeroAsiento;
    }



}
