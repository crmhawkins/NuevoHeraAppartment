<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistorialDescuento;
use Carbon\Carbon;

class VerHistorialDescuentos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ver:historial-descuentos 
                            {--fecha= : Fecha específica para filtrar (formato: Y-m-d)}
                            {--estado= : Filtrar por estado (pendiente, aplicado, revertido, error)}
                            {--apartamento= : ID del apartamento para filtrar}
                            {--limit=10 : Número de registros a mostrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra el historial de descuentos aplicados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = $this->option('fecha');
        $estado = $this->option('estado');
        $apartamentoId = $this->option('apartamento');
        $limit = $this->option('limit');

        $this->info('📊 HISTORIAL DE DESCUENTOS APLICADOS');
        $this->line('');

        // Construir query
        $query = HistorialDescuento::with(['apartamento', 'tarifa', 'configuracionDescuento']);

        if ($fecha) {
            $query->where('fecha_aplicacion', $fecha);
            $this->info("📅 Filtrando por fecha: {$fecha}");
        }

        if ($estado) {
            $query->where('estado', $estado);
            $this->info("📋 Filtrando por estado: {$estado}");
        }

        if ($apartamentoId) {
            $query->where('apartamento_id', $apartamentoId);
            $this->info("🏠 Filtrando por apartamento ID: {$apartamentoId}");
        }

        $historial = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        if ($historial->isEmpty()) {
            $this->warn('❌ No se encontraron registros de descuentos');
            return;
        }

        $this->info("📈 Se encontraron {$historial->count()} registros:");
        $this->line('');

        foreach ($historial as $registro) {
            $this->mostrarRegistro($registro);
        }

        // Mostrar estadísticas
        $this->mostrarEstadisticas($query);
    }

    /**
     * Mostrar un registro individual
     */
    private function mostrarRegistro($registro)
    {
        $apartamento = $registro->apartamento;
        $tarifa = $registro->tarifa;
        $configuracion = $registro->configuracionDescuento;

        $this->line("🆔 ID: {$registro->id}");
        $this->line("🏠 Apartamento: {$apartamento->nombre}");
        $this->line("💰 Tarifa: {$tarifa->nombre} ({$registro->precio_original}€)");
        $this->line("📅 Fecha aplicación: {$registro->fecha_aplicacion->format('d/m/Y')}");
        $this->line("📅 Rango descuento: {$registro->rango_fechas}");
        $this->line("📊 Descuento: {$registro->porcentaje_formateado}");
        $this->line("💵 Precio con descuento: {$registro->precio_con_descuento}€");
        $this->line("📈 Días aplicados: {$registro->dias_aplicados}");
        $this->line("💸 Ahorro total: {$registro->ahorro_total}€");
        $this->line("📋 Estado: {$registro->estado_formateado}");
        
        if ($registro->observaciones) {
            $this->line("📝 Observaciones: {$registro->observaciones}");
        }
        
        // Mostrar datos del momento si están disponibles
        if ($registro->datos_momento) {
            $verificacion = $registro->verificarRequisitosCumplidos();
            $this->line("📊 DATOS DEL MOMENTO:");
            $this->line("   🏢 Edificio: " . ($registro->datos_momento['edificio']['nombre'] ?? 'N/A'));
            $this->line("   📅 Fecha análisis: " . ($registro->datos_momento['fecha_analisis'] ?? 'N/A'));
            $this->line("   📈 Ocupación: " . ($registro->datos_momento['ocupacion_actual'] ?? 'N/A') . "%");
            $this->line("   🎯 Acción: " . ($registro->datos_momento['accion'] ?? 'N/A'));
            $this->line("   ✅ Requisitos cumplidos: " . ($verificacion['cumplidos'] ? 'SÍ' : 'NO'));
            $this->line("   📝 Razón: " . $verificacion['razon']);
        }
        
        $this->line("🕒 Creado: {$registro->created_at->format('d/m/Y H:i:s')}");
        $this->line('');
    }

    /**
     * Mostrar estadísticas
     */
    private function mostrarEstadisticas($query)
    {
        $this->info('📊 ESTADÍSTICAS:');
        
        $totalRegistros = $query->count();
        $totalAplicados = $query->where('estado', 'aplicado')->count();
        $totalPendientes = $query->where('estado', 'pendiente')->count();
        $totalErrores = $query->where('estado', 'error')->count();
        $totalAhorro = $query->where('estado', 'aplicado')->sum('ahorro_total');

        $this->line("   📈 Total registros: {$totalRegistros}");
        $this->line("   ✅ Aplicados: {$totalAplicados}");
        $this->line("   ⏳ Pendientes: {$totalPendientes}");
        $this->line("   ❌ Errores: {$totalErrores}");
        $this->line("   💰 Ahorro total: {$totalAhorro}€");
    }
}
