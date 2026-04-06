<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DocumentDetectionService
{
    /**
     * Detectar tipo de documento usando OCR (Estrategia híbrida)
     */
    public function detectDocumentType($imagePath)
    {
        try {
            Log::info('Iniciando detección de documento', ['image' => $imagePath]);
            
            $ocrResult = '';
            $method = '';
            
            // Estrategia 1: Intentar Tesseract local primero
            if ($this->isTesseractAvailable()) {
                try {
                    $ocrResult = $this->extractTextWithTesseract($imagePath);
                    $method = 'tesseract_local';
                    Log::info('OCR completado con Tesseract local');
                } catch (\Exception $e) {
                    Log::warning('Tesseract local falló, intentando servicio externo: ' . $e->getMessage());
                }
            }
            
            // Estrategia 2: Si Tesseract falla o no está disponible, usar servicio externo
            if (empty($ocrResult)) {
                try {
                    $ocrResult = $this->extractTextWithGoogleVision($imagePath);
                    $method = 'google_vision';
                    Log::info('OCR completado con Google Vision API');
                } catch (\Exception $e) {
                    Log::warning('Google Vision falló, intentando OCR básico: ' . $e->getMessage());
                }
            }
            
            // Estrategia 3: Si todo falla, usar detección básica por patrones visuales
            if (empty($ocrResult)) {
                $ocrResult = $this->extractTextWithBasicDetection($imagePath);
                $method = 'basic_detection';
                Log::info('OCR completado con detección básica');
            }
            
            // Analizar el texto extraído
            $documentType = $this->analyzeTextForDocumentType($ocrResult);
            $documentType['method'] = $method;
            
            Log::info('Detección completada', [
                'type' => $documentType['type'],
                'confidence' => $documentType['confidence'] ?? 0,
                'method' => $method
            ]);
            
            return $documentType;
            
        } catch (\Exception $e) {
            Log::error('Error en detección de documento: ' . $e->getMessage());
            return [
                'type' => 'unknown',
                'confidence' => 0,
                'error' => $e->getMessage(),
                'method' => 'error'
            ];
        }
    }
    
    /**
     * Extraer texto usando Tesseract OCR
     */
    private function extractTextWithTesseract($imagePath)
    {
        // Verificar si Tesseract está instalado
        $tesseractPath = $this->findTesseractPath();
        
        if (!$tesseractPath) {
            throw new \Exception('Tesseract OCR no está instalado');
        }
        
        // Comando para extraer texto
        $command = escapeshellcmd($tesseractPath) . ' ' . 
                  escapeshellarg($imagePath) . ' ' . 
                  escapeshellarg('stdout') . ' ' . 
                  '-l spa+eng --psm 6';
        
        Log::info('Ejecutando comando Tesseract', ['command' => $command]);
        
        $output = shell_exec($command . ' 2>&1');
        
        if (!$output) {
            throw new \Exception('Error ejecutando Tesseract OCR');
        }
        
        return $output;
    }
    
    /**
     * Extraer texto usando Google Vision API
     */
    private function extractTextWithGoogleVision($imagePath)
    {
        $apiKey = config('services.google.vision_api_key');
        
        if (!$apiKey) {
            throw new \Exception('Google Vision API key no configurada');
        }
        
        $imageData = base64_encode(file_get_contents($imagePath));
        
        $response = Http::post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageData
                    ],
                    'features' => [
                        [
                            'type' => 'TEXT_DETECTION',
                            'maxResults' => 1
                        ]
                    ]
                ]
            ]
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Error en Google Vision API: ' . $response->body());
        }
        
        $data = $response->json();
        
        if (isset($data['responses'][0]['textAnnotations'][0]['description'])) {
            return $data['responses'][0]['textAnnotations'][0]['description'];
        }
        
        return '';
    }
    
    /**
     * Analizar texto para determinar tipo de documento
     */
    private function analyzeTextForDocumentType($text)
    {
        $text = strtoupper($text);
        
        // Patrones para DNI español
        $dniPatterns = [
            '/DOCUMENTO NACIONAL DE IDENTIDAD/i',
            '/DNI/i',
            '/ESPANA/i',
            '/REINO DE ESPANA/i',
            '/\b\d{8}[A-Z]\b/', // Formato DNI: 8 dígitos + letra
            '/<[A-Z]{3}[A-Z0-9<]{20}/', // MRZ formato DNI
        ];
        
        // Patrones para pasaporte español
        $passportPatterns = [
            '/PASAPORTE/i',
            '/PASSPORT/i',
            '/ESPANA/i',
            '/REINO DE ESPANA/i',
            '/P[A-Z]{2}[A-Z0-9<]{20}/', // MRZ formato pasaporte
        ];
        
        $dniScore = 0;
        $passportScore = 0;
        
        // Contar coincidencias para DNI
        foreach ($dniPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $dniScore++;
            }
        }
        
        // Contar coincidencias para pasaporte
        foreach ($passportPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $passportScore++;
            }
        }
        
        // Determinar tipo de documento
        if ($dniScore > $passportScore) {
            return [
                'type' => 'dni',
                'confidence' => min(($dniScore / count($dniPatterns)) * 100, 100),
                'score' => $dniScore,
                'text_found' => $this->extractRelevantText($text, 'dni')
            ];
        } elseif ($passportScore > $dniScore) {
            return [
                'type' => 'passport',
                'confidence' => min(($passportScore / count($passportPatterns)) * 100, 100),
                'score' => $passportScore,
                'text_found' => $this->extractRelevantText($text, 'passport')
            ];
        } else {
            return [
                'type' => 'unknown',
                'confidence' => 0,
                'score' => 0,
                'text_found' => substr($text, 0, 200) // Primeros 200 caracteres
            ];
        }
    }
    
    /**
     * Extraer texto relevante del documento
     */
    private function extractRelevantText($text, $type)
    {
        $relevantText = [];
        
        if ($type === 'dni') {
            // Extraer número de DNI
            if (preg_match('/\b\d{8}[A-Z]\b/', $text, $matches)) {
                $relevantText['dni_number'] = $matches[0];
            }
            
            // Extraer MRZ si existe
            if (preg_match('/<[A-Z]{3}[A-Z0-9<]{20}/', $text, $matches)) {
                $relevantText['mrz'] = $matches[0];
            }
        }
        
        if ($type === 'passport') {
            // Extraer número de pasaporte
            if (preg_match('/\b[A-Z]{2}\d{6}\b/', $text, $matches)) {
                $relevantText['passport_number'] = $matches[0];
            }
            
            // Extraer MRZ si existe
            if (preg_match('/P[A-Z]{2}[A-Z0-9<]{20}/', $text, $matches)) {
                $relevantText['mrz'] = $matches[0];
            }
        }
        
        return $relevantText;
    }
    
    /**
     * Verificar si Tesseract está disponible
     */
    private function isTesseractAvailable()
    {
        $tesseractPath = $this->findTesseractPath();
        return $tesseractPath !== null;
    }
    
    /**
     * Detección básica por patrones visuales (sin OCR)
     */
    private function extractTextWithBasicDetection($imagePath)
    {
        // Esta es una implementación básica que analiza patrones visuales
        // En lugar de extraer texto real, busca características visuales
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return '';
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Análisis básico de proporciones
        $aspectRatio = $width / $height;
        
        // DNI español: proporción aproximada 1.586:1
        // Pasaporte: proporción aproximada 1.4:1
        if ($aspectRatio > 1.5 && $aspectRatio < 1.7) {
            return 'DOCUMENTO NACIONAL DE IDENTIDAD ESPANA DNI';
        } elseif ($aspectRatio > 1.3 && $aspectRatio < 1.5) {
            return 'PASAPORTE ESPANA PASSPORT';
        }
        
        return 'DOCUMENTO DESCONOCIDO';
    }
    
    /**
     * Buscar ruta de Tesseract
     */
    private function findTesseractPath()
    {
        $possiblePaths = [
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
            '/opt/homebrew/bin/tesseract',
            'tesseract' // En PATH
        ];
        
        foreach ($possiblePaths as $path) {
            if ($path === 'tesseract') {
                // Verificar si está en PATH
                $output = shell_exec('which tesseract 2>/dev/null');
                if ($output && trim($output)) {
                    return trim($output);
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Validar MRZ (Machine Readable Zone)
     */
    public function validateMRZ($mrz)
    {
        if (strlen($mrz) < 30) {
            return false;
        }
        
        // Validar checksum MRZ
        $checksum = $this->calculateMRZChecksum($mrz);
        
        return $checksum;
    }
    
    /**
     * Calcular checksum MRZ
     */
    private function calculateMRZChecksum($mrz)
    {
        // Implementar algoritmo de checksum MRZ
        // Esto es una implementación básica
        $weights = [7, 3, 1];
        $sum = 0;
        
        for ($i = 0; $i < strlen($mrz) - 1; $i++) {
            $char = $mrz[$i];
            $value = $this->getMRZValue($char);
            $sum += $value * $weights[$i % 3];
        }
        
        $checksum = $sum % 10;
        $expectedChecksum = $this->getMRZValue($mrz[strlen($mrz) - 1]);
        
        return $checksum === $expectedChecksum;
    }
    
    /**
     * Obtener valor numérico para caracteres MRZ
     */
    private function getMRZValue($char)
    {
        if (is_numeric($char)) {
            return (int)$char;
        }
        
        if (ctype_alpha($char)) {
            return ord(strtoupper($char)) - ord('A') + 10;
        }
        
        return 0; // Para caracteres especiales como '<'
    }
}
