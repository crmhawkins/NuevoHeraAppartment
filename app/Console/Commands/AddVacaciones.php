<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AddVacaciones extends Command
{
    protected $signature = 'vacacioner:add';
    protected $description = 'Añade vacaciones';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $value = 1.83;
        $value2 = 1.83;
        $adminUserId = 101;
        DB::update('UPDATE holidays SET quantity = quantity + CASE WHEN admin_user_id  = ? THEN ? ELSE ? END', [$adminUserId, $value2, $value]);

        $this->info('Comando completado: Vacaciones');
    }

}
