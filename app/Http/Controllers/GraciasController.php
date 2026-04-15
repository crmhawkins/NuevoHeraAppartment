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
        try {
            $response_data = app(\App\Services\AIGatewayService::class)->chatCompletion([
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
            ]);

            return [
                'status' => 'ok',
                'messages' => $response_data,
            ];
        } catch (\Throwable $e) {
            $error = [
                'status' => 'error',
                'messages' => 'Error al realizar la solicitud: ' . $e->getMessage(),
            ];
            Storage::disk('local')->put('errorChapt.txt', $error['messages']);

            return response()->json($error);
        }
    }
}
