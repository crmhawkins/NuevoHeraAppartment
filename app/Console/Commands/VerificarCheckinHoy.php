<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Reserva;
use App\Services\WhatsappNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarCheckinHoy extends Command
{
    protected $signature = 'checkin:verificar-hoy';

    protected $description = 'Verifica que las reservas de hoy tienen todo preparado para el check-in';

    public function __construct(
        private readonly WhatsappNotificationService $whatsappNotificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $hoy = Carbon::today()->toDateString();

        Log::info('checkin:verificar-hoy: iniciando verificación', ['fecha' => $hoy]);

        // Obtener reservas con entrada hoy que no estén canceladas (estado_id != 4)
        $reservas = Reserva::whereDate('fecha_entrada', $hoy)
            ->where('estado_id', '!=', 4)
            ->with(['cliente', 'apartamento.edificio'])
            ->get();

        if ($reservas->isEmpty()) {
            Log::info('checkin:verificar-hoy: No hay reservas con entrada hoy');
            $this->info('No hay reservas con entrada hoy.');
            return Command::SUCCESS;
        }

        $this->info("Reservas con entrada hoy: {$reservas->count()}");

        $reservasConProblemas = [];

        foreach ($reservas as $reserva) {
            $problemas = $this->verificarReserva($reserva);

            if (!empty($problemas)) {
                $reservasConProblemas[] = [
                    'reserva' => $reserva,
                    'problemas' => $problemas,
                ];
            }
        }

        if (empty($reservasConProblemas)) {
            Log::info('checkin:verificar-hoy: Todas las reservas de hoy OK');
            $this->info('Todas las reservas de hoy están correctas.');
            return Command::SUCCESS;
        }

        // Construir mensaje de alerta
        $mensaje = $this->construirMensaje($reservasConProblemas, $hoy, $reservas->count());

        $this->warn($mensaje);

        // Enviar alerta por WhatsApp
        $this->enviarWhatsApp($mensaje);

        // Crear notificación interna para administradores
        $this->crearNotificacionInterna($reservasConProblemas, $hoy, $reservas->count());

        Log::info('checkin:verificar-hoy: alerta enviada', [
            'reservas_con_problemas' => count($reservasConProblemas),
            'total_entradas' => $reservas->count(),
        ]);

        return Command::SUCCESS;
    }

    /**
     * Verificar una reserva y devolver la lista de problemas encontrados.
     */
    private function verificarReserva(Reserva $reserva): array
    {
        $problemas = [];
        $apartamento = $reserva->apartamento;
        $cliente = $reserva->cliente;

        // 1. Sin código en cerradura (solo si el apartamento tiene cerradura digital)
        if ($apartamento && !empty($apartamento->ttlock_lock_id)) {
            if (empty($reserva->codigo_enviado_cerradura)) {
                $problemas[] = 'Sin código en cerradura';
            }
        }

        // 2. Sin datos del huésped (DNI no entregado)
        if (empty($reserva->dni_entregado)) {
            $problemas[] = 'Datos del huésped no rellenados';
        }

        // 3. Sin código de acceso generado
        if (empty($reserva->codigo_acceso)) {
            $problemas[] = 'Sin código de acceso generado';
        }

        // 4. Cliente sin teléfono
        if ($cliente) {
            $sinTelefono = empty($cliente->telefono) && empty($cliente->telefono_movil);
            if ($sinTelefono) {
                $problemas[] = 'Cliente sin teléfono';
            }
        }

        // 5. Cliente sin email
        if ($cliente && empty($cliente->email)) {
            $problemas[] = 'Cliente sin email';
        }

        // 6. MIR no enviado (solo si el edificio tiene MIR activo)
        if ($apartamento && $apartamento->edificio && $apartamento->edificio->mir_activo) {
            if (empty($reserva->mir_enviado)) {
                $problemas[] = 'MIR no enviado';
            }
        }

        return $problemas;
    }

    /**
     * Construir el mensaje de alerta en español.
     */
    private function construirMensaje(array $reservasConProblemas, string $fecha, int $totalEntradas): string
    {
        $lineas = [];
        $lineas[] = "⚠️ ALERTA CHECK-IN HOY ({$fecha})";
        $lineas[] = '';

        foreach ($reservasConProblemas as $item) {
            $reserva = $item['reserva'];
            $problemas = $item['problemas'];

            $apartamentoNombre = $reserva->apartamento->titulo ?? 'Sin apartamento';
            $clienteNombre = $reserva->cliente->alias ?? 'Sin cliente';

            $lineas[] = "🔴 Reserva #{$reserva->id} - {$apartamentoNombre} - {$clienteNombre}:";

            foreach ($problemas as $problema) {
                $lineas[] = "- {$problema}";
            }

            $lineas[] = '';
        }

        $totalConProblemas = count($reservasConProblemas);
        $lineas[] = "Total: {$totalConProblemas} reservas con incidencias de {$totalEntradas} entradas hoy.";

        return implode("\n", $lineas);
    }

    /**
     * Enviar alerta por WhatsApp a los destinatarios configurados.
     */
    private function enviarWhatsApp(string $mensaje): void
    {
        try {
            $this->whatsappNotificationService->sendToConfiguredRecipients($mensaje);
            Log::info('checkin:verificar-hoy: alerta WhatsApp enviada correctamente');
        } catch (\Throwable $e) {
            Log::error('checkin:verificar-hoy: error enviando WhatsApp', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crear notificación interna para administradores.
     */
    private function crearNotificacionInterna(array $reservasConProblemas, string $fecha, int $totalEntradas): void
    {
        $totalConProblemas = count($reservasConProblemas);

        $title = "Alerta Check-in Hoy ({$fecha})";
        $message = "{$totalConProblemas} reservas con incidencias de {$totalEntradas} entradas hoy.";

        // Preparar datos con detalle de cada reserva problemática
        $data = [
            'fecha' => $fecha,
            'total_entradas' => $totalEntradas,
            'total_con_problemas' => $totalConProblemas,
            'detalle' => collect($reservasConProblemas)->map(function ($item) {
                return [
                    'reserva_id' => $item['reserva']->id,
                    'apartamento' => $item['reserva']->apartamento->titulo ?? null,
                    'cliente' => $item['reserva']->cliente->alias ?? null,
                    'problemas' => $item['problemas'],
                ];
            })->toArray(),
        ];

        try {
            Notification::createForAdmins(
                Notification::TYPE_SISTEMA,
                $title,
                $message,
                $data,
                Notification::PRIORITY_CRITICAL,
                Notification::CATEGORY_WARNING
            );
            Log::info('checkin:verificar-hoy: notificación interna creada');
        } catch (\Throwable $e) {
            Log::error('checkin:verificar-hoy: error creando notificación interna', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
