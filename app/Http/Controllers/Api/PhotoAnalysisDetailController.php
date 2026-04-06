<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhotoAnalysis;
use Illuminate\Support\Facades\Log;

class PhotoAnalysisDetailController extends Controller
{
    public function show($id)
    {
        try {
            $analisis = PhotoAnalysis::with(['limpieza.apartamento', 'empleada', 'categoria'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'analisis' => [
                    'id' => $analisis->id,
                    'calidad_general' => $analisis->calidad_general,
                    'puntuacion' => $analisis->puntuacion,
                    'cumple_estandares' => $analisis->cumple_estandares,
                    'deficiencias' => $analisis->deficiencias,
                    'observaciones' => $analisis->observaciones,
                    'recomendaciones' => $analisis->recomendaciones,
                    'image_url' => $analisis->image_url,
                    'fecha_analisis' => $analisis->fecha_analisis->format('d/m/Y H:i'),
                    'empleada' => $analisis->empleada ? $analisis->empleada->name : 'N/A',
                    'apartamento' => $analisis->limpieza->apartamento ? $analisis->limpieza->apartamento->nombre : 'N/A',
                    'categoria' => $analisis->categoria_nombre
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo análisis: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el análisis'
            ], 500);
        }
    }
}
