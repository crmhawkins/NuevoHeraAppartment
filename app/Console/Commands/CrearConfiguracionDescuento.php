<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfiguracionDescuento;

class CrearConfiguracionDescuento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crear:configuracion-descuento 
                            {--porcentaje=20 : Porcentaje de descuento}
                            {--nombre= : Nombre de la configuración}
                            {--descripcion= : Descripción de la configuración}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea una configuración de descuento por defecto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $porcentaje = $this->option('porcentaje');
        $nombre = $this->option('nombre') ?: 'Descuento Temporada Baja';
        $descripcion = $this->option('descripcion') ?: 'Descuento automático para temporada baja cuando es viernes y hay días libres la semana siguiente';

        // Verificar si ya existe una configuración activa
        $configuracionExistente = ConfiguracionDescuento::activas()->first();
        
        if ($configuracionExistente) {
            if (!$this->confirm("Ya existe una configuración activa: '{$configuracionExistente->nombre}'. ¿Deseas crear una nueva?")) {
                $this->info('❌ Operación cancelada');
                return;
            }
        }

        // Crear nueva configuración
        $configuracion = ConfiguracionDescuento::create([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'porcentaje_descuento' => $porcentaje,
            'activo' => true,
            'condiciones' => [
                'dia_semana' => 'friday',
                'temporada' => 'baja',
                'dias_minimos_libres' => 1
            ]
        ]);

        $this->info('✅ Configuración de descuento creada exitosamente');
        $this->line('');
        $this->info('📋 Detalles de la configuración:');
        $this->line("   ID: {$configuracion->id}");
        $this->line("   Nombre: {$configuracion->nombre}");
        $this->line("   Descripción: {$configuracion->descripcion}");
        $this->line("   Descuento: {$configuracion->porcentaje_formateado}");
        $this->line("   Estado: " . ($configuracion->activo ? 'Activo' : 'Inactivo'));
        $this->line('');
        $this->info('💡 Ahora puedes usar el comando:');
        $this->line('   php artisan aplicar:descuentos-channex --dry-run');
        $this->line('   php artisan aplicar:descuentos-channex');
    }
}
