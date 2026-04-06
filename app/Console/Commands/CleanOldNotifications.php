<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:clean {--days=30 : Number of days to keep notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notifications from the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $threshold = now()->subDays($days);
        
        $this->info("Cleaning notifications older than {$days} days...");
        
        // Contar notificaciones a eliminar
        $count = Notification::where('created_at', '<', $threshold)->count();
        
        if ($count === 0) {
            $this->info('No old notifications found to clean.');
            return;
        }
        
        // Eliminar notificaciones antiguas
        $deleted = Notification::where('created_at', '<', $threshold)->delete();
        
        $this->info("Cleaned up {$deleted} old notifications.");
        
        // Log la limpieza
        Log::info("Old notifications cleaned", [
            'deleted_count' => $deleted,
            'days_threshold' => $days,
            'threshold_date' => $threshold->toDateTimeString()
        ]);
        
        // Mostrar estadísticas actuales
        $total = Notification::count();
        $unread = Notification::whereNull('read_at')->count();
        
        $this->line("Current notification stats:");
        $this->line("- Total notifications: {$total}");
        $this->line("- Unread notifications: {$unread}");
    }
}
