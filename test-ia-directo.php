<?php

/**
 * Script de prueba directa de la IA local
 * Uso: php test-ia-directo.php
 */

require __DIR__ . '/vendor/autoload.php';

$endpoint = 'https://192.168.1.45/chat/chat';
$apiKey = 'OllamaAPI_2024_K8mN9pQ2rS5tU7vW3xY6zA1bC4eF8hJ0lM';
$modelo = 'qwen3:latest';

$promptBase = "Eres un asistente de apartamentos turísticos.";

$instruccionesFunciones = "\n\nFUNCIONES DISPONIBLES:\n" .
    "Cuando el usuario te proporcione un código de reserva y necesite las claves, DEBES usar: [FUNCION:obtener_claves:codigo_reserva=CODIGO]\n" .
    "Cuando haya un problema técnico o avería que requiera intervención, usa: [FUNCION:notificar_tecnico:descripcion=DESCRIPCION:urgencia=alta|media|baja]\n" .
    "Cuando soliciten limpieza, usa: [FUNCION:notificar_limpieza:tipo_limpieza=TIPO:observaciones=OBS]\n\n" .
    "IMPORTANTE: Si el usuario menciona un código de reserva y necesita claves, usa obtener_claves INMEDIATAMENTE.\n" .
    "Si NO necesitas ejecutar ninguna función, responde normalmente al usuario de forma natural y útil.";

$promptSystem = $promptBase . $instruccionesFunciones;

$historial = [];

echo "🧪 PRUEBA INTERACTIVA DE IA LOCAL\n";
echo "=====================================\n";
echo "URL: {$endpoint}\n";
echo "Modelo: {$modelo}\n";
echo "Código de prueba: HMR2Y2RSF4\n";
echo "Escribe 'salir' para terminar\n\n";

$conversacion = [
    "Hola",
    "Tengo un problema no me ha llegado el codigo del apartamento",
    "HMR2Y2RSF4 ese es el codigo"
];

foreach ($conversacion as $index => $mensaje) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Mensaje " . ($index + 1) . ": {$mensaje}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Construir prompt
    $promptCompleto = $promptSystem;
    if (!empty($historial)) {
        $promptCompleto .= "\n\n" . implode("\n", $historial);
    }
    $promptCompleto .= "\n\nUsuario: " . $mensaje . "\nAsistente:";
    
    echo "📤 Enviando a IA local...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'prompt' => $promptCompleto,
            'modelo' => $modelo
        ]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Error cURL: {$error}\n\n";
        continue;
    }
    
    if ($httpCode !== 200) {
        echo "❌ Error HTTP {$httpCode}: {$response}\n\n";
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['success']) || !$data['success']) {
        echo "❌ Error en respuesta:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        continue;
    }
    
    $respuestaTexto = $data['respuesta'] ?? null;
    
    if (!$respuestaTexto) {
        echo "❌ Respuesta vacía\n\n";
        continue;
    }
    
    echo "✅ Respuesta IA:\n";
    echo "{$respuestaTexto}\n";
    
    // Verificar función
    if (preg_match('/\[FUNCION:([^:]+):(.+?)\]/', $respuestaTexto, $matches)) {
        echo "\n⚠️  FUNCIÓN DETECTADA: " . trim($matches[1]) . "\n";
    }
    
    // Agregar al historial
    $historial[] = "Usuario: " . $mensaje;
    $historial[] = "Asistente: " . $respuestaTexto;
    
    // Mantener solo últimos 20 intercambios
    if (count($historial) > 40) {
        $historial = array_slice($historial, -40);
    }
    
    echo "\n";
    sleep(1);
}

echo "✅ Prueba completada\n";
