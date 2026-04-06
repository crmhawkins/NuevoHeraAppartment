<?php

namespace App\Services;

use App\Models\Incidencia;
use App\Models\Reparaciones;
use App\Models\Setting;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TecnicoNotificationService
{
    /**
     * Notificar a técnicos sobre una incidencia
     */
    public static function notifyTechniciansAboutIncident(Incidencia $incidencia)
    {
        Log::info("🔔 NOTIFICAR TÉCNICOS - Iniciando para incidencia ID: {$incidencia->id}");

        try {
            // Obtener técnicos disponibles
            $tecnicos = self::obtenerTecnicosDisponibles($incidencia->prioridad);

            if ($tecnicos->isEmpty()) {
                Log::warning("⚠️ No hay técnicos disponibles para notificar");
                return [
                    'success' => false,
                    'message' => 'No hay técnicos disponibles',
                    'tecnicos_notificados' => []
                ];
            }

            Log::info("✅ Técnicos encontrados: " . $tecnicos->count() . " técnicos");

            // Preparar información de la incidencia
            $infoIncidencia = self::prepararInformacionIncidencia($incidencia);

            // Buscar template de WhatsApp
            $template = WhatsappTemplate::where('name', 'reparaciones')
                ->where('name', 'not like', '%_null%')
                ->first();

            $tecnicosNotificados = [];
            $errores = [];

            // Enviar mensaje a cada técnico
            foreach ($tecnicos as $tecnico) {
                try {
                    Log::info("📱 Enviando mensaje al técnico: {$tecnico->nombre} - {$tecnico->telefono}");

                    if ($template) {
                        // Enviar usando template
                        $resultado = self::enviarMensajeTemplate(
                            $tecnico->telefono,
                            $template->name,
                            [
                                '1' => $tecnico->nombre ?? 'Técnico',
                                '2' => $infoIncidencia['apartamento'],
                                '3' => $infoIncidencia['edificio'],
                                '4' => $infoIncidencia['mensaje'],
                                '5' => $infoIncidencia['telefono'] ?? 'Sistema'
                            ]
                        );
                    } else {
                        // Enviar mensaje simple
                        $mensajeTexto = self::generarMensajeSimple($incidencia, $tecnico, $infoIncidencia);
                        $resultado = self::enviarMensajeSimple($tecnico->telefono, $mensajeTexto);
                    }

                    if ($resultado['success'] ?? false) {
                        $tecnicosNotificados[] = $tecnico->id;
                        Log::info("✅ Mensaje enviado exitosamente al técnico: {$tecnico->nombre}");
                    } else {
                        $errores[] = "Error enviando a {$tecnico->nombre}: " . ($resultado['error'] ?? 'Error desconocido');
                        Log::error("❌ Error enviando a {$tecnico->nombre}");
                    }
                } catch (\Exception $e) {
                    $errores[] = "Error con técnico {$tecnico->nombre}: " . $e->getMessage();
                    Log::error("❌ Excepción enviando a {$tecnico->nombre}: " . $e->getMessage());
                }
            }

            // Actualizar incidencia con información de notificación
            $incidencia->update([
                'tecnico_notificado_at' => now(),
                'tecnicos_notificados' => json_encode($tecnicosNotificados),
                'metodo_notificacion' => 'whatsapp'
            ]);

            Log::info("✅ Notificación completada. Técnicos notificados: " . count($tecnicosNotificados));

            return [
                'success' => true,
                'message' => count($tecnicosNotificados) . ' técnico(s) notificado(s) correctamente',
                'tecnicos_notificados' => $tecnicosNotificados,
                'errores' => $errores
            ];

        } catch (\Exception $e) {
            Log::error("❌ Error en notifyTechniciansAboutIncident: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al notificar técnicos: ' . $e->getMessage(),
                'tecnicos_notificados' => []
            ];
        }
    }

    /**
     * Obtener técnicos disponibles según prioridad
     */
    private static function obtenerTecnicosDisponibles($prioridad = 'media')
    {
        // Por ahora, obtener todos los técnicos activos
        // En el futuro se puede filtrar por horario según prioridad
        $query = Reparaciones::where('activo', true);

        // Si es urgente, notificar a todos
        // Si no, se puede filtrar por horario (implementación futura)
        if ($prioridad !== 'urgente') {
            // Opcional: filtrar por horario disponible
            // Por ahora, notificamos a todos los activos
        }

        return $query->orderBy('nombre')->get();
    }

    /**
     * Preparar información de la incidencia para el mensaje
     */
    private static function prepararInformacionIncidencia(Incidencia $incidencia)
    {
        // Obtener apartamento
        $apartamento = 'Apartamento no identificado';
        if ($incidencia->apartamento) {
            $apartamento = $incidencia->apartamento->nombre ?? $incidencia->apartamento->titulo ?? 'Apartamento no identificado';
        } elseif ($incidencia->apartamento_nombre) {
            $apartamento = $incidencia->apartamento_nombre;
        }

        // Obtener edificio
        $edificio = 'Edificio no identificado';
        if ($incidencia->apartamento && $incidencia->apartamento->edificioName) {
            $edificio = $incidencia->apartamento->edificioName->nombre ?? 'Edificio no identificado';
        }

        // Preparar mensaje
        $mensaje = "🚨 NUEVA INCIDENCIA DE REPARACIÓN\n\n";
        $mensaje .= "📋 Título: {$incidencia->titulo}\n";
        $mensaje .= "📝 Descripción: {$incidencia->descripcion}\n";
        $mensaje .= "🏠 Apartamento: {$apartamento}\n";
        $mensaje .= "🏢 Edificio: {$edificio}\n";
        $mensaje .= "⚡ Prioridad: " . strtoupper($incidencia->prioridad) . "\n";
        
        if ($incidencia->empleada) {
            $mensaje .= "👩‍🔧 Reportado por: {$incidencia->empleada->name}\n";
        }
        
        $mensaje .= "📅 Fecha: " . $incidencia->created_at->format('d/m/Y H:i');

        return [
            'apartamento' => $apartamento,
            'edificio' => $edificio,
            'mensaje' => $mensaje,
            'telefono' => $incidencia->telefono_cliente ?? null
        ];
    }

    /**
     * Generar mensaje simple (sin template)
     */
    private static function generarMensajeSimple(Incidencia $incidencia, $tecnico, $infoIncidencia)
    {
        return "🚨 NUEVA INCIDENCIA DE REPARACIÓN\n\n" .
               "👨‍🔧 Técnico: {$tecnico->nombre}\n" .
               "📋 Título: {$incidencia->titulo}\n" .
               "📝 Descripción: {$incidencia->descripcion}\n" .
               "🏠 Apartamento: {$infoIncidencia['apartamento']}\n" .
               "🏢 Edificio: {$infoIncidencia['edificio']}\n" .
               "⚡ Prioridad: " . strtoupper($incidencia->prioridad) . "\n" .
               ($incidencia->empleada ? "👩‍🔧 Reportado por: {$incidencia->empleada->name}\n" : "") .
               "📅 Fecha: " . $incidencia->created_at->format('d/m/Y H:i');
    }

    /**
     * Enviar mensaje usando template de WhatsApp
     */
    private static function enviarMensajeTemplate($phone, $templateName, $parameters = [])
    {
        Log::info("📱 ENVIAR MENSAJE TEMPLATE - Iniciando para: {$phone}");
        Log::info("🔧 Template: {$templateName}");
        Log::info("📋 Parámetros: " . json_encode($parameters));

        $token = Setting::whatsappToken();

        $mensajeTemplate = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "template",
            "template" => [
                "name" => $templateName,
                "language" => [
                    "code" => "es"
                ]
            ]
        ];

        // Agregar parámetros si existen
        if (!empty($parameters)) {
            $mensajeTemplate["template"]["components"] = [
                [
                    "type" => "body",
                    "parameters" => array_values(array_map(function($value) {
                        return [
                            "type" => "text",
                            "text" => $value
                        ];
                    }, $parameters))
                ]
            ];
        }

        $urlMensajes = Setting::whatsappUrl();

        Log::info("🌐 Enviando petición a WhatsApp API...");
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post($urlMensajes, $mensajeTemplate);

        if ($response->failed()) {
            Log::error("❌ Error enviando template de WhatsApp: " . $response->body());
            return ['success' => false, 'error' => 'Error enviando template'];
        }

        $responseJson = $response->json();
        Log::info("✅ Template enviado exitosamente. Response: " . json_encode($responseJson));

        return ['success' => true, 'response' => $responseJson];
    }

    /**
     * Enviar mensaje simple (sin template)
     */
    private static function enviarMensajeSimple($phone, $texto)
    {
        Log::info("📱 ENVIAR MENSAJE SIMPLE - Iniciando para: {$phone}");

        $token = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "text",
            "text" => [
                "body" => $texto
            ]
        ];

        $urlMensajes = Setting::whatsappUrl();

        Log::info("🌐 Enviando petición a WhatsApp API...");
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post($urlMensajes, $mensajePersonalizado);

        if ($response->failed()) {
            Log::error("❌ Error enviando mensaje simple: " . $response->body());
            return ['success' => false, 'error' => 'Error enviando mensaje'];
        }

        $responseJson = $response->json();
        Log::info("✅ Mensaje simple enviado exitosamente. Response: " . json_encode($responseJson));

        return ['success' => true, 'response' => $responseJson];
    }
}

