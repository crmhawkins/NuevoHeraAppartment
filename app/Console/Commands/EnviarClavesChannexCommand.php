<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Models\MensajeAuto;
use App\Models\Apartamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\ClienteService;
use App\Services\MetodoEntradaService;

class EnviarClavesChannexCommand extends Command
{
    protected $signature = 'ari:enviar-claves-channex';
    protected $description = 'Envía mensajes de claves por Channex para reservas de hoy (Booking/Airbnb). Ejecutar manualmente cuando sea necesario.';

    public function handle()
    {
        $this->info("🔑 Buscando reservas de hoy para enviar claves por Channex...");
        $this->newLine();

        // Obtener la fecha de hoy
        $fechaHoyStr = Carbon::now()->format('Y-m-d');

        // Buscar reservas de hoy que:
        // 1. Tengan fecha_entrada = hoy
        // 2. NO estén canceladas (estado_id != 4)
        // 3. NO sean de la web (origen != 'web')
        // 4. Tengan id_channex (booking ID de Channex)
        // 5. Tengan mensaje de bienvenida enviado (categoria_id = 4)
        // 6. Tengan DNI subido (dni_entregado = true)
        // NOTA: No verificamos si ya se enviaron las claves - esto es para reenvío manual si falla algo
        $reservas = Reserva::whereDate('fecha_entrada', '=', $fechaHoyStr)
            ->where(function($query) {
                $query->where('estado_id', '!=', 4)
                      ->orWhereNull('estado_id');
            })
            ->where('origen', '!=', 'web')
            ->whereNotNull('id_channex') // Booking ID de Channex
            ->where('dni_entregado', true) // DNI debe estar subido
            ->whereIn('id', function ($query) {
                $query->select('reserva_id')
                    ->from('mensajes_auto')
                    ->where('categoria_id', 4); // Tiene mensaje de bienvenida
            })
            ->with(['cliente', 'apartamento.edificioName'])
            ->get();

        if ($reservas->isEmpty()) {
            $this->info("✅ No se encontraron reservas para enviar claves.");
            return 0;
        }

        $this->info("📋 Encontradas {$reservas->count()} reserva(s) para procesar.");
        $this->newLine();

        $clienteService = app(ClienteService::class);
        $metodoEntradaService = app(MetodoEntradaService::class);
        $vetoService = app(\App\Services\ClienteVetadoService::class);
        $enviadas = 0;
        $errores = 0;
        $omitidas = 0;
        $vetadas = 0;

        foreach ($reservas as $reserva) {
            try {
                // [VETO 2026-04-19] Si la reserva esta vetada, enviar mensaje
                // de derecho de admision en vez de las claves.
                $vetoService->detectarYMarcarReserva($reserva);
                if ($reserva->vetada) {
                    $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad ?? 'ES');
                    $mensajeVeto = $vetoService->mensajeDerechoAdmision($idiomaCliente);
                    $this->warn("🚫 Reserva #{$reserva->id}: CLIENTE VETADO -> enviando derecho de admision");
                    try {
                        \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                            $mensajeVeto,
                            $reserva->id_channex
                        );
                        Log::warning('[EnviarClavesChannex] Mensaje derecho admision enviado (veto)', [
                            'reserva_id' => $reserva->id,
                            'veto_id' => $reserva->veto_id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('[EnviarClavesChannex] Error enviando derecho admision', [
                            'reserva_id' => $reserva->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    $vetadas++;
                    continue;
                }

                // Verificar que tenga mensaje de bienvenida
                $mensajeBienvenida = MensajeAuto::where('reserva_id', $reserva->id)
                    ->where('categoria_id', 4)
                    ->first();

                if (!$mensajeBienvenida) {
                    $this->warn("⚠️  Reserva #{$reserva->id}: No tiene mensaje de bienvenida. Saltando...");
                    $omitidas++;
                    continue;
                }

                // Verificar que el DNI esté subido
                if (empty($reserva->dni_entregado) || $reserva->dni_entregado != true) {
                    $this->warn("⚠️  Reserva #{$reserva->id}: DNI no subido. Saltando...");
                    $omitidas++;
                    continue;
                }

                $apartamentoReservado = $reserva->apartamento;
                if (!$apartamentoReservado) {
                    $this->warn("⚠️  Reserva #{$reserva->id}: Apartamento no encontrado. Saltando...");
                    $omitidas++;
                    continue;
                }

                // Obtener código de idioma
                $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad ?? 'ES');

                $metodoEntrada = $metodoEntradaService->resolverParaReserva($reserva);

                // Preparar datos para el mensaje de claves
                $edificioLegacy = $apartamentoReservado->edificio ?? null; // legacy (puede no existir)
                $edificioId = $apartamentoReservado->edificio_id ?? null;
                $esEdificio1 = ($edificioId === 1) || ($edificioLegacy === 1);

                $datosClaves = [
                    'nombre' => $reserva->cliente->nombre ?? $reserva->cliente->alias,
                    'apartamento' => $reserva->apartamento->titulo,
                    'metodo_entrada' => $metodoEntrada,
                    'claveEntrada' => $reserva->apartamento->edificioName->clave ?? '',
                    'clavePiso' => $reserva->apartamento->claves ?? '',
                    'url' => $esEdificio1
                        ? 'https://goo.gl/maps/qb7AxP1JAxx5yg3N9'
                        : 'https://maps.app.goo.gl/t81tgLXnNYxKFGW4A',
                ];

                $this->info("🔄 Procesando reserva #{$reserva->id} ({$reserva->origen})");
                $this->line("   Cliente: {$datosClaves['nombre']}");
                $this->line("   Apartamento: {$datosClaves['apartamento']}");
                $this->line("   Booking ID (Channex): {$reserva->id_channex}");
                $this->line("   Código Reserva: {$reserva->codigo_reserva}");

                // [2026-04-19] Flujo digital real con PIN unico:
                //  - Si la reserva ya tiene codigo_acceso programado en la cerradura,
                //    se lo enviamos con su ventana horaria.
                //  - Si aun no (cron de programacion todavia no corrio o fallo),
                //    intentamos programar sobre la marcha con AccessCodeService.
                //  - Fallback: mensaje "estamos preparando tu codigo" solo si NADA
                //    se pudo programar.
                if ($metodoEntrada === MetodoEntradaService::METODO_DIGITAL) {
                    if (empty($reserva->codigo_acceso) || empty($reserva->codigo_enviado_cerradura)) {
                        try {
                            app(\App\Services\AccessCodeService::class)->generarYProgramar($reserva);
                            $reserva->refresh();
                        } catch (\Throwable $e) {
                            Log::error('[EnviarClavesChannex] Error generando PIN', [
                                'reserva_id' => $reserva->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $pinReal = $reserva->codigo_acceso ?: null;
                    if ($pinReal && $reserva->codigo_enviado_cerradura) {
                        $fechaEntradaFmt = \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y');
                        $fechaSalidaFmt  = \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y');
                        $enlaceLimpio = $esEdificio1 ? 'goo.gl/maps/qb7AxP1JAxx5yg3N9' : 'maps.app.goo.gl/t81tgLXnNYxKFGW4A';

                        $mensajeChat = match (substr((string) $idiomaCliente, 0, 2)) {
                            'es' => "🔐 Acceso al portal\n\nTu código de acceso único: *{$pinReal}* (pulsa # después)\n\nVálido del {$fechaEntradaFmt} a las 15:00h hasta el {$fechaSalidaFmt} a las 11:00h.\n\nDirección: {$enlaceLimpio}\n\nCualquier duda, estamos a tu disposición.",
                            'fr' => "🔐 Accès au portail\n\nVotre code d'accès unique : *{$pinReal}* (appuyez sur # après)\n\nValable du {$fechaEntradaFmt} à 15:00h jusqu'au {$fechaSalidaFmt} à 11:00h.\n\nAdresse : {$enlaceLimpio}",
                            'de' => "🔐 Zugang zum Portal\n\nIhr einmaliger Zugangscode: *{$pinReal}* (anschließend # drücken)\n\nGültig vom {$fechaEntradaFmt} ab 15:00 Uhr bis {$fechaSalidaFmt} um 11:00 Uhr.\n\nAdresse: {$enlaceLimpio}",
                            default => "🔐 Access to the portal\n\nYour unique access code: *{$pinReal}* (press # after)\n\nValid from {$fechaEntradaFmt} at 15:00 until {$fechaSalidaFmt} at 11:00.\n\nAddress: {$enlaceLimpio}",
                        };
                    } else {
                        // Aun no hay PIN listo — placeholder temporal
                        $mensajeChat = match (substr((string) $idiomaCliente, 0, 2)) {
                            'es' => "Tu acceso será mediante cerradura digital. Estamos preparando tu código — te llegará antes de tu llegada. Si no lo recibes 6h antes, escríbenos.",
                            'fr' => "Votre accès se fera via serrure digitale. Nous préparons votre code — il arrivera avant votre arrivée.",
                            'de' => "Ihr Zugang erfolgt über ein digitales Schloss. Ihr Code wird vor der Ankunft zugestellt.",
                            default => "Your access will be via a digital lock. Your code is being prepared — it will arrive before your check-in.",
                        };
                        Log::warning('[EnviarClavesChannex] Flujo digital sin PIN listo', [
                            'reserva_id' => $reserva->id,
                            'codigo_acceso' => $reserva->codigo_acceso,
                            'enviado_cerradura' => $reserva->codigo_enviado_cerradura,
                        ]);
                    }
                } else {
                    $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('claves', $datosClaves, $idiomaCliente);
                }

                Log::info('Enviando mensaje de claves por Channex', [
                    'reserva_id' => $reserva->id,
                    'id_channex' => $reserva->id_channex,
                    'codigo_reserva' => $reserva->codigo_reserva,
                    'origen' => $reserva->origen,
                    'idioma' => $idiomaCliente,
                    'datos_claves' => $datosClaves
                ]);

                // Enviar al chat de Channex usando el id_channex (booking ID de Channex)
                $resultado = \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                    $mensajeChat,
                    $reserva->id_channex  // ✅ Usar id_channex como booking ID de Channex
                );

                if ($resultado) {
                    // Crear o actualizar registro de mensaje enviado (por si ya existía)
                    MensajeAuto::updateOrCreate(
                        [
                            'reserva_id' => $reserva->id,
                            'categoria_id' => 3, // Mensaje de claves
                        ],
                        [
                            'cliente_id' => $reserva->cliente_id,
                            'fecha_envio' => Carbon::now()
                        ]
                    );

                    $this->info("   ✅ Mensaje de claves enviado correctamente a Channex");
                    $enviadas++;

                    Log::info('Mensaje de claves enviado exitosamente por Channex', [
                        'reserva_id' => $reserva->id,
                        'id_channex' => $reserva->id_channex,
                        'codigo_reserva' => $reserva->codigo_reserva
                    ]);
                } else {
                    $this->error("   ❌ Error al enviar mensaje a Channex");
                    $errores++;

                    Log::error('Error al enviar mensaje de claves por Channex', [
                        'reserva_id' => $reserva->id,
                        'id_channex' => $reserva->id_channex,
                        'codigo_reserva' => $reserva->codigo_reserva,
                        'resultado' => $resultado
                    ]);
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Excepción: " . $e->getMessage());
                $errores++;

                Log::error('Excepción al enviar claves por Channex', [
                    'reserva_id' => $reserva->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        // Resumen final
        $this->newLine();
        $this->info("📊 Resumen:");
        $this->info("   ✅ Enviadas: {$enviadas}");
        if ($omitidas > 0) {
            $this->warn("   ⏭️  Omitidas: {$omitidas}");
        }
        if ($vetadas > 0) {
            $this->warn("   🚫 Vetadas (derecho de admision): {$vetadas}");
        }
        if ($errores > 0) {
            $this->error("   ❌ Errores: {$errores}");
        }
        $this->info("   📋 Total procesadas: {$reservas->count()}");
        $this->newLine();
        $this->info("✅ Proceso de envío de claves por Channex finalizado.");

        return 0;
    }
}
