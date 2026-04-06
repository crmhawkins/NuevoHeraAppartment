<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentDetectionService;
use Illuminate\Support\Facades\Storage;

class DocumentDetectionController extends Controller
{
    protected $detectionService;
    
    public function __construct(DocumentDetectionService $detectionService)
    {
        $this->detectionService = $detectionService;
    }
    
    /**
     * Mostrar página de pruebas de detección
     */
    public function showTestPage()
    {
        return view('document-detection.test');
    }
    
    /**
     * Procesar imagen para detección de documento
     */
    public function processImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|string', // Base64 image
                'side' => 'required|in:front,rear'
            ]);
            
            Log::info('Procesando imagen para detección', [
                'side' => $request->side,
                'image_size' => strlen($request->image)
            ]);
            
            // Decodificar imagen base64
            $imageData = $this->decodeBase64Image($request->image);
            
            if (!$imageData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de imagen inválido'
                ], 400);
            }
            
            // Guardar imagen temporal
            $filename = 'detection_' . time() . '_' . $request->side . '.jpg';
            $tempPath = storage_path('app/temp/' . $filename);
            
            // Crear directorio si no existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            
            file_put_contents($tempPath, $imageData);
            
            // Detectar tipo de documento
            $detectionResult = $this->detectionService->detectDocumentType($tempPath);
            
            // Limpiar archivo temporal
            unlink($tempPath);
            
            return response()->json([
                'success' => true,
                'detection' => $detectionResult,
                'side' => $request->side
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error procesando imagen: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando imagen: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Decodificar imagen base64
     */
    private function decodeBase64Image($base64String)
    {
        // Remover prefijo data:image/jpeg;base64,
        if (strpos($base64String, ',') !== false) {
            $base64String = explode(',', $base64String)[1];
        }
        
        $imageData = base64_decode($base64String);
        
        if ($imageData === false) {
            return null;
        }
        
        return $imageData;
    }
    
    /**
     * Instalar Tesseract OCR
     */
    public function installTesseract()
    {
        try {
            $commands = [
                'sudo apt update',
                'sudo apt install -y tesseract-ocr',
                'sudo apt install -y tesseract-ocr-spa', // Español
                'sudo apt install -y tesseract-ocr-eng'  // Inglés
            ];
            
            $output = [];
            foreach ($commands as $command) {
                $result = shell_exec($command . ' 2>&1');
                $output[] = $command . ': ' . $result;
            }
            
            return response()->json([
                'success' => true,
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar instalación de Tesseract
     */
    public function checkTesseract()
    {
        try {
            $output = shell_exec('tesseract --version 2>&1');
            
            return response()->json([
                'success' => true,
                'installed' => !empty($output),
                'version' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'installed' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

