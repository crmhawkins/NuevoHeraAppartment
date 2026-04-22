<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-22] Endereza fotos de DNI / pasaporte usando Qwen3-VL:8b para
 * detectar la orientacion real del contenido (no la de los pixeles de la
 * imagen). Complementa a DniImageOrienter (EXIF + aspect) para los casos
 * en que la foto ya es apaisada pero el carnet dentro del frame esta
 * rotado, o donde mi heuristica dejo la foto boca abajo.
 *
 * Le preguntamos al modelo: "¿cuantos grados hay que rotar esta imagen
 * para que el texto se lea correctamente?" y aplicamos la rotacion si
 * es !=0. Responde con JSON: {"rotacion": 0|90|180|270}.
 *
 * Uso:
 *   php artisan dni:enderezar-ia --dry-run --limit=20
 *   php artisan dni:enderezar-ia --reserva=6335
 *   php artisan dni:enderezar-ia --desde=2026-02-01 --limit=200
 */
class DniEnderezarIa extends Command
{
    protected $signature = 'dni:enderezar-ia
        {--dry-run : solo reporta, no escribe}
        {--reserva= : procesar solo las fotos de esta reserva}
        {--desde= : procesar solo fotos de reservas con fecha_entrada desde YYYY-MM-DD}
        {--limit=100 : maximo de fotos por ejecucion}';

    protected $description = 'Usa Qwen3-VL para detectar la orientacion real de cada foto de DNI y rotarla si hace falta.';

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $limit   = (int) $this->option('limit');
        $reserva = $this->option('reserva');
        $desde   = $this->option('desde');

        $baseUrl = config('services.hawkins_ai.url', env('HAWKINS_AI_URL'));
        $model   = config('services.hawkins_ai.model', env('HAWKINS_AI_MODEL', 'qwen3-vl:8b'));
        $apiKey  = config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));

        if (empty($baseUrl)) {
            $this->error('HAWKINS_AI_URL no configurada.');
            return self::FAILURE;
        }

        $ollamaUrl = rtrim(preg_replace('/:11435(\/)?$/', ':11434$1', rtrim($baseUrl, '/')), '/') . '/api/chat';

        $q = Photo::query()
            ->whereIn('photo_categoria_id', [13, 14, 15, 16])
            ->orderByDesc('id')
            ->limit($limit);

        if ($reserva) $q->where('reserva_id', (int) $reserva);
        if ($desde) {
            $ids = \App\Models\Reserva::whereDate('fecha_entrada', '>=', $desde)->pluck('id');
            $q->whereIn('reserva_id', $ids);
        }

        $fotos = $q->get();
        if ($fotos->isEmpty()) {
            $this->info('No hay fotos que procesar.');
            return self::SUCCESS;
        }

        $this->info("Analizando {$fotos->count()} fotos con la IA...");
        $rotadas = 0; $yaOk = 0; $errores = 0;

        foreach ($fotos as $f) {
            $rel = preg_replace('~^private/~', '', (string) $f->url);
            $abs = storage_path('app/' . $rel);

            if (!is_file($abs)) {
                $errores++;
                continue;
            }

            $rotacion = $this->detectarRotacionIa($abs, $ollamaUrl, $model, $apiKey);
            if ($rotacion === null) {
                $errores++;
                $this->line(" [?]  photo#{$f->id}: IA no respondio");
                continue;
            }

            if ($rotacion === 0) {
                $yaOk++;
                continue;
            }

            if ($dryRun) {
                $this->line(" [DRY] photo#{$f->id}: IA dice rotar {$rotacion}°");
                $rotadas++;
                continue;
            }

            if ($this->rotarFichero($abs, $rotacion)) {
                $rotadas++;
                $this->info(" [OK] photo#{$f->id}: rotada {$rotacion}°");
                Log::info('[dni:enderezar-ia] Foto rotada', ['photo_id' => $f->id, 'grados' => $rotacion]);
            } else {
                $errores++;
                $this->line(" [X]  photo#{$f->id}: GD fallo al rotar");
            }
        }

        $this->line('');
        $this->info("Total: {$fotos->count()} | rotadas: {$rotadas} | ya OK: {$yaOk} | errores: {$errores}");
        return self::SUCCESS;
    }

    /**
     * Le pregunta a Qwen3-VL cuantos grados hay que rotar la imagen para
     * que quede correctamente orientada. Devuelve 0, 90, 180, 270 o null.
     */
    private function detectarRotacionIa(string $imagePath, string $ollamaUrl, string $model, ?string $apiKey): ?int
    {
        try {
            $bytes = @file_get_contents($imagePath);
            if ($bytes === false) return null;
            $b64 = base64_encode($bytes);

            $prompt =
                "Mira esta foto de un documento de identidad espanol (DNI, NIE o pasaporte). "
                . "Analiza la orientacion del TEXTO y la foto del titular. "
                . "Dime cuantos grados hay que rotar la imagen (en sentido horario) para que el texto se lea correctamente de izquierda a derecha y la foto del titular quede en posicion vertical normal.\n\n"
                . "Responde SOLO con un JSON de una sola linea: {\"rotacion\": N} donde N es 0, 90, 180 o 270.\n"
                . "- 0  = el documento ya esta bien orientado, no rotes\n"
                . "- 90 = hay que rotar 90 grados HORARIO\n"
                . "- 180 = el documento esta boca abajo\n"
                . "- 270 = hay que rotar 270 horario (= 90 antihorario)";

            $resp = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json', 'X-API-Key' => (string) $apiKey])
                ->post($ollamaUrl, [
                    'model'    => $model,
                    'messages' => [[
                        'role'    => 'user',
                        'content' => $prompt,
                        'images'  => [$b64],
                    ]],
                    'stream'  => false,
                    'options' => ['temperature' => 0.0, 'num_predict' => 30],
                ]);
            if (!$resp->successful()) return null;
            $data = $resp->json();
            $content = $data['message']['content'] ?? '';
            if (!is_string($content)) return null;

            // Buscar {"rotacion": N}
            if (preg_match('/"rotacion"\s*:\s*(\d+)/', $content, $m)) {
                $n = (int) $m[1];
                return in_array($n, [0, 90, 180, 270], true) ? $n : null;
            }
            // Fallback: primer numero que aparezca
            if (preg_match('/(0|90|180|270)/', $content, $m)) {
                return (int) $m[1];
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('[dni:enderezar-ia] Excepcion llamando IA', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function rotarFichero(string $path, int $grados): bool
    {
        if (!function_exists('imagecreatefromstring')) return false;
        $bytes = @file_get_contents($path);
        if ($bytes === false) return false;
        $img = @imagecreatefromstring($bytes);
        if (!$img) return false;

        $rot = imagerotate($img, -$grados, 0);
        imagedestroy($img);
        if (!$rot) return false;

        $ok = imagejpeg($rot, $path, 90);
        imagedestroy($rot);
        return (bool) $ok;
    }
}
