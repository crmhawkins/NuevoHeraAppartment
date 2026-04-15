<?php

namespace App\Http\Controllers;

use App\Models\ChatGpt;
use App\Models\Cliente;
use App\Models\Configuraciones;
use App\Models\Mensaje;
use App\Models\MensajeAuto;
use App\Models\PromptAsistente;
use App\Models\Reparaciones;
use App\Models\Reserva;
use App\Models\Setting;
use App\Models\Whatsapp;
use App\Services\ClienteService;
use Carbon\Carbon;
use CURLFile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;
use libphonenumber\PhoneNumberFormat;
use Illuminate\Support\Facades\Log;
use Laravel\Prompts\Prompt;
use PhpOption\None;

class WhatsappController2 extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function hookWhatsapp(Request $request)
    {
        $responseJson = env('WHATSAPP_KEY', 'valorPorDefecto');

        $query = $request->all();
        $mode = $query['hub_mode'];
        $token = $query['hub_verify_token'];
        $challenge = $query['hub_challenge'];

        // Formatear la fecha y hora actual
        $dateTime = Carbon::now()->format('Y-m-d_H-i-s'); // Ejemplo de formato: 2023-11-13_15-30-25

        // Crear un nombre de archivo con la fecha y hora actual
        $filename = "hookWhatsapp_{$dateTime}.txt";

        Storage::disk('local')->put($filename, json_encode($request->all()));

        return response($challenge, 200)->header('Content-Type', 'text/plain');

    }

    public function processHookWhatsapp(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        // Crear carpeta si no existe
        if (!Storage::exists('whatsapp/json')) {
            Storage::makeDirectory('whatsapp/json');
        }

        // Generar nombre dinámico para el archivo
        $timestamp = now()->format('Ymd_His_u');
        $filename = "whatsapp/json/{$timestamp}.json"; // <- Solo el nombre del archivo es dinámico

        // Guardar contenido en storage/app/whatsapp/json/NOMBRE.json
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $tipo = $data['entry'][0]['changes'][0]['value']['messages'][0]['type'];

        if ($tipo == 'audio') {
            $this->audioMensaje($data);
        }elseif($tipo == 'image') {
            $this->imageMensaje($data);
        }else {
            $this->textMensaje($data);
        }

        return response(200)->header('Content-Type', 'text/plain');

    }

    public function audioMensaje( $data ){
        // $idMedia = $data['entry'][0]['changes'][0]['value']['messages'][0]['audio']['id'];
        // $phone = $data['entry'][0]['changes'][0]['value']['messages'][0]['from'];

        // Storage::disk('local')->put('audio-'.$idMedia.'.txt', json_encode($data) );

        // $url = str_replace('/\/', '/', $this->obtenerAudio($idMedia));

        // Storage::disk('local')->put('url-'.$idMedia.'.txt', $url );

        // $fileAudio = $this->obtenerAudioMedia($url,$idMedia);

        // // Storage::disk('local')->put('Conversion-'.$idMedia.'.txt', $fileAudio  );
        // $file = Storage::disk('public')->get( $idMedia.'.ogg');

        // $SpeechToText = $this->audioToText($file);


        // // if (isset(json_decode($SpeechToText)[0]['DisplayText'])) {
        // //     # code...
        // // }
        // Storage::disk('local')->put('phone-'.$idMedia.'.txt', $phone );

        // Storage::disk('local')->put('transcripcion-'.$idMedia.'.txt', $SpeechToText );

        // // $reponseChatGPT = $this->chatGpt($SpeechToText);
        // Storage::disk('local')->put('reponseChatGPT-'.$idMedia.'.txt', $reponseChatGPT );

        // $respuestaWhatsapp = $this->contestarWhatsapp($phone, $reponseChatGPT['messages']);
        // Storage::disk('local')->put('respuestaWhatsapp-'.$idMedia.'.txt', $respuestaWhatsapp );

        // $dataRegistrarChat = [
        //     'id_mensaje' => $data['entry'][0]['changes'][0]['value']['messages'][0]['id'],
        //     'remitente' => $data['entry'][0]['changes'][0]['value']['contacts'][0]['wa_id'],
        //     'mensaje' => $SpeechToText,
        //     'respuesta' => str_replace('"','',$reponseChatGPT['messages'] ),
        //     'status' => 1,
        //     'type' => 'audio'
        // ];
        // ChatGpt::create( $dataRegistrarChat );
    }

    public function imageMensaje( $data )
    {

        // Comprobamos si el Mensaje existe ya
        $mensajeExiste = ChatGpt::where('id_mensaje', $data['entry'][0]['changes'][0]['value']['messages'][0]['id'])->first();

        // Obetenemos el numero de Telefono
        $phone = $data['entry'][0]['changes'][0]['value']['messages'][0]['from'];
        Storage::disk('publico')->put('data-'.$phone.'.txt', json_encode($data) );

        // Comprobamos si existe algun cliente con ese telefono
        $cliente = Cliente::where('telefono', $phone)->first();
        // Si el cliente existe vamos a buscar una reserva que tenga
        if ($cliente != null) {
            // Reservas del cliente que nos ha escrito
            $idImg = $data['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'];
            $fileName = $this->descargarImage($idImg); // obtenemos el nombre de la imagen

            $respuestaImageChatGPT = $this->chatGptPruebasConImagen($fileName);

            Storage::disk('publico')->put('RespuestaChatSobreImagen-'.$idImg.'.txt', json_encode($respuestaImageChatGPT) );
            return true;



            $reservas = Reserva::where('cliente_id', $cliente->id)->get();
            // Comprobamos si existen reservas
            if (count($reservas) > 0) {
                foreach ($reservas as $reserva) {
                    $hoy = Carbon::now()->toDateString(); // Obtener solo la fecha de hoy (YYYY-MM-DD)
                    if ($reserva->fecha_entrada->toDateString() >= $hoy) {
                        $idImg = $data['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'];
                        $fileName = $this->descargarImage($idImg); // obtenemos el nombre de la imagen

                        $respuestaImageChatGPT = $this->chatGptPruebasConImagen($fileName);

                        Storage::disk('publico')->put('RespuestaChatSobreImagen-'.$idImg.'.txt', $respuestaImageChatGPT );
                        return true;
                        //    'nombre': nombre,
                        //    'apellido1': apellido1,
                        //    'apellido2': apellido2,
                        //    'nacionalidad': data['cliente']['nacionalidadCode'],
                        //    'nacionalidadStr': data['cliente']['nacionalidadStr'],
                        //    'tipoDocumento': data['cliente']['tipo_documento'],
                        //    'tipoDocumentoStr': data['cliente']['tipo_documento_str'],
                        //    'numIdentificacion': data['cliente']['num_identificacion'],
                        //    'fechaExpedicionDoc': datetime.strptime(data['cliente']['fecha_expedicion_doc'], '%Y-%m-%d').strftime('%d/%m/%Y'),
                        //    'dia': datetime.strptime(data['cliente']['fecha_nacimiento'], '%Y-%m-%d').day,
                        //    'mes': datetime.strptime(data['cliente']['fecha_nacimiento'], '%Y-%m-%d').month,
                        //    'ano': datetime.strptime(data['cliente']['fecha_nacimiento'], '%Y-%m-%d').year,
                        //    'fechaNacimiento': datetime.strptime(data['cliente']['fecha_nacimiento'], '%Y-%m-%d').strftime('%d/%m/%Y'),
                        //    'sexo': data['cliente']['sexo_str'],
                        //    'sexoStr': data['cliente']['sexo'],
                        //    'fechaEntrada': datetime.strptime(data['fecha_entrada'], '%Y-%m-%d').strftime('%d/%m/%Y'),

                    /*    {
                            "id":"chatcmpl-ABjhmK5e9ctr1oPPgo6EPvb5k0SUN",
                            "object":"chat.completion",
                            "created":1727360662,
                            "model":"gpt-4o-2024-05-13",
                            "choices":[
                                {
                                    "index":0,
                                    "message":{
                                        "role":"assistant",
                                        "content":
                                            "isDni": true,
                                            "isPasaporte": false,
                                            "informacion": {
                                                "nombre": "FILIPE ANDR\u00c9\",
                                                "apellido1": "JESUS",
                                                "apellido2": "CASTANHA",
                                                "fechaNacimiento": "15 06 1988",
                                                "fechaExpedicionDoc": "03 08 2031",
                                                "pais": "PORTUGAL",
                                                "numIdentificacion": "13379841",
                                                "value": "A9125AAAAA",
                                                "isEuropean": true,
                                                "sexo": "Masculino o Femenino",
                                                "nacionalidadStr": ,
                                                "nacionalidad": ,
                                                "tipoDocumento": ,
                                                "tipoDocumentoStr": ,
                                                "sexoStr": ,
                                                "dia": esto es sobre la fecha de nacimiento ,
                                                "mes": esto es sobre la fecha de nacimiento ,
                                                "ano": esto es sobre la fecha de nacimiento ,
                                            }
                                    },
                                    "refusal":null
                                },
                                    "logprobs":null,
                                    "finish_reason":"stop"
                                }
                            ],
                            "usage":
                                {
                                    "prompt_tokens":4866,
                                    "completion_tokens":135,
                                    "total_tokens":5001,
                                    "completion_tokens_details":{
                                        "reasoning_tokens":0
                                    }
                                },
                            "system_fingerprint":"fp_3537616b13"
                        } */

                        // if($respuestaImageChatGPT['isDni'] == true){
                        //     if($cliente->nombre == $respuestaImageChatGPT){
                        //         $cliente->nombre == null ? $cliente->nombre = $respuestaImageChatGPT['informacion']->nombre : '';
                        //         $cliente->apellido1 == null ? $cliente->apellido1 = $respuestaImageChatGPT['informacion']->apellido1 : '';
                        //         $cliente->apellido2 == null ? $cliente->apellido2 = $respuestaImageChatGPT['informacion']->apellido2 : '';
                        //         $cliente->nacionalidad == null ? $cliente->nacionalidad = $respuestaImageChatGPT['informacion']->nacionalidad : '';
                        //         $cliente->nombre == null ? $cliente->nombre = $respuestaImageChatGPT['informacion']->nombre : '';
                        //         $cliente->nombre == null ? $cliente->nombre = $respuestaImageChatGPT['informacion']->nombre : '';
                        //         $cliente->nombre == null ? $cliente->nombre = $respuestaImageChatGPT['informacion']->nombre : '';
                        //     }
                        // }elseif($respuestaImageChatGPT['isPasaporte'] == true){

                        // }


                        // $responseImage = '!';

                        // $dataRegistrarChat = [
                        //     'id_mensaje' => $data['entry'][0]['changes'][0]['value']['messages'][0]['id'],
                        //     'remitente' => $data['entry'][0]['changes'][0]['value']['contacts'][0]['wa_id'],
                        //     'mensaje' => $data['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'],
                        //     'respuesta' => $responseImage,
                        //     'status' => 1,
                        //     'type' => 'image'
                        // ];
                        // ChatGpt::create( $dataRegistrarChat );
                        //Storage::disk('local')->put( 'image-'.$fileName.'.txt', json_encode($data) );

                    }
                }
            }
        }else {

            $fileName = $this->descargarImageTemporal(null); // temporalWhatsapp/fileName.[jpg,png] obtenemos la ruta completa que esta en public
        }

        Storage::disk('local')->put('phone-Prueba.txt', json_encode($phone) );
        Storage::disk('local')->put('phone-mensaje.txt', json_encode($mensajeExiste) );

        if ($mensajeExiste == null) {

            $idMedia = $data['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'];

            Storage::disk('local')->put('image-'.$idMedia.'.txt', json_encode($data) );

            $descargarImage = $this->descargarImage($idMedia);
            Storage::disk('publico')->put('nombreImagen.txt', $descargarImage );

            $responseImage = 'Gracias!! recuerda que soy una inteligencia artificial y que no puedo ver lo que me has enviado pero mi supervisora María lo verá en el horario de 09:00 a 18:00 de Lunes a viernes. Si es tu DNI o Pasaporte es suficiente con enviármelo a mi. Mi supervisora lo recibirá. Muchas gracias!!';
            $respuestaImage = $this->chatGptPruebasConImagen($descargarImage);
            Storage::disk('publico')->put('lecturaImagen-'.$idMedia.'.txt', $respuestaImage );

            // $respuestaWhatsapp = $this->contestarWhatsapp($phone, $responseImage);

            $dataRegistrarChat = [
                'id_mensaje' => $data['entry'][0]['changes'][0]['value']['messages'][0]['id'],
                'remitente' => $data['entry'][0]['changes'][0]['value']['contacts'][0]['wa_id'],
                'mensaje' => $data['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'],
                'respuesta' => $responseImage,
                'status' => 1,
                'type' => 'image'
            ];
            ChatGpt::create( $dataRegistrarChat );
        }
    }

    public function obtenerImage($imageId)
    {
        // Suponiendo que tienes una URL base para obtener imágenes
        // $url = "https://api.whatsapp.com/v1/media/{$imageId}";
        $url = "https://graph.facebook.com/v20.0/{$imageId}/";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . Setting::whatsappToken()
        ])->get($url);

        if ($response->successful()) {
            Storage::disk('local')->put('image-response-url-response.txt', $response );

            $mediaUrl = $response->json()['url'];
            return $mediaUrl;
        }

        return null;
    }

    public function descargarImageTemporal($imageId)
    {
        // URL base para obtener imágenes de WhatsApp
        $url = "https://graph.facebook.com/v20.0/{$imageId}";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . Setting::whatsappToken()
        ])->get($url);

        if ($response->successful()) {
            $mediaUrl = $response->json()['url'];

            // Descargar el archivo de medios
            $mediaResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . Setting::whatsappToken()
            ])->get($mediaUrl);

            if ($mediaResponse->successful()) {
                $extension = explode('/', $mediaResponse->header('Content-Type'))[1];
                $filename = $imageId . '.' . $extension;
                Storage::disk('publico')->put('temporalWhatsapp/' . $filename, $mediaResponse->body());
                return 'temporalWhatsapp/'.$filename;
            }
        }

        return null;
    }

    public function descargarImage($imageId)
    {
        // URL base para obtener imágenes de WhatsApp
        $url = "https://graph.facebook.com/v20.0/{$imageId}";
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . Setting::whatsappToken()
        ])->get($url);

        if ($response->successful()) {
            $mediaUrl = $response->json()['url'];

            // Descargar el archivo de medios
            $mediaResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . Setting::whatsappToken()
            ])->get($mediaUrl);

            if ($mediaResponse->successful()) {
                $extension = explode('/', $mediaResponse->header('Content-Type'))[1];
                $filename = $imageId . '.' . $extension;
                Storage::disk('publico')->put('imagenesWhatsapp/' . $filename, $mediaResponse->body());
                return $filename;
            }
        }

        return null;
    }

    public function textMensaje( $data )
    {
        // Obtenemos la fecha actual de la peticion
        $fecha = Carbon::now()->format('Y-m-d_H-i-s');

        Storage::disk('local')->put('Mensaje_Texto_Reicibido-'.$fecha.'.txt', json_encode($data) );

        // Whatsapp::create(['mensaje' => json_encode($data)]);
        $id = $data['entry'][0]['changes'][0]['value']['messages'][0]['id'];
        $phone = $data['entry'][0]['changes'][0]['value']['messages'][0]['from'];
        $mensaje = $data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];

        $mensajeExiste = ChatGpt::where('id_mensaje', $id)->get();

        $prueba = $this->enviarMensajeOpenAiChatCompletions($id, $mensaje, $phone);
        // dd($mensaje);
        $respuestaWhatsapp = $this->contestarWhatsapp($phone, $prueba);

        // dd($prueba);
        return response()->json(['respuesta' => $prueba], 200);

        if (count($mensajeExiste) > 0) {
            return response()->json('Mensaje ya recibido', 200);
        }else {

            // $isAveria = $this->chatGpModelo($mensaje);
            // Storage::disk('local')->put( 'Contestacion del modelo-'.$fecha.'.txt', json_encode($isAveria) );


            $mensajesAnteriores = ChatGpt::where('remitente', $phone)
                ->latest() // Asegura que el mensaje más reciente sea seleccionado
                ->first();

            $dataRegistrar = [
                'id_mensaje' => $id,
                'id_three' => null,
                'remitente' => $phone,
                'mensaje' => $mensaje,
                'respuesta' => null,
                'status' => 1,
                'status_mensaje' => null,
                'type' => 'text',
                'date' => Carbon::now()
            ];
            $mensajeCreado = ChatGpt::create($dataRegistrar);

            // Enviar la question al asistente
            $reponseChatGPT = $this->chatGpt($mensaje, $id, $phone, $mensajeCreado->id);
            //$reponseChatGPT = $this->enviarMensajeAlAsistente(null, $mensaje);
            //dd($reponseChatGPT);
            $respuestaWhatsapp = $this->contestarWhatsapp($phone, $reponseChatGPT);

            if(isset($respuestaWhatsapp['error'])){
                dd($respuestaWhatsapp);
            };

            // $mensajeCreado->update([
            //     'respuesta'=> $reponseChatGPT
            // ]);

            return response()->json($reponseChatGPT)->header('Content-Type', 'json');

        }
    }


