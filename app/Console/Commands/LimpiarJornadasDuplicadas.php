<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fichaje;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LimpiarJornadasDuplicadas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jornadas:limpiar {--user-id= : ID específico del usuario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia jornadas duplicadas y mantiene solo una jornada activa por usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Iniciando limpieza de jornadas duplicadas...');
        
        try {
            if ($this->option('user-id')) {
                $userId = $this->option('user-id');
                $this->limpiarJornadasUsuario($userId);
            } else {
                $this->limpiarTodasLasJornadas();
            }
            
            $this->info('✅ Limpieza de jornadas completada exitosamente');
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la limpieza: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function limpiarTodasLasJornadas()
    {
        $this->info('📊 Limpiando jornadas para todos los usuarios...');
        
        // Obtener todos los usuarios con jornadas activas
        $usuariosConJornadas = DB::table('fichajes')
            ->whereNull('hora_salida')
            ->select('user_id')
            ->distinct()
            ->get();
            
        $this->info('👥 Usuarios con jornadas activas: ' . $usuariosConJornadas->count());
        
        foreach ($usuariosConJornadas as $usuario) {
            $this->limpiarJornadasUsuario($usuario->user_id);
        }
    }
    
    private function limpiarJornadasUsuario($userId)
    {
        $this->info("👤 Procesando usuario ID: {$userId}");
        
        // Obtener todas las jornadas activas del usuario
        $jornadasActivas = Fichaje::where('user_id', $userId)
            ->whereNull('hora_salida')
            ->orderBy('hora_entrada', 'asc')
            ->get();
            
        if ($jornadasActivas->count() <= 1) {
            $this->info("   ✅ Usuario {$userId} tiene solo 1 jornada activa, no necesita limpieza");
            return;
        }
        
        $this->warn("   ⚠️  Usuario {$userId} tiene {$jornadasActivas->count()} jornadas activas");
        
        // Mantener solo la primera jornada (más antigua)
        $jornadaAMantener = $jornadasActivas->first();
        $jornadasAEliminar = $jornadasActivas->slice(1);
        
        $this->info("   📅 Manteniendo jornada ID: {$jornadaAMantener->id} (iniciada: {$jornadaAMantener->hora_entrada})");
        
        // Finalizar las jornadas duplicadas (establecer hora_salida)
        foreach ($jornadasAEliminar as $jornada) {
            $this->warn("   🗑️  Finalizando jornada duplicada ID: {$jornada->id} (iniciada: {$jornada->hora_entrada})");
            
            $jornada->hora_salida = $jornada->hora_entrada; // Mismo momento que se inició
            $jornada->save();
            
            $this->info("   ✅ Jornada ID {$jornada->id} finalizada");
        }
        
        $this->info("   🎯 Usuario {$userId} ahora tiene solo 1 jornada activa");
    }
}
