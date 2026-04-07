<?php

namespace App\Console\Commands;

use App\Models\PromptAsistente;
use App\Models\ChatGpt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestIAChat extends Command
{
    protected $signature = 'test:ia-chat';
    protected $description = 'Prueba interactiva de la IA local - Conversación en tiempo real';

    private $remitente = '34600000000';
    private $endpoint;
    private $apiKey;
    private $modelo = 'qwen3:latest';

    public function __construct()
    {
        parent::__construct();
        $this->endpoint = config('services.hawkins_ai.url', env('HAWKINS_AI_URL'));
        $this->apiKey = config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));
    }
    private $historial = [];

    public function handle()
    {
        $this->info("🧪 PRUEBA INTERACTIVA DE IA LOCAL");
        $this->info("=====================================");
        $this->info("URL IA: {$this->endpoint}");
        $this->info("Modelo: {$this->modelo}");
        $this->info("Remitente: {$this->remitente}");
        $this->info("Código de prueba: HMR2Y2RSF4");
        $this->newLine();
        $this->info("Escribe 'salir' para terminar");
        $this->newLine();
        
        // Obtener prompt base
        $promptAsistente = PromptAsistente::first();
        $promptBase = $promptAsistente ? $promptAsistente->prompt : "Eres un asistente de apartamentos turísticos.";
        
        // Instrucciones de funciones
        $instruccionesFunciones = "\n\nFUNCIONES DISPONIBLES:\n" .
            "Cuando el usuario te proporcione un código de reserva y necesite las claves, DEBES usar: [FUNCION:obtener_claves:codigo_reserva=CODIGO]\n" .
            "Cuando haya un problema técnico o avería que requiera intervención, usa: [FUNCION:notificar_tecnico:descripcion=DESCRIPCION:urgencia=alta|media|baja]\n" .
            "Cuando soliciten limpieza, usa: [FUNCION:notificar_limpieza:tipo_limpieza=TIPO:observaciones=OBS]\n\n" .
            "IMPORTANTE: Si el usuario menciona un código de reserva y necesita claves, usa obtener_claves INMEDIATAMENTE.\n" .
            "Si NO necesitas ejecutar ninguna función, responde normalmente al usuario de forma natural y útil.";
        
        $promptSystem = $promptBase . $instruccionesFunciones;
        
        // Bucle de conversación
        while (true) {
            $mensaje = $this->ask('👤 Tú');
            
            if (strtolower(trim($mensaje)) === 'salir') {
                $this->info("👋 Hasta luego!");
                break;
            }
            
            if (empty(trim($mensaje))) {
                continue;
            }
            
            // Construir prompt con historial
            $promptCompleto = $promptSystem;
            if (!empty($this->historial)) {
                $promptCompleto .= "\n\n" . implode("\n", $this->historial);
            }
            $promptCompleto .= "\n\nUsuario: " . $mensaje . "\nAsistente:";
            
            $this->info("🤖 Enviando a IA local...");
            
            try {
                $httpClient = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ]);
                
                // Si es IP local, deshabilitar verificación SSL
                if (preg_match('/192\.168\./', $this->endpoint) || preg_match('/127\.0\.0\.1/', $this->endpoint) || preg_match('/localhost/', $this->endpoint)) {
                    $httpClient = $httpClient->withoutVerifying();
                }
                
                $response = $httpClient->timeout(60)->post($this->endpoint, [
                    'prompt' => $promptCompleto,
                    'modelo' => $this->modelo
                ]);
                
                if ($response->failed()) {
                    $this->error("❌ Error HTTP: " . $response->status());
                    $this->error($response->body());
                    continue;
                }
                
                $data = $response->json();
                
                if (!isset($data['success']) || !$data['success']) {
                    $this->error("❌ Error en respuesta:");
                    $this->error(json_encode($data, JSON_PRETTY_PRINT));
                    continue;
                }
                
                $respuestaTexto = $data['respuesta'] ?? null;
                
                if (!$respuestaTexto) {
                    $this->error("❌ Respuesta vacía");
                    continue;
                }
                
                // Mostrar respuesta
                $this->newLine();
                $this->line("🤖 IA: " . $respuestaTexto);
                $this->newLine();
                
                // Verificar si contiene función
                if (preg_match('/\[FUNCION:([^:]+):(.+?)\]/', $respuestaTexto, $matches)) {
                    $nombreFuncion = trim($matches[1]);
                    $this->warn("⚠️  FUNCIÓN DETECTADA: {$nombreFuncion}");
                }
                
                // Agregar al historial
                $this->historial[] = "Usuario: " . $mensaje;
                $this->historial[] = "Asistente: " . $respuestaTexto;
                
                // Mantener solo últimos 20 intercambios
                if (count($this->historial) > 40) {
                    $this->historial = array_slice($this->historial, -40);
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Excepción: " . $e->getMessage());
                if ($this->option('verbose')) {
                    $this->error($e->getTraceAsString());
                }
            }
        }
        
        return 0;
    }
}
