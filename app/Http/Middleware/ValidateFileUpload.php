<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateFileUpload
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a rutas de subida de DNI
        if ($request->is('dni/store') || $request->routeIs('dni.store')) {
            // Verificar si el contenido excede post_max_size
            $contentLength = $request->header('Content-Length');
            $maxPostSize = $this->getMaxPostSize();
            
            if ($contentLength && $contentLength > $maxPostSize) {
                return redirect()->back()
                    ->withErrors(['files' => 'El tamaño total de los archivos excede el límite permitido. Máximo: ' . $this->formatBytes($maxPostSize)])
                    ->withInput();
            }
            
            // Verificar archivos individuales
            $allFiles = $request->allFiles();
            $maxFileSize = $this->getMaxFileSize();
            
            foreach ($allFiles as $key => $file) {
                if (is_array($file)) {
                    foreach ($file as $singleFile) {
                        if ($singleFile->getSize() > $maxFileSize) {
                            return redirect()->back()
                                ->withErrors(['files' => "El archivo {$singleFile->getClientOriginalName()} excede el tamaño máximo permitido. Máximo: " . $this->formatBytes($maxFileSize)])
                                ->withInput();
                        }
                    }
                } else {
                    if ($file->getSize() > $maxFileSize) {
                        return redirect()->back()
                            ->withErrors(['files' => "El archivo {$file->getClientOriginalName()} excede el tamaño máximo permitido. Máximo: " . $this->formatBytes($maxFileSize)])
                            ->withInput();
                    }
                }
            }
        }
        
        return $next($request);
    }
    
    /**
     * Get the maximum post size in bytes.
     */
    private function getMaxPostSize(): int
    {
        $maxPostSize = ini_get('post_max_size');
        return $this->convertToBytes($maxPostSize);
    }
    
    /**
     * Get the maximum file size in bytes.
     */
    private function getMaxFileSize(): int
    {
        $maxFileSize = ini_get('upload_max_filesize');
        return $this->convertToBytes($maxFileSize);
    }
    
    /**
     * Convert PHP size format to bytes.
     */
    private function convertToBytes(string $size): int
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int) $size;
        
        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}