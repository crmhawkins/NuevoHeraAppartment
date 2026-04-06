<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Services\MIRService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TestMIREnvio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mir:test-envio 
                            {--reserva_id= : ID de la reserva específica a enviar}
                            {--fecha= : Fecha de reserva a buscar (formato: Y-m-d, por defecto ayer)}
                            {--entorno=sandbox : Entorno a usar (sandbox o production)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el envío de una reserva a MIR (Ministerio del Interior)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PRUEBA DE ENVÍO A MIR ===');
        $this->newLine();

        // Obtener entorno
        $entorno = $this->option('entorno');
        if (!in_array($entorno, ['sandbox', 'production'])) {
            $this->error('El entorno debe ser "sandbox" o "production"');
            return 1;
        }

        // Establecer entorno temporalmente
        \App\Models\Setting::set('mir_entorno', $entorno, 'Entorno MIR para prueba');
        $this->info("Entorno configurado: {$entorno}");
        $this->newLine();

        // Buscar reserva
        $reserva = null;
        
        if ($reservaId = $this->option('reserva_id')) {
            $reserva = Reserva::with(['cliente', 'apartamento.edificio'])
                ->where('id', $reservaId)
                ->first();
            
            if (!$reserva) {
                $this->error("No se encontró la reserva con ID: {$reservaId}");
                return 1;
            }
        } else {
            // Buscar reserva de ayer o fecha especificada
            $fecha = $this->option('fecha') 
                ? Carbon::parse($this->option('fecha')) 
                : Carbon::yesterday();
            
            $this->info("Buscando reservas con fecha de entrada: {$fecha->format('Y-m-d')}");
            
            // Buscar reservas con huéspedes y datos completos
            $reservas = Reserva::with(['cliente', 'apartamento.edificio'])
                ->whereDate('fecha_entrada', $fecha->format('Y-m-d'))
                ->whereHas('cliente', function($query) {
                    $query->whereNotNull('num_identificacion')
                          ->where('num_identificacion', '!=', '');
                })
                ->whereHas('apartamento.edificio', function($query) {
                    $query->whereNotNull('codigo_establecimiento')
                          ->where('codigo_establecimiento', '!=', '');
                })
                ->orderBy('id', 'desc')
                ->get();
            
            // Filtrar reservas que tengan huéspedes con DNI
            $reservas = $reservas->filter(function($reserva) {
                $huespedes = \App\Models\Huesped::where('reserva_id', $reserva->id)
                    ->whereNotNull('numero_identificacion')
                    ->where('numero_identificacion', '!=', '')
                    ->count();
                return $huespedes > 0;
            });
            
            if ($reservas->isEmpty()) {
                $this->warn("No se encontraron reservas con huéspedes completos para la fecha: {$fecha->format('Y-m-d')}");
                $this->info("Buscando en un rango más amplio...");
                
                // Buscar en los últimos 7 días
                $reservas = Reserva::with(['cliente', 'apartamento.edificio'])
                    ->whereBetween('fecha_entrada', [
                        Carbon::now()->subDays(7)->format('Y-m-d'),
                        Carbon::now()->format('Y-m-d')
                    ])
                    ->whereHas('cliente', function($query) {
                        $query->whereNotNull('num_identificacion')
                              ->where('num_identificacion', '!=', '');
                    })
                    ->whereHas('apartamento.edificio', function($query) {
                        $query->whereNotNull('codigo_establecimiento')
                              ->where('codigo_establecimiento', '!=', '');
                    })
                    ->orderBy('fecha_entrada', 'desc')
                    ->orderBy('id', 'desc')
                    ->limit(10)
                    ->get();
                
                // Filtrar reservas que tengan huéspedes con DNI
                $reservas = $reservas->filter(function($reserva) {
                    $huespedes = \App\Models\Huesped::where('reserva_id', $reserva->id)
                        ->whereNotNull('numero_identificacion')
                        ->where('numero_identificacion', '!=', '')
                        ->count();
                    return $huespedes > 0;
                });
            }
            
            if ($reservas->isEmpty()) {
                $this->error('No se encontraron reservas con datos completos (cliente con DNI y huéspedes con DNI)');
                return 1;
            }
            
            // Mostrar lista de reservas encontradas
            $this->info("Se encontraron {$reservas->count()} reserva(s) con datos completos:");
            $this->newLine();
            
            $reservasArray = [];
            foreach ($reservas as $r) {
                $huespedesCount = \App\Models\Huesped::where('reserva_id', $r->id)->count();
                $reservasArray[] = [
                    'ID' => $r->id,
                    'Código' => $r->codigo_reserva ?? 'N/A',
                    'Cliente' => ($r->cliente->nombre ?? '') . ' ' . ($r->cliente->apellido1 ?? ''),
                    'DNI Cliente' => $r->cliente->num_identificacion ?? 'N/A',
                    'Huéspedes' => $huespedesCount,
                    'Fecha Entrada' => $r->fecha_entrada ? Carbon::parse($r->fecha_entrada)->format('Y-m-d') : 'N/A',
                ];
            }
            
            $this->table(
                ['ID', 'Código', 'Cliente', 'DNI Cliente', 'Huéspedes', 'Fecha Entrada'],
                $reservasArray
            );
            
            // Si hay más de una, preguntar cuál usar
            if ($reservas->count() > 1) {
                $reservaId = $this->ask('¿Qué reserva quieres usar? (ID)', $reservas->first()->id);
                $reserva = $reservas->firstWhere('id', $reservaId);
                
                if (!$reserva) {
                    $this->error("Reserva no encontrada");
                    return 1;
                }
            } else {
                $reserva = $reservas->first();
            }
        }

        // Validar que la reserva tenga los datos necesarios
        $this->newLine();
        $this->info("Reserva seleccionada:");
        $this->line("  ID: {$reserva->id}");
        $this->line("  Código: " . ($reserva->codigo_reserva ?? 'N/A'));
        $this->line("  Cliente: " . ($reserva->cliente->nombre ?? '') . ' ' . ($reserva->cliente->apellido1 ?? ''));
        $this->line("  DNI Cliente: " . ($reserva->cliente->num_identificacion ?? 'N/A'));
        $huespedesCount = \App\Models\Huesped::where('reserva_id', $reserva->id)->count();
        $this->line("  Huéspedes: " . $huespedesCount);
        $this->line("  Fecha Entrada: " . ($reserva->fecha_entrada ? Carbon::parse($reserva->fecha_entrada)->format('Y-m-d H:i') : 'N/A'));
        $this->line("  Fecha Salida: " . ($reserva->fecha_salida ? Carbon::parse($reserva->fecha_salida)->format('Y-m-d H:i') : 'N/A'));
        
        // Validar apartamento y edificio
        if (!$reserva->apartamento) {
            $this->error('La reserva no tiene apartamento asociado');
            return 1;
        }
        
        // Cargar explícitamente el edificio si no está cargado
        if (!$reserva->apartamento->relationLoaded('edificio')) {
            $reserva->apartamento->load('edificio');
        }
        
        // Obtener código de establecimiento (puede estar en el apartamento o en el edificio)
        $codigoEstablecimiento = null;
        
        // Primero intentar desde el apartamento directamente
        if (!empty($reserva->apartamento->codigo_establecimiento)) {
            $codigoEstablecimiento = $reserva->apartamento->codigo_establecimiento;
        }
        // Si no, intentar desde el edificio
        elseif ($reserva->apartamento->edificio && is_object($reserva->apartamento->edificio)) {
            $codigoEstablecimiento = $reserva->apartamento->edificio->codigo_establecimiento ?? null;
        }
        // Si el edificio es solo un ID, cargarlo explícitamente
        elseif ($reserva->apartamento->edificio_id) {
            $edificio = \App\Models\Edificio::find($reserva->apartamento->edificio_id);
            if ($edificio) {
                $codigoEstablecimiento = $edificio->codigo_establecimiento ?? null;
            }
        }
        
        if (empty($codigoEstablecimiento)) {
            $this->error('No se encontró código de establecimiento. Verifica que el apartamento o su edificio tengan el código configurado.');
            $this->line("  Apartamento ID: {$reserva->apartamento->id}");
            $this->line("  Edificio ID: " . ($reserva->apartamento->edificio_id ?? 'N/A'));
            return 1;
        }
        
        $this->line("  Código Establecimiento: {$codigoEstablecimiento}");
        $this->newLine();

        // Mostrar configuración MIR
        $config = \App\Models\Setting::whereIn('key', [
            'mir_codigo_arrendador',
            'mir_arrendador',
            'mir_codigo_establecimiento',
            'mir_usuario',
            'mir_aplicacion',
            'mir_entorno'
        ])->pluck('value', 'key');
        
        $this->info("Configuración MIR:");
        $this->line("  Código Arrendador: " . ($config['mir_codigo_arrendador'] ?? $config['mir_arrendador'] ?? 'NO CONFIGURADO'));
        $this->line("  Código Establecimiento: {$codigoEstablecimiento} (obtenido de la reserva)");
        $this->line("  Usuario: " . ($config['mir_usuario'] ?? 'NO CONFIGURADO'));
        $this->line("  Aplicación: " . ($config['mir_aplicacion'] ?? 'NO CONFIGURADO'));
        $this->line("  Entorno: " . ($config['mir_entorno'] ?? 'sandbox'));
        $this->newLine();

        // Confirmar envío
        if (!$this->confirm('¿Deseas enviar esta reserva a MIR?', true)) {
            $this->info('Envío cancelado');
            return 0;
        }

        // Enviar a MIR
        $this->info('Enviando reserva a MIR...');
        $this->newLine();

        try {
            $mirService = new MIRService();
            $resultado = $mirService->enviarReserva($reserva);

            $this->newLine();
            if ($resultado['success']) {
                $this->info('✓ ENVÍO EXITOSO');
                $this->line("  Estado: " . ($resultado['estado'] ?? 'N/A'));
                $this->line("  Código Referencia: " . ($resultado['codigo_referencia'] ?? 'N/A'));
                $this->line("  Mensaje: " . ($resultado['mensaje'] ?? 'N/A'));
                
                // Mostrar respuesta completa si está disponible
                if (!empty($resultado['respuesta_completa'])) {
                    $this->newLine();
                    $this->line("Respuesta completa:");
                    $this->line($resultado['respuesta_completa']);
                }
            } else {
                $this->error('✗ ERROR EN EL ENVÍO');
                $this->line("  Estado: " . ($resultado['estado'] ?? 'N/A'));
                $this->line("  Mensaje: " . ($resultado['mensaje'] ?? 'N/A'));
                
                if (!empty($resultado['respuesta_completa'])) {
                    $this->newLine();
                    $this->line("Respuesta completa:");
                    $this->line($resultado['respuesta_completa']);
                }
            }

            return $resultado['success'] ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('✗ EXCEPCIÓN: ' . $e->getMessage());
            $this->line("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
