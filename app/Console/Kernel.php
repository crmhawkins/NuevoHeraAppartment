<?php

namespace App\Console;

use App\Mail\DespedidaEmail;
use App\Mail\EnvioClavesEmail;
use App\Models\Apartamento;
use App\Models\ChatGpt;
use App\Models\Invoices;
use App\Models\InvoicesReferenceAutoincrement;
use App\Models\MensajeAuto;
use App\Models\Reserva;
use App\Models\Setting;
use App\Models\WhatsappMensaje;
use Carbon\Carbon;
use App\Services\ClienteService;
use App\Services\MetodoEntradaService;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Spatie\UrlSigner\UrlSigner;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // \App\Console\Commands\CheckComprobacion::class,
        // \App\Console\Commands\FetchEmails::class,
        // \App\Console\Commands\CategorizeEmails::class,
        \App\Console\Commands\CleanExpiredSessions::class,
        \App\Console\Commands\CleanOldLogs::class,
        \App\Console\Commands\GenerateLogReport::class,
        \App\Console\Commands\CleanOldNotifications::class,
        \App\Console\Commands\FixAmenityMovements::class,
        \App\Console\Commands\FixAllAmenitiesMovements::class,
        \App\Console\Commands\CheckOverlappingReservations::class,
        \App\Console\Commands\EnviarClavesChannexCommand::class,
        \App\Console\Commands\EnviarMIRPendientes::class,
        \App\Console\Commands\ProgramarCerradurasProximas::class,
        \App\Console\Commands\VerificarCheckinHoy::class,
        \App\Console\Commands\ImportarMovimientosBanco::class,
        \App\Console\Commands\DetectOrphanedReservations::class,
    ];


    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        //$schedule->command('reservas:sincronizar')->hourly(); // o daily(), hourly(), etc.
        $schedule->command('reservas:check-overlaps')->everyMinute()->withoutOverlapping();
        $schedule->command('reservas:detectar-huerfanas')->dailyAt('06:00');

        // Generar token DNI en reservas activas que no tienen token (enlace DNI roto)
        $schedule->command('reservas:generar-token-dni')->everyMinute()->withoutOverlapping();

        $schedule->command('vacacioner:add')->monthlyOn(1, '08:00');

        // Enviar claves por Channex todos los días a las 14:00
        $schedule->command('ari:enviar-claves-channex')->dailyAt('14:00');

        // Generar turnos de trabajo todos los días a las 7:00 AM
        $schedule->command('turnos:generar')->dailyAt('07:00');

        // Ejecuta el comando cada minuto. Usa AIGatewayService con fallback
        // automatico a Hawkins AI cuando OpenAI falla (quota, red, 5xx), asi
        // que no se pierde la clasificacion aunque OpenAI este caido.
        $schedule->command('emails:categorize')->everyMinute()->withoutOverlapping();

        // Programa el comando para que se ejecute cada 5 minutos
        $schedule->command('emails:fetch')->everyFiveMinutes()->withoutOverlapping();

        // Procesa facturas subidas a storage/app/facturas/pendientes/.
        // Llama a la IA para extraer importe+fecha, busca el gasto que matchea
        // y lo asocia automaticamente. Cada 5 minutos, sin solapamiento.
        $schedule->command('facturas:procesar-pendientes')->everyFiveMinutes()->withoutOverlapping();

        // Tarea programada de Limpieza de numero de telefono del cliente.
        $schedule->command('clean:phonenumbers')->twiceDaily(1, 13);

        // Tarea programada de Nacionalidad del cliente ejecutada con éxito.
        $schedule->call(function (ClienteService $clienteService) {
            // Obtener la fecha de hoy
            $hoy = Carbon::now();
            // Obtenemos la reservas que sean igual o superior a la fecha de entrada de hoy y no tengan el DNI Enrtegado.
            $reservasEntrada = Reserva::where('dni_entregado', null)
            ->where('estado_id', 1)
            // ->where('fecha_entrada', '>=', $hoy->toDateString())
            ->get();

            foreach($reservasEntrada as $reserva){
                $resultado = $clienteService->getIdiomaClienteID($reserva->cliente_id);
            }

            Log::info("Tarea programada de Nacionalidad del cliente ejecutada con éxito.");
        })->everyMinute();

        // Aviso recordatorio DNI - Se ejecuta a partir de las 16:00 del día de entrada
        // Envía recordatorio al huésped por WhatsApp y aviso al equipo de gestión
        $schedule->call(function (ClienteService $clienteService) {
            // Hoy
            $hoy = Carbon::now();

            // Solo ejecutar después de las 16:00 de la tarde (hora límite para recordar DNI)
            if ($hoy->hour >= 16) {
                // Obtener reservas que tengan la fecha de entrada igual al día de hoy y que no tengan el DNI entregado
                $reservasEntrada = Reserva::where('dni_entregado', null)
                                    ->where('estado_id', 1)
                                    ->whereDate('fecha_entrada', '=', $hoy->toDateString())
                                    ->get();

                foreach ($reservasEntrada as $reserva) {
                    // Comprobamos si ya existe un mensaje automático para esta reserva
                    $mensaje = MensajeAuto::where('reserva_id', $reserva->id)
                                        ->where('categoria_id', 8)
                                        ->first();
                    if ($mensaje) {
                        continue; // Ya enviado
                    }

                        // Cliente
                        $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);

                        $cliente = $reserva->cliente;
                        // URL de DNI
                        $url = 'https://crm.apartamentosalgeciras.com/dni-user/'.$reserva->token;

                        // Obtener teléfono del cliente con fallback
                        $telefono = $cliente->telefono_movil ?? $cliente->telefono ?? null;
                        $phoneCliente = !empty($telefono) ? $this->limpiarNumeroTelefono($telefono) : null;

                        // 1. Enviar recordatorio al HUÉSPED por WhatsApp (si tiene token para el enlace)
                        if (!empty($reserva->token)) {
                            if (!empty($phoneCliente)) {
                                $this->mensajesAutomaticosBoton('dni', $reserva->token, $phoneCliente, $idiomaCliente);
                                Log::info('Recordatorio DNI enviado al huésped', [
                                    'reserva_id' => $reserva->id,
                                    'telefono' => $phoneCliente,
                                ]);
                            } else {
                                Log::warning('Recordatorio DNI: Reserva sin teléfono de contacto, se omite WhatsApp al huésped', ['reserva_id' => $reserva->id]);
                            }
                        }

                        // 2. Enviar aviso al equipo de gestión
                        $telefonosEnvios = [
                            // 'Ivan' => '34605621704',
                            'Elena' => '34664368232',
                            'David' => '34622440984'
                        ];

                        // Obtenemos el telefono del cliente limpio (para incluir en el aviso al equipo)
                        $telefonoCliente = !empty($telefono) ? $this->limpiarNumeroTelefono($telefono) : '';

                        foreach ($telefonosEnvios as $phone) {
                            $resultado = $this->noEntregadoDNIMensaje($cliente->alias, $reserva->codigo_reserva, $reserva->origen, $phone, $telefonoCliente, $url);
                        }

                        // Crear registro atómicamente para evitar duplicados
                        MensajeAuto::firstOrCreate(
                            ['reserva_id' => $reserva->id, 'categoria_id' => 8],
                            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                        );
                }

                Log::info("Tarea programada de recordatorio DNI (16:00) ejecutada con éxito.");
            }
        })->everyMinute();

        // Tarea comprobacion del estado del PC
        //$schedule->command('check:comprobacion')->everyFifteenMinutes();

        // Limpiar logs antiguos cada día a las 2:00 AM
        $schedule->command('logs:clean --days=30')->dailyAt('02:00');

        // Limpiar notificaciones antiguas cada día a las 3:00 AM
        $schedule->command('notifications:clean --days=30')->dailyAt('03:00');

        // Programar códigos de acceso en cerraduras TTLock para reservas que ya están dentro del rango de 150 días
        $schedule->command('cerraduras:programar-proximas')->daily()->at('06:00')->withoutOverlapping();

        // Enviar a MIR las reservas pendientes (red de seguridad)
        // Se ejecuta 2 veces al día: 10:00 y 22:00
        $schedule->command('mir:enviar-pendientes')->twiceDaily(10, 22)->withoutOverlapping();

        // Verificar que las reservas de hoy tienen todo preparado para el check-in
        $schedule->command('checkin:verificar-hoy')->dailyAt('08:00')->withoutOverlapping();

        // Verificar fichajes de limpiadoras (a las 10:00 tras inicio de turnos, y a las 17:30 tras fin)
        $schedule->command('fichajes:verificar-limpiadoras')->dailyAt('10:00');
        $schedule->command('fichajes:verificar-limpiadoras')->dailyAt('17:30');

        // Importar movimientos bancarios de Bankinter automaticamente
        // DESHABILITADO: el scraper ahora corre en un PC Windows externo con IP residencial.
        // Bankinter bloquea IPs de datacenter. La tarea programada esta en el PC via schtasks.
        // $schedule->command('banco:importar-movimientos')->dailyAt('06:00')->withoutOverlapping();

        // Aplicar descuento del 20% a apartamentos libres (SOLO lunes a jueves a las 10:00)
        // NO se ejecuta viernes, sábado ni domingo
        // Se ejecuta con --confirmar para evitar interacción en modo cron
        // $schedule->command('aplicar:descuento-apartamentos-libres --confirmar')
        //     ->at('10:00')
        //     ->when(function () {
        //         // Solo ejecutar de lunes a jueves
        //         // En Carbon: 0=Domingo, 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado
        //         // Excluye: 0=Domingo, 5=Viernes, 6=Sábado
        //         $dayOfWeek = Carbon::now()->dayOfWeek;
        //         return in_array($dayOfWeek, [1, 2, 3, 4]);
        //     });

        // Tarea de Generacion de Factura
        $schedule->call(function () {

            // Obtener la fecha de hoy (sin la hora)
            $hoy = Carbon::now()->subDay(1); // La fecha actual
            $juevesPasado = Carbon::now()->subDays(8); // Restar 5 días para obtener el jueves de la semana pasada


            // Obtener reservas desde el jueves pasado hasta hoy (inclusive)
            $reservas = Reserva::whereDate('fecha_salida', '>=', $juevesPasado)
            ->whereDate('fecha_salida', '<=', $hoy)
            // ->whereNotIn('estado_id', [5, 6]) // Filtrar estado_id diferente de 5 o 6
            ->whereNotIn('estado_id', [4]) // Filtrar estado_id diferente de 5 o 6
            ->get();


            foreach( $reservas as $reserva){
                $invoice = Invoices::where('reserva_id', $reserva->id)->first();

                if ($invoice == null) {
                    // Validar que la reserva no esté marcada para no facturar
                    if ($reserva->no_facturar) {
                        Log::info("Reserva {$reserva->id} no facturada (marcada como no_facturar)");
                        continue; // Saltar esta reserva
                    }

                    // Validar que el precio sea mayor o igual a 10 euros
                    if ($reserva->precio < 10) {
                        Log::info("Reserva {$reserva->id} con precio {$reserva->precio}€ no facturada (menor a 10€)");
                        continue; // Saltar esta reserva
                    }

                    // Obtener datos de facturación del cliente
                    $cliente = $reserva->cliente;
                    if (!$cliente) {
                        Log::warning("Cliente no encontrado para reserva {$reserva->id}");
                        continue;
                    }

                    // Verificar que el cliente tenga datos de facturación completos
                    if (!$cliente->tieneDatosFacturacionCompletos()) {
                        Log::warning("Cliente {$cliente->id} no tiene datos de facturación completos para reserva {$reserva->id}");
                        continue;
                    }

                     // Cálculo correcto de la base imponible y el IVA
                    $total = $reserva->precio;
                    $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
                    $iva = $total - $base; // Calcular el IVA

                    // Generar descripción básica (los datos de facturación se obtienen dinámicamente en el PDF)
                    $descripcion = "Estancia en apartamento: " . $reserva->apartamento->titulo;
                    $descripcion .= "\nFecha de entrada: " . $reserva->fecha_entrada;
                    $descripcion .= "\nFecha de salida: " . $reserva->fecha_salida;
                    $descripcion .= "\nNúmero de personas: " . $reserva->numero_personas;

                    $data = [
                        'budget_id' => null,
                        'cliente_id' => $reserva->cliente_id,
                        'reserva_id' => $reserva->id,
                        'invoice_status_id' => 1,
                        'concepto' => 'Estancia en apartamento: '. $reserva->apartamento->titulo,
                        'description' => $descripcion,
                        'fecha' => $reserva->fecha_salida,
                        'fecha_cobro' => null,
                        'base' => round($base, 2), // Redondear la base a 2 decimales
                        'iva' => round($iva, 2), // Redondear el IVA a 2 decimales
                        'descuento' => null,
                        'total' => round($total, 2), // Asegurarse de que el total también esté redondeado
                        'created_at' => $reserva->fecha_salida,
                        'updated_at' => $reserva->fecha_salida,
                    ];

                    Log::info("Generando factura para reserva {$reserva->id} con datos de facturación del cliente {$cliente->id}");
                    $crearFactura = Invoices::create($data);

                    $referencia = $this->generateBudgetReference($crearFactura);
                    $crearFactura->reference = $referencia['reference'];
                    $crearFactura->reference_autoincrement_id = $referencia['id'];
                    $crearFactura->invoice_status_id = 3;
                    $crearFactura->save();
                    $reserva->estado_id = 5;
                    $reserva->save();

                    Log::info("Factura {$crearFactura->id} generada exitosamente para reserva {$reserva->id}");
                }

            }

        })->everyMinute();

        // Liberar holds de reserva web expirados en Channex
        $schedule->command('ari:liberar-holds-expirados')->everyMinute()->withoutOverlapping();

        // Cancelar reservas web con pago pendiente tras X minutos (config: web_reservas_hold_minutes, por defecto 10)
        $schedule->command('ari:cancelar-reservas-web-pago-pendiente')->everyMinute();

        // Tarea para el envio por primera vez de DNI
        $schedule->call(function (ClienteService $clienteService) {
            // Obtener la fecha de hoy
            $hoy = Carbon::now();
            // Obtener la fecha de dos días después
            $dosDiasDespues = Carbon::now()->addDays(2)->format('Y-m-d');
            $hoyFormateado = Carbon::now()->format('Y-m-d');

            // Modificar la consulta para obtener reservas desde hoy hasta dentro de dos días
            $reservasEntrada = Reserva::where('dni_entregado', null)
            ->where('estado_id', 1)
            ->whereDate('fecha_entrada', '>=', now())
            ->get();


            /*  MENSAJES TEMPLATE:
                    - dni
                    - bienvenido
                    - consulta
                    - ocio
                    - despedida

                IDIOMAS:
                    - es
                    - en
                    - de
                    - fr
                    - it
                    - ar
                    - pt_PT
            */
            // Validamos si hay reservas pendiente del DNI
            if(count($reservasEntrada) != 0){
                // Recorremos las reservas
                foreach($reservasEntrada as $reserva){

                    // Obtenemos el mensaje del DNI si existe
                    $mensajeDNI = MensajeAuto::where('reserva_id', $reserva->id)->where('categoria_id', 1)->first();
                    // Validamos si existe mensaje de DNI enviado
                    if ($mensajeDNI != null) {
                        continue; // Ya enviado
                    }

                        $token = bin2hex(random_bytes(16)); // Genera un token de 32 caracteres
                        $reserva->token = $token;
                        $reserva->save();
                        Storage::disk('local')->put('reserva.txt', $reserva );

                        $mensaje = 'https://crm.apartamentosalgeciras.com/dni-user/'.$token;

                        // Obtener teléfono con fallback
                        $telefonoRaw = $reserva->cliente->telefono_movil ?? $reserva->cliente->telefono ?? null;
                        $phoneCliente = !empty($telefonoRaw) ? $this->limpiarNumeroTelefono($telefonoRaw) : null;

                        $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);

                        if (!empty($phoneCliente)) {
                            $enviarMensaje = $this->mensajesAutomaticosBoton('dni', $token , $phoneCliente, $idiomaCliente );
                            Storage::disk('local')->put('enviaMensaje'.$reserva->cliente_id.'.txt', $enviarMensaje );
                        } else {
                            Log::warning('Primer envío DNI: Reserva sin teléfono de contacto, se omite WhatsApp', ['reserva_id' => $reserva->id]);
                        }

                        // Crear registro atómicamente para evitar duplicados
                        MensajeAuto::firstOrCreate(
                            ['reserva_id' => $reserva->id, 'categoria_id' => 1],
                            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                        );

                        // Usar email secundario solo si es válido, sino usar el principal
                        $emailDestino = (!empty($reserva->cliente->email_secundario) && filter_var($reserva->cliente->email_secundario, FILTER_VALIDATE_EMAIL))
                            ? $reserva->cliente->email_secundario
                            : $reserva->cliente->email;

                        $mensajeEmail = $this->dniEmail($idiomaCliente, $token);
                        $enviarEmail = $this->enviarEmail($emailDestino, 'emails.envioClavesEmail', $mensajeEmail, 'Hawkins Suite - DNI', $token);

                                                // Si la reserva NO es de la web, enviar también al chat de Channex
                        if ($reserva->origen !== 'web' && !empty($reserva->id_channex)) {
                            // Crear mensaje específico para el chat
                            $datosDNI = [
                                'token' => $token
                            ];

                            $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('dni', $datosDNI, $idiomaCliente);

                            // Enviar al chat de Channex usando el bookingId
                            \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                                $mensajeChat,
                                $reserva->id_channex
                            );
                        }

                }

            }
            Log::info("Tarea programada de Primer envio de DNI ejecutada con éxito.");
        })->everyMinute();

        // Ejecutar el comando cada hora
       // $schedule->command('ari:fullsync')->hourly();
        //$schedule->command('ari:liberar-canceladas')->everyFiveMinutes();

        // Tarea par enviar los mensajes automatizados cuando se ha entregado el DNI
        $schedule->call(function (ClienteService $clienteService) {
            // Obtener la fecha de hoy
            $hoy = Carbon::now();

            // Reservas
            $reservas = Reserva::whereDate('fecha_entrada', '=', date('Y-m-d'))
            ->whereNotIn('estado_id', [4, 7, 8, 9, 10])
            ->get();
            /* ->where('dni_entregado', '!=', null) */

            foreach($reservas as $reserva){
                // 🔄 Refrescar la reserva para obtener datos actualizados (especialmente dni_entregado)
                $reserva->refresh();

                // Apartamento
                $apartamentoReservado = Apartamento::find($reserva->apartamento_id);

                // Fecha de Hoy
                $FechaHoy = new \DateTime();

                // Formatea la fecha actual a una cadena 'Y-m-d'
                $fechaHoyStr = $FechaHoy->format('Y-m-d');

                // Horas objetivo para lanzar mensajes
                // $horaObjetivoBienvenida = new \DateTime($fechaHoyStr . ' 08:48:00');
                $horaObjetivoBienvenida = new \DateTime($fechaHoyStr . ' 12:00:00');
                $horaObjetivoCodigo = new \DateTime($fechaHoyStr . ' 14:00:00');
                $horaObjetivoConsulta = new \DateTime($fechaHoyStr . ' 16:00:00');
                $horaObjetivoOcio = new \DateTime($fechaHoyStr . ' 18:00:00');

                // Diferencias horarias para las horas objetivos
                $diferenciasHoraBienvenida = $hoy->diff($horaObjetivoBienvenida)->format('%R%H%I');
                $diferenciasHoraCodigos = $hoy->diff($horaObjetivoCodigo)->format('%R%H%I');
                $diferenciasHoraConsulta = $hoy->diff($horaObjetivoConsulta)->format('%R%H%I');
                $diferenciasHoraOcio = $hoy->diff($horaObjetivoOcio)->format('%R%H%I');

                // Comprobacion de los mensajes enviados automaticamente
                $mensajeBienvenida = MensajeAuto::where('reserva_id', $reserva->id)->where('categoria_id', 4)->first();
                $mensajeClaves = MensajeAuto::where('reserva_id', $reserva->id)->where('categoria_id', 3)->first();
                $mensajeConsulta = MensajeAuto::where('reserva_id', $reserva->id)->where('categoria_id', 5)->first();
                $mensajeOcio = MensajeAuto::where('reserva_id', $reserva->id)->where('categoria_id', 6)->first();
                // Obtener teléfono con fallback
                $telefonoRaw = $reserva->cliente->telefono_movil ?? $reserva->cliente->telefono ?? null;
                $phoneCliente = !empty($telefonoRaw) ? $this->limpiarNumeroTelefono($telefonoRaw) : null;

                if (empty($phoneCliente)) {
                    Log::warning('Mensajes automáticos: Reserva sin teléfono de contacto', ['reserva_id' => $reserva->id, 'cliente_id' => $reserva->cliente_id]);
                }

                // MENSAJE DE BIEVENIDA
                if ($diferenciasHoraBienvenida <= 0 && $mensajeBienvenida == null) {

                    // Asegurar que la reserva tenga token para el enlace del botón
                    if (empty($reserva->token)) {
                        $token = bin2hex(random_bytes(16)); // Genera un token de 32 caracteres
                        $reserva->token = $token;
                        $reserva->save();
                        Log::info('🔑 Token generado para reserva en mensaje de bienvenida', [
                            'reserva_id' => $reserva->id,
                            'token' => $token
                        ]);
                    }

                    // Obtenemos codigo de idioma
                    $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);
                    // Enviamos el mensaje con el token de la reserva para el botón (solo si hay teléfono)
                    if (!empty($phoneCliente)) {
                        $data = $this->bienvenidoMensaje($reserva->cliente->nombre, $phoneCliente, $idiomaCliente, $reserva->token);
                        Storage::disk('local')->put('Mensaje_bienvenida'.$reserva->cliente_id.'.txt', $data );
                    } else {
                        Log::warning('Bienvenida: Se omite WhatsApp por falta de teléfono', ['reserva_id' => $reserva->id]);
                    }

                        // Crear registro atómicamente para evitar duplicados
                        $mensajeBienvenida = MensajeAuto::firstOrCreate(
                            ['reserva_id' => $reserva->id, 'categoria_id' => 4],
                            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                        );

                        // Si la reserva NO es de la web, enviar también al chat de Channex
                        if ($reserva->origen !== 'web' && !empty($reserva->id_channex)) {
                            try {
                                $datosBienvenida = [
                                    'nombre' => $reserva->cliente->nombre ?? $reserva->cliente->alias
                                ];

                                $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('bienvenida', $datosBienvenida, $idiomaCliente);

                                $resultado = \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                                    $mensajeChat,
                                    $reserva->id_channex
                                );

                                Log::info('Mensaje de bienvenida enviado a Channex:', ['resultado' => $resultado, 'booking_id' => $reserva->id_channex]);
                            } catch (\Exception $e) {
                                Log::error('Error al enviar mensaje de bienvenida al chat:', [
                                    'error' => $e->getMessage(),
                                    'reserva_id' => $reserva->id,
                                    'booking_id' => $reserva->codigo_reserva
                                ]);
                            }
                        }

                }

                // MENSAJE CLAVES DEL APARTAMENTO
                if ($diferenciasHoraCodigos <= 0 && $mensajeBienvenida != null && $mensajeClaves == null) {
                    $tiempoDesdeBienvenida = $mensajeBienvenida->created_at->diffInMinutes(Carbon::now());
                    if ($tiempoDesdeBienvenida >= 1) {
                        Log::info('🔑 PROCESANDO ENVÍO DE CLAVES AUTOMÁTICO', [
                            'reserva_id' => $reserva->id,
                            'cliente_id' => $reserva->cliente_id,
                            'telefono_cliente' => $phoneCliente,
                            'tiempo_desde_bienvenida' => $tiempoDesdeBienvenida
                        ]);

                        // Verificar que el DNI esté subido antes de enviar las claves
                        if (empty($reserva->dni_entregado) || $reserva->dni_entregado != true) {
                            Log::warning('⚠️ No se pueden enviar claves: el DNI no ha sido subido', [
                                'reserva_id' => $reserva->id,
                                'dni_entregado' => $reserva->dni_entregado,
                                'dni_entregado_tipo' => gettype($reserva->dni_entregado)
                            ]);
                            continue; // Saltar esta reserva y continuar con la siguiente
                        }

                        // Verificar que el teléfono no esté vacío
                        if (empty($phoneCliente)) {
                            Log::error('❌ No se pueden enviar claves: el teléfono del cliente está vacío', [
                                'reserva_id' => $reserva->id,
                                'cliente_id' => $reserva->cliente_id,
                                'telefono_original' => $reserva->cliente->telefono ?? 'null'
                            ]);
                            continue;
                        }

                        // Obtenemos el codigo de entrada del apartamento
                        //$code = $this->codigoApartamento($reserva->apartamento_id);
                        // Obtenemos codigo de idioma
                        $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);
                        // Enviamos el mensaje
                        $edificioLegacy = $apartamentoReservado->edificio ?? null; // legacy (puede no existir)
                        $edificioId = $apartamentoReservado->edificio_id ?? null;
                        $esEdificio1 = ($edificioId === 1) || ($edificioLegacy === 1);
                        $enlace = $esEdificio1 ? 'https://goo.gl/maps/qb7AxP1JAxx5yg3N9' : 'https://maps.app.goo.gl/t81tgLXnNYxKFGW4A';
                        $enlaceLimpio = $esEdificio1 ? 'goo.gl/maps/qb7AxP1JAxx5yg3N9' : 'maps.app.goo.gl/t81tgLXnNYxKFGW4A';

                        $metodoEntrada = app(MetodoEntradaService::class)->resolverParaReserva($reserva);
                        if ($metodoEntrada === MetodoEntradaService::METODO_DIGITAL) {
                            $mensajeDigital = match (substr((string) $idiomaCliente, 0, 2)) {
                                'es' => "Tu acceso será mediante cerradura digital.\n\nLa entrega del código está pendiente de integración con nuestra plataforma de accesos (código único por cliente y ventana horaria). Si lo necesitas, contáctanos y te ayudamos.",
                                'fr' => "Votre accès se fera via une serrure digitale.\n\nLa livraison du code est en attente d’intégration avec notre plateforme d’accès (code unique par client et fenêtre horaire). Si besoin, contactez-nous.",
                                'de' => "Ihr Zugang erfolgt über ein digitales Schloss.\n\nDie Code-Zustellung wartet noch auf die Integration mit unserer Zugang-Plattform (ein Code pro Gast, zeitlich begrenzt). Bei Bedarf kontaktieren Sie uns.",
                                default => "Your access will be via a digital lock.\n\nCode delivery is pending integration with our access platform (unique code per guest and time window). If you need it, please contact us.",
                            };

                            if (!empty($phoneCliente)) {
                                $this->enviarMensajeTextoWhatsapp($phoneCliente, $mensajeDigital);
                            } else {
                                Log::warning('Claves digital: Se omite WhatsApp por falta de teléfono', ['reserva_id' => $reserva->id]);
                            }

                            // Crear registro atómicamente para evitar duplicados
                            MensajeAuto::firstOrCreate(
                                ['reserva_id' => $reserva->id, 'categoria_id' => 3],
                                ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                            );

                            Log::info('🔐 ACCESO DIGITAL - mensaje placeholder enviado', [
                                'reserva_id' => $reserva->id,
                                'apartamento_id' => $reserva->apartamento_id,
                                'telefono_cliente' => $phoneCliente,
                                'idioma' => $idiomaCliente,
                            ]);

                            continue;
                        }

                        Log::info('📤 INICIANDO ENVÍO DE MENSAJE DE CLAVES', [
                            'reserva_id' => $reserva->id,
                            'apartamento_id' => $reserva->apartamento_id,
                            'telefono_cliente' => $phoneCliente,
                            'idioma' => $idiomaCliente,
                            'es_atico' => $reserva->apartamento_id === 1
                        ]);

                        if ($reserva->apartamento_id === 1) {
                            $data = $this->clavesMensajeAtico(
                                $reserva->cliente->nombre,
                                $reserva->apartamento->titulo, $reserva->apartamento->edificioName->clave,
                                $reserva->apartamento->claves,
                                $phoneCliente,
                                $idiomaCliente,
                                $idiomaCliente == 'pt_PT' ? 'codigo_atico_por' : 'codigos_atico',
                                $url = $enlace,
                                $url2 = $enlaceLimpio
                            );
                        } else {
                            $data = $this->clavesMensaje(
                                $reserva->cliente->nombre == null ? $reserva->cliente->alias : $reserva->cliente->nombre, $reserva->apartamento->titulo,
                                $reserva->apartamento->edificioName->clave,
                                $reserva->apartamento->claves,
                                $phoneCliente,
                                $idiomaCliente,
                                $enlace
                            );
                            //Storage::disk('local')->put('Mensaje_claves'.$reserva->cliente_id.'.txt', $data );

                        }

                        // Verificar respuesta del envío
                        if ($data) {
                            $responseData = json_decode($data, true);
                            if (isset($responseData['messages'][0]['id'])) {
                                Log::info('✅ Mensaje de claves enviado correctamente', [
                                    'reserva_id' => $reserva->id,
                                    'message_id' => $responseData['messages'][0]['id'],
                                    'telefono' => $phoneCliente
                                ]);
                            } else {
                                Log::error('❌ Error en respuesta de envío de claves', [
                                    'reserva_id' => $reserva->id,
                                    'telefono' => $phoneCliente,
                                    'response' => substr($data, 0, 500)
                                ]);
                            }
                        } else {
                            Log::error('❌ No se recibió respuesta del envío de claves', [
                                'reserva_id' => $reserva->id,
                                'telefono' => $phoneCliente
                            ]);
                        }

                        // Crear registro atómicamente para evitar duplicados
                        $mensajeClaves = MensajeAuto::firstOrCreate(
                            ['reserva_id' => $reserva->id, 'categoria_id' => 3],
                            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                        );

                        if ($reserva->apartamento_id === 1) {
                            $mensaje = $this->clavesEmailAtico(
                                $idiomaCliente,
                                $reserva->cliente->nombre,
                                $reserva->apartamento->titulo,
                                $reserva->apartamento->edificioName->clave,
                                $reserva->apartamento->claves
                            );
                            //Storage::disk('local')->put('Mensaje_claves'.$reserva->cliente_id.'.txt', $data );

                        }else {
                            $mensaje = $this->clavesEmail(
                                $idiomaCliente,
                                $reserva->cliente->nombre,
                                $reserva->apartamento->titulo,
                                $reserva->apartamento->edificioName->clave,
                                $reserva->apartamento->claves,
                                $apartamentoReservado->edificio
                            );
                            //Storage::disk('local')->put('Mensaje_claves'.$reserva->cliente_id.'.txt', $data );

                        }

                        // if (
                        //     strpos($reserva->cliente->email_secundario, 'booking') !== false ||
                        //     strpos($reserva->cliente->email, 'booking') !== false
                        // ) {
                        //     // El email contiene 'booking'
                        //     // enviarEmail( $correo, $vista, $data, $asunto, $token, )
                        //     $enviarEmail = $this->enviarEmail(
                        //         $reserva->cliente->email_secundario,
                        //         'emails.envioClavesEmail',
                        //         $mensaje,
                        //         'Hawkins Suite - Claves',
                        //         $token = null
                        //     );
                        // }

                         // Verificamos y enviamos al email secundario si no es null, no vacío y es válido
                        if (!empty($reserva->cliente->email_secundario) && filter_var($reserva->cliente->email_secundario, FILTER_VALIDATE_EMAIL)) {
                            $this->enviarEmail(
                                $reserva->cliente->email_secundario,
                                'emails.envioClavesEmail',
                                $mensaje,
                                'Hawkins Suite - Claves',
                                null
                            );
                        }

                        // Verificamos y enviamos al email principal si no es null ni vacío
                        if (!empty($reserva->cliente->email)) {
                            $this->enviarEmail(
                                $reserva->cliente->email,
                                'emails.envioClavesEmail',
                                $mensaje,
                                'Hawkins Suite - Claves',
                                null
                            );
                        }

                        // Si la reserva NO es de la web, enviar también al chat de Channex
                        Log::info('🔍 VERIFICANDO ENVÍO DE CLAVES POR CHANNEX - Inicio', [
                            'reserva_id' => $reserva->id,
                            'codigo_reserva' => $reserva->codigo_reserva,
                            'origen' => $reserva->origen,
                            'origen_es_web' => $reserva->origen === 'web',
                            'id_channex' => $reserva->id_channex,
                            'id_channex_vacio' => empty($reserva->id_channex),
                            'condicion_origen' => $reserva->origen !== 'web',
                            'condicion_id_channex' => !empty($reserva->id_channex),
                            'condicion_completa' => ($reserva->origen !== 'web' && !empty($reserva->id_channex))
                        ]);

                        if ($reserva->origen !== 'web' && !empty($reserva->id_channex)) {
                            Log::info('✅ ENTRANDO EN ENVÍO DE CLAVES POR CHANNEX', [
                                'reserva_id' => $reserva->id,
                                'codigo_reserva' => $reserva->codigo_reserva,
                                'id_channex' => $reserva->id_channex,
                                'idioma_cliente' => $idiomaCliente,
                                'apartamento_id' => $apartamentoReservado->id ?? null,
                                'apartamento_titulo' => $apartamentoReservado->titulo ?? null
                            ]);

                            // Usar el método helper para enviar claves por Channex
                            $resultadoEnvio = $this->enviarClavesPorChannex($reserva, $idiomaCliente, $apartamentoReservado);

                            Log::info('📤 RESULTADO DEL ENVÍO DE CLAVES POR CHANNEX', [
                                'reserva_id' => $reserva->id,
                                'codigo_reserva' => $reserva->codigo_reserva,
                                'id_channex' => $reserva->id_channex,
                                'resultado' => $resultadoEnvio,
                                'resultado_tipo' => gettype($resultadoEnvio),
                                'resultado_booleano' => $resultadoEnvio === true ? 'true' : ($resultadoEnvio === false ? 'false' : 'otro'),
                                'enviado_exitosamente' => $resultadoEnvio === true
                            ]);
                        } else {
                            Log::warning('❌ NO SE ENVÍA CLAVES POR CHANNEX - Condición no cumplida', [
                                'reserva_id' => $reserva->id,
                                'codigo_reserva' => $reserva->codigo_reserva,
                                'origen' => $reserva->origen,
                                'origen_es_web' => $reserva->origen === 'web',
                                'id_channex' => $reserva->id_channex,
                                'id_channex_vacio' => empty($reserva->id_channex),
                                'razon_no_envio' => $reserva->origen === 'web' ? 'Reserva es de la web' : 'Falta id_channex'
                            ]);
                        }

                    }
                }

                // MENSAJE DE SI TIENE ALGUNA CONSULTA
                if ($diferenciasHoraConsulta <= 0 && $mensajeClaves != null && $mensajeConsulta == null) {
                    $tiempoDesdeClaves = $mensajeClaves->created_at->diffInMinutes(Carbon::now());
                    if ($tiempoDesdeClaves >= 1) {
                        // Obtenemos codigo de idioma
                        $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);
                        // Enviamos el mensaje (solo si hay teléfono)
                        if (!empty($phoneCliente)) {
                            $data = $this->consultaMensaje($reserva->cliente->nombre, $phoneCliente, $idiomaCliente );
                            Storage::disk('local')->put('Mensaje_claves2'.$reserva->cliente_id.'.txt', $data );
                        } else {
                            Log::warning('Consulta: Se omite WhatsApp por falta de teléfono', ['reserva_id' => $reserva->id]);
                        }

                        // Crear registro atómicamente para evitar duplicados
                        $mensajeConsulta = MensajeAuto::firstOrCreate(
                            ['reserva_id' => $reserva->id, 'categoria_id' => 5],
                            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                        );

                        // Si la reserva NO es de la web, enviar también al chat de Channex
                        if ($reserva->origen !== 'web' && !empty($reserva->id_channex)) {
                            try {
                                $datosConsulta = [
                                    'nombre' => $reserva->cliente->nombre ?? $reserva->cliente->alias
                                ];

                                $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('consulta', $datosConsulta, $idiomaCliente);

                                $resultado = \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                                    $mensajeChat,
                                    $reserva->id_channex
                                );

                                Log::info('Mensaje de consulta enviado a Channex:', ['resultado' => $resultado, 'booking_id' => $reserva->id_channex]);
                            } catch (\Exception $e) {
                                Log::error('Error al enviar mensaje de consulta al chat:', [
                                    'error' => $e->getMessage(),
                                    'reserva_id' => $reserva->id,
                                    'booking_id' => $reserva->codigo_reserva
                                ]);
                            }
                        }
                    }
                }

                // MENSAJE DE OCIO
                if ($diferenciasHoraOcio <= 0 && $mensajeConsulta != null && $mensajeOcio == null) {
                    // 🔄 BUG FIX: Usar mensajeConsulta en lugar de mensajeClaves
                    $tiempoDesdeConsulta = $mensajeConsulta->created_at->diffInMinutes(Carbon::now());
                    if ($tiempoDesdeConsulta >= 1) {
                        // Obtenemos codigo de idioma
                        $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);
                        // Enviamos el mensaje (solo si hay teléfono)
                        if (!empty($phoneCliente)) {
                            $data = $this->ocioMensaje($reserva->cliente->nombre, $phoneCliente, $idiomaCliente);
                            Storage::disk('local')->put('Mensaje_ocio'.$reserva->cliente_id.'.txt', $data );
                        } else {
                            Log::warning('Ocio: Se omite WhatsApp por falta de teléfono', ['reserva_id' => $reserva->id]);
                        }

                        // Crear registro atómicamente para evitar duplicados
                        $mensajeOcio = MensajeAuto::firstOrCreate(
                            ['reserva_id' => $reserva->id, 'categoria_id' => 6],
                            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                        );

                        // Si la reserva NO es de la web, enviar también al chat de Channex
                        if ($reserva->origen !== 'web' && !empty($reserva->codigo_reserva)) {
                            try {
                                $datosOcio = [
                                    'nombre' => $reserva->cliente->nombre ?? $reserva->cliente->alias
                                ];

                                $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('ocio', $datosOcio, $idiomaCliente);

                                $resultado = \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                                    $mensajeChat,
                                    $reserva->id_channex
                                );

                                Log::info('Mensaje de ocio enviado a Channex:', ['resultado' => $resultado, 'booking_id' => $reserva->codigo_reserva]);
                            } catch (\Exception $e) {
                                Log::error('Error al enviar mensaje de ocio al chat:', [
                                    'error' => $e->getMessage(),
                                    'reserva_id' => $reserva->id,
                                    'booking_id' => $reserva->codigo_reserva
                                ]);
                            }
                        }
                    }
                }
            }

            Log::info("Tarea programada de Envio de mensajes Automatizados ejecutada con éxito.");
        })->everyMinute();


        // Tarea par enviar los mensajes despedida cuando se ha entregado el DNI
        $schedule->call(function (ClienteService $clienteService) {
            // Obtener la fecha de hoy
            $hoy = Carbon::now();

            $reservas = Reserva::whereDate('fecha_salida', '=', date('Y-m-d'))
            ->get();
            /* ->where('dni_entregado', '!=', null) */

            foreach($reservas as $reserva){
                // Fecha de Hoy
                $FechaHoy = new \DateTime();
                // Formatea la fecha actual a una cadena 'Y-m-d'
                $fechaHoyStr = $FechaHoy->format('Y-m-d');

                // Horas objetivo para lanzar mensajes

                $horaObjetivoDespedida = new \DateTime($fechaHoyStr . ' 12:00:00');

                // Comprobacion de los mensajes enviados automaticamente

                $mensajeDespedida = MensajeAuto::where('reserva_id', $reserva->id)->where('categoria_id', 7)->first();

                if ($hoy->gte(Carbon::parse($horaObjetivoDespedida)) && $mensajeDespedida == null) {
                        // Obtenemos codigo de idioma
                        $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);

                        // Obtener teléfono con fallback
                        $telefonoRawDesp = $reserva->cliente->telefono_movil ?? $reserva->cliente->telefono ?? null;
                        $phoneClienteDesp = !empty($telefonoRawDesp) ? $this->limpiarNumeroTelefono($telefonoRawDesp) : null;

                        // Enviamos el mensaje por WhatsApp (solo si hay teléfono)
                        if (!empty($phoneClienteDesp)) {
                            $data = $this->despedidaMensaje($reserva->cliente->nombre, $phoneClienteDesp, $idiomaCliente);
                        } else {
                            Log::warning('Despedida: Se omite WhatsApp por falta de teléfono', ['reserva_id' => $reserva->id]);
                        }

                        // Enviamos el email de despedida
                        try {
                            $nombreCliente = $reserva->cliente->nombre ?? $reserva->cliente->alias;
                            $contenidoEmail = $this->despedidaEmailContenido($idiomaCliente, $nombreCliente);

                            // Asunto según idioma
                            $asuntoDespedida = match (substr((string) $idiomaCliente, 0, 2)) {
                                'es' => 'Hawkins Suite - Gracias por su estancia',
                                'fr' => 'Hawkins Suite - Merci pour votre séjour',
                                'de' => 'Hawkins Suite - Vielen Dank für Ihren Aufenthalt',
                                default => 'Hawkins Suite - Thank you for your stay',
                            };

                            if (!empty($reserva->cliente->email)) {
                                Mail::to($reserva->cliente->email)->send(new DespedidaEmail(
                                    $contenidoEmail,
                                    $asuntoDespedida
                                ));
                            }

                            if (!empty($reserva->cliente->email_secundario) && filter_var($reserva->cliente->email_secundario, FILTER_VALIDATE_EMAIL)) {
                                Mail::to($reserva->cliente->email_secundario)->send(new DespedidaEmail(
                                    $contenidoEmail,
                                    $asuntoDespedida
                                ));
                            }

                            Log::info('Email de despedida enviado', [
                                'reserva_id' => $reserva->id,
                                'idioma' => $idiomaCliente,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Error al enviar email de despedida: ' . $e->getMessage(), [
                                'reserva_id' => $reserva->id,
                            ]);

                            // Notificación interna
                            try {
                                \App\Models\Notification::createForAdmins(
                                    \App\Models\Notification::TYPE_SISTEMA,
                                    'Error envío email despedida',
                                    "No se pudo enviar email de despedida para Reserva #{$reserva->id}: {$e->getMessage()}",
                                    ['email' => $reserva->cliente->email ?? null, 'reserva_id' => $reserva->id],
                                    \App\Models\Notification::PRIORITY_MEDIUM,
                                    \App\Models\Notification::CATEGORY_ERROR
                                );
                            } catch (\Exception $notifEx) {
                                Log::error('No se pudo crear notificación de fallo email despedida', ['error' => $notifEx->getMessage()]);
                            }
                        }

                        // Crear registro atómicamente para evitar duplicados
                        MensajeAuto::firstOrCreate(
                            ['reserva_id' => $reserva->id, 'categoria_id' => 7],
                            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
                        );

                        // Si la reserva NO es de la web, enviar también al chat de Channex
                        if ($reserva->origen !== 'web' && !empty($reserva->id_channex)) {
                            $datosDespedida = [
                                'nombre' => $reserva->cliente->nombre ?? $reserva->cliente->alias
                            ];

                            $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('despedida', $datosDespedida, $idiomaCliente);

                            \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
                                $mensajeChat,
                                $reserva->id_channex
                            );
                        }

                }
            }

            Log::info("Tarea programada Mensaje de despedida ejecutada con éxito.");
        })->everyMinute();

        // // Tarea para revisar los mensajes de whatsapp
        // $schedule->call(function () {
        //     // Obtener los mensajes de WhatsApp
        //     $mensajes = $this->obtenerMensajesWhatsapp();

        //     foreach ($mensajes as $mensaje) {
        //         $contenido = $mensaje['contenido'];

        //         // Preparar el prompt para el modelo GPT-3
        //         $prompt = "Analiza el siguiente mensaje y determina si es una queja o una avería: \"$contenido\"";

        //         // Llamar al modelo GPT-3 para analizar el mensaje
        //         $resultado = $this->analizarMensajeConGPT3($prompt);

        //         if ($resultado === 'queja') {
        //             Log::info("Mensaje identificado como queja: " . $contenido);
        //             // Aquí puedes agregar lógica adicional para manejar quejas
        //         } elseif ($resultado === 'avería') {
        //             Log::info("Mensaje identificado como avería: " . $contenido);
        //             // Aquí puedes agregar lógica adicional para manejar averías
        //         } else {
        //             Log::info("Mensaje no identificado claramente: " . $contenido);
        //         }
        //     }

        // })->everyMinute();

    }

    private function enviarMensajeTextoWhatsapp(string $telefono, string $texto): ?string
    {
        $tokenEnv = Setting::whatsappToken();
        $urlMensajes = Setting::whatsappUrl();

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $telefono,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $texto,
            ],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $urlMensajes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $tokenEnv,
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            Log::error("❌ Error de cURL al enviar mensaje de texto", [
                'telefono' => $telefono,
                'error' => $curlError,
            ]);
            return null;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Log::error("❌ Error al enviar mensaje de texto", [
                'telefono' => $telefono,
                'http_code' => $httpCode,
                'response' => $response,
            ]);
            return null;
        }

        return $response;
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    public function webPol($data){
        // $credentials = array(
        //     'user' => 'H11070GEV04',
        //     'pass' => 'H4Kins4p4rtamento2023'
        // );
        // $data = [
        //     'username' => 'H11070GEV04',
        //     'password' => 'H4Kins4p4rtamento2023',
        //     '_csrf' => '49614a9a-efc7-4c36-9063-b1cd6824aa9a'
        // ];
        //https://webpol.policia.es/e-hotel/execute_login
        //https://webpol.policia.es/e-hotel/login
        //https://webpol.policia.es/hospederia/manual/vista/grabadorManual
        //https://webpol.policia.es/hospederia/manual/insertar/huesped

        $browser = new HttpBrowser(HttpClient::create());
        $crawler = $browser->request('GET', 'https://webpol.policia.es/e-hotel/login');
        $csrfToken = $crawler->filter('meta[name="_csrf"]')->attr('content');

        $response1 = $browser->getResponse();
        $statusCode1 = $response1->getStatusCode();
        if ($statusCode1 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            echo '1 - Código de estado HTTP: ' . $statusCode1;
            return;
        }

        $cookiesArray = [];
        foreach ($browser->getCookieJar()->all() as $cookie) {
            $cookiesArray[$cookie->getName()] = $cookie->getValue();
        }

        $postData = [
            'username' => env('MIR_USERNAME'),
            'password' => env('MIR_PASSWORD'),
            '_csrf'    => $csrfToken
        ];

        $headers = [
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_COOKIE' => 'FRONTAL_JSESSIONID: ' . $cookiesArray['FRONTAL_JSESSIONID'] . ' UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_: ' . $cookiesArray['UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_'] . ' cookiesession1: ' . $cookiesArray['cookiesession1']
        ];

        $browser->setServerParameters($headers);
        $crawler = $browser->request(
            'POST',
            'https://webpol.policia.es/e-hotel/execute_login',
            $postData
        );

        $response2 = $browser->getResponse();
        $statusCode2 = $response2->getStatusCode();
        if ($statusCode2 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            echo '2 - Código de estado HTTP: ' . $statusCode2;
            return;
        }

        $crawler = $browser->request('GET', 'https://webpol.policia.es/e-hotel/hospederia/manual/vista/grabadorManual');
        $idHospederia = $crawler->filter('#idHospederia')->attr('value');

        $response3 = $browser->getResponse();
        $statusCode3 = $response3->getStatusCode();
        if ($statusCode3 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            echo '3 - Código de estado HTTP: ' . $statusCode3;
            return;
        }
        mb_internal_encoding("UTF-8");

        $apellido = mb_convert_encoding('CASTAÑOS', 'UTF-8');


        $headers = [
            'Cookie' => 'FRONTAL_JSESSIONID: ' . $cookiesArray['FRONTAL_JSESSIONID'] . ' UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_: ' . $cookiesArray['UqZBpD3n3iHPAgNS9Fnn5SbNcvsF5IlbdcvFr4ieqh8_'] . ' cookiesession1: ' . $cookiesArray['cookiesession1'],
            'Accept' => 'text/html, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Referer' => 'https://webpol.policia.es/e-hotel/inicio',
            'X-Csrf-Token' => $csrfToken,
            'X-Requested-With' => 'XMLHttpRequest',
            // Otros encabezados según sea necesario
        ];
        // $data['apellido1'] = mb_convert_encoding('CASTAÑOS', 'UTF-8');
        $data['idHospederia'] = $idHospederia;
        $data['_csrf'] = $csrfToken;

        // 'idHospederia' => $idHospederia,
        // '_csrf' => $csrfToken
        $browser->setServerParameters($headers);

        $crawler = $browser->request(
            'POST',
            'https://webpol.policia.es/e-hotel/hospederia/manual/insertar/huesped',
            $data
        );
        // Diagnóstico: Ver contenido de la respuesta
        $responseContent = $browser->getResponse()->getContent();
        echo $responseContent;

        $response4 = $browser->getResponse();
        $statusCode4 = $response4->getStatusCode();

        if ($browser->getResponse()->getStatusCode() == 302) {
            $crawler = $browser->followRedirect();
            // Sigue la redirección
        }

        if ($statusCode4 == 200) {
            $responseContent = $crawler->html();
        } else {
            // Manejar el caso en que la respuesta no es exitosa
            // echo '4 - Código de estado HTTP: ' . $statusCode4 . $csrfToken . ' id: '. $idHospederia;
            return;
        }
        return [
            $csrfToken,
            $cookiesArray,
            $responseContent
        ];
    }

    function limpiarNumeroTelefono($numero) {
        // Eliminar el signo más y cualquier espacio
        $numeroLimpio = preg_replace('/\+|\s+/', '', $numero);

        return $numeroLimpio;
    }

    public function contestarWhatsapp($phone, $texto){
        $token = Setting::whatsappToken();
        // return $texto;
        $mensajePersonalizado = '{
            "messaging_product": "whatsapp",
            "recipient_type": "individual",
            "to": "'.$phone.'",
            "type": "text",
            "text": {
                "body": "'.$texto.'"
            }
        }';
        // return $mensajePersonalizado;

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
            CURLOPT_POSTFIELDS => $mensajePersonalizado,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        Storage::disk('local')->put('response0001.txt', json_encode($response) . json_encode($mensajePersonalizado) );
        return $response;

    }

    // Mensaje DNI
    public function mensajesAutomaticosBoton($template, $token, $telefono, $idioma = 'en'){
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => $template,
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => 0,
                        "parameters" => [
                            ["type" => "text", "text" => $token]
                        ]
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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        return $response;
    }

    public function codigoApartamento($habitacion){
        $apartamento = Apartamento::find($habitacion);


        if ($apartamento) {
            switch ($habitacion) {
                case 1:
                    return [
                            'nombre' => 'ATICO',
                            'codigo' => $apartamento->claves
                        ];
                    break;

                case 2:
                    return [
                        'nombre' => '2A',
                        'codigo' => $apartamento->claves
                    ];
                    break;

                case 3:
                    return [
                        'nombre' => '2B',
                        'codigo' => $apartamento->claves
                    ];
                    break;

                case 4:
                    return [
                        'nombre' => '1A',
                        'codigo' => $apartamento->claves
                    ];
                    break;

                case 5:
                    return [
                        'nombre' => '1B',
                        'codigo' => $apartamento->claves
                    ];
                    break;

                case 6:
                    return [
                        'nombre' => 'BA',
                        'codigo' => $apartamento->claves
                    ];
                    break;

                case 7:
                    return [
                        'nombre' => 'BB',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 8:
                    return [
                        'nombre' => 'Atico',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 9:
                    return [
                        'nombre' => '3A',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 10:
                    return [
                        'nombre' => '3B',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 11:
                    return [
                        'nombre' => '3C',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 12:
                    return [
                        'nombre' => '2A',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 13:
                    return [
                        'nombre' => '2B',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 14:
                    return [
                        'nombre' => '1A',
                        'codigo' => $apartamento->claves
                    ];
                    break;
                case 15:
                    return [
                        'nombre' => '1B',
                        'codigo' => $apartamento->claves
                    ];
                    break;

                default:
                return [
                    'nombre' => 'Error',
                    'codigo' => '0000'
                ];
                    break;
            }
        }

    }

    public function mensajesAutomaticos($template, $nombre, $telefono, $idioma = 'en'){
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => $template,
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        return $response;

    }

    /**
     * Registra un mensaje WhatsApp automatico enviado por el CRM al cliente
     * en la tabla whatsapp_mensajes (detalle) y whatsapp_mensaje_chatgpt (conversacion)
     * para que aparezca en la seccion "Conversaciones" del CRM junto con los
     * mensajes entrantes, en vez de quedar solo en los logs.
     *
     * @param string $telefono      Numero del cliente (sin +)
     * @param string $textoLegible  Texto que se mostrara en la conversacion
     * @param string $tipoAuto      Identificador breve: 'bienvenida', 'claves', 'recordatorio_dni', ...
     * @param string|null $messageIdMeta ID del mensaje devuelto por WhatsApp Cloud API
     * @param array  $metadata      Payload original enviado a Meta (para auditoria)
     */
    private function registrarWhatsappAutomatico(
        string $telefono,
        string $textoLegible,
        string $tipoAuto,
        ?string $messageIdMeta,
        array $metadata = []
    ): void {
        try {
            // 1) Registro detallado del mensaje saliente (un registro por mensaje
            //    individual, con el id de Meta para tracking de estados/entregas)
            WhatsappMensaje::create([
                'mensaje_id' => $messageIdMeta,
                'tipo' => 'template',
                'contenido' => $textoLegible,
                'remitente' => null, // null = saliente (enviado por nosotros)
                'fecha_mensaje' => now(),
                'metadata' => is_array($metadata) ? $metadata : [],
            ]);

            // 2) Registro en la tabla de conversacion. La vista "whatsapp" del CRM
            //    agrupa por 'remitente', asi que metemos el telefono del cliente
            //    ahi para que el mensaje auto aparezca dentro de SU conversacion.
            //    mensaje=null + respuesta=texto indica que es un saliente auto.
            ChatGpt::create([
                'id_mensaje' => $messageIdMeta,
                'remitente' => $telefono,
                'mensaje' => null,
                'respuesta' => $textoLegible,
                'status' => 1,
                'type' => 'auto_' . $tipoAuto,
                'date' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar WhatsApp auto en conversaciones', [
                'telefono' => $telefono,
                'tipo' => $tipoAuto,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function noEntregadoDNIMensaje($nombre, $codigoReserva, $plataforma, $telefono, $telefonoCliente, $url, $idioma = 'es', ){
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => 'dni_no_entregado',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $nombre
                            ],
                            [
                                "type" => "text",
                                "text" => $codigoReserva
                            ],
                            [
                                "type" => "text",
                                "text" => $plataforma
                            ],
                            [
                                "type" => "text",
                                "text" => $telefonoCliente
                            ],
                            [
                                "type" => "text",
                                "text" => $url
                            ],
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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);

        // Registrar en conversaciones del CRM si el envio fue OK.
        // Este mensaje se envia al CLIENTE (telefonoCliente), no al equipo.
        $responseJson = json_decode($response, true);
        if (is_array($responseJson) && isset($responseJson['messages'][0]['id']) && !empty($telefonoCliente)) {
            $this->registrarWhatsappAutomatico(
                $telefonoCliente,
                "⚠️ Recordatorio DNI enviado a {$nombre} (reserva {$codigoReserva} · {$plataforma})",
                'recordatorio_dni',
                $responseJson['messages'][0]['id'],
                $mensajePersonalizado
            );
        }

        return $response;
    }

    public function bienvenidoMensaje($nombre, $telefono, $idioma = 'en', $tokenReserva = null){
        $tokenEnv = Setting::whatsappToken();

        // Generar URL del botón con el token de la reserva
        $urlDNI = null;
        if ($tokenReserva) {
            $urlDNI = 'https://crm.apartamentosalgeciras.com/dni-user/' . $tokenReserva;
        }

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => 'bienvenido',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
                        ],
                    ],
                ],
            ],
        ];

        // Agregar botón con URL dinámica si hay token de reserva
        if ($urlDNI) {
            $mensajePersonalizado["template"]["components"][] = [
                "type" => "button",
                "sub_type" => "url",
                "index" => 0,
                "parameters" => [
                    [
                        "type" => "text",
                        "text" => $urlDNI
                    ]
                ]
            ];
        }

        $urlMensajes = Setting::whatsappUrl();

        Log::info("📤 Enviando mensaje de bienvenida automático", [
            'telefono' => $telefono,
            'nombre' => $nombre,
            'idioma' => $idioma,
            'token_reserva' => $tokenReserva,
            'url_dni' => $urlDNI
        ]);

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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            Log::error("❌ Error de cURL al enviar mensaje de bienvenida", [
                'telefono' => $telefono,
                'error' => $curlError
            ]);
            return $response;
        }

        $responseJson = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseJson['messages'][0]['id'])) {
            Log::info("✅ Mensaje de bienvenida enviado exitosamente", [
                'telefono' => $telefono,
                'message_id' => $responseJson['messages'][0]['id'],
                'http_code' => $httpCode
            ]);

            // Registrar en conversaciones del CRM
            $texto = "👋 Mensaje de bienvenida enviado a {$nombre}";
            if ($urlDNI) $texto .= " — enlace check-in: {$urlDNI}";
            $this->registrarWhatsappAutomatico(
                $telefono,
                $texto,
                'bienvenida',
                $responseJson['messages'][0]['id'],
                $mensajePersonalizado
            );
        } else {
            Log::error("❌ Error al enviar mensaje de bienvenida", [
                'telefono' => $telefono,
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }

        return $response;
    }

    public function clavesMensaje($nombre, $apartamento, $puertaPrincipal, $codigoApartamento, $telefono, $idioma = 'en', $url){
        $tokenEnv = Setting::whatsappToken();
        $data = [
            ["type" => "text", "text" => $nombre],
            ["type" => "text", "text" => $apartamento],
            ["type" => "text", "text" => $puertaPrincipal],
            ["type" => "text", "text" => $codigoApartamento],
            ["type" => "text", "text" => $url],
            ["type" => "text", "text" => $idioma]
        ];
        Storage::disk('local')->put('Mensaje_claves_variables'.$nombre.'.txt', json_encode($data) );

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => 'codigos',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
                            ["type" => "text", "text" => $apartamento],
                            ["type" => "text", "text" => $puertaPrincipal],
                            ["type" => "text", "text" => $codigoApartamento],
                            ["type" => "text", "text" => $url]

                        ],
                    ],
                ],
            ],
        ];

        $urlMensajes = Setting::whatsappUrl();

        Log::info("📤 Enviando mensaje de claves automático", [
            'telefono' => $telefono,
            'nombre' => $nombre,
            'apartamento' => $apartamento,
            'idioma' => $idioma
        ]);

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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            Log::error("❌ Error de cURL al enviar mensaje de claves", [
                'telefono' => $telefono,
                'error' => $curlError
            ]);
            return $response;
        }

        $responseJson = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseJson['messages'][0]['id'])) {
            Log::info("✅ Mensaje de claves enviado exitosamente", [
                'telefono' => $telefono,
                'message_id' => $responseJson['messages'][0]['id'],
                'http_code' => $httpCode
            ]);

            // Registrar en conversaciones del CRM
            $this->registrarWhatsappAutomatico(
                $telefono,
                "🔑 Claves enviadas a {$nombre} para {$apartamento}. Puerta principal: {$puertaPrincipal} · Apartamento: {$codigoApartamento}",
                'claves',
                $responseJson['messages'][0]['id'],
                $mensajePersonalizado
            );
        } else {
            Log::error("❌ Error al enviar mensaje de claves", [
                'telefono' => $telefono,
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }

        return $response;
    }

    public function clavesMensajeAtico($nombre, $apartamento, $puertaPrincipal, $codigoApartamento, $telefono, $idioma = 'en', $template, $url, $url2){
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => $template,
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
                            ["type" => "text", "text" => $apartamento],
                            ["type" => "text", "text" => $puertaPrincipal],
                            ["type" => "text", "text" => $codigoApartamento],
                            ["type" => "text", "text" => $url]
                        ],
                    ],
                    [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => "0",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $url2
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $urlMensajes = Setting::whatsappUrl();

        Log::info("📤 Enviando mensaje de claves (ático) automático", [
            'telefono' => $telefono,
            'nombre' => $nombre,
            'apartamento' => $apartamento,
            'idioma' => $idioma,
            'template' => $template
        ]);

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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            Log::error("❌ Error de cURL al enviar mensaje de claves (ático)", [
                'telefono' => $telefono,
                'error' => $curlError
            ]);
            return $response;
        }

        $responseJson = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseJson['messages'][0]['id'])) {
            Log::info("✅ Mensaje de claves (ático) enviado exitosamente", [
                'telefono' => $telefono,
                'message_id' => $responseJson['messages'][0]['id'],
                'http_code' => $httpCode
            ]);

            // Registrar en conversaciones del CRM
            $this->registrarWhatsappAutomatico(
                $telefono,
                "🔑 Claves enviadas a {$nombre} para {$apartamento} (ático). Puerta principal: {$puertaPrincipal} · Apartamento: {$codigoApartamento}",
                'claves_atico',
                $responseJson['messages'][0]['id'],
                $mensajePersonalizado
            );
        } else {
            Log::error("❌ Error al enviar mensaje de claves (ático)", [
                'telefono' => $telefono,
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }

        return $response;
    }

    public function consultaMensaje($nombre, $telefono, $idioma = 'en'){
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => 'consulta',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
                        ],
                    ],
                ],
            ],
        ];

        $urlMensajes = Setting::whatsappUrl();

        Log::info("📤 Enviando mensaje de consulta automático", [
            'telefono' => $telefono,
            'nombre' => $nombre,
            'idioma' => $idioma
        ]);

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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            Log::error("❌ Error de cURL al enviar mensaje de consulta", [
                'telefono' => $telefono,
                'error' => $curlError
            ]);
            return $response;
        }

        $responseJson = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseJson['messages'][0]['id'])) {
            Log::info("✅ Mensaje de consulta enviado exitosamente", [
                'telefono' => $telefono,
                'message_id' => $responseJson['messages'][0]['id'],
                'http_code' => $httpCode
            ]);

            // Registrar en conversaciones del CRM
            $this->registrarWhatsappAutomatico(
                $telefono,
                "🤔 Consulta automática enviada a {$nombre} (preguntando si todo va bien en la estancia)",
                'consulta',
                $responseJson['messages'][0]['id'],
                $mensajePersonalizado
            );
        } else {
            Log::error("❌ Error al enviar mensaje de consulta", [
                'telefono' => $telefono,
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }

        return $response;
    }

    public function despedidaMensaje($nombre, $telefono, $idioma = 'en'){
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => 'despedida',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);

        // Registrar en conversaciones del CRM si el envio fue OK
        $responseJson = json_decode($response, true);
        if (is_array($responseJson) && isset($responseJson['messages'][0]['id'])) {
            $this->registrarWhatsappAutomatico(
                $telefono,
                "👋 Despedida enviada a {$nombre} tras su estancia",
                'despedida',
                $responseJson['messages'][0]['id'],
                $mensajePersonalizado
            );
        }

        return $response;
    }

    public function ocioMensaje($nombre, $telefono, $idioma = 'en'){
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => 'ocio',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
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
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);

        // Registrar en conversaciones del CRM si el envio fue OK
        $responseJson = json_decode($response, true);
        if (is_array($responseJson) && isset($responseJson['messages'][0]['id'])) {
            $this->registrarWhatsappAutomatico(
                $telefono,
                "🎯 Sugerencias de ocio enviadas a {$nombre}",
                'ocio',
                $responseJson['messages'][0]['id'],
                $mensajePersonalizado
            );
        }

        return $response;
    }

    public function dniEmail($idioma, $token){

        switch ($idioma) {
            case 'es':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Gracias por reservar en los apartamentos Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                La legislación Española Nos obliga a solicitarle si Documento Nacional de Identidad o su pasaporte. Es obligatorio que nos lo facilite o no podrá alojarse en el apartamento.
                </p>
                <p style="margin: 0 !important">
                    Le dejamos un enlace para que rellene sus datos y nos lo facilite la copia del DNI o Pasaporte:
                </p>
                <p>
                    <a class="btn btn-primary" href="https://crm.apartamentosalgeciras.com/dni-user/'.$token.'" target="_blank">https://crm.apartamentosalgeciras.com/dni-user/'.$token.'</a>
                </p>

                <p style="margin: 0 !important">
                    Las claves de acceso al apartamento se las enviamos el dia de su llegada por whatsapp y correo electronico, asegurese de tener la informacion de contacto correctamente.
                </p>
                <br>
                <p style="margin: 0 !important">Gracias por utilizar nuestra aplicación!</p>
                ';
                return $temaplate;
                break;

            case 'fr':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Merci de réserver chez les appartements Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                    La législation espagnole nous oblige à vous demander votre carte d'."'".'identité nationale ou votre passeport. l est obligatoire que vous nous le fournissiez, sinon vous ne pourrez pas séjourner dans l'."'".'appartement.
                </p>
                <p style="margin: 0 !important">
                    Nous vous laissons un lien pour nous le fournir via le bouton ci-dessous:
                </p>
                <p>
                    <a class="btn btn-primary" href="https://crm.apartamentosalgeciras.com/dni-user/'.$token.'" target="_blank">https://crm.apartamentosalgeciras.com/dni-user/'.$token.'</a>
                </p>
                <p style="margin: 0 !important">
                    Les codes d'."'".'accès à l'."'".'appartement vous seront envoyés le jour de votre arrivée par WhatsApp et par e-mail, assurez-vous d'."'".'avoir les informations de contact correctes.
                </p>
                <br>
                <p style="margin: 0 !important">Merci d'."'".'utiliser notre application!</p>
                ';
                return $temaplate;
                break;

            case 'ar':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    شكراً لحجزكم في شقق هوكينز!!
                </h3>

                <p style="margin: 0 !important">
                    يُلزمنا القانون الإسباني بطلب هويتكم الوطنية أو جواز سفركم. من الضروري أن تقدموه لنا، وإلا لن تتمكنوا من الإقامة في الشقة.
                </p>
                <p style="margin: 0 !important">
                    :نترك لكم رابطاً لتقديمه لنا عبر الزر أدناه.
                </p>
                <p>
                    <a class="btn btn-primary" href="https://crm.apartamentosalgeciras.com/dni-user/'.$token.'" target="_blank">https://crm.apartamentosalgeciras.com/dni-user/'.$token.'</a>
                </p>
                <p style="margin: 0 !important">
                سنرسل لك رموز الوصول إلى الشقة في يوم وصولك عبر تطبيق WhatsApp والبريد الإلكتروني، وتأكد من حصولك على معلومات الاتصال بشكل صحيح.
                </p>
                <br>
                <p style="margin: 0 !important">شكرا لك على استخدام التطبيق لدينا!</p>
                ';
                return $temaplate;
                break;

            case 'de':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Danke, dass Sie sich für die Hawkins Apartments entschieden haben!!
                </h3>

                <p style="margin: 0 !important">
                    Die spanische Gesetzgebung verpflichtet uns, Ihren Personalausweis oder Ihren Reisepass anzufordern. Es ist obligatorisch, dass Sie uns diesen zur Verfügung stellen, ansonsten können Sie nicht in der Wohnung übernachten.
                </p>
                <p style="margin: 0 !important">
                Wir hinterlassen Ihnen einen Link, um uns dies über den unteren Button zu übermitteln.:
                </p>
                <p>
                    <a class="btn btn-primary" href="https://crm.apartamentosalgeciras.com/dni-user/'.$token.'" target="_blank">https://crm.apartamentosalgeciras.com/dni-user/'.$token.'</a>
                </p>
                <p style="margin: 0 !important">
                    Wir senden Ihnen die Zugangscodes zum Apartment am Tag Ihrer Ankunft per WhatsApp und E-Mail zu. Stellen Sie sicher, dass Sie die Kontaktinformationen korrekt haben.                </p>
                <br>
                <p style="margin: 0 !important">
                    Vielen Dank, dass Sie unsere Anwendung nutzen!
                </p>
                ';
                return $temaplate;
                break;

            case 'pt_PT':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                Obrigado por reservar nos apartamentos Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                    A legislação espanhola nos obriga a solicitar o seu Documento Nacional de Identidade ou passaporte. É obrigatório que nos forneça, caso contrário, não poderá ficar no apartamento.
                </p>
                <p style="margin: 0 !important">
                    Deixamos um link para nos fornecer isso através do botão abaixo:
                </p>
                <p>
                    <a class="btn btn-primary" href="https://crm.apartamentosalgeciras.com/dni-user/'.$token.'" target="_blank">https://crm.apartamentosalgeciras.com/dni-user/'.$token.'</a>
                </p>
                <p style="margin: 0 !important">
                    Enviaremos os códigos de acesso ao apartamento no dia da sua chegada por WhatsApp e email, certifique-se de ter os dados de contato corretos.
                </p>
                <br>
                <p style="margin: 0 !important">
                    Obrigado por usar nosso aplicativo!
                </p>
                ';
                return $temaplate;
                break;

            case 'it':
                $$temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Grazie per aver prenotato presso gli appartamenti Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                    La legislazione spagnola ci obbliga a richiedere il vostro Documento Nazionale d'."'".'Identità o il passaporto. È obbligatorio che ce lo forniate, altrimenti non potrete soggiornare nell'."'".'appartamento.
                </p>
                <p style="margin: 0 !important">
                    Vi lasciamo un link per fornircelo tramite il pulsante in basso:
                </p>
                <p>
                    <a class="btn btn-primary" href="https://crm.apartamentosalgeciras.com/dni-user/'.$token.'" target="_blank">https://crm.apartamentosalgeciras.com/dni-user/'.$token.'</a>
                </p>
                <p style="margin: 0 !important">
                Ti invieremo i codici di accesso all'."'".'appartamento il giorno del tuo arrivo tramite WhatsApp ed e-mail, assicurati di avere le informazioni di contatto corrette.
                </p>
                <br>
                <p style="margin: 0 !important">
                    Grazie per aver utilizzato la nostra applicazione!
                </p>
                ';
                return $temaplate;
                break;

            default:
                //en
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Thank you for booking at Hawkins Apartments!!
                </h3>

                <p style="margin: 0 !important">
                    Spanish legislation requires us to request your National Identity Document or your passport. It is mandatory that you provide it to us or you will not be able to stay in the apartment.
                </p>
                <p style="margin: 0 !important">
                    We leave you a link to fill out your information and provide us with a copy of your DNI or Passport:
                </p>
                <p>
                    <a class="btn btn-primary" href="https://crm.apartamentosalgeciras.com/dni-user/'.$token.'" target="_blank">https://crm.apartamentosalgeciras.com/dni-user/'.$token.'</a>
                </p>
                <p style="margin: 0 !important">
                    Thank you for using our application!We will send you the access codes to the apartment on the day of your arrival by WhatsApp and email, make sure you have the contact information correctly.
                </p>
                <br>
                <p style="margin: 0 !important">Thank you for using our application!</p>
                ';
                return $temaplate;
                break;
        }

    }

    public function clavesEmail($idioma, $cliente, $apartamento, $claveEntrada, $clavePiso, $edificio = 1){

        $enlace = $edificio == 1 ? 'https://goo.gl/maps/qb7AxP1JAxx5yg3N9' : 'https://maps.app.goo.gl/t81tgLXnNYxKFGW4A';

        switch ($idioma) {
            case 'es':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Gracias por reservar en los apartamentos Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                Hola '.$cliente.'!! La ubicación de los apartamentos es: <a class="btn btn-primary" href="'.$enlace.'">'.$enlace.'</a>.
                </p>
                <p style="margin: 0 !important">
                    Tu apartamento es el '.$apartamento.', los códigos para entrar al apartamento son: Para la puerta principal '.$claveEntrada.' y para la puerta de tu apartamento '.$clavePiso.'.
                </p>
                <p style="margin: 0 !important">
                    Espero que pases una estancia maravillosa.
                </p>
                <br>
                <p style="margin: 0 !important">Gracias por utilizar nuestra aplicación!</p>
                ';
                return $temaplate;
                break;

            case 'fr':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Merci de votre réservation chez les appartements Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                Bonjour '.$cliente.'!! L\'emplacement des appartements est: <a class="btn btn-primary" href="'.$enlace.'">'.$enlace.'</a>.
                </p>
                <p style="margin: 0 !important">
                    Votre appartement est le '.$apartamento.', les codes pour entrer dans l\'appartement sont : Pour la porte principale '.$claveEntrada.' et pour la porte de votre appartement '.$clavePiso.'.
                </p>
                <p style="margin: 0 !important">
                    J\'espère que vous passerez un séjour merveilleux.
                </p>
                <br>
                <p style="margin: 0 !important">Merci d\'utiliser notre application!</p>
                ';

                return $temaplate;
                break;

            case 'ar':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    شكرًا لك على حجزك في شقق هوكينز!!
                </h3>

                <p style="margin: 0 !important">
                مرحبًا '.$cliente.'!! موقع الشقق هو: <a class="btn btn-primary" href="'.$enlace.'">'.$enlace.'</a>.
                </p>
                <p style="margin: 0 !important">
                    شقتك هي '.$apartamento.'، رموز الدخول للشقة هي: للباب الرئيسي '.$claveEntrada.' ولباب شقتك '.$clavePiso.'.
                </p>
                <p style="margin: 0 !important">
                    أتمنى لك إقامة رائعة.
                </p>
                <br>
                <p style="margin: 0 !important">شكرًا لك على استخدام تطبيقنا!</p>
                ';
                return $temaplate;
                break;

            case 'de':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Danke für Ihre Buchung bei den Hawkins Apartments!!
                </h3>

                <p style="margin: 0 !important">
                Hallo '.$cliente.'!! Die Lage der Apartments ist: <a class="btn btn-primary" href="'.$enlace.'">'.$enlace.'</a>.
                </p>
                <p style="margin: 0 !important">
                    Ihr Apartment ist das '.$apartamento.', die Codes zum Betreten des Apartments sind: Für die Haupteingangstür '.$claveEntrada.' und für die Tür Ihrer Wohnung '.$clavePiso.'.
                </p>
                <p style="margin: 0 !important">
                    Ich hoffe, Sie haben einen wunderbaren Aufenthalt.
                </p>
                <br>
                <p style="margin: 0 !important">Danke, dass Sie unsere Anwendung nutzen!</p>
                ';
                return $temaplate;
                break;

            case 'pt_PT':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Obrigado por reservar nos apartamentos Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                    Olá '.$cliente.'!! A localização dos apartamentos é: <a class="btn btn-primary" href="'.$enlace.'">'.$enlace.'</a>.
                </p>
                <p style="margin: 0 !important">
                    "Seu apartamento é o '.$apartamento.', os códigos para entrar no apartamento são: Para a porta principal '.$claveEntrada.' e para a porta do seu apartamento '.$clavePiso.'."
                </p>
                <p style="margin: 0 !important">
                    Espero que tenha uma estadia maravilhosa.
                </p>
                <br>
                <p style="margin: 0 !important">Obrigado por utilizar nossa aplicação!</p>
                ';
                return $temaplate;
                break;

            case 'it':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Grazie per aver prenotato all'."'".'Hawkins Apartments!!
                </h3>

                <p style="margin: 0 !important">
                    Ciao  '.$cliente.'!! La posizione degli appartamenti è: <a class="btn btn-primary" href="'.$enlace.'">'.$enlace.'</a>.
                </p>
                <p style="margin: 0 !important">
                    "Il tuo appartamento è il '.$apartamento.', i codici per entrare nell ´ appartamento sono: per la porta principale '.$claveEntrada.' e per la porta del tuo appartamento '.$clavePiso.'."
                </p>
                <p style="margin: 0 !important">
                    Spero che tu abbia un soggiorno meraviglioso.
                </p>
                <br>
                <p style="margin: 0 !important">Grazie per aver utilizzato la nostra applicazione!</p>
                ';
                return $temaplate;
                break;

            default:
                //en
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Thank you for booking at Hawkins Apartments!!
                </h3>

                <p style="margin: 0 !important">
                    Hello  '.$cliente.'!! The location of the apartments is: <a class="btn btn-primary" href="'.$enlace.'">'.$enlace.'</a>.
                </p>
                <p style="margin: 0 !important">
                    Your apartment is '.$apartamento.', the codes to enter the apartment are: for the main door '.$claveEntrada.' and for the door of your apartment '.$clavePiso.'.
                </p>
                <p style="margin: 0 !important">
                    I hope you have a wonderful stay.
                </p>
                <br>
                <p style="margin: 0 !important">Thank you for using our application!</p>
                ';
                return $temaplate;
                break;
        }

    }

    public function clavesEmailAtico($idioma, $cliente, $apartamento, $claveEntrada, $clavePiso){

        switch ($idioma) {
            case 'es':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Gracias por reservar en los apartamentos Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                Hola '.$cliente.'!!
                </p>
                <p>
                Te indico que la entrada debes realizarla después de las 15 horas
                </p>
                <p>
                    La ubicación de los apartamentos es: <a class="btn btn-primary" href="https://goo.gl/maps/qb7AxP1JAxx5yg3N9" >Ir a google map</a></p>
                </p>
                <p style="margin: 0 !important">
                    Tu apartamento es el '.$apartamento.', los códigos para entrar al apartamento son: Para la puerta principal '.$claveEntrada.'.
                </p>
                <p>
                Tienes que subir a la 3 planta, ahi estará la caja con sus llaves, clave es '.$clavePiso.' debes de darle a la pestaña negra y ahí estarán las llaves.
                </p>
                <p style="margin: 0 !important">
                    Espero que pases una estancia maravillosa.
                </p>
                <br>
                <p style="margin: 0 !important">Gracias por utilizar nuestra aplicación!</p>
                ';
                return $temaplate;
                break;

            case 'fr':
                $temaplate = '
                <h3 style="color:#0F1739; text-align: center">
                    Merci de votre réservation chez les appartements Hawkins!!
                </h3>

                <p style="margin: 0 !important">
                Bonjour '.$cliente.'!! L\'emplacement des appartements est: <a class="btn btn-primary" href="https://goo.gl/maps/qb7AxP1JAxx5yg3N9">Allez sur Google Map</a>.
                </p>
                <p style="margin: 0 !important">
                    Votre appartement est le '.$apartamento.', les codes pour entrer dans l\'appartement sont : Pour la porte principale '.$claveEntrada.' et pour la porte de votre appartement '.$clavePiso.'.
                </p>
                <p style="margin: 0 !important">
                    J\'espère que vous passerez un séjour merveilleux.
                </p>
                <br>
                <p style="margin: 0 !important">Merci d\'utiliser notre application!</p>
                ';

                return $temaplate;
                break;

                case 'ar':
                    $temaplate = '
                    <h3 style="color:#0F1739; text-align: center">
                        شكرًا لحجزك في شقق هوكينز!!
                    </h3>

                    <p style="margin: 0 !important">
                    مرحبًا '.$cliente.'!!
                    </p>
                    <p>
                    يرجى ملاحظة أنه يمكنك تسجيل الدخول بعد الساعة 2 مساءً.
                    </p>
                    <p>
                        موقع الشقق هو: <a class="btn btn-primary" href="https://goo.gl/maps/qb7AxP1JAxx5yg3N9">اذهب إلى خريطة جوجل</a></p>
                    </p>
                    <p style="margin: 0 !important">
                        شقتك هي الرقم '.$apartamento.'، أكواد الدخول هي: للباب الرئيسي '.$claveEntrada.'.
                    </p>
                    <p>
                    عليك الصعود إلى الطابق الثالث، حيث ستجد صندوقًا يحتوي على المفاتيح، الرمز هو '.$clavePiso.'. اضغط على اللسان الأسود للوصول إلى المفاتيح.
                    </p>
                    <p style="margin: 0 !important">
                        أتمنى لك إقامة رائعة.
                    </p>
                    <br>
                    <p style="margin: 0 !important">شكرًا لاستخدامك تطبيقنا!</p>
                    ';
                    return $temaplate;
                    break;


                case 'de':
                    $temaplate = '
                    <h3 style="color:#0F1739; text-align: center">
                        Danke für Ihre Buchung bei den Hawkins Apartments!!
                    </h3>

                    <p style="margin: 0 !important">
                    Hallo '.$cliente.'!!
                    </p>
                    <p>
                    Bitte beachten Sie, dass der Check-in nach 15 Uhr möglich ist.
                    </p>
                    <p>
                        Die Lage der Apartments ist: <a class="btn btn-primary" href="https://goo.gl/maps/qb7AxP1JAxx5yg3N9">Gehen Sie zu Google Map</a></p>
                    </p>
                    <p style="margin: 0 !important">
                        Ihr Apartment ist das '.$apartamento.', die Zugangscodes sind: Für die Haupteingangstür '.$claveEntrada.'.
                    </p>
                    <p>
                    Sie müssen in die 3. Etage gehen, dort finden Sie eine Box mit den Schlüsseln, der Code ist '.$clavePiso.'. Bitte drücken Sie die schwarze Lasche, um die Schlüssel zu entnehmen.
                    </p>
                    <p style="margin: 0 !important">
                        Ich hoffe, Sie haben einen wunderbaren Aufenthalt.
                    </p>
                    <br>
                    <p style="margin: 0 !important">Vielen Dank für die Nutzung unserer App!</p>
                    ';
                    return $temaplate;
                    break;


                    case 'pt':
                        $temaplate = '
                        <h3 style="color:#0F1739; text-align: center">
                            Obrigado por reservar nos apartamentos Hawkins!!
                        </h3>

                        <p style="margin: 0 !important">
                        Olá '.$cliente.'!!
                        </p>
                        <p>
                        Por favor, note que o check-in é após as 15:00 horas.
                        </p>
                        <p>
                            A localização dos apartamentos é: <a class="btn btn-primary" href="https://goo.gl/maps/qb7AxP1JAxx5yg3N9">Vá para o mapa do Google</a></p>
                        </p>
                        <p style="margin: 0 !important">
                            Seu número de apartamento é '.$apartamento.', os códigos para entrar são: Para a porta principal '.$claveEntrada.'.
                        </p>
                        <p>
                        Você deve subir ao 3º andar, onde encontrará uma caixa com as chaves, o código é '.$clavePiso.'.
                        </p>
                        <p style="margin: 0 !important">
                            Espero que tenha uma estadia maravilhosa.
                        </p>
                        <br>
                        <p style="margin: 0 !important">Obrigado por usar nosso aplicativo!</p>
                        ';
                        return $temaplate;
                        break;


                        case 'it':
                            $temaplate = '
                            <h3 style="color:#0F1739; text-align: center">
                                Grazie per aver prenotato presso Hawkins Apartments!!
                            </h3>

                            <p style="margin: 0 !important">
                            Ciao '.$cliente.'!!
                            </p>
                            <p>
                            Ti ricordo che il check-in è possibile dopo le 15:00.
                            </p>
                            <p>
                                La posizione degli appartamenti è: <a class="btn btn-primary" href="https://goo.gl/maps/qb7AxP1JAxx5yg3N9">Vai su Google Map</a></p>
                            </p>
                            <p style="margin: 0 !important">
                                Il tuo appartamento è il numero '.$apartamento.', i codici per entrare sono: Per la porta principale '.$claveEntrada.'.
                            </p>
                            <p>
                            Devi salire al terzo piano, dove troverai una scatola con le chiavi, il codice è '.$clavePiso.'. Premi la linguetta nera per accedere alle chiavi.
                            </p>
                            <p style="margin: 0 !important">
                                Spero che tu abbia un soggiorno meraviglioso.
                            </p>
                            <br>
                            <p style="margin: 0 !important">Grazie per aver utilizzato la nostra applicazione!</p>
                            ';
                            return $temaplate;
                            break;


            default:
                //en
                    $temaplate = '
                    <h3 style="color:#0F1739; text-align: center">
                        Thank you for booking at Hawkins Apartments!!
                    </h3>

                    <p style="margin: 0 !important">
                    Hello '.$cliente.'!!
                    </p>
                    <p>
                    Please note that check-in is after 3 PM.
                    </p>
                    <p>
                        The location of the apartments is: <a class="btn btn-primary" href="https://goo.gl/maps/qb7AxP1JAxx5yg3N9">Go to google map</a></p>
                    </p>
                    <p style="margin: 0 !important">
                        Your apartment number is '.$apartamento.', the entry codes are: For the main door '.$claveEntrada.'.
                    </p>
                    <p>
                    You need to go up to the 3rd floor, where you will find a box with the keys, the code is '.$clavePiso.'. Please press the black tab to access the keys.
                    </p>
                    <p style="margin: 0 !important">
                        I hope you have a wonderful stay.
                    </p>
                    <br>
                    <p style="margin: 0 !important">Thank you for using our app!</p>
                    ';
                    return $temaplate;
                    break;
        }

    }

    /**
     * Genera el contenido HTML del email de despedida según el idioma del cliente.
     */
    public function despedidaEmailContenido($idioma, $nombre)
    {
        switch ($idioma) {
            case 'es':
                $template = '
                <h3 style="color:#0F1739; text-align: center">
                    Gracias por tu estancia en Hawkins Suite
                </h3>
                <p style="margin: 0 !important">
                    Hola ' . $nombre . ',
                </p>
                <p style="margin: 0 !important">
                    Ha sido un placer tenerte como huésped. Esperamos que hayas disfrutado de tu estancia en nuestros apartamentos.
                </p>
                <p style="margin: 0 !important">
                    Si tienes un momento, nos encantaría que nos dejases una valoración. Tu opinión nos ayuda a mejorar.
                </p>
                <p style="margin: 0 !important">
                    Esperamos verte de nuevo pronto. Un cordial saludo del equipo de Hawkins Suite.
                </p>
                ';
                return $template;

            case 'fr':
                $template = '
                <h3 style="color:#0F1739; text-align: center">
                    Merci pour votre séjour chez Hawkins Suite
                </h3>
                <p style="margin: 0 !important">
                    Bonjour ' . $nombre . ',
                </p>
                <p style="margin: 0 !important">
                    Ce fut un plaisir de vous accueillir. Nous espérons que vous avez apprécié votre séjour dans nos appartements.
                </p>
                <p style="margin: 0 !important">
                    Si vous avez un moment, nous serions ravis que vous nous laissiez un avis. Votre opinion nous aide à nous améliorer.
                </p>
                <p style="margin: 0 !important">
                    Nous espérons vous revoir bientôt. Cordialement, l\'équipe Hawkins Suite.
                </p>
                ';
                return $template;

            case 'de':
                $template = '
                <h3 style="color:#0F1739; text-align: center">
                    Vielen Dank für Ihren Aufenthalt bei Hawkins Suite
                </h3>
                <p style="margin: 0 !important">
                    Hallo ' . $nombre . ',
                </p>
                <p style="margin: 0 !important">
                    Es war uns eine Freude, Sie als Gast zu haben. Wir hoffen, dass Sie Ihren Aufenthalt in unseren Apartments genossen haben.
                </p>
                <p style="margin: 0 !important">
                    Wenn Sie einen Moment Zeit haben, würden wir uns über eine Bewertung freuen. Ihre Meinung hilft uns, uns zu verbessern.
                </p>
                <p style="margin: 0 !important">
                    Wir hoffen, Sie bald wiederzusehen. Mit freundlichen Grüßen, das Hawkins Suite Team.
                </p>
                ';
                return $template;

            default:
                // Inglés por defecto
                $template = '
                <h3 style="color:#0F1739; text-align: center">
                    Thank you for your stay at Hawkins Suite
                </h3>
                <p style="margin: 0 !important">
                    Hello ' . $nombre . ',
                </p>
                <p style="margin: 0 !important">
                    It was a pleasure having you as our guest. We hope you enjoyed your stay at our apartments.
                </p>
                <p style="margin: 0 !important">
                    If you have a moment, we would love for you to leave us a review. Your feedback helps us improve.
                </p>
                <p style="margin: 0 !important">
                    We hope to see you again soon. Best regards from the Hawkins Suite team.
                </p>
                ';
                return $template;
        }
    }

    public function enviarEmail( $correo, $vista, $data, $asunto, $token, ){

        // Validar email antes de enviar
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            \Illuminate\Support\Facades\Log::warning('Kernel: email no válido, no se envía', ['correo' => $correo]);
            return;
        }

        // 'emails.envioClavesEmail'

        try {
            Mail::to($correo)->send(new EnvioClavesEmail(
                $vista,
                $data,
                $asunto,
                $token
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al enviar email desde Kernel::enviarEmail', [
                'correo' => $correo,
                'asunto' => $asunto,
                'error' => $e->getMessage(),
            ]);

            // Notificación interna
            try {
                \App\Models\Notification::createForAdmins(
                    \App\Models\Notification::TYPE_SISTEMA,
                    'Error envío email',
                    "No se pudo enviar email a {$correo}: {$e->getMessage()}",
                    ['email' => $correo, 'asunto' => $asunto],
                    \App\Models\Notification::PRIORITY_MEDIUM,
                    \App\Models\Notification::CATEGORY_ERROR
                );
            } catch (\Exception $notifEx) {
                \Illuminate\Support\Facades\Log::error('No se pudo crear notificación de fallo email', ['error' => $notifEx->getMessage()]);
            }
        }

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

   /**
    * Envía las claves del apartamento por Channex cuando se crea una reserva nueva después de las 14:00
    * Método estático que puede ser llamado desde cualquier lugar
    *
    * @param Reserva $reserva
    * @return bool
    */
   public static function enviarClavesPorChannexSiEsNecesario($reserva)
   {
       try {
           // 🔄 Refrescar la reserva para obtener datos actualizados (especialmente dni_entregado)
           $reserva->refresh();

           // Solo procesar si:
           // 1. NO es de la web
           // 2. Tiene id_channex
           // 3. Es de hoy (fecha_entrada = hoy)
           // 4. Son más de las 14:00
           // 5. Tiene mensaje de bienvenida
           if ($reserva->origen === 'web' || empty($reserva->id_channex)) {
               return false;
           }

           $fechaHoy = Carbon::now()->format('Y-m-d');
           $horaActual = Carbon::now()->hour;

           // Verificar que sea de hoy
           if ($reserva->fecha_entrada != $fechaHoy) {
               return false;
           }

           // Verificar que sean más de las 14:00
           if ($horaActual < 14) {
               return false;
           }

           // Verificar que tenga mensaje de bienvenida
           $mensajeBienvenida = MensajeAuto::where('reserva_id', $reserva->id)
               ->where('categoria_id', 4)
               ->first();

           if (!$mensajeBienvenida) {
               Log::info('No se puede enviar claves por Channex: falta mensaje de bienvenida', [
                   'reserva_id' => $reserva->id
               ]);
               return false;
           }

           // Verificar que el DNI esté subido antes de enviar las claves
           if (empty($reserva->dni_entregado) || $reserva->dni_entregado != true) {
               Log::info('No se puede enviar claves por Channex: el DNI no ha sido subido', [
                   'reserva_id' => $reserva->id,
                   'dni_entregado' => $reserva->dni_entregado,
                   'dni_entregado_tipo' => gettype($reserva->dni_entregado)
               ]);
               return false;
           }

           // Verificar que no se hayan enviado ya las claves
           $mensajeClaves = MensajeAuto::where('reserva_id', $reserva->id)
               ->where('categoria_id', 3)
               ->first();

           if ($mensajeClaves) {
               Log::info('Las claves ya fueron enviadas por Channex', [
                   'reserva_id' => $reserva->id
               ]);
               return false;
           }

           // Cargar relaciones necesarias
           $reserva->load(['cliente', 'apartamento.edificioName']);

           if (!$reserva->apartamento) {
               Log::warning('No se puede enviar claves por Channex: apartamento no encontrado', [
                   'reserva_id' => $reserva->id
               ]);
               return false;
           }

           $clienteService = app(ClienteService::class);
           $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad ?? 'ES');

           // Preparar datos para el mensaje de claves
           $datosClaves = [
               'nombre' => $reserva->cliente->nombre ?? $reserva->cliente->alias,
               'apartamento' => $reserva->apartamento->titulo,
               'claveEntrada' => $reserva->apartamento->edificioName->clave ?? '',
               'clavePiso' => $reserva->apartamento->claves ?? '',
               'url' => $reserva->apartamento->edificio == 1
                   ? 'https://goo.gl/maps/qb7AxP1JAxx5yg3N9'
                   : 'https://maps.app.goo.gl/t81tgLXnNYxKFGW4A'
           ];

           // Crear mensaje de chat
           $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('claves', $datosClaves, $idiomaCliente);

           Log::info('Enviando claves por Channex para reserva nueva después de las 14:00', [
               'reserva_id' => $reserva->id,
               'id_channex' => $reserva->id_channex,
               'codigo_reserva' => $reserva->codigo_reserva,
               'origen' => $reserva->origen,
               'idioma' => $idiomaCliente,
               'hora_actual' => $horaActual
           ]);

           // Enviar al chat de Channex usando el id_channex
           $resultado = \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
               $mensajeChat,
               $reserva->id_channex
           );

           if ($resultado) {
               // Crear registro de mensaje enviado
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

               Log::info('Claves enviadas exitosamente por Channex para reserva nueva', [
                   'reserva_id' => $reserva->id,
                   'id_channex' => $reserva->id_channex,
                   'codigo_reserva' => $reserva->codigo_reserva
               ]);

               return true;
           } else {
               Log::error('Error al enviar claves por Channex para reserva nueva', [
                   'reserva_id' => $reserva->id,
                   'id_channex' => $reserva->id_channex,
                   'codigo_reserva' => $reserva->codigo_reserva
               ]);
               return false;
           }

       } catch (\Exception $e) {
           Log::error('Excepción al enviar claves por Channex para reserva nueva', [
               'reserva_id' => $reserva->id ?? null,
               'error' => $e->getMessage(),
               'trace' => $e->getTraceAsString(),
           ]);
           return false;
       }
   }

   /**
    * Envía las claves del apartamento por Channex usando la misma lógica que el comando
    *
    * @param Reserva $reserva
    * @param string $idiomaCliente
    * @param Apartamento $apartamentoReservado
    * @return bool
    */
   private function enviarClavesPorChannex($reserva, $idiomaCliente, $apartamentoReservado)
   {
       Log::info('🚀 MÉTODO enviarClavesPorChannex - Inicio', [
           'reserva_id' => $reserva->id,
           'codigo_reserva' => $reserva->codigo_reserva,
           'id_channex' => $reserva->id_channex,
           'idioma_cliente' => $idiomaCliente,
           'apartamento_id' => $apartamentoReservado->id ?? null
       ]);

       try {
           // Verificar que tenga id_channex
           Log::info('🔍 Verificando id_channex', [
               'reserva_id' => $reserva->id,
               'id_channex' => $reserva->id_channex,
               'id_channex_vacio' => empty($reserva->id_channex),
               'id_channex_tipo' => gettype($reserva->id_channex)
           ]);

           if (empty($reserva->id_channex)) {
               Log::warning('❌ No se puede enviar claves por Channex: falta id_channex', [
                   'reserva_id' => $reserva->id,
                   'codigo_reserva' => $reserva->codigo_reserva
               ]);
               return false;
           }

           // Verificar que tenga mensaje de bienvenida
           Log::info('🔍 Verificando mensaje de bienvenida', [
               'reserva_id' => $reserva->id
           ]);

           $mensajeBienvenida = MensajeAuto::where('reserva_id', $reserva->id)
               ->where('categoria_id', 4)
               ->first();

           if (!$mensajeBienvenida) {
               Log::warning('❌ No se puede enviar claves por Channex: falta mensaje de bienvenida', [
                   'reserva_id' => $reserva->id,
                   'codigo_reserva' => $reserva->codigo_reserva,
                   'categoria_buscada' => 4
               ]);
               return false;
           }

           Log::info('✅ Mensaje de bienvenida encontrado', [
               'reserva_id' => $reserva->id,
               'mensaje_bienvenida_id' => $mensajeBienvenida->id,
               'fecha_envio_bienvenida' => $mensajeBienvenida->fecha_envio ?? null
           ]);

           // Verificar que el DNI esté subido antes de enviar las claves
           Log::info('🔍 Verificando DNI entregado', [
               'reserva_id' => $reserva->id,
               'dni_entregado' => $reserva->dni_entregado,
               'dni_entregado_tipo' => gettype($reserva->dni_entregado),
               'dni_entregado_es_true' => $reserva->dni_entregado === true,
               'dni_entregado_es_1' => $reserva->dni_entregado == 1
           ]);

           if (empty($reserva->dni_entregado) || $reserva->dni_entregado != true) {
               Log::warning('❌ No se puede enviar claves por Channex: el DNI no ha sido subido', [
                   'reserva_id' => $reserva->id,
                   'codigo_reserva' => $reserva->codigo_reserva,
                   'dni_entregado' => $reserva->dni_entregado,
                   'dni_entregado_tipo' => gettype($reserva->dni_entregado)
               ]);
               return false;
           }

           Log::info('✅ DNI entregado verificado correctamente', [
               'reserva_id' => $reserva->id
           ]);

           // Preparar datos para el mensaje de claves
           $datosClaves = [
               'nombre' => $reserva->cliente->nombre ?? $reserva->cliente->alias,
               'apartamento' => $reserva->apartamento->titulo,
               'claveEntrada' => $reserva->apartamento->edificioName->clave ?? '',
               'clavePiso' => $reserva->apartamento->claves ?? '',
               'url' => $apartamentoReservado->edificio == 1
                   ? 'https://goo.gl/maps/qb7AxP1JAxx5yg3N9'
                   : 'https://maps.app.goo.gl/t81tgLXnNYxKFGW4A'
           ];

           // Crear mensaje de chat
           $mensajeChat = \App\Http\Controllers\WebhookController::crearMensajeChat('claves', $datosClaves, $idiomaCliente);

           Log::info('Enviando mensaje de claves por Channex desde Kernel', [
               'reserva_id' => $reserva->id,
               'id_channex' => $reserva->id_channex,
               'codigo_reserva' => $reserva->codigo_reserva,
               'origen' => $reserva->origen,
               'idioma' => $idiomaCliente,
               'datos_claves' => $datosClaves
           ]);

           // Enviar al chat de Channex usando el id_channex (booking ID de Channex)
           Log::info('📤 Llamando a enviarMensajeAutomaticoAChannex', [
               'reserva_id' => $reserva->id,
               'id_channex' => $reserva->id_channex,
               'mensaje_length' => strlen($mensajeChat),
               'mensaje_preview' => substr($mensajeChat, 0, 150)
           ]);

           $resultado = \App\Http\Controllers\WebhookController::enviarMensajeAutomaticoAChannex(
               $mensajeChat,
               $reserva->id_channex
           );

           Log::info('📥 RESULTADO de enviarMensajeAutomaticoAChannex', [
               'reserva_id' => $reserva->id,
               'id_channex' => $reserva->id_channex,
               'codigo_reserva' => $reserva->codigo_reserva,
               'resultado' => $resultado,
               'resultado_tipo' => gettype($resultado),
               'resultado_es_true' => $resultado === true,
               'resultado_es_false' => $resultado === false,
               'resultado_booleano' => $resultado === true ? 'true' : ($resultado === false ? 'false' : 'otro')
           ]);

           if ($resultado) {
               // Crear o actualizar registro de mensaje enviado
               Log::info('💾 Guardando registro de mensaje enviado en MensajeAuto', [
                   'reserva_id' => $reserva->id,
                   'categoria_id' => 3
               ]);

               $mensajeAuto = MensajeAuto::updateOrCreate(
                   [
                       'reserva_id' => $reserva->id,
                       'categoria_id' => 3, // Mensaje de claves
                   ],
                   [
                       'cliente_id' => $reserva->cliente_id,
                       'fecha_envio' => Carbon::now()
                   ]
               );

               Log::info('✅ Mensaje de claves enviado exitosamente por Channex desde Kernel', [
                   'reserva_id' => $reserva->id,
                   'id_channex' => $reserva->id_channex,
                   'codigo_reserva' => $reserva->codigo_reserva,
                   'mensaje_auto_id' => $mensajeAuto->id,
                   'fecha_envio' => $mensajeAuto->fecha_envio
               ]);

               return true;
           } else {
               Log::error('Error al enviar mensaje de claves por Channex desde Kernel', [
                   'reserva_id' => $reserva->id,
                   'id_channex' => $reserva->id_channex,
                   'codigo_reserva' => $reserva->codigo_reserva,
                   'resultado' => $resultado
               ]);
               return false;
           }

       } catch (\Exception $e) {
           Log::error('❌ EXCEPCIÓN en enviarClavesPorChannex', [
               'reserva_id' => $reserva->id ?? null,
               'codigo_reserva' => $reserva->codigo_reserva ?? null,
               'id_channex' => $reserva->id_channex ?? null,
               'error' => $e->getMessage(),
               'error_file' => $e->getFile(),
               'error_line' => $e->getLine(),
               'trace' => $e->getTraceAsString(),
           ]);
           return false;
       }
   }

   public function generateBudgetReference(Invoices $invoices)
   {
       // Obtener la fecha de salida de la reserva para usarla en la generación de la referencia
       $budgetCreationDate = $invoices->reserva->fecha_salida ?? now(); // Usar la fecha de salida de la reserva
       $datetimeBudgetCreationDate = new \DateTime($budgetCreationDate);

       // Formatear la fecha para obtener los componentes necesarios
       $year = $datetimeBudgetCreationDate->format('Y');
       $monthNum = $datetimeBudgetCreationDate->format('m');

       // [FIX 2026-04-17] Race condition: antes este metodo leia la ultima
       // referencia SIN lockForUpdate y sin transaccion. El cron everyMinute
       // que genera facturas al checkout podia arrancar dos procesos a la vez
       // y ambos leer reference_autoincrement=42, ambos guardar 43, y otros
       // llamadores que usan InvoicesController (con lock) terminaban saltando
       // bloques enteros (0042 -> 0179 observado en produccion 16/04/2026).
       // Ahora envolvemos en transaccion + lockForUpdate y ademas corroboramos
       // contra la tabla invoices para no colisionar con numeros ya usados.
       return \Illuminate\Support\Facades\DB::transaction(function () use ($year, $monthNum) {
           $latestReference = InvoicesReferenceAutoincrement::where('year', $year)
               ->where('month_num', $monthNum)
               ->orderBy('id', 'desc')
               ->lockForUpdate()
               ->first();

           $newReferenceAutoincrement = $latestReference ? $latestReference->reference_autoincrement + 1 : 1;

           // Comprobacion defensiva: si en la tabla invoices ya existe una
           // referencia MAS ALTA para ese mes/anio (porque se creo por otra
           // via o migracion), saltamos al siguiente valor para no colisionar.
           $maxInInvoices = Invoices::where('reference', 'like', $year . '/' . $monthNum . '/%')
               ->orderBy('reference', 'desc')
               ->lockForUpdate()
               ->value('reference');
           if ($maxInInvoices) {
               // reference tiene formato "YYYY/MM/NNNNNN" (o "RYYYY/MM/NNNNNN" para rectificativas)
               $cleaned = ltrim($maxInInvoices, 'R');
               $parts = explode('/', $cleaned);
               if (count($parts) === 3) {
                   $maxNumero = (int) $parts[2];
                   if ($maxNumero >= $newReferenceAutoincrement) {
                       $newReferenceAutoincrement = $maxNumero + 1;
                   }
               }
           }

           $formattedAutoIncrement = str_pad((string) $newReferenceAutoincrement, 6, '0', STR_PAD_LEFT);
           $reference = $year . '/' . $monthNum . '/' . $formattedAutoIncrement;

           $referenceToSave = new InvoicesReferenceAutoincrement([
               'reference_autoincrement' => $newReferenceAutoincrement,
               'year' => $year,
               'month_num' => $monthNum,
           ]);
           $referenceToSave->save();

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
   }

}
