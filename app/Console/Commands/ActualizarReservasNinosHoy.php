<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Models\Apartamento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActualizarReservasNinosHoy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservas:actualizar-ninos-hoy {--force : Forzar actualización sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las reservas de hoy con información de niños desde Channex';

    /**
     * API Token de Channex
     */
    private $apiToken;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->apiToken = env('CHANNEX_TOKEN');
        
        if (!$this->apiToken) {
            $this->error('❌ Error: CHANNEX_TOKEN no configurado en .env');
            return 1;
        }

        $this->info('🚀 Iniciando actualización de reservas de hoy con información de niños...');
        
        // Obtener reservas de hoy
        $hoy = Carbon::today();
        $reservasHoy = Reserva::whereDate('fecha_entrada', $hoy)
            ->whereNotNull('id_channex')
            ->where('estado_id', '!=', 4) // Excluir canceladas
            ->get();

        if ($reservasHoy->isEmpty()) {
            $this->info('ℹ️  No hay reservas de hoy con ID de Channex para actualizar.');
            return 0;
        }

        $this->info("📅 Encontradas {$reservasHoy->count()} reservas de hoy para actualizar.");

        if (!$this->option('force')) {
            if (!$this->confirm('¿Deseas continuar con la actualización?')) {
                $this->info('❌ Operación cancelada por el usuario.');
                return 0;
            }
        }

        $bar = $this->output->createProgressBar($reservasHoy->count());
        $bar->start();

        $actualizadas = 0;
        $errores = 0;
        $sinCambios = 0;

        foreach ($reservasHoy as $reserva) {
            try {
                $resultado = $this->actualizarReservaNinos($reserva);
                
                if ($resultado === 'actualizada') {
                    $actualizadas++;
                } elseif ($resultado === 'sin_cambios') {
                    $sinCambios++;
                } else {
                    $errores++;
                }
            } catch (\Exception $e) {
                $errores++;
                Log::error('Error actualizando reserva con niños', [
                    'reserva_id' => $reserva->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info('📊 Resumen de la actualización:');
        $this->info("✅ Reservas actualizadas: {$actualizadas}");
        $this->info("ℹ️  Sin cambios: {$sinCambios}");
        $this->info("❌ Errores: {$errores}");

        if ($errores > 0) {
            $this->warn("⚠️  Se encontraron {$errores} errores. Revisa los logs para más detalles.");
        }

        $this->info('🎯 Actualización completada.');
        return 0;
    }

    /**
     * Actualiza la información de niños de una reserva específica
     */
    private function actualizarReservaNinos(Reserva $reserva)
    {
        try {
            // Obtener datos actualizados desde Channex
            $response = Http::withHeaders([
                'user-api-key' => $this->apiToken,
            ])->get("https://app.channex.io/api/v1/bookings/{$reserva->id_channex}");

            if (!$response->successful()) {
                $this->error("Error obteniendo datos de Channex para reserva {$reserva->id}: " . $response->status());
                return 'error';
            }

            $bookingData = $response->json()['data']['attributes'];
            
            // Buscar la habitación correspondiente
            $room = null;
            foreach ($bookingData['rooms'] as $r) {
                $fechaEntrada = is_string($reserva->fecha_entrada) ? $reserva->fecha_entrada : $reserva->fecha_entrada->format('Y-m-d');
                $fechaSalida = is_string($reserva->fecha_salida) ? $reserva->fecha_salida : $reserva->fecha_salida->format('Y-m-d');
                
                if ($r['checkin_date'] === $fechaEntrada &&
                    $r['checkout_date'] === $fechaSalida) {
                    $room = $r;
                    break;
                }
            }

            if (!$room) {
                $this->warn("No se encontró habitación correspondiente para reserva {$reserva->id}");
                return 'error';
            }

            // Calcular nuevos valores de niños
            $nuevoNumeroNinos = ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0);
            $nuevasEdadesNinos = $room['occupancy']['ages'] ?? [];
            $nuevasNotasNinos = $this->generarNotasNinos($room['occupancy']);

            // Verificar si hay cambios
            $hayCambios = false;
            $cambios = [];

            if ($reserva->numero_ninos != $nuevoNumeroNinos) {
                $cambios['numero_ninos'] = [
                    'anterior' => $reserva->numero_ninos,
                    'nuevo' => $nuevoNumeroNinos
                ];
                $hayCambios = true;
            }

            if ($reserva->edades_ninos != $nuevasEdadesNinos) {
                $cambios['edades_ninos'] = [
                    'anterior' => $reserva->edades_ninos,
                    'nuevo' => $nuevasEdadesNinos
                ];
                $hayCambios = true;
            }

            if ($reserva->notas_ninos != $nuevasNotasNinos) {
                $cambios['notas_ninos'] = [
                    'anterior' => $reserva->notas_ninos,
                    'nuevo' => $nuevasNotasNinos
                ];
                $hayCambios = true;
            }

            if ($hayCambios) {
                // Actualizar la reserva
                $reserva->update([
                    'numero_ninos' => $nuevoNumeroNinos,
                    'edades_ninos' => $nuevasEdadesNinos,
                    'notas_ninos' => $nuevasNotasNinos,
                    'updated_at' => now(),
                ]);

                // Log del cambio
                Log::info('Reserva actualizada con información de niños', [
                    'reserva_id' => $reserva->id,
                    'codigo_reserva' => $reserva->codigo_reserva,
                    'cambios' => $cambios,
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'cliente' => $reserva->cliente ? $reserva->cliente->alias : 'N/A'
                ]);

                return 'actualizada';
            } else {
                return 'sin_cambios';
            }

        } catch (\Exception $e) {
            Log::error('Error actualizando reserva con niños', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 'error';
        }
    }

    /**
     * Genera notas descriptivas sobre los niños en la reserva
     */
    private function generarNotasNinos($occupancy)
    {
        $notas = [];
        
        $totalNinos = ($occupancy['children'] ?? 0) + ($occupancy['infants'] ?? 0);
        
        if ($totalNinos > 0) {
            $notas[] = "Niños: {$totalNinos}";
            
            // Información específica sobre infants (bebés)
            if (isset($occupancy['infants']) && $occupancy['infants'] > 0) {
                $notas[] = "Bebés: {$occupancy['infants']}";
            }
            
            // Información específica sobre children (niños)
            if (isset($occupancy['children']) && $occupancy['children'] > 0) {
                $notas[] = "Niños mayores: {$occupancy['children']}";
            }
            
            if (isset($occupancy['ages']) && is_array($occupancy['ages'])) {
                $edades = [];
                foreach ($occupancy['ages'] as $edad) {
                    if ($edad <= 2) {
                        $edades[] = "bebé ({$edad} años)";
                    } elseif ($edad <= 12) {
                        $edades[] = "niño ({$edad} años)";
                    } else {
                        $edades[] = "adolescente ({$edad} años)";
                    }
                }
                $notas[] = "Edades: " . implode(', ', $edades);
            }
            
            // Información adicional sobre cunas si hay bebés
            if (isset($occupancy['ages']) && in_array(0, $occupancy['ages'])) {
                $notas[] = "Se requiere cuna para bebé";
            }
            
            // Información sobre camas adicionales si hay niños
            if ($totalNinos > 0) {
                $notas[] = "Se pueden proporcionar camas adicionales para niños";
            }
            
            // Información específica sobre infants
            if (isset($occupancy['infants']) && $occupancy['infants'] > 0) {
                $notas[] = "Consideraciones especiales para bebés";
            }
        }
        
        return !empty($notas) ? implode('. ', $notas) . '.' : null;
    }
}
