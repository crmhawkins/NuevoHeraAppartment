<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Models\ChatGpt;
use App\Services\MetodoEntradaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WhatsappToolsController extends Controller
{
    /**
     * Obtener claves de acceso al apartamento
     */
    public function obtenerClaves(Request $request)
    {
        $request->validate([
            'codigo_reserva' => 'required|string'
        ]);

        $codigoReserva = $request->codigo_reserva;

        Log::info("🔑 OBTENER CLAVES - Código: {$codigoReserva}");

        $reserva = Reserva::with(['apartamento', 'apartamento.edificioName'])->where('codigo_reserva', $codigoReserva)->first();

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna reserva con ese código.',
                'data' => null
            ], 404);
        }

        // Verificaciones
        $hoy = now();
        $fechaEntrada = Carbon::parse($reserva->fecha_entrada);
        $horaActual = now()->format('H:i');

        if (empty($reserva->dni_entregado)) {
            $url = 'https://crm.apartamentosalgeciras.com/dni-user/' . $reserva->token;
            return response()->json([
                'success' => false,
                'message' => 'Debes entregar el DNI antes de recibir las claves. Puedes hacerlo en: ' . $url,
                'data' => [
                    'url_dni' => $url,
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'dni_entregado' => false
                ]
            ], 400);
        }

        if ($fechaEntrada->format('Y-m-d') !== $hoy->format('Y-m-d')) {
            return response()->json([
                'success' => false,
                'message' => 'Las claves solo están disponibles el día de entrada.',
                'data' => [
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'fecha_actual' => $hoy->format('Y-m-d')
                ]
            ], 400);
        }

        if ($horaActual < '15:00') {
            return response()->json([
                'success' => false,
                'message' => 'Las claves estarán disponibles a partir de las 15:00.',
                'data' => [
                    'hora_actual' => $horaActual,
                    'hora_disponible' => '15:00'
                ]
            ], 400);
        }

        // Clave del edificio (según el apartamento)
        $claveEdificio = null;
        if ($reserva->apartamento && $reserva->apartamento->edificioName) {
            $claveEdificio = $reserva->apartamento->edificioName->clave ?? null;
        }

        $metodoEntradaService = app(MetodoEntradaService::class);
        $metodoEntrada = $metodoEntradaService->resolverParaReserva($reserva);

        if ($metodoEntrada === MetodoEntradaService::METODO_DIGITAL) {
            return response()->json([
                'success' => true,
                'message' => 'Acceso digital configurado. Entrega de códigos pendiente de integración con la plataforma externa.',
                'data' => [
                    'codigo_reserva' => $reserva->codigo_reserva,
                    'apartamento' => $reserva->apartamento->titulo ?? 'N/A',
                    'metodo_entrada' => $metodoEntrada,
                    'digital' => [
                        'estado' => 'pendiente_integracion',
                        'codigo' => null,
                        'validez_desde' => null,
                        'validez_hasta' => null,
                    ],
                    'clave_edificio' => $claveEdificio,
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'fecha_salida' => $reserva->fecha_salida,
                    'dni_entregado' => true,
                ],
            ]);
        }

        // Si todo está correcto, devolver las claves
        return response()->json([
            'success' => true,
            'message' => 'Claves de acceso obtenidas correctamente.',
            'data' => [
                'codigo_reserva' => $reserva->codigo_reserva,
                'apartamento' => $reserva->apartamento->titulo ?? 'N/A',
                'metodo_entrada' => $metodoEntrada,
                'claves' => $reserva->apartamento->claves ?? 'Contacta con nosotros para obtener las claves',
                'clave_edificio' => $claveEdificio,
                'fecha_entrada' => $reserva->fecha_entrada,
                'fecha_salida' => $reserva->fecha_salida,
                'dni_entregado' => true
            ]
        ]);
    }

    /**
     * Notificar al técnico sobre una avería
     */
    public function notificarTecnico(Request $request)
    {
        $request->validate([
            'descripcion_problema' => 'required|string|max:1000',
            'urgencia' => 'required|string|in:baja,media,alta'
        ]);

        $descripcion = $request->descripcion_problema;
        $urgencia = $request->urgencia;
        $telefono = $request->telefono ?? 'No especificado';

        Log::info("🚨 NOTIFICAR TÉCNICO - Urgencia: {$urgencia}, Teléfono: {$telefono}");

        // Registrar la avería en logs
        $this->registrarAveria($telefono, $descripcion, $urgencia);

        // Enviar mensaje al técnico
        $this->enviarMensajeTecnico($telefono, $descripcion, $urgencia);

        return response()->json([
            'success' => true,
            'message' => 'Avería registrada y técnico notificado correctamente.',
            'data' => [
                'descripcion' => $descripcion,
                'urgencia' => $urgencia,
                'telefono' => $telefono,
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Notificar al equipo de limpieza
     */
    public function notificarLimpieza(Request $request)
    {
        $request->validate([
            'tipo_limpieza' => 'required|string|max:500',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        $tipoLimpieza = $request->tipo_limpieza;
        $observaciones = $request->observaciones ?? '';
        $telefono = $request->telefono ?? 'No especificado';

        Log::info("🧹 NOTIFICAR LIMPIEZA - Tipo: {$tipoLimpieza}, Teléfono: {$telefono}");

        // Registrar la solicitud de limpieza en logs
        $this->registrarLimpieza($telefono, $tipoLimpieza, $observaciones);

        // Enviar mensaje a la limpiadora
        $this->enviarMensajeLimpiadora($telefono, $tipoLimpieza, $observaciones);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de limpieza registrada y equipo notificado correctamente.',
            'data' => [
                'tipo_limpieza' => $tipoLimpieza,
                'observaciones' => $observaciones,
                'telefono' => $telefono,
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Registrar avería en logs
     */
    private function registrarAveria($telefono, $descripcion, $urgencia)
    {
        Log::info("📝 REGISTRAR AVERÍA", [
            'telefono' => $telefono,
            'descripcion' => $descripcion,
            'urgencia' => $urgencia,
            'timestamp' => now()->toISOString()
        ]);

        // Aquí podrías guardar en base de datos si tienes una tabla de averías
        // Averia::create([...]);
    }

    /**
     * Enviar mensaje al técnico
     */
    private function enviarMensajeTecnico($telefono, $descripcion, $urgencia)
    {
        $mensaje = "🚨 AVERÍA REPORTADA\n";
        $mensaje .= "📞 Teléfono: {$telefono}\n";
        $mensaje .= "⚠️ Urgencia: " . strtoupper($urgencia) . "\n";
        $mensaje .= "📝 Descripción: {$descripcion}\n";
        $mensaje .= "🕐 Hora: " . now()->format('H:i:s');

        Log::info("👨‍🔧 MENSAJE TÉCNICO", [
            'mensaje' => $mensaje,
            'telefono_cliente' => $telefono
        ]);

        // Aquí implementarías el envío real del mensaje (WhatsApp, SMS, etc.)
        // $this->enviarWhatsApp($numeroTecnico, $mensaje);
    }

    /**
     * Registrar limpieza en logs
     */
    private function registrarLimpieza($telefono, $tipoLimpieza, $observaciones)
    {
        Log::info("📝 REGISTRAR LIMPIEZA", [
            'telefono' => $telefono,
            'tipo_limpieza' => $tipoLimpieza,
            'observaciones' => $observaciones,
            'timestamp' => now()->toISOString()
        ]);

        // Aquí podrías guardar en base de datos si tienes una tabla de solicitudes de limpieza
        // SolicitudLimpieza::create([...]);
    }

    /**
     * Enviar mensaje a la limpiadora
     */
    private function enviarMensajeLimpiadora($telefono, $tipoLimpieza, $observaciones)
    {
        $mensaje = "🧹 SOLICITUD DE LIMPIEZA\n";
        $mensaje .= "📞 Teléfono: {$telefono}\n";
        $mensaje .= "🔧 Tipo: {$tipoLimpieza}\n";
        if ($observaciones) {
            $mensaje .= "📝 Observaciones: {$observaciones}\n";
        }
        $mensaje .= "🕐 Hora: " . now()->format('H:i:s');

        Log::info("👩‍🔧 MENSAJE LIMPIADORA", [
            'mensaje' => $mensaje,
            'telefono_cliente' => $telefono
        ]);

        // Aquí implementarías el envío real del mensaje (WhatsApp, SMS, etc.)
        // $this->enviarWhatsApp($numeroLimpiadora, $mensaje);
    }

    /**
     * Verificar disponibilidad de apartamentos para una fecha
     */
    public function verificarDisponibilidad(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'tipo_apartamento' => 'nullable|string',
            'edificio_id' => 'nullable|integer'
        ]);

        $fecha = Carbon::parse($request->fecha);
        $tipoApartamento = $request->tipo_apartamento;
        $edificioId = $request->edificio_id;

        Log::info("🏠 VERIFICAR DISPONIBILIDAD - Fecha: {$fecha->format('Y-m-d')}");

        // Obtener apartamentos con filtros opcionales
        $query = \App\Models\Apartamento::with(['edificio', 'reservas' => function($q) use ($fecha) {
            $q->where('estado_id', '!=', 4) // Excluir reservas canceladas
              ->where('fecha_entrada', '<=', $fecha)
              ->where('fecha_salida', '>', $fecha);
        }]);

        // Aplicar filtros si se proporcionan
        if ($edificioId) {
            $query->where('edificio_id', $edificioId);
        }

        if ($tipoApartamento) {
            $query->where('titulo', 'like', '%' . $tipoApartamento . '%');
        }

        $apartamentos = $query->get();

        $disponibles = [];
        $ocupados = [];

        foreach ($apartamentos as $apartamento) {
            $tieneReserva = $apartamento->reservas->isNotEmpty();
            
            $apartamentoData = [
                'id' => $apartamento->id,
                'titulo' => $apartamento->titulo,
                'descripcion' => $apartamento->descripcion,
                'edificio' => $apartamento->edificio->nombre ?? 'Sin edificio',
                'capacidad' => $apartamento->capacidad ?? 'No especificada',
                'precio_base' => $apartamento->precio_base ?? 'No especificado',
                'claves' => $apartamento->claves ?? 'No disponibles'
            ];

            if ($tieneReserva) {
                $reserva = $apartamento->reservas->first();
                $apartamentoData['reserva'] = [
                    'codigo_reserva' => $reserva->codigo_reserva,
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'fecha_salida' => $reserva->fecha_salida,
                    'cliente' => $reserva->cliente->nombre ?? 'Sin nombre'
                ];
                $ocupados[] = $apartamentoData;
            } else {
                $disponibles[] = $apartamentoData;
            }
        }

        $totalApartamentos = $apartamentos->count();
        $totalDisponibles = count($disponibles);
        $totalOcupados = count($ocupados);
        $porcentajeDisponibilidad = $totalApartamentos > 0 ? round(($totalDisponibles / $totalApartamentos) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'message' => "Disponibilidad verificada para {$fecha->format('d/m/Y')}",
            'data' => [
                'fecha_consultada' => $fecha->format('Y-m-d'),
                'resumen' => [
                    'total_apartamentos' => $totalApartamentos,
                    'disponibles' => $totalDisponibles,
                    'ocupados' => $totalOcupados,
                    'porcentaje_disponibilidad' => $porcentajeDisponibilidad
                ],
                'apartamentos_disponibles' => $disponibles,
                'apartamentos_ocupados' => $ocupados,
                'filtros_aplicados' => [
                    'tipo_apartamento' => $tipoApartamento,
                    'edificio_id' => $edificioId
                ]
            ]
        ]);
    }

    /**
     * Verificar si una reserva existe
     */
    public function verificarReserva(Request $request)
    {
        $request->validate([
            'codigo_reserva' => 'required|string'
        ]);

        $codigoReserva = $request->codigo_reserva;

        Log::info("🔍 VERIFICAR RESERVA - Código: {$codigoReserva}");

        $reserva = Reserva::with(['cliente', 'apartamento', 'apartamento.edificio'])
            ->where('codigo_reserva', $codigoReserva)
            ->first();

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna reserva con ese código.',
                'data' => [
                    'codigo_reserva' => $codigoReserva,
                    'existe' => false
                ]
            ], 404);
        }

        // Información básica de la reserva
        $reservaData = [
            'codigo_reserva' => $reserva->codigo_reserva,
            'existe' => true,
            'fecha_entrada' => $reserva->fecha_entrada,
            'fecha_salida' => $reserva->fecha_salida,
            'estado' => $reserva->estado->nombre ?? 'Sin estado',
            'apartamento' => [
                'id' => $reserva->apartamento->id,
                'titulo' => $reserva->apartamento->titulo,
                'edificio' => $reserva->apartamento->edificio->nombre ?? 'Sin edificio'
            ],
            'cliente' => [
                'id' => $reserva->cliente->id,
                'nombre' => $reserva->cliente->nombre,
                'telefono' => $reserva->cliente->telefono ?? 'No disponible',
                'email' => $reserva->cliente->email ?? 'No disponible'
            ],
            'dni_entregado' => !empty($reserva->dni_entregado),
            'claves_disponibles' => !empty($reserva->claves),
            'fecha_entrada_formateada' => Carbon::parse($reserva->fecha_entrada)->format('d/m/Y'),
            'fecha_salida_formateada' => Carbon::parse($reserva->fecha_salida)->format('d/m/Y')
        ];

        // Verificar si es el día de entrada
        $hoy = now();
        $fechaEntrada = Carbon::parse($reserva->fecha_entrada);
        $esDiaEntrada = $fechaEntrada->format('Y-m-d') === $hoy->format('Y-m-d');
        
        // Verificar si puede obtener claves
        $puedeObtenerClaves = $esDiaEntrada && 
                             !empty($reserva->dni_entregado) && 
                             now()->format('H:i') >= '15:00';

        $reservaData['es_dia_entrada'] = $esDiaEntrada;
        $reservaData['puede_obtener_claves'] = $puedeObtenerClaves;

        if (!$puedeObtenerClaves && $esDiaEntrada) {
            if (empty($reserva->dni_entregado)) {
                $reservaData['mensaje_claves'] = 'Debe entregar el DNI antes de obtener las claves';
                $reservaData['url_dni'] = 'https://crm.apartamentosalgeciras.com/dni-user/' . $reserva->token;
            } elseif (now()->format('H:i') < '15:00') {
                $reservaData['mensaje_claves'] = 'Las claves estarán disponibles a partir de las 15:00';
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Reserva encontrada correctamente.',
            'data' => $reservaData
        ]);
    }
}