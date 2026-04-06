<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\ApartamentoLimpieza;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class ApartamentoLimpioController extends Controller
{
    /**
     * Mostrar las fotos del apartamento limpio para una reserva
     *
     * @param string $token
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function show($token)
    {
        try {
            // Validar token y obtener reserva
            $reserva = Reserva::where('token', $token)
                ->with(['apartamento', 'cliente'])
                ->firstOrFail();

            // Obtener apartamento
            $apartamento = $reserva->apartamento;
            
            if (!$apartamento) {
                Log::warning('Reserva sin apartamento asociado', ['reserva_id' => $reserva->id, 'token' => $token]);
                abort(404, 'Apartamento no encontrado');
            }

            // Buscar última limpieza anterior al día de entrada
            $limpieza = ApartamentoLimpieza::where('apartamento_id', $reserva->apartamento_id)
                ->where('tipo_limpieza', 'apartamento')
                ->where('status_id', 3) // Completada
                ->where(function($query) use ($reserva) {
                    // Buscar limpiezas que terminaron antes de la fecha de entrada
                    $query->where(function($q) use ($reserva) {
                        $q->whereNotNull('fecha_fin')
                          ->where('fecha_fin', '<', $reserva->fecha_entrada);
                    })
                    ->orWhere(function($q) use ($reserva) {
                        // Si no tiene fecha_fin, usar fecha_comienzo
                        $q->whereNull('fecha_fin')
                          ->whereNotNull('fecha_comienzo')
                          ->where('fecha_comienzo', '<', $reserva->fecha_entrada);
                    })
                    ->orWhere(function($q) use ($reserva) {
                        // Si no tiene ninguna fecha, usar created_at
                        $q->whereNull('fecha_fin')
                          ->whereNull('fecha_comienzo')
                          ->where('created_at', '<', $reserva->fecha_entrada);
                    });
                })
                ->orderByRaw('COALESCE(fecha_fin, fecha_comienzo, created_at) DESC')
                ->with(['fotos' => function($query) {
                    $query->whereNotNull('url')
                          ->orderBy('created_at', 'asc');
                }])
                ->first();

            // Si no hay limpieza anterior, buscar la más reciente del apartamento
            if (!$limpieza) {
                $limpieza = ApartamentoLimpieza::where('apartamento_id', $reserva->apartamento_id)
                    ->where('tipo_limpieza', 'apartamento')
                    ->where('status_id', 3) // Completada
                    ->orderByRaw('COALESCE(fecha_fin, fecha_comienzo, created_at) DESC')
                    ->with(['fotos' => function($query) {
                        $query->whereNotNull('url')
                              ->orderBy('created_at', 'asc');
                    }])
                    ->first();
            }

            // Obtener fotos de la limpieza
            $fotos = collect();
            $fecha_limpieza = null;
            
            if ($limpieza) {
                // Primero buscar en apartamento_limpieza_items (donde se guardan las fotos de limpieza)
                $itemsFotos = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $limpieza->id)
                    ->whereNotNull('photo_url')
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                // Convertir items a formato compatible con la vista
                foreach ($itemsFotos as $item) {
                    $fotos->push((object)[
                        'id' => $item->id,
                        'url' => $item->photo_url,
                        'created_at' => $item->created_at
                    ]);
                }
                
                // Si no hay fotos en items, buscar en la tabla photos
                if ($fotos->isEmpty()) {
                    $fotos = $limpieza->fotos()->whereNotNull('url')->orderBy('created_at', 'asc')->get();
                }
                
                $fecha_limpieza = $limpieza->fecha_fin ?? $limpieza->fecha_comienzo ?? $limpieza->created_at;
            }

            // Establecer idioma (prioridad: request > sesión > cliente > español)
            $locale = request()->get('lang', session('locale', $reserva->cliente->idioma ?? 'es'));
            $idiomasPermitidos = ['es', 'en', 'fr', 'de', 'it', 'pt'];
            if (!in_array($locale, $idiomasPermitidos)) {
                $locale = 'es';
            }
            
            App::setLocale($locale);
            session(['locale' => $locale]);
            session()->save();

            Log::info('Mostrando apartamento limpio', [
                'reserva_id' => $reserva->id,
                'apartamento_id' => $apartamento->id,
                'limpieza_id' => $limpieza ? $limpieza->id : null,
                'fotos_count' => $fotos->count(),
                'locale' => $locale
            ]);

            return view('apartamento-limpio.show', compact(
                'reserva',
                'apartamento',
                'limpieza',
                'fotos',
                'fecha_limpieza',
                'locale'
            ));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Token de reserva no encontrado', ['token' => $token]);
            abort(404, 'Reserva no encontrada');
        } catch (\Exception $e) {
            Log::error('Error al mostrar apartamento limpio', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error al cargar la información');
        }
    }
}

