<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ProgramarActualizacionNinosDiaria extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservas:programar-actualizacion-ninos {--add-to-kernel : Añadir al Kernel para ejecución automática}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Programa la actualización automática diaria de información de niños en reservas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('⏰ Configurando actualización automática diaria de información de niños...');
        
        if ($this->option('add-to-kernel')) {
            $this->añadirAlKernel();
        } else {
            $this->mostrarInstrucciones();
        }

        return 0;
    }

    /**
     * Añade el comando al Kernel para ejecución automática
     */
    private function añadirAlKernel()
    {
        $this->info('🔧 Añadiendo comando al Kernel...');
        
        $kernelPath = app_path('Console/Kernel.php');
        $kernelContent = file_get_contents($kernelPath);
        
        // Verificar si ya está añadido
        if (strpos($kernelContent, 'ActualizarReservasNinosHoy') !== false) {
            $this->warn('⚠️  El comando ya está configurado en el Kernel.');
            return;
        }
        
        // Buscar la línea donde añadir el schedule
        if (strpos($kernelContent, 'schedule') !== false) {
            // Añadir después de la línea que contenga 'schedule'
            $nuevoKernelContent = str_replace(
                'protected function schedule(Schedule $schedule)',
                "protected function schedule(Schedule \$schedule)\n        {\n            // Actualizar información de niños en reservas de hoy\n            \$schedule->command('reservas:actualizar-ninos-hoy --force')\n                ->dailyAt('08:00')\n                ->withoutOverlapping()\n                ->runInBackground()\n                ->onSuccess(function () {\n                    Log::info('Actualización automática de niños completada exitosamente');\n                })\n                ->onFailure(function () {\n                    Log::error('Error en actualización automática de niños');\n                });\n        }",
                $kernelContent
            );
            
            if (file_put_contents($kernelPath, $nuevoKernelContent)) {
                $this->info('✅ Comando añadido al Kernel exitosamente.');
                $this->info('🕐 Se ejecutará automáticamente todos los días a las 8:00 AM.');
            } else {
                $this->error('❌ Error al escribir en el archivo Kernel.php');
            }
        } else {
            $this->error('❌ No se pudo encontrar la función schedule en Kernel.php');
        }
    }

    /**
     * Muestra instrucciones para configuración manual
     */
    private function mostrarInstrucciones()
    {
        $this->info('📋 INSTRUCCIONES PARA CONFIGURACIÓN MANUAL:');
        $this->newLine();
        
        $this->line('1. 📁 Abre el archivo: <comment>app/Console/Kernel.php</comment>');
        $this->line('2. 🔍 Busca la función <comment>schedule()</comment>');
        $this->line('3. 📝 Añade esta línea dentro de la función:');
        $this->newLine();
        
        $this->line('<comment>// Actualizar información de niños en reservas de hoy</comment>');
        $this->line('<comment>$schedule->command(\'reservas:actualizar-ninos-hoy --force\')</comment>');
        $this->line('<comment>    ->dailyAt(\'08:00\')</comment>');
        $this->line('<comment>    ->withoutOverlapping()</comment>');
        $this->line('<comment>    ->runInBackground()</comment>');
        $this->line('<comment>    ->onSuccess(function () {</comment>');
        $this->line('<comment>        Log::info(\'Actualización automática de niños completada exitosamente\');</comment>');
        $this->line('<comment>    })</comment>');
        $this->line('<comment>    ->onFailure(function () {</comment>');
        $this->line('<comment>        Log::error(\'Error en actualización automática de niños\');</comment>');
        $this->line('<comment>    });</comment>');
        
        $this->newLine();
        $this->line('4. 💾 Guarda el archivo');
        $this->line('5. 🚀 Ejecuta: <comment>php artisan schedule:work</comment> (para desarrollo)');
        $this->line('6. 🕐 En producción, configura un cron job para ejecutar: <comment>php artisan schedule:run</comment>');
        
        $this->newLine();
        $this->info('🔄 O ejecuta este comando con --add-to-kernel para configuración automática:');
        $this->line('<comment>php artisan reservas:programar-actualizacion-ninos --add-to-kernel</comment>');
    }
}
