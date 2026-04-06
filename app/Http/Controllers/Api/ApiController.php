<?php

namespace App\Http\Controllers\Api;

use App\Models\Apartamento;
use App\Models\Edificio;
use App\Models\Reserva;
use App\Models\ChatGpt;
use App\Models\Cliente;
use App\Models\Huesped;
use App\Models\Invoices;
use App\Models\InvoicesReferenceAutoincrement;
use App\Models\MensajeAuto;
use App\Models\Photo;
use App\Services\ChatGptService;
use Carbon\Carbon;
use Carbon\Cli\Invoker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente
use App\Models\Reparaciones;
use App\Models\Setting;

class ApiController extends Controller
{
    /**
     * Obtener las reservas para hoy
     */
    // public function obtenerReservasHoy(Request $request)
    // {
    //     // Obtener la fecha y la hora actual
    //     $hoy = Carbon::now();
    //     $horaLimite = Carbon::createFromTime(14, 0, 0); // Hora límite: 14:00

    //     // Filtrar las reservas cuya fecha de entrada sea hoy
    //     $reservas = Reserva::whereDate('fecha_entrada', $hoy->toDateString())
    //         ->where(function ($query) use ($hoy, $horaLimite) {
    //             // Excluir las reservas cuya fecha de salida sea hoy y la hora sea mayor a las 14:00
    //             $query->whereDate('fecha_salida', '>', $hoy->toDateString())
    //                 ->orWhere(function ($query) use ($hoy, $horaLimite) {
    //                     $query->whereDate('fecha_salida', $hoy->toDateString())
    //                         ->whereTime('fecha_salida', '<', $horaLimite->toTimeString());
    //                 });
    //         })
    //         ->get();

    //     // Verificar si hay reservas y formatear los datos para la respuesta
    //     if ($reservas->isNotEmpty()) {
    //         $data = $reservas->map(function ($reserva) {
    //             return [
    //                 'codigo_reserva' => $reserva->codigo_reserva,
    //                 'cliente' => $reserva->cliente['nombre'] == null ? $reserva->cliente->alias : $reserva->cliente['nombre'] . ' ' . $reserva->cliente['apellido1'],
    //                 'apartamento' => $reserva->apartamento->titulo,
    //                 'edificio' => isset($reserva->apartamento->edificioName->nombre) ? $reserva->apartamento->edificioName->nombre : 'Edificio Hawkins Suite',
    //                 'fecha_entrada' => $reserva->fecha_entrada,
    //                 'clave' => $reserva->apartamento->claves
    //             ];
    //         });

    //         return response()->json($data, 200);
    //     } else {
    //         return response()->json('Error, no se encontraron reservas para hoy', 400);
    //     }
    // }

    public function obtenerReservasHoy(Request $request)
    {
        // Obtener la fecha y hora actual
        $hoy = Carbon::now();

        // Filtrar las reservas activas
        $reservas = Reserva::where('fecha_entrada', '<=', $hoy) // La reserva ya inició
            ->where(function ($query) use ($hoy) {
                $query->where('fecha_salida', '>', $hoy) // Aún no ha salido
                    ->orWhere(function ($query) use ($hoy) {
                        $query->whereDate('fecha_salida', $hoy->toDateString())
                              ->whereTime('fecha_salida', '>', $hoy->toTimeString()); // Salida más tarde hoy
                    });
            })
            ->get();

        // Verificar si hay reservas y formatear los datos para la respuesta
        if ($reservas->isNotEmpty()) {
            $data = $reservas->map(function ($reserva) {
                return [
                    'codigo_reserva' => $reserva->codigo_reserva,
                    'cliente' => $reserva->cliente['nombre'] == null ? $reserva->cliente->alias : $reserva->cliente['nombre'] . ' ' . $reserva->cliente['apellido1'],
                    'apartamento' => $reserva->apartamento->titulo,
                    'edificio' => isset($reserva->apartamento->edificioName->nombre) ? $reserva->apartamento->edificioName->nombre : 'Edificio Hawkins Suite',
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'fecha_salida' => $reserva->fecha_salida,
                    'clave' => $reserva->apartamento->claves
                ];
            });

            return response()->json($data, 200);
        } else {
            return response()->json('No hay reservas activas', 400);
        }
    }


    /**
     * Obtener los apartamentos
     */
    public function obtenerApartamentos()
    {
        $apartamentos = Apartamento::all();
        return response()->json($apartamentos);
    }

