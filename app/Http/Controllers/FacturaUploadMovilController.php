<?php

namespace App\Http\Controllers;

use App\Models\FacturaPendiente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Endpoint publico (sin auth) para que los trabajadores suban fotos de facturas
 * desde su movil. Se valida un token compartido (services.facturas.upload_token).
 *
 * Flujo:
 *  GET  /facturas/subir/{token}  -> renderiza una pagina simple con <input type=file capture>
 *  POST /facturas/subir/{token}  -> guarda el archivo en storage/app/facturas/pendientes/
 *                                    y registra la fila en facturas_pendientes.
 *                                    El cron facturas:procesar-pendientes lo recoge en
 *                                    los siguientes 5 minutos.
 *
 * Seguridad:
 *  - Token timing-safe (hash_equals)
 *  - Rate-limit 20 uploads/min/IP (aplicado en la ruta)
 *  - Validacion mime y tamano (max 10 MB)
 *  - Filename saneado (sin paths, sin caracteres raros)
 *  - Esta ruta debe estar en la lista de excepciones de VerifyCsrfToken.
 */
class FacturaUploadMovilController extends Controller
{
    /**
     * Renderiza el formulario de subida.
     */
    public function show(string $token)
    {
        if (!$this->tokenValido($token)) {
            abort(404);
        }
        return view('public.facturas.subir', ['token' => $token]);
    }

    /**
     * Guarda el archivo subido.
     */
    public function store(Request $request, string $token)
    {
        if (!$this->tokenValido($token)) {
            abort(404);
        }

        $request->validate([
            'factura' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif,pdf', 'max:10240'], // 10 MB
        ], [
            'factura.required' => 'Tienes que elegir una foto.',
            'factura.mimes'    => 'Solo se aceptan fotos (jpg, png, webp, heic) o PDFs.',
            'factura.max'      => 'La foto es demasiado grande (max 10 MB).',
        ]);

        $file = $request->file('factura');

        // Carpeta destino
        $destDir = storage_path('app/facturas/pendientes');
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Nombre saneado y unico
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(6);
        $filename = "{$timestamp}_{$random}.{$ext}";

        try {
            $file->move($destDir, $filename);
        } catch (\Throwable $e) {
            Log::error('[FacturaUploadMovil] Error guardando archivo', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['factura' => 'No se pudo guardar el archivo. Intentalo de nuevo.']);
        }

        $relPath = 'facturas/pendientes/' . $filename;
        $abs = $destDir . DIRECTORY_SEPARATOR . $filename;

        // Registrar en tabla
        FacturaPendiente::create([
            'filename'      => $file->getClientOriginalName() ?: $filename,
            'storage_path'  => $relPath,
            'size_bytes'    => @filesize($abs) ?: null,
            'mime_type'     => @mime_content_type($abs) ?: $file->getMimeType(),
            'status'        => 'pendiente',
            'uploaded_from' => 'mobile_web',
            'uploaded_by_ip'=> $request->ip(),
        ]);

        Log::info('[FacturaUploadMovil] Factura subida', [
            'filename' => $filename,
            'ip' => $request->ip(),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return redirect()
            ->route('facturas.subir.show', ['token' => $token])
            ->with('success', 'Factura subida correctamente. Se procesara automaticamente en unos minutos.');
    }

    /**
     * Comprueba el token de forma timing-safe.
     */
    private function tokenValido(string $token): bool
    {
        $expected = config('services.facturas.upload_token');
        if (empty($expected) || !is_string($expected)) {
            return false;
        }
        return hash_equals($expected, $token);
    }
}
