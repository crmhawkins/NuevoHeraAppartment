<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanPhoneNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:phonenumbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean phone numbers in the clients table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $batchSize = 100; // Número de registros a procesar por lote
        do {
            $clients = DB::table('clientes')
                ->where('telefono', 'like', '%+%')
                ->orWhere('telefono', 'like', '% %')
                ->limit($batchSize)
                ->get();

            foreach ($clients as $client) {
                $cleanedPhone = str_replace(['+', ' '], '', $client->telefono);
                DB::table('clientes')
                    ->where('id', $client->id)
                    ->update(['telefono' => $cleanedPhone]);
            }
        } while ($clients->count() > 0);

        $this->info('Phone numbers cleaned successfully.');

        return 0;
    }
}
