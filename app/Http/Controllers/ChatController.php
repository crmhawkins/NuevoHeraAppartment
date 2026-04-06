<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use App\Models\Apartamento;
use App\Models\Reserva;

use Illuminate\Http\Request;
use Carbon\Carbon;

class ChatController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function sendMessage(Request $request)
    {
        $message = "¿Cuáles son los apartamentos disponibles hoy para reservar?";

        // Definir las funciones API
        $functions = [
            [
                "name" => "GetAllApartments",
                "description" => "Retrieve a list of all apartments.",
                // "parameters" => [
                //     "type" => "object",
                //     "properties" => [
                //         "apartments" => [
                //             "type" => "array",
                //             "description" => "List of apartments available.",
                //             "items" => [
                //                 "type" => "object",
                //                 "properties" => [
                //                     "apartment_id" => ["type" => "string", "description" => "Unique identifier for the apartment"],
                //                     "name" => ["type" => "string", "description" => "Name of the apartment"],
                //                     "capacity" => ["type" => "integer", "description" => "Maximum occupancy of the apartment"]
                //                 ]
                //             ]
                //         ]
                //     ],
                //     "required" => ["apartments"]
                // ]
            ],
            [
                "name" => "GetApartmentsLibre",
                "description" => "Retrieve a list of all apartments available today.",
            ],
            [
                "name" => "ReportTechnicalIssue",
                "description" => "Report a technical issue that requires attention.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "description" => ["type" => "string", "description" => "Description of the technical issue."],
                        "apartment_id" => ["type" => "string", "description" => "ID of the apartment with the issue."]
                    ],
                    "required" => ["description", "apartment_id"]
                ]
            ]
        ];

        $response = $this->openAIService->sendMessage($message, $functions);


        // Verificar si OpenAI solicita una llamada a función
        if (isset($response['choices'][0]['finish_reason']) && $response['choices'][0]['finish_reason'] === 'function_call') {
            $function_call = $response['choices'][0]['message']['function_call'];

            // Determinar cuál función se solicitó
            if ($function_call['name'] === 'GetAllApartments') {
                // Llamar a la función para obtener apartamentos
                $apartments = $this->getAllApartments(); // Obtiene el array de apartamentos

                $messageToSend = "Aquí están los apartamentos disponibles: " . json_encode($apartments);
                return $this->openAIService->sendMessage($messageToSend, $functions);
            } elseif ($function_call['name'] === 'GetApartmentsLibre') {
                // Llamar a la función para reportar un problema técnico
                $apartamentos = $this->obtenerApartamentosDisponibles();

                $messageToSend = "Aquí están los apartamentos disponibles: " . json_encode($apartamentos);
                return $this->openAIService->sendMessage($messageToSend, $functions);
            } elseif ($function_call['name'] === 'ReportTechnicalIssue') {
                // Extraer parámetros
                $params = json_decode($function_call['arguments'], true);
                $description = $params['description'] ?? '';
                $apartment_id = $params['apartment_id'] ?? '';

                // Llamar a la función para reportar un problema técnico
                $issueReport = $this->reportTechnicalIssue($apartment_id, $description);

                // Construir la respuesta para OpenAI
                return $this->openAIService->sendMessage("He reportado el problema: $description", [], [
                    'name' => 'ReportTechnicalIssue',
                    'arguments' => json_encode($issueReport) // Convierte a JSON para que OpenAI pueda procesarlo
                ]);
            }
        }
        return response()->json($response);
    }

    public function reportTechnicalIssue($apartment_id, $description)
    {
        // Lógica para registrar el problema técnico, por ejemplo, guardarlo en la base de datos
        // Aquí estamos simulando la respuesta
        return [
            "status" => "Issue reported successfully for apartment ID $apartment_id: $description"
        ];
    }

    public function getAllApartments()
    {
        // Obtén todos los apartamentos desde la base de datos usando el modelo `Apartamento`
        $apartments = Apartamento::all();

        // Devuelve la lista de apartamentos en formato JSON
        return response()->json([
            "apartments" => $apartments
        ]);
    }


    /**
     * Obtener los apartamentos disponibles
     */
    public function obtenerApartamentosDisponibles()
    {
        // Obtener la fecha y la hora actual
        $hoy = Carbon::now();

        // Obtener los IDs de los apartamentos que están reservados hoy
        $reservasHoy = Reserva::whereDate('fecha_entrada', '<=', $hoy->toDateString())
            ->whereDate('fecha_salida', '>=', $hoy->toDateString())
            ->pluck('apartamento_id');

        // Obtener los apartamentos que no están en las reservas de hoy
        $apartamentosDisponibles = Apartamento::whereNotIn('id', $reservasHoy)->get();

        // Formatear los datos para la respuesta
        $data = $apartamentosDisponibles->map(function ($apartamento) {
            return [
                'id' => $apartamento->id,
                'titulo' => $apartamento->titulo,
                'descripcion' => $apartamento->descripcion, // Asegúrate de que este campo existe en tu modelo
                'edificio' => $apartamento->edificioName->nombre ?? 'Edificio Hawkins Suite', // Agregar el nombre del edificio
                // 'claves' => $apartamento->claves,
                // Agrega más campos según lo necesites
            ];
        });


        return response()->json($data, 200);
    }



}
