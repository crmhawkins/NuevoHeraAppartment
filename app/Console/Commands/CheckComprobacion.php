<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Comprobacion;
use App\Models\Setting;
use Carbon\Carbon;

class CheckComprobacion extends Command
{
    protected $signature = 'check:comprobacion';
    protected $description = 'Check the last record of Comprobacion and execute mensajesAutomaticos if needed';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lastComprobacion = Comprobacion::latest()->first();

        if ($lastComprobacion) {
            $createdAt = Carbon::parse($lastComprobacion->created_at);
            $now = Carbon::now();
            $diffInMinutes = $createdAt->diffInMinutes($now);

            if ($diffInMinutes > 500) {
                $telefonos = [
                    'Ivan' => '34605621704',
                    'Elena' => '34664368232',
                    'Africa' => '34655659573',
                    'David' => '34622440984'
                ];

                foreach($telefonos as $telefono){

                    $this->mensajesAutomaticos($lastComprobacion->created_at, $telefono);
                }
            }
        }
    }

    public function mensajesAutomaticos($fecha, $telefono, $idioma = 'es')
    {
    
        $tokenEnv = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefono,
            "type" => "template",
            "template" => [
                "name" => 'error',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $fecha],
                        ],
                    ],
                ],
            ],
        ];

        $urlMensajes = Setting::whatsappUrl();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlMensajes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($mensajePersonalizado),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$tokenEnv
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        return $response;
    }
}
