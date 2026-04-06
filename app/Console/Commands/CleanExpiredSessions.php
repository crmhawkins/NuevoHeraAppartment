<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clean {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired session files to improve system stability';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('¿Estás seguro de que quieres limpiar las sesiones expiradas?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->info('Limpiando sesiones expiradas...');

        $sessionPath = storage_path('framework/sessions');
        $cleanedCount = 0;

        if (File::exists($sessionPath)) {
            $files = File::files($sessionPath);
            
            foreach ($files as $file) {
                $filePath = $file->getPathname();
                $lastModified = File::lastModified($filePath);
                $age = time() - $lastModified;
                
                // Eliminar archivos de sesión más antiguos de 8 horas
                if ($age > 28800) { // 8 horas en segundos
                    File::delete($filePath);
                    $cleanedCount++;
                }
            }
        }

        $this->info("Se limpiaron {$cleanedCount} archivos de sesión expirados.");
        
        // Limpiar también la caché de sesiones
        $this->call('cache:clear');
        $this->info('Caché de sesiones limpiada.');

        return 0;
    }
}
