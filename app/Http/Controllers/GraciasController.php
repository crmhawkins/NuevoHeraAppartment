<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class GraciasController extends Controller
{
    //
    public function index($idioma){
        // Validar y establecer el idioma
        $idiomasPermitidos = ['es', 'en', 'fr', 'de', 'it', 'pt'];
        if (!in_array($idioma, $idiomasPermitidos)) {
            $idioma = 'es';
        }
        
        // Establecer el locale de la aplicación
        App::setLocale($idioma);
        session(['locale' => $idioma]);
        session()->save();
        
        Log::info('Mostrando página de gracias', [
            'idioma' => $idioma,
            'locale' => App::getLocale()
        ]);

        return view('gracias', compact('idioma'));
    }
    public function contacto(){
        return view('contacto');
    }

    public function chatGpt($texto) 
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        // Configurar los parámetros de la solicitud
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token
        );


        $data = array(
            "messages" => [
                [
                    "role" => "user",
                    'content' => $texto
                ]
            ], 
            "model" => "gpt-4-1106-preview",
            "temperature" => 0,
            "max_tokens" => 1000,
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0,
            "stop" => ["_END"]
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];
            Storage::disk('local')->put('errorChapt.txt', $error['messages'] );

            return response()->json( $error );

        } else {
            $response_data = json_decode($response, true);
            $responseReturn = [
            'status' => 'ok',
            //    'messages' => $response_data['choices'][0]['text']
            'messages' => $response_data
            ];
            //  Storage::disk('local')->put('respuestaFuncionChapt.txt', $responseReturn );

            return $responseReturn;
        }
    }
}
