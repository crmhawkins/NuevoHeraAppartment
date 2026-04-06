<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old log files to prevent disk space issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        $logPath = storage_path('logs');
        
        $this->info("Cleaning log files older than {$days} days...");
        
        if (!File::exists($logPath)) {
            $this->error('Log directory does not exist: ' . $logPath);
            return 1;
        }
        
        $deletedFiles = 0;
        $deletedSize = 0;
        
        $files = File::glob($logPath . '/*.log');
        
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(File::lastModified($file));
            
            if ($fileTime->lt($cutoffDate)) {
                $fileSize = File::size($file);
                File::delete($file);
                $deletedFiles++;
                $deletedSize += $fileSize;
                
                $this->line("Deleted: " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");
            }
        }
        
        $this->info("Cleanup completed!");
        $this->info("Deleted {$deletedFiles} files");
        $this->info("Freed " . $this->formatBytes($deletedSize) . " of disk space");
        
        return 0;
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