function enviarMensajeOpenAiChatCompletions($id, $nuevoMensaje, $remitente)
{
    $modelo = 'gpt-4o';
    $promptAsistente = PromptAsistente::first(); // o all()->first() si usas all()

    $promptSystem = [
        "role" => "system",
        "content" => $promptAsistente ? $promptAsistente->prompt : "No hay prompt configurado aún."
    ];


    // Guardar el mensaje del usuario
    ChatGpt::create([
        'id_mensaje' => $id,
        'remitente' => $remitente,
        'mensaje' => $nuevoMensaje,
        'respuesta' => null,
        'status' => 0, // por 'respondido'
        'date' => now()
    ]);

    // Historial: últimos 20 mensajes válidos (mensaje + respuesta)
    $historial = ChatGpt::where('remitente', $remitente)
        ->orderBy('date', 'desc')
        ->limit(20)
        ->get()
        ->reverse()
        ->flatMap(function ($chat) {
            $mensajes = [];

            if (!empty($chat->mensaje)) {
                $mensajes[] = [
                    "role" => "user",
                    "content" => $chat->mensaje,
                ];
            }

            if (!empty($chat->respuesta)) {
                $mensajes[] = [
                    "role" => "assistant",
                    "content" => $chat->respuesta,
                ];
            }

            return $mensajes;
        })
        ->toArray();

    // Añadir nuevo mensaje del usuario
    $historial[] = [
        "role" => "user",
        "content" => $nuevoMensaje,
    ];

    // Unir con prompt
    $mensajes = array_merge([$promptSystem], $historial);

    // Llamar a IA via gateway (OpenAI con fallback a Hawkins)
    try {
        $response = app(\App\Services\AIGatewayService::class)->chatCompletion([
            'model' => $modelo,
            'messages' => $mensajes,
            'temperature' => 0.7,
        ]);
    } catch (\Throwable $e) {
        return null;
    }

    $respuestaTexto = $response['choices'][0]['message']['content'] ?? null;
    if (!$respuestaTexto) {
        return null;
    }

    // Guardar la respuesta generada
    ChatGpt::where('remitente', $remitente)
        ->whereNull('respuesta')
        ->orderByDesc('created_at')
        ->limit(1)
        ->update([
            'respuesta' => $respuestaTexto,
            'status' => 1, // por 'respondido'
        ]);

    return $respuestaTexto;
}



    public function envioAutoVoz(Request $request)
    {

        $tipo = $request->tipo;

        // Leticia  y Saray

        if ($tipo == 1) {
            $manitas = Reparaciones::all();
            $mensaje = $request->mensaje;
            $phone = $request->phone;
            $enviarMensajeLimpiadora = $this->mensajesPlantillaNull( 'Leticia o Saray', $mensaje, $phone, '34633065237' );
            return response('Mensaje Enviado')->header('Content-Type', 'text/plain');

        } elseif ($tipo == 2){
            $manitas = Reparaciones::all();
            $mensaje = $request->mensaje;
            $phone = $request->phone;
            $enviarMensajeAverias = $this->mensajesPlantillaNull( $manitas[0]->nombre, $mensaje , $phone, $manitas[0]->telefono);
            return response('Mensaje Enviado')->header('Content-Type', 'text/plain');

        } elseif ($tipo == 3){
            $telefonos = [
                '34622440984',
                // '34664368232',
                // '34605621704'
            ];
            $origen = $request->origen;
            foreach ($telefonos as $key => $telefono) {
                $enviarMensajeAverias = $this->mensajesPlantillaAlerta( $telefono, $origen );
                # code...
            }
            return response('Mensaje Enviado')->header('Content-Type', 'text/plain');
        }
    }

    public function clasificarMensaje($mensaje)
    {
        try {
            $response_data = app(\App\Services\AIGatewayService::class)->chatCompletion([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente que clasifica mensajes en: "averia", "limpieza", "reserva_apartamento", o "otro".'],
                    ['role' => 'user', 'content' => $mensaje]
                ],
                'max_tokens' => 10
            ]);

            if (isset($response_data['choices'][0]['message']['content'])) {
                return trim(strtolower($response_data['choices'][0]['message']['content']));
            }
        } catch (\Throwable $e) {
            \Log::warning('clasificarMensaje fallo via AIGateway: ' . $e->getMessage());
        }

        return 'otro';
    }

    public function gestionarAveria($phone, $mensaje)
    {
        // Aquí podrías registrar la avería en la base de datos
        return "Hemos registrado tu avería. Nuestro equipo te contactará pronto.";
    }

    public function gestionarLimpieza($phone, $mensaje)
    {
        // Aquí podrías programar una limpieza en el sistema
        return "Hemos programado el servicio de limpieza. Te avisaremos cuando esté confirmado.";
    }

    public function gestionarReserva($phone, $mensaje)
    {
        // Aquí podrías consultar la disponibilidad y responder al usuario
        return "Por favor, indícanos la fecha y el apartamento que deseas reservar.";
    }

    public function procesarMensajeGeneral($mensaje, $id, $phone, $idMensaje)
    {
        return "Procesamiento del mensaje general";
        // Aquí iría tu código original para procesar la conversación con el asistente
    }

    public function chatGpt($mensaje, $id, $phone = null, $idMensaje)
    {
        //dd($id, $idMensaje, $mensaje, $phone);

        $categoria = $this->clasificarMensaje($mensaje);

        switch ($categoria) {
            case 'averia':
                return $this->gestionarAveria($phone, $mensaje);
            case 'limpieza':
                return $this->gestionarLimpieza($phone, $mensaje);
            case 'reserva_apartamento':
                return $this->gestionarReserva($phone, $mensaje);
            default:
                return $this->procesarMensajeGeneral($mensaje, $id, $phone, $idMensaje);
        }

        $existeHilo = ChatGpt::find($idMensaje);

        if ($existeHilo) {

            $fechaLimite = '2024-11-18 13:30:00';

            $mensajeAnterior = ChatGpt::where('remitente', $phone)
                ->where('created_at', '>=', $fechaLimite)
                ->get();

            if ($mensajeAnterior) {
                if (isset($mensajeAnterior[1])) {
                    # code...
                    if ($mensajeAnterior[1]->id_three == null) {
                        //dd($existeHilo);
                        $three_id = $this->crearHilo();
                        //dd($three_id);
                        $existeHilo->id_three = $three_id['id'];
                        $existeHilo->save();
                        $mensajeAnterior[1]->id_three = $three_id['id'];
                        $mensajeAnterior[1]->save();
                        //dd($existeHilo);
                    } else {
                        $three_id['id'] = $mensajeAnterior[1]->id_three;
                        //dd($three_id['id']);
                        $existeHilo->id_three = $mensajeAnterior[1]->id_three;
                        $existeHilo->save();
                        $three_id['id'] = $existeHilo->id_three;
                    }
                } else {
                    $three_id = $this->crearHilo();
                    //dd($three_id);
                    $existeHilo->id_three = $three_id['id'];
                    $existeHilo->save();
                    // $mensajeAnterior[1]->id_three = $three_id['id'];
                    // $mensajeAnterior[1]->save();
                }
                // Existe un mensaje válido posterior a la fecha límite
            } else {
                $three_id = $this->crearHilo();
                //dd($three_id);
                $existeHilo->id_three = $three_id['id'];
                $existeHilo->save();
                // $mensajeAnterior[1]->id_three = $three_id['id'];
                // $mensajeAnterior[1]->save();
            }

            $hilo = $this->mensajeHilo($three_id['id'], $mensaje);
            // Independientemente de si el hilo es nuevo o existente, inicia la ejecución
            $ejecuccion = $this->ejecutarHilo($three_id['id']);
            $ejecuccionStatus = $this->ejecutarHiloStatus($three_id['id'], $ejecuccion['id']);
            // Inicia un bucle para esperar hasta que el hilo se complete
            while (true) {
                //$ejecuccion = $this->ejecutarHilo($three_id['id']);

                if ($ejecuccionStatus['status'] === 'in_progress') {
                    // Espera activa antes de verificar el estado nuevamente
                    sleep(2); // Ajusta este valor según sea necesario

                    // Verifica el estado del paso actual del hilo
                    $pasosHilo = $this->ejecutarHiloISteeps($three_id['id'], $ejecuccion['id']);
                    if ($pasosHilo['data'][0]['status'] === 'completed') {
                        // Si el paso se completó, verifica el estado general del hilo
                        $ejecuccionStatus = $this->ejecutarHiloStatus($three_id['id'],$ejecuccion['id']);
                    }
                } elseif ($ejecuccionStatus['status'] === 'completed') {
                    // El hilo ha completado su ejecución, obtiene la respuesta final
                    $mensajes = $this->listarMensajes($three_id['id']);
                    //dd($mensajes);
                    if(count($mensajes['data']) > 0){
                        return $mensajes['data'][0]['content'][0]['text']['value'];
                    }
                } else {
                    // Maneja otros estados, por ejemplo, errores
                    //dd($ejecuccionStatus);
                    //return; // Sale del bucle si se encuentra un estado inesperado
                }

            }
        }
    }

    public function enviarMensajeAlAsistente($assistant_id = 'asst_KfPsIM26MjS662Vlq6h9WnuH', $mensaje)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = "https://api.openai.com/v2/assistants/".$assistant_id."/messages";

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];
        $body = json_encode([
            "input" => [
                "message" => $mensaje
            ]
        ]);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            return ['status' => 'error', 'message' => 'CURL error: ' . curl_error($curl)];
        }
        // Guardar la respuesta para seguimiento
        Storage::disk('local')->put('respuesta_asistente_00.txt', $response);
        return json_decode($response, true);
    }

    public function crearHilo()
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads';

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $response_data = json_decode($response, true);
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud: '.$response_data
            ];
            return $error;

        } else {
            $response_data = json_decode($response, true);
            Storage::disk('local')->put('Crear Hilo Prueba.txt', $response );
            return $response_data;
        }
    }

    public function recuperarHilo($id_thread)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread;

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
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

        } else {
            $response_data = json_decode($response, true);
            // Storage::disk('local')->put('Respuesta_Peticion_ChatGPT-'.$id.'.txt', $response );
            Storage::disk('local')->put('Recuperar Hilo Prueba.txt', $response );

            return $response_data;
        }
    }

    public function ejecutarHilo($id_thread){
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread.'/runs';

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );

        $body = [
            // "assistant_id" => 'asst_zYokKNRE98fbjUsKpkSzmU9Y'
            "assistant_id" => 'asst_KfPsIM26MjS662Vlq6h9WnuH'
        ];
        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($body));

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];

        } else {
            $response_data = json_decode($response, true);
            // Guardar la respuesta en un archivo con el storage
            Storage::disk('local')->put('Ejecutar Hilo Prueba.txt', $response);
            return $response_data;
        }
    }

    public function mensajeHilo($id_thread, $pregunta)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread.'/messages';

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );
        $body = [
            "role" => "user",
            "content" => $pregunta
        ];

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($body));


        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $response_data = json_decode($response, true);
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud: '.$response_data
            ];
            return $error;

        } else {
            $response_data = json_decode($response, true);
            //Storage::disk('local')->put('Respuesta_Peticion_ChatGPT-'.$id.'.txt', $response );
            Storage::disk('local')->put('Mensajes del Hilo Prueba.txt', $response );

            return $response_data;
        }
    }

    public function ejecutarHiloStatus($id_thread, $id_runs){
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'. $id_thread .'/runs/'.$id_runs;

        $headers = array(
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
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

        } else {
            $response_data = json_decode($response, true);
            return $response_data;
        }
    }

    public function ejecutarHiloISteeps($id_thread, $id_runs)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread. '/runs/' .$id_runs. '/steps';

        $headers = array(
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
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

        } else {
            $response_data = json_decode($response, true);
            return $response_data;
        }
    }

    public function listarMensajes($id_thread)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'. $id_thread .'/messages';

        $headers = array(
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if( $response === false ){
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];

        } else {
            $response_data = json_decode( $response, true );
            Storage::disk('local')->put('Listar Mensaes del Hilo Prueba.txt', $response );

            return $response_data;
        }
    }

	function asegurarSignoInterrogacion( $string ) {
		// Comprueba si el último carácter es ?
		if ( substr( $string, -1 ) !== '?' ) {
			// Si no lo es, añade ? al final
			$string .= '?';
		}
		return $string;
	}

    public function contestarWhatsapp($phone, $texto) {
        $token = Setting::whatsappToken();

        // Construir la carga útil como un array en lugar de un string JSON
        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "text",
            "text" => [
                "body" => $texto
            ]
        ];

        $urlMensajes = Setting::whatsappUrl();

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $urlMensajes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($mensajePersonalizado),  // Asegúrate de que mensajePersonalizado sea un array
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            Log::error("Error en cURL al enviar mensaje de WhatsApp: " . $error);
            return ['error' => $error];
        }
        curl_close($curl);

        try {
            $responseJson = json_decode($response, true);
            Storage::disk('local')->put("Respuesta_Envio_Whatsapp-{$phone}.txt", $response);
            return $responseJson;
        } catch (\Exception $e) {
            Log::error("Error al guardar la respuesta de WhatsApp: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function chatGptPruebasConImagen($imagenFilename)
    {
        // Cargar los JSON de paises y tipos desde la carpeta pública
        $paisesFilePath = public_path('paises.json');
        $tiposFilePath = public_path('tipos.json');

        $paisesData = json_decode(file_get_contents($paisesFilePath), true);
        $tiposData = json_decode(file_get_contents($tiposFilePath), true);

        // Leer la imagen y convertirla a base64
        $imagePath = public_path('imagenesWhatsapp/' . $imagenFilename);
        if (file_exists($imagePath)) {
            $imageData = file_get_contents($imagePath);
            $imageBase64 = 'data:image/jpeg;base64,' . base64_encode($imageData);
        } else {
            return response()->json(['error' => 'La imagen no se encuentra.']);
        }

        // Convertir los datos de países y tipos a texto JSON
        $paisesJsonText = json_encode($paisesData);
        $tiposJsonText = json_encode($tiposData);

        // Construir el contenido del mensaje que incluye la imagen en base64, paises y tipos de documento como texto
        $params = array(
            "model" => "gpt-4o",
            "messages" => [
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Analiza esta imagen y dime si es un DNI o pasaporte. Devuélveme solo un JSON con esta estructura: {isDni: true/false, isPasaporte: true/false, informacion: {nombre, apellido1, apellido2, fechaNacimiento, fechaExpedicionDoc, pais, numIdentificacion, value, isEuropean, mensaje}. En mensaje debes colocar tu respuesta para poder contestar al cliente. Aquí tienes información adicional sobre países y tipos de documentos:"
                        ],
                        [
                            "type" => "text",
                            "text" => "Paises: " . $paisesJsonText
                        ],
                        [
                            "type" => "text",
                            "text" => "Tipos: " . $tiposJsonText
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => $imageBase64
                            ]
                        ]
                    ]
                ]
            ]
        );

        try {
            $response_data = app(\App\Services\AIGatewayService::class)->chatCompletion($params);
            Storage::disk('publico')->put('RespuestaChatSobreImagenDirecto-'.$imagePath.'.txt', json_encode($response_data));
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Error al realizar la solicitud: ' . $e->getMessage()]);
        }

        // Procesar la respuesta para ajustar los campos adicionales
        if (!empty($response_data)) {
            $informacion = $response_data['informacion'] ?? [];

            // Agregar campos adicionales basados en los datos JSON y la lógica que mencionas:

            // Buscar la nacionalidad en el JSON de países
            $pais = $informacion['pais'] ?? '';
            if (!empty($paisesData[$pais])) {
                $informacion['nacionalidadStr'] = $pais;
                $informacion['nacionalidad'] = $paisesData[$pais]['value'] ?? '';
                $informacion['isEuropean'] = $paisesData[$pais]['isEuropean'] ?? false;
            }

            // Buscar el tipo de documento en el JSON de tipos de documento
            $tipoDocumento = $informacion['tipoDocumento'] ?? '';
            foreach ($tiposData as $tipo) {
                if ($tipo['codigo'] == $tipoDocumento) {
                    $informacion['tipoDocumentoStr'] = $tipo['descripcion'];
                    break;
                }
            }

            // Validar el sexo y convertirlo a "M" o "F"
            $sexo = $informacion['sexo'] ?? '';
            $informacion['sexoStr'] = ($sexo == 'Masculino') ? 'M' : 'F';

            // Procesar fecha de nacimiento (día, mes, año)
            if (!empty($informacion['fechaNacimiento'])) {
                $fecha = explode(' ', $informacion['fechaNacimiento']);
                if (count($fecha) === 3) {
                    $informacion['dia'] = $fecha[0];
                    $informacion['mes'] = $fecha[1];
                    $informacion['ano'] = $fecha[2];
                }
            }

            // Retornar la respuesta procesada
            return [
                'isDni' => $response_data['isDni'] ?? false,
                'isPasaporte' => $response_data['isPasaporte'] ?? false,
                'informacion' => $informacion
            ];
        }

        return response()->json(['status' => 'error', 'message' => 'No se recibió respuesta válida.']);
    }

    public function obtenerStringDNI($tipo)
    {
        switch ($tipo) {
            case 'D':
                return "DNI";
            case 'C':
                return "PERMISO CONDUCIR ESPAÑOL";
            case 'X':
                return "PERMISO DE RESIDENCIA DE ESTADO MIEMBRO DE LA UE";
            case 'N':
                return "NIE O TARJETA ESPAÑOLA DE EXTRANJEROS";
            case 'I':
                return "CARTA DE IDENTIDAD EXTRANJERA";
            case 'P':
                return "PASAPORTE";
            default:
                return "Desconocido";
        }
    }

    public function chatGpModelo( $texto )
    {
        $fecha = Carbon::now()->format('Y-m-d_H-i-s');

        $params = array(
            "model" => "gpt-4o",
            "messages" => [
                [
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => 'Analiza el contenido del mensaje recibido:
                            1. Si el mensaje contiene quejas sobre averías, fallos, roturas o mal funcionamiento (excluyendo problemas con WiFi y claves de acceso al apartamento):
                            - Devuelve "TRUE".

                            2. Si el mensaje es sobre problemas con WiFi o claves de acceso al apartamento:
                            - Devuelve "FALSE".

                            3. Si el mensaje trata sobre la limpieza o los suministros del apartamento (tales como toallas, papel higiénico, champú, etc.) y se refiere a incidencias (no ubicación o deseos de servicios adicionales):
                            - Devuelve "NULL".

                            4. Si el mensaje pregunta por la ubicación de los suministros o desea información sobre servicios adicionales de limpieza (por ejemplo, precios o solicitud de limpieza extra):
                            - Devuelve "FALSE".

                            5. Si el mensaje no está relacionado con ninguno de los temas anteriores:
                            - Devuelve "FALSE".

                            Recuerda: La respuesta debe ser "TRUE", "FALSE" o "NULL" en mayúsculas. No incluyas ningún otro tipo de respuesta.

                            Este es el mensaje: ' . $texto

                        ]
                    ]
                ]
            ]
        );
        Storage::disk('local')->put('Justo antes de enviar al modelo'.$fecha.'.txt', json_encode($texto) );

        try {
            $response_data = app(\App\Services\AIGatewayService::class)->chatCompletion($params);
            Storage::disk('local')->put('respuestaFuncionChaptParaReparaciones.txt', json_encode($response_data));

            $content = $response_data['choices'][0]['message']['content'] ?? '';
            Storage::disk('local')->put('respuestaFuncionChaptParaReparaciones.txt', $content);

            return $content;
        } catch (\Throwable $e) {
            $error = [
                'status' => 'error',
                'messages' => 'Error al realizar la solicitud: ' . $e->getMessage(),
            ];
            Storage::disk('local')->put('errorChapt.txt', $error['messages']);

            return response()->json($error);
        }
    }

    public function chatGptPruebas( $texto )
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        // Configurar los parámetros de la solicitud
        $url = 'https://api.openai.com/v1/completions';
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token
        );


        $data = array(
            "prompt" => $texto .' ->',
        // "model" => "davinci:ft-personal:apartamentos-hawkins-2023-04-27-09-45-29",
        // "model" => "davinci:ft-personal:modeloapartamentos-2023-05-24-16-36-49",
        // "model" => "davinci:ft-personal:apartamentosjunionew-2023-06-14-21-19-15",
        // "model" => "davinci:ft-personal:apartamento-junio-2023-07-26-23-23-07",
        // "model" => "davinci:ft-personal:apartamentosoctubre-2023-10-03-16-01-24",
        "model" => "davinci:ft-personal:apartamentos20octubre-2023-10-20-13-53-04",
        "temperature" => 0,
        "max_tokens"=> 200,
        "top_p"=> 1,
        "frequency_penalty"=> 0,
        "presence_penalty"=> 0,
        "stop"=> ["_END"]
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
            'messages' => $response_data['choices'][0]['text']
            ];
            Storage::disk('local')->put('respuestaFuncionChapt.txt', $responseReturn['messages'] );

            return $response_data;
        }
    }

    public function mensajesPlantillaNull($nombre, $mensaje, $telefono, $telefonoManitas, $idioma = 'es'){
        $token = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefonoManitas,
            "type" => "template",
            "template" => [
                "name" => 'reparaciones_null',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $nombre],
                            ["type" => "text", "text" => $mensaje],
                            ["type" => "text", "text" => $telefono],
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
                'Authorization: Bearer '.$token
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        return $response;

    }

    public function mensajesPlantillaAlerta($telefonoManitas, $origen, $idioma = 'en'){
        $token = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $telefonoManitas,
            "type" => "template",
            "template" => [
                "name" => 'averias_scrapping',
                "language" => ["code" => $idioma],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $origen],
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
                'Authorization: Bearer '.$token
            ),

        ));

        $response = curl_exec($curl);
        curl_close($curl);
        // $responseJson = json_decode($response);
        return $response;

    }

    // Vista de los mensajes
    public function whatsapp()
    {
        // $mensajes = ChatGpt::all();
        $mensajes = ChatGpt::orderBy('created_at', 'desc')->get();
        $resultado = [];
        foreach ($mensajes as $elemento) {

            //$remitenteSinPrefijo = (substr($elemento['remitente'], 0, 2) == "34") ? substr($elemento['remitente'], 2) : $elemento['remitente'];

			$remitenteSinPrefijo =$elemento['remitente'];
            // Busca el cliente cuyo teléfono coincide con el remitente del mensaje.
            $cliente = Cliente::where('telefono', '+'.$remitenteSinPrefijo)->first();

            // Si se encontró un cliente, añade su nombre al elemento del mensaje.
            if ($cliente) {
				if($cliente->nombre != ''){
                $elemento['nombre_remitente'] = $cliente->nombre . ' ' . $cliente->apellido1;
				}else {
					$elemento['nombre_remitente'] = $cliente->alias;
				}
            } else {
                // Si no se encuentra el cliente, puedes optar por dejar el campo vacío o asignar un valor predeterminado.
                $elemento['nombre_remitente'] = 'Desconocido';
            }

            $resultado[$elemento['remitente']][] = $elemento;


        }
        // dd($resultado);

        // var_dump(var_export($result, true));
        return view('whatsapp.index', compact('resultado'));
    }

}
