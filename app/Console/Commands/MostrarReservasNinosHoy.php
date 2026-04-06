<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MostrarReservasNinosHoy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservas:mostrar-ninos-hoy {--formato=table : Formato de salida (table, json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra las reservas de hoy con información de niños para las limpiadoras';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏠 Información de reservas de hoy con niños para el equipo de limpieza');
        $this->newLine();

        // Obtener reservas de hoy
        $hoy = Carbon::today();
        $reservasHoy = Reserva::with(['cliente', 'apartamento', 'estado'])
            ->whereDate('fecha_entrada', $hoy)
            ->where('estado_id', '!=', 4) // Excluir canceladas
            ->orderBy('fecha_entrada')
            ->get();

        if ($reservasHoy->isEmpty()) {
            $this->info('ℹ️  No hay reservas de hoy.');
            return 0;
        }

        $this->info("📅 Fecha: {$hoy->format('d/m/Y')} - Total de reservas: {$reservasHoy->count()}");
        $this->newLine();

        // Filtrar reservas con niños
        $reservasConNinos = $reservasHoy->filter(function ($reserva) {
            return $reserva->numero_ninos > 0;
        });

        $reservasSinNinos = $reservasHoy->filter(function ($reserva) {
            return $reserva->numero_ninos == 0;
        });

        // Mostrar resumen
        $this->info("👶 Reservas CON niños: {$reservasConNinos->count()}");
        $this->info("👥 Reservas SIN niños: {$reservasSinNinos->count()}");
        $this->newLine();

        $formato = $this->option('formato');

        if ($formato === 'json') {
            $this->mostrarFormatoJson($reservasHoy);
        } elseif ($formato === 'csv') {
            $this->mostrarFormatoCsv($reservasHoy);
        } else {
            $this->mostrarFormatoTable($reservasHoy);
        }

        // Mostrar información especial para limpiadoras
        if ($reservasConNinos->isNotEmpty()) {
            $this->newLine();
            $this->info('🔍 INFORMACIÓN ESPECIAL PARA LIMPIEZA:');
            $this->newLine();
            
            foreach ($reservasConNinos as $reserva) {
                $this->mostrarInformacionLimpieza($reserva);
            }
        }

        return 0;
    }

    /**
     * Muestra la información en formato tabla
     */
    private function mostrarFormatoTable($reservas)
    {
        $headers = [
            'ID', 'Apartamento', 'Cliente', 'Entrada', 'Salida', 'Adultos', 'Niños', 'Edades', 'Notas', 'Estado'
        ];

        $rows = [];
        foreach ($reservas as $reserva) {
            $edades = is_array($reserva->edades_ninos) ? implode(', ', $reserva->edades_ninos) : 'N/A';
            $notas = $reserva->notas_ninos ?: 'Sin notas';
            
            $rows[] = [
                $reserva->id,
                $reserva->apartamento ? $reserva->apartamento->nombre : 'N/A',
                $reserva->cliente ? $reserva->cliente->alias : 'N/A',
                is_string($reserva->fecha_entrada) ? $reserva->fecha_entrada : $reserva->fecha_entrada->format('d/m'),
                is_string($reserva->fecha_salida) ? $reserva->fecha_salida : $reserva->fecha_salida->format('d/m'),
                $reserva->numero_personas,
                $reserva->numero_ninos,
                $edades,
                substr($notas, 0, 50) . (strlen($notas) > 50 ? '...' : ''),
                $reserva->estado ? $reserva->estado->nombre : 'N/A'
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Muestra la información en formato JSON
     */
    private function mostrarFormatoJson($reservas)
    {
        $datos = [];
        foreach ($reservas as $reserva) {
            $datos[] = [
                'id' => $reserva->id,
                'apartamento' => $reserva->apartamento ? $reserva->apartamento->nombre : null,
                'cliente' => $reserva->cliente ? $reserva->cliente->alias : null,
                'fecha_entrada' => is_string($reserva->fecha_entrada) ? $reserva->fecha_entrada : $reserva->fecha_entrada->format('Y-m-d'),
                'fecha_salida' => is_string($reserva->fecha_salida) ? $reserva->fecha_salida : $reserva->fecha_salida->format('Y-m-d'),
                'numero_personas' => $reserva->numero_personas,
                'numero_ninos' => $reserva->numero_ninos,
                'edades_ninos' => $reserva->edades_ninos,
                'notas_ninos' => $reserva->notas_ninos,
                'estado' => $reserva->estado ? $reserva->estado->nombre : null
            ];
        }

        $this->line(json_encode($datos, JSON_PRETTY_PRINT));
    }

    /**
     * Muestra la información en formato CSV
     */
    private function mostrarFormatoCsv($reservas)
    {
        $headers = [
            'ID', 'Apartamento', 'Cliente', 'Entrada', 'Salida', 'Adultos', 'Niños', 'Edades', 'Notas', 'Estado'
        ];

        $this->line(implode(',', $headers));

        foreach ($reservas as $reserva) {
            $edades = is_array($reserva->edades_ninos) ? implode(';', $reserva->edades_ninos) : 'N/A';
            $notas = str_replace(',', ';', $reserva->notas_ninos ?: 'Sin notas');
            
            $row = [
                $reserva->id,
                $reserva->apartamento ? $reserva->apartamento->nombre : 'N/A',
                $reserva->cliente ? $reserva->cliente->alias : 'N/A',
                is_string($reserva->fecha_entrada) ? $reserva->fecha_entrada : $reserva->fecha_entrada->format('Y-m-d'),
                is_string($reserva->fecha_salida) ? $reserva->fecha_salida : $reserva->fecha_salida->format('Y-m-d'),
                $reserva->numero_personas,
                $reserva->numero_ninos,
                $edades,
                $notas,
                $reserva->estado ? $reserva->estado->nombre : 'N/A'
            ];

            $this->line(implode(',', $row));
        }
    }

    /**
     * Muestra información especial para limpieza
     */
    private function mostrarInformacionLimpieza($reserva)
    {
        $this->line("🏠 <info>Apartamento: " . ($reserva->apartamento ? $reserva->apartamento->nombre : 'N/A') . "</info>");
        $this->line("👤 Cliente: " . ($reserva->cliente ? $reserva->cliente->alias : 'N/A'));
        $this->line("📅 Entrada: " . (is_string($reserva->fecha_entrada) ? $reserva->fecha_entrada : $reserva->fecha_entrada->format('d/m/Y')));
        $this->line("👶 Niños: <comment>{$reserva->numero_ninos}</comment>");
        
        if (is_array($reserva->edades_ninos) && !empty($reserva->edades_ninos)) {
            $edades = [];
            foreach ($reserva->edades_ninos as $edad) {
                if ($edad <= 2) {
                    $edades[] = "bebé ({$edad} años)";
                } elseif ($edad <= 12) {
                    $edades[] = "niño ({$edad} años)";
                } else {
                    $edades[] = "adolescente ({$edad} años)";
                }
            }
            $this->line("🎂 Edades: <comment>" . implode(', ', $edades) . "</comment>");
        }
        
        if ($reserva->notas_ninos) {
            $this->line("📝 Notas: <comment>{$reserva->notas_ninos}</comment>");
        }
        
        // Recomendaciones específicas para limpieza
        $this->line("🧹 <question>Recomendaciones de limpieza:</question>");
        
        if (is_array($reserva->edades_ninos)) {
            if (in_array(0, $reserva->edades_ninos)) {
                $this->line("   • Prestar atención especial a superficies bajas (bebés gatean)");
                $this->line("   • Verificar que no haya objetos pequeños o peligrosos");
            }
            
            if (array_filter($reserva->edades_ninos, function($edad) { return $edad <= 5; })) {
                $this->line("   • Limpiar a fondo áreas de juego y dormitorios");
                $this->line("   • Verificar enchufes y seguridad");
            }
            
            if (array_filter($reserva->edades_ninos, function($edad) { return $edad > 12; })) {
                $this->line("   • Limpiar áreas de estudio si las hay");
                $this->line("   • Verificar equipos electrónicos");
            }
        }
        
        $this->newLine();
    }
}
