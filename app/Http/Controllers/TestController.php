<?php

namespace App\Http\Controllers;

use App\Models\ChatGpt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{

    public function chatGpt(Request $request)
    {
        $id = $request->input('id');
        $phone = $request->input('phone');
        $mensaje = $request->input('mensaje');
        $categoria = $this->clasificarMensaje($mensaje);

        switch ($categoria) {
            case 'averia':
                return $this->gestionarAveria($phone, $mensaje, $id);
            case 'limpieza':
                return $this->gestionarLimpieza($phone, $mensaje, $id);
            case 'reserva_apartamento':
                return $this->gestionarReserva($phone, $mensaje, $id);
            default:
                return $this->procesarMensajeGeneral($mensaje, $id, $phone,);
        }

    }

    public function clasificarMensaje($mensaje)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/chat/completions';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        $body = json_encode([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente que clasifica mensajes en: "averia", "limpieza", "reserva_apartamento", o "otro".'],
                ['role' => 'user', 'content' => $mensaje]
            ],
            'max_tokens' => 10
        ]);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($curl);
        curl_close($curl);

        $response_data = json_decode($response, true);

        if (isset($response_data['choices'][0]['message']['content'])) {
            return trim(strtolower($response_data['choices'][0]['message']['content']));
        }

        return 'otro';
    }

    public function gestionarAveria($phone, $mensaje, $idMensaje)
    {
        // Aquí podrías registrar la avería en la base de datos
        $respuestaAsistente = $this->chatGptAsistente($mensaje, $phone, $idMensaje, true);
        // dd($respuestaAsistente);
        return "Hemos registrado tu avería. Nuestro equipo te contactará pronto." . $respuestaAsistente;
    }

    public function gestionarLimpieza($phone, $mensaje, $idMensaje)
    {
        // Aquí podrías programar una limpieza en el sistema
        $respuestaAsistente = $this->chatGptAsistente($mensaje, $phone, $idMensaje, true);
        return "Hemos programado el servicio de limpieza. Te avisaremos cuando esté confirmado." . $respuestaAsistente;
    }

    public function gestionarReserva($phone, $mensaje, $idMensaje)
    {
        // Aquí podrías consultar la disponibilidad y responder al usuario
        $respuestaAsistente = $this->chatGptAsistente($mensaje, $phone, $idMensaje, true);

        return "Por favor, indícanos la fecha y el apartamento que deseas reservar." . $respuestaAsistente;
    }

    public function procesarMensajeGeneral($mensaje, $phone, $idMensaje)
    {
        $respuestaAsistente = $this->chatGptAsistente($mensaje, $phone, $idMensaje);

        return "Procesamiento del mensaje general" . $respuestaAsistente;
        // Aquí iría tu código original para procesar la conversación con el asistente
    }




    // ****** ASISTENTE DE OPEN AI ****** //

    // INTERACCION CON EL ASISTENTE DE OPEN AI
    public function chatGptAsistente($mensaje, $phone = null, $idMensaje, $status = false)
    {
        $existeHilo = ChatGpt::find($idMensaje);
        dd($existeHilo);

        // Obtén o crea el hilo
        $three_id = $this->obtenerOcrearHilo($existeHilo, $phone);

        // Enviar el mensaje del cliente al asistente
        $this->mensajeHilo($three_id, $mensaje, $status, $mensaje); // Enviar el mensaje al asistente

        // Manejar la ejecución del hilo
        return $this->manejarEjecucionHilo($three_id, $mensaje);

        //return "No se pudo encontrar el hilo.";
    }


    private function obtenerOcrearHilo($existeHilo, $phone)
    {
        $fechaLimite = '2024-11-18 13:30:00';
        $mensajeAnterior = ChatGpt::where('remitente', $phone)
            ->where('created_at', '>=', $fechaLimite)
            ->get();

        if ($mensajeAnterior && isset($mensajeAnterior[1])) {
            return $this->actualizarOcrearHilo($existeHilo, $mensajeAnterior[1]);
        } else {
            return $this->crearNuevoHilo($existeHilo);
        }
    }

    private function actualizarOcrearHilo($existeHilo, $mensajeAnterior)
    {
        if ($mensajeAnterior->id_three == null) {
            $three_id = $this->crearHilo();
            $existeHilo->id_three = $three_id['id'];
            $existeHilo->save();
            $mensajeAnterior->id_three = $three_id['id'];
            $mensajeAnterior->save();
        } else {
            $three_id['id'] = $mensajeAnterior->id_three;
            $existeHilo->id_three = $mensajeAnterior->id_three;
            $existeHilo->save();
        }
        return $three_id['id'];
    }

    private function crearNuevoHilo($existeHilo)
    {
        $three_id = $this->crearHilo();
        $existeHilo->id_three = $three_id['id'];
        $existeHilo->save();
        return $three_id['id'];
    }

    private function manejarEjecucionHilo($three_id, $mensaje)
    {
        // $hilo = $this->mensajeHilo($three_id, $mensaje);
        $ejecuccion = $this->ejecutarHilo($three_id);
        // dd($ejecuccion);
        $ejecuccionStatus = $this->ejecutarHiloStatus($three_id, $ejecuccion['id']);

        while (true) {
            if ($ejecuccionStatus['status'] === 'in_progress') {
                sleep(2);
                $pasosHilo = $this->ejecutarHiloISteeps($three_id, $ejecuccion['id']);
                if ($pasosHilo['data'][0]['status'] === 'completed') {
                    $ejecuccionStatus = $this->ejecutarHiloStatus($three_id, $ejecuccion['id']);
                }
            } elseif ($ejecuccionStatus['status'] === 'completed') {
                $mensajes = $this->listarMensajes($three_id);
                if (count($mensajes['data']) > 0) {
                    return $mensajes['data'][0]['content'][0]['text']['value'];
                }
            } else {
                return 'Error';
            }
        }
    }


    // ****** FUNCIONES DE HILOS ****** //

    // CREACION DE UN HILO
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
            return $response_data;
        }
    }

    // RECUPERACION DEL HILO SEGUN EL ID DEL HILO
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
            return $response_data;
        }
    }

    // EJECUTAR HILO
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

    public function mensajeHilo($id_thread, $pregunta, $status = false, $mensaje = null)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread.'/messages';

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v2"
        );

        // Prepara el cuerpo del mensaje
        $body = [];

        // Si el estado está activado, se envía un mensaje de estado
        if($status && $mensaje) {
            //dd($mensaje);
            $body[] = [
                "role" => "system",
                "content" => $mensaje
            ];
        }

        // Agregar el mensaje del usuario
        $body[] = [
            "role" => "user",
            "content" => $pregunta
        ];

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Verificar la respuesta
        if ($response === false) {
            return [
                'status' => 'error',
                'messages' => 'Error al realizar la solicitud'
            ];
        } else {
            $response_data = json_decode($response, true);
            // Aquí puedes guardar la respuesta o manejar lo que necesites
            return $response_data;
        }
    }




}
