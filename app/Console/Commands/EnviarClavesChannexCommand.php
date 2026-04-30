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
        //
        // [2026-04-30] CAMBIO IMPORTANTE: ya NO se filtra por dni_entregado.
        // Antes: si la reserva no tenia DNI subido se SALTABA y el huesped no
        // recibia nada el dia de entrada (se quedaba en el limbo). Eso paso
        // con la reserva 5525 (Gloria) hoy.
        // Ahora: SIEMPRE se envian las claves. Si ademas el DNI no esta
        // subido se envia un AVISO ADICIONAL con la URL para subirlo,
        // recordando que sin DNI esta prohibido el acceso por ley.
        // NOTA: No verificamos si ya se enviaron las claves - esto es para reenvío manual si falla algo
        $reservas = Reserva::whereDate('fecha_entrada', '=', $fechaHoyStr)
            ->where(function($query) {
                $query->where('estado_id', '!=', 4)
                      ->orWhereNull('estado_id');
            })
            ->where('origen', '!=', 'web')
            ->whereNotNull('id_channex') // Booking ID de Channex
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
                    // Cancelar la reserva vetada para liberar disponibilidad
                    try {
                        $vetoService->cancelarReservaVetada($reserva);
                    } catch (\Throwable $e) {
                        Log::error('[EnviarClavesChannex] Error cancelando reserva vetada', [
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

                // [2026-04-30] Ya NO bloqueamos el envio de claves por falta de DNI.
                // Las claves se envian SIEMPRE. Si no hay DNI se envia ademas
                // un aviso (ver bloque al final del envio exitoso).
                $sinDni = empty($reserva->dni_entregado) || $reserva->dni_entregado != true;

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

                    // [2026-04-30] Si la reserva NO tiene el DNI subido, ademas
                    // de las claves enviamos el aviso "Sube el DNI o acceso
                    // prohibido" en el idioma del cliente. Texto extraido del
                    // template Meta `dni_dia_entrada` (ya aprobado, ids 74-80).
                    // No bloqueamos las claves pero el huesped recibe la senal
                    // clara de que sin DNI no entra.
                    if ($sinDni) {
                        try {
                            $avisoDni = $this->construirAvisoDniDiaEntrada($idiomaCliente, $reserva->token);
                            $rAviso = \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                                $avisoDni,
                                $reserva->id_channex
                            );
                            if ($rAviso) {
                                MensajeAuto::updateOrCreate(
                                    ['reserva_id' => $reserva->id, 'categoria_id' => 5], // 5 = aviso DNI dia entrada
                                    ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                                );
                                $this->warn("   ⚠️  Aviso DNI enviado (reserva sin DNI)");
                                Log::info('[EnviarClavesChannex] Aviso dni_dia_entrada enviado tras claves', [
                                    'reserva_id' => $reserva->id,
                                    'id_channex' => $reserva->id_channex,
                                    'idioma' => $idiomaCliente,
                                ]);
                            } else {
                                $this->error("   ❌ Aviso DNI fallo al enviar a Channex");
                                Log::error('[EnviarClavesChannex] Aviso dni_dia_entrada fallo', [
                                    'reserva_id' => $reserva->id,
                                    'id_channex' => $reserva->id_channex,
                                ]);
                            }
                        } catch (\Throwable $e) {
                            Log::error('[EnviarClavesChannex] Excepcion enviando aviso dni_dia_entrada', [
                                'reserva_id' => $reserva->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
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

    /**
     * [2026-04-30] Construye el texto del aviso "Sube el DNI o acceso prohibido"
     * en el idioma del cliente. Texto base extraido del template WhatsApp Meta
     * 'dni_dia_entrada' (ids 74-80) ya aprobado por Meta.
     *
     * Como el envio es por inbox de Channex (texto libre, no plantilla Meta),
     * adaptamos el body con el enlace ya construido al token de la reserva.
     *
     * @param string $idioma  codigo de idioma del cliente (es, en, fr, de, it, pt_PT, ar)
     * @param string $token   token unico de la reserva (para construir el enlace)
     */
    private function construirAvisoDniDiaEntrada(string $idioma, string $token): string
    {
        $url = "https://crm.apartamentosalgeciras.com/dni-user/{$token}";
        // Normalizar idioma a codigo de 2 letras
        $key = strtolower(substr($idioma, 0, 2));

        $textos = [
            'es' => "⚠️ Información importante sobre su reserva\n\nEstimado cliente, si no sube su documento de identidad DNI o PASAPORTE a nuestra plataforma está incumpliendo la legislación española y está prohibido el acceso a nuestro alojamiento.\n\nPor favor, acceda al siguiente enlace y envíenos su documentación:\n{$url}",
            'en' => "⚠️ Important information about your reservation\n\nDear guest, if you do not upload your ID document (DNI or passport) to our platform you are not complying with Spanish legislation and access to our accommodation is forbidden.\n\nPlease use the link below to send us your documentation:\n{$url}",
            'fr' => "⚠️ Information importante concernant votre réservation\n\nCher client, si vous ne téléchargez pas votre pièce d'identité (DNI ou passeport) sur notre plateforme, vous ne respectez pas la législation espagnole et l'accès à notre hébergement est interdit.\n\nVeuillez accéder au lien suivant et nous envoyer vos documents :\n{$url}",
            'de' => "⚠️ Wichtige Informationen zu Ihrer Reservierung\n\nSehr geehrter Gast, wenn Sie Ihren Ausweis (DNI oder Reisepass) nicht auf unserer Plattform hochladen, verstoßen Sie gegen die spanische Gesetzgebung und der Zugang zu unserer Unterkunft ist untersagt.\n\nBitte nutzen Sie den folgenden Link, um uns Ihre Dokumente zu schicken:\n{$url}",
            'it' => "⚠️ Informazione importante sulla sua prenotazione\n\nGentile ospite, se non carica il suo documento d'identità (DNI o passaporto) sulla nostra piattaforma sta violando la legislazione spagnola e l'accesso al nostro alloggio è vietato.\n\nLa preghiamo di accedere al seguente link e inviarci la sua documentazione:\n{$url}",
            'pt' => "⚠️ Informação importante sobre a sua reserva\n\nPrezado cliente, se não enviar o seu documento de identidade (DNI ou passaporte) para a nossa plataforma, está a incumprir a legislação espanhola e o acesso ao nosso alojamento está proibido.\n\nPor favor, aceda ao seguinte link e envie-nos a sua documentação:\n{$url}",
            'ar' => "⚠️ معلومات مهمة بشأن حجزك\n\nعزيزي الضيف، إذا لم تقم بتحميل وثيقة هويتك (DNI أو جواز السفر) على منصتنا، فأنت تخالف القانون الإسباني ويُمنع الوصول إلى مكان إقامتنا.\n\nيرجى الدخول إلى الرابط التالي وإرسال وثائقك إلينا:\n{$url}",
        ];

        return $textos[$key] ?? $textos['en'];
    }
}
