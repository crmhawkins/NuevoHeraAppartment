<?php
namespace App\Services;

use App\Models\Reserva;
use Illuminate\Support\Facades\Storage;
use OpenAI;
use OpenAI\Client;

class ChatGptService
{
    protected $client;
    protected $filePath;
    protected $phoneNumber;

    public function __construct()
    {
        // Inicializa el cliente con la clave API
        $this->client = OpenAI::client(env('TOKEN_OPENAI'));
    }

    protected function getFilePath()
    {
        return __DIR__ . '/' . $this->phoneNumber . '.json';
    }
    
    public function enviarMensajeAsistente($mensaje, $phone, $user = null)
    {
        $this->phoneNumber = $phone;
        $this->filePath = $this->getFilePath();
    
        // Guardar la pregunta en el archivo
        $this->logMessage($mensaje, true);
    
        // Recuperar el contexto de mensajes previos
        $context = $this->getContext();
    
        // Instrucciones para el asistente
        // Leer las instrucciones desde el archivo de texto
        try {
            if (Storage::exists('instrucciones.txt')) {
                $instrucciones = Storage::get('instrucciones.txt');
            } else {
                // Si el archivo no existe, lanzamos una excepción
                throw new \Exception("El archivo de instrucciones no se encuentra.");
            }
        } catch (\Exception $e) {
            return "Lo siento, ocurrió un error al cargar las instrucciones: " . $e->getMessage();
        }
    
        try {
            // Asegurarte de que el contenido sea siempre una cadena o un array
            $mensajeFormateado = is_array($mensaje) ? json_encode($mensaje) : (string) $mensaje;
           //dd($mensajeFormateado);

            // Enviar el mensaje al asistente con el contexto
            $response = $this->client->chat()->create([
                'model' => 'gpt-4',
                'messages' => array_merge($context, [
                    [
                        'role' => 'system',
                        'content' => $instrucciones
                    ],
                    [
                        'role' => $user != null ? $user : 'user',
                        'content' => $mensajeFormateado,  // Asegurarte de que sea una cadena
                    ],
                ]),
            ]);
            // dd()
            // Procesar la respuesta del asistente
            return $this->parseResponse($response);
    
        } catch (\Exception $e) {
            return "Lo siento, ocurrió un error al procesar tu solicitud: " . $e->getMessage();
        }
    }
    

    protected function parseResponse($response)
    {
        // Extraer el mensaje de la respuesta del asistente
        if (isset($response['choices'][0]['message']['content'])) {
            $responseChatJson = $response['choices'][0]['message']['content'];

            // Guardar la respuesta en el archivo
            $this->logMessage($responseChatJson, false);

            // Eliminar comillas simples y ajustar el JSON si es necesario
            $responseChatJson = trim($responseChatJson, "'");
            $responseChatJson = str_replace("'", '"', $responseChatJson);

            // Decodificar el JSON a un array PHP
            $responseChat = json_decode($responseChatJson, true);
            // dd($responseChat); // Depuración: Verifica el JSON recibido antes de procesarlo

            if (json_last_error() !== JSON_ERROR_NONE) {
                return "Error al decodificar JSON: " . json_last_error_msg();
            }

            // Verificar que el JSON contiene las claves necesarias
            if (isset($responseChat['aviso'], $responseChat['Tipo'])) {
                if ($responseChat['aviso'] == true && $responseChat['Tipo'] == 2 && isset($responseChat['codigo'])) {
                    $reserva = Reserva::where('codigo_reserva', $responseChat['codigo'])->first();
                    if ($reserva) {
                        $data = [
                            'codigo_reserva' => $reserva->codigo_reserva,
                            'cliente' => $reserva->cliente['nombre'] == null ? $reserva->cliente->alias : $reserva->cliente['nombre'] .' ' . $reserva->cliente['apellido1'],
                            'apartamento' => $reserva->apartamento->titulo,
                            'edificio' => isset($reserva->apartamento->edificioName->nombre) ? $reserva->apartamento->edificioName->nombre : 'Edificio Hawkins Suite',
                            'fecha_entrada' => $reserva->fecha_entrada,
                        ];

                        return $this->enviarMensajeAsistente(json_encode($data), $this->phoneNumber, 'system');
                    } else {
                        return "Reserva no encontrada.";
                    }
                }else{

                }

                return $responseChat;
            } else {
                return $responseChat['mensaje'];
            }
        }

        return "Lo siento, no pude procesar la solicitud.";
    }

    protected function logMessage($message, $isQuestion = true)
    {
        // Obtener el contexto actual del archivo (si existe)
        $context = $this->getContext();

        // Crear una nueva entrada de mensaje
        $type = $isQuestion ? 'user' : 'assistant';
        $entry = [
            'role' => $type,
            'content' => $message
        ];

        // Añadir la nueva entrada al contexto
        $context[] = $entry;

        // Guardar el contexto actualizado en el archivo
        file_put_contents($this->filePath, json_encode($context, JSON_PRETTY_PRINT));
    }

    protected function getContext()
    {
        // Verificar si el archivo existe
        if (file_exists($this->filePath)) {
            // Cargar el contenido del archivo JSON
            $content = file_get_contents($this->filePath);
            $context = json_decode($content, true);

            // Verificar si la decodificación fue exitosa y devolver el array
            return is_array($context) ? $context : [];
        }

        // Si el archivo no existe, retornar un array vacío
        return [];
    }
}