    /**
     * Obtener apartamentos activos en Channex (id_channex no nulo) con información básica.
     *
     * Pensado para consumo por plataformas externas.
     */
    public function obtenerApartamentosChannex(Request $request)
    {
        $apartamentos = Apartamento::query()
            ->whereNotNull('id_channex')
            ->with(['edificioName:id,nombre'])
            ->orderBy('id')
            ->get([
                'id',
                'nombre',
                'titulo',
                'id_channex',
                'edificio_id',
                'city',
                'address',
                'zip_code',
                'max_guests',
                'bedrooms',
                'bathrooms',
                'size',
                'latitude',
                'longitude',
                'check_in_time',
                'check_out_time',
            ]);

        $data = $apartamentos->map(function (Apartamento $apartamento) {
            return [
                'id' => $apartamento->id,
                'nombre' => $apartamento->nombre,
                'titulo' => $apartamento->titulo,
                'id_channex' => $apartamento->id_channex,
                'edificio_id' => $apartamento->edificio_id,
                'edificio_nombre' => optional($apartamento->edificioName)->nombre,
                'city' => $apartamento->city,
                'address' => $apartamento->address,
                'zip_code' => $apartamento->zip_code,
                'max_guests' => $apartamento->max_guests,
                'bedrooms' => $apartamento->bedrooms,
                'bathrooms' => $apartamento->bathrooms,
                'size' => $apartamento->size,
                'latitude' => $apartamento->latitude,
                'longitude' => $apartamento->longitude,
                'check_in_time' => $apartamento->check_in_time,
                'check_out_time' => $apartamento->check_out_time,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Obtener los apartamentos disponibles
     */
    public function obtenerApartamentosDisponibles(Request $request)
    {
        // Obtener la fecha y la hora actual
        $hoy = Carbon::now();

        // Obtener los IDs de los apartamentos que están reservados hoy
        $reservasHoy = Reserva::whereDate('fecha_entrada', '<=', $hoy->toDateString())
            ->whereDate('fecha_salida', '>=', $hoy->toDateString())
            ->pluck('apartamento_id');

        // Obtener los apartamentos que no están en las reservas de hoy
        $apartamentosDisponibles = Apartamento::whereNotIn('id', $reservasHoy)->where('id_booking', '!=', 1)->get();

        // Formatear los datos para la respuesta
        $data = $apartamentosDisponibles->map(function ($apartamento) {
            return [
                'id' => $apartamento->id,
                'titulo' => $apartamento->titulo,
                'descripcion' => $apartamento->descripcion, // Asegúrate de que este campo existe en tu modelo
                'edificio' => $apartamento->edificioName->nombre ?? 'Edificio Hawkins Suite', // Agregar el nombre del edificio
                'claves' => $apartamento->claves,
                // Agrega más campos según lo necesites
            ];
        });

        return response()->json($data, 200);
    }


   /**
     * Averias tecnico
     */
    public function averiasTecnico(Request $request)
    {
        $manitas = Reparaciones::all();

        $phone = $request->phone;

        // Guardar la solicitud en un archivo .txt
        $data = "Averias tecnico: " . json_encode($request->all()) . "\n";
        file_put_contents(storage_path('app/averias_tecnico.txt'), $data, FILE_APPEND);

        return response()->json('Averias tecnico enviada', 200);
    }

    /**
     * Equipo de limpieza
     */
    public function equipoLimpieza(Request $request)
    {
        $phone = $request->phone;

        // Guardar la solicitud en un archivo .txt
        $data = "Equipo de limpieza: " . json_encode($request->all()) . "\n";
        file_put_contents(storage_path('app/equipo_limpieza.txt'), $data, FILE_APPEND);

        return response()->json('Equipo de limpieza enviada', 200);
    }

    /**
     * Obtener reservas para integraciones externas con filtros flexibles.
     *
     * Filtros soportados (query string):
     * - fecha_desde: fecha ISO (YYYY-MM-DD) para filtrar por rango de fechas (fecha_entrada >=)
     * - fecha_hasta: fecha ISO (YYYY-MM-DD) para filtrar por rango de fechas (fecha_salida <=)
     * - actualizado_desde: fecha/hora ISO para filtrar por updated_at >=
     * - apartamento_id: ID de apartamento
     * - edificio_id: ID de edificio (filtra por reservas cuyo apartamento pertenece a ese edificio)
     * - estado: ID de estado (ej. excluir canceladas estado_id=4 por defecto)
     *
     * Paginación:
     * - page: número de página (por defecto 1)
     * - per_page: elementos por página (por defecto 50, máximo 200)
     */
    public function obtenerReservas(Request $request)
    {
        $query = Reserva::query()
            ->with(['cliente:id,nombre,apellido1,alias,email,telefono', 'apartamento:id,titulo,edificio_id', 'apartamento.edificio:id,nombre'])
            // Excluir canceladas por defecto (estado_id = 4) salvo que se pida explícitamente
            ->when(!$request->filled('estado'), function ($q) {
                $q->where(function ($q2) {
                    $q2->where('estado_id', '!=', 4)->orWhereNull('estado_id');
                });
            });

        if ($request->filled('estado')) {
            $query->where('estado_id', $request->integer('estado'));
        }

        if ($request->filled('apartamento_id')) {
            $query->where('apartamento_id', $request->integer('apartamento_id'));
        }

        if ($request->filled('edificio_id')) {
            $edificioId = $request->integer('edificio_id');
            $query->whereHas('apartamento', function ($q) use ($edificioId) {
                $q->where('edificio_id', $edificioId);
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_entrada', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_salida', '<=', $request->input('fecha_hasta'));
        }

        if ($request->filled('actualizado_desde')) {
            $query->where('updated_at', '>=', $request->input('actualizado_desde'));
        }

        // Paginación controlada
        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(1, min($perPage, 200));

        $reservas = $query
            ->orderBy('fecha_entrada', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $data = $reservas->getCollection()->map(function (Reserva $reserva) {
            return [
                'id' => $reserva->id,
                'codigo_reserva' => $reserva->codigo_reserva,
                'apartamento_id' => $reserva->apartamento_id,
                'apartamento_titulo' => optional($reserva->apartamento)->titulo,
                'edificio_id' => optional($reserva->apartamento)->edificio_id,
                'edificio_nombre' => optional(optional($reserva->apartamento)->edificio)->nombre,
                'cliente_id' => $reserva->cliente_id,
                'cliente_nombre_completo' => $reserva->cliente
                    ? trim(($reserva->cliente->nombre ?? '') . ' ' . ($reserva->cliente->apellido1 ?? '')) ?: $reserva->cliente->alias
                    : null,
                'cliente_email' => optional($reserva->cliente)->email,
                'cliente_telefono' => optional($reserva->cliente)->telefono,
                'fecha_entrada' => $reserva->fecha_entrada,
                'fecha_salida' => $reserva->fecha_salida,
                'numero_personas' => $reserva->numero_personas,
                'numero_ninos' => $reserva->numero_ninos,
                'precio' => $reserva->precio,
                'neto' => $reserva->neto,
                'comision' => $reserva->comision,
                'iva' => $reserva->iva,
                'origen' => $reserva->origen,
                'estado_id' => $reserva->estado_id,
                'id_channex' => $reserva->id_channex,
                'no_facturar' => $reserva->no_facturar,
                'created_at' => $reserva->created_at,
                'updated_at' => $reserva->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'meta' => [
                'current_page' => $reservas->currentPage(),
                'per_page' => $reservas->perPage(),
                'total' => $reservas->total(),
                'last_page' => $reservas->lastPage(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Obtener el listado de edificios para integraciones externas.
     *
     * Devuelve todos los edificios activos (no soft-deleted) con sus campos básicos.
     * No aplica paginación porque el volumen esperado es pequeño.
     */
    public function obtenerEdificios(Request $request)
    {
        $edificios = Edificio::query()
            ->orderBy('id')
            ->get(['id', 'nombre', 'clave', 'codigo_establecimiento']);

        // Formato de respuesta estable para la plataforma externa
        $data = $edificios->map(function (Edificio $edificio) {
            return [
                'id' => $edificio->id,
                'nombre' => $edificio->nombre,
                'clave' => $edificio->clave,
                'codigo_establecimiento' => $edificio->codigo_establecimiento,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    /**
     * Agregar compra reserva
     */
    public function agregarCompraReserva(Request $request)
    {
        
    }

    public function mensajesPlantillaAverias($nombreManita, $apartamento, $edificio, $mensaje, $telefono, $telefonoManitas, $idioma = 'es'){
        $token = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefonoManitas,
            "type" => "template",
            "template" => [
                "name" => 'reparaciones',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombreManita],
                            ["type" => "text", "text" => $apartamento],
                            ["type" => "text", "text" => $edificio],
                            ["type" => "text", "text" => $mensaje],
                            ["type" => "text", "text" => $telefono],
                        ],
                    ],
                ],
            ],
        ];

        $urlMensajes = Setting::whatsappUrl();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlMensajes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($mensajePersonalizado),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        return $response;

    }


    public function mensajesPlantillaLimpiadora($apartamento, $edificio, $mensaje, $telefono, $telefonoLimpiadora, $idioma = 'es'){
        $token = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefonoLimpiadora,
            "type" => "template",
            "template" => [
                "name" => '',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $apartamento],
                            ["type" => "text", "text" => $edificio],
                            ["type" => "text", "text" => $mensaje],
                            ["type" => "text", "text" => $telefono],
                        ],
                    ],
                ],
            ],
        ];

        $urlMensajes = Setting::whatsappUrl();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlMensajes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($mensajePersonalizado),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        return $response;

    }


}

