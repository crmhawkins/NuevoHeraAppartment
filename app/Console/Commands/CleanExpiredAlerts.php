<?php

namespace App\Console\Commands;

use App\Models\Alert;
use Illuminate\Console\Command;

class CleanExpiredAlerts extends Command
{
    protected $signature = 'alerts:clean-expired';
    protected $description = 'Limpiar alertas expiradas';

    public function handle()
    {
        $expiredAlerts = Alert::where('expires_at', '<', now())->delete();
        
        $this->info("Se eliminaron {$expiredAlerts} alertas expiradas.");
    }
}
