<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LugarCercano;
use App\Models\Apartamento;
use App\Models\CategoriaLugar;
use App\Services\OpenStreetMapService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AdminLugaresCercanosController extends Controller
{
    /**
     * Mostrar lugares cercanos de un apartamento
     */
    public function index($apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        $lugares = LugarCercano::where('apartamento_id', $apartamentoId)
            ->orderBy('categoria')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
        
        return view('admin.lugares-cercanos.index', compact('apartamento', 'lugares'));
    }

    /**
     * Mostrar formulario para crear lugar cercano
     */
    public function create($apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        return view('admin.lugares-cercanos.create', compact('apartamento'));
    }

    /**
     * Guardar nuevo lugar cercano
     */
    public function store(Request $request, $apartamentoId)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|in:que_hay_cerca,restaurantes,transporte,playas,aeropuertos',
            'tipo' => 'nullable|string|max:100',
            'distancia' => 'nullable|numeric|min:0',
            'unidad_distancia' => 'nullable|string|in:km,m',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $validated['apartamento_id'] = $apartamentoId;
        $validated['unidad_distancia'] = $validated['unidad_distancia'] ?? 'km';
        $validated['activo'] = $request->has('activo');

        LugarCercano::create($validated);

        Alert::success('Éxito', 'Lugar cercano creado correctamente');
        return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
    }

    /**
     * Mostrar formulario para editar lugar cercano
     */
    public function edit($apartamentoId, $id)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        $lugar = LugarCercano::where('apartamento_id', $apartamentoId)
            ->findOrFail($id);
        
        return view('admin.lugares-cercanos.edit', compact('apartamento', 'lugar'));
    }

    /**
     * Actualizar lugar cercano
     */
    public function update(Request $request, $apartamentoId, $id)
    {
        $lugar = LugarCercano::where('apartamento_id', $apartamentoId)
            ->findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|in:que_hay_cerca,restaurantes,transporte,playas,aeropuertos',
            'tipo' => 'nullable|string|max:100',
            'distancia' => 'nullable|numeric|min:0',
            'unidad_distancia' => 'nullable|string|in:km,m',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $validated['unidad_distancia'] = $validated['unidad_distancia'] ?? 'km';
        $validated['activo'] = $request->has('activo');

        $lugar->update($validated);

        Alert::success('Éxito', 'Lugar cercano actualizado correctamente');
        return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
    }

    /**
     * Eliminar lugar cercano
     */
    public function destroy($apartamentoId, $id)
    {
        $lugar = LugarCercano::where('apartamento_id', $apartamentoId)
            ->findOrFail($id);
        
        $lugar->delete();

        Alert::success('Éxito', 'Lugar cercano eliminado correctamente');
        return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
    }

    /**
     * Generar lugares cercanos automáticamente usando OpenStreetMap
     */
    public function generarAutomaticamente(Request $request, $apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);

        // Verificar que el apartamento tenga coordenadas
        if (!$apartamento->latitude || !$apartamento->longitude) {
            Alert::error('Error', 'El apartamento debe tener coordenadas (latitud y longitud) para buscar lugares cercanos. Por favor, configura las coordenadas en la página de edición del apartamento.');
            return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
        }

        // Validar que las coordenadas sean válidas (rango correcto)
        $lat = (float) $apartamento->latitude;
        $lon = (float) $apartamento->longitude;
        
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            Alert::error('Error', 'Las coordenadas del apartamento no son válidas. Por favor, verifica latitud (-90 a 90) y longitud (-180 a 180).');
            \Log::error('Coordenadas inválidas en apartamento', [
                'apartamento_id' => $apartamentoId,
                'latitude' => $lat,
                'longitude' => $lon
            ]);
            return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
        }

        // Log de inicio de búsqueda
        \Log::info('Iniciando búsqueda de lugares cercanos', [
            'apartamento_id' => $apartamentoId,
            'coordenadas' => "{$lat},{$lon}",
            'ciudad' => $apartamento->city ?? 'N/A',
            'direccion' => $apartamento->address ?? 'N/A'
        ]);

        // OPCIONAL: Borrar lugares existentes si se solicita
        $borrarExistentes = $request->input('borrar_existentes', false);
        if ($borrarExistentes) {
            $eliminados = LugarCercano::where('apartamento_id', $apartamentoId)->delete();
            \Log::info('Lugares existentes eliminados', [
                'apartamento_id' => $apartamentoId,
                'eliminados' => $eliminados
            ]);
        }

        $categoriasSeleccionadas = $request->input('categorias', []);
        
        if (empty($categoriasSeleccionadas)) {
            // Si no se especifican categorías, usar todas las activas para búsqueda automática
            $categorias = CategoriaLugar::paraBusquedaAutomatica()->ordenadas()->get();
        } else {
            $categorias = CategoriaLugar::whereIn('id', $categoriasSeleccionadas)
                ->activas()
                ->ordenadas()
                ->get();
        }

        if ($categorias->isEmpty()) {
            Alert::warning('Sin categorías', 'No hay categorías disponibles para buscar.');
            return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
        }

        $osmService = new OpenStreetMapService();
        $lugaresEncontrados = 0;
        $lugaresDuplicados = 0;
        $lugaresFueraRadio = 0;

        try {
            foreach ($categorias as $categoria) {
                // Obtener parámetros de búsqueda de la categoría
                $params = $categoria->obtenerParametrosBusqueda();
                
                // Log de búsqueda por categoría
                \Log::info('Buscando lugares para categoría', [
                    'categoria' => $categoria->nombre,
                    'tipo_categoria' => $categoria->tipo_categoria,
                    'params' => $params
                ]);
                
                // Buscar lugares
                $resultados = $osmService->buscarLugaresCercanos(
                    $lat,
                    $lon,
                    $params
                );

                \Log::info('Resultados encontrados para categoría', [
                    'categoria' => $categoria->nombre,
                    'categoria_id' => $categoria->id,
                    'amenity_osm' => $categoria->amenity_osm,
                    'shop_osm' => $categoria->shop_osm,
                    'tourism_osm' => $categoria->tourism_osm,
                    'leisure_osm' => $categoria->leisure_osm,
                    'radio_metros' => $categoria->radio_metros,
                    'total_resultados' => count($resultados),
                    'params_enviados' => $params
                ]);
                
                // Si no hay resultados, intentar con un radio mayor (fallback)
                if (empty($resultados) && $categoria->radio_metros < 10000) {
                    \Log::warning('No se encontraron resultados, intentando con radio mayor', [
                        'categoria' => $categoria->nombre,
                        'radio_original' => $categoria->radio_metros,
                        'radio_nuevo' => $categoria->radio_metros * 2
                    ]);
                    
                    $paramsFallback = $params;
                    $paramsFallback['radius'] = $categoria->radio_metros * 2;
                    $resultados = $osmService->buscarLugaresCercanos($lat, $lon, $paramsFallback);
                    
                    \Log::info('Resultados con radio mayor', [
                        'categoria' => $categoria->nombre,
                        'total_resultados' => count($resultados)
                    ]);
                }

                // Contador para limitar a 5 por categoría
                $lugaresGuardadosEnCategoria = 0;
                $maxLugaresPorCategoria = 5;

                foreach ($resultados as $resultado) {
                    // LIMITAR a 5 lugares por categoría
                    if ($lugaresGuardadosEnCategoria >= $maxLugaresPorCategoria) {
                        \Log::debug('Límite de 5 lugares alcanzado para categoría', [
                            'categoria' => $categoria->nombre
                        ]);
                        break;
                    }

                    // Validar distancia: rechazar resultados demasiado lejanos (más de 2x el radio)
                    $radioMaximo = $params['radius'] * 2;
                    if ($resultado['distancia_metros'] > $radioMaximo) {
                        $lugaresFueraRadio++;
                        \Log::debug('Lugar descartado por estar fuera del radio máximo', [
                            'nombre' => $resultado['nombre'],
                            'distancia' => $resultado['distancia_metros'] . 'm',
                            'radio_maximo' => $radioMaximo . 'm'
                        ]);
                        continue;
                    }

                    // Verificar si ya existe un lugar similar (por nombre y categoría)
                    $existe = LugarCercano::where('apartamento_id', $apartamentoId)
                        ->where('categoria', $categoria->tipo_categoria)
                        ->where('nombre', $resultado['nombre'])
                        ->exists();

                    if ($existe) {
                        $lugaresDuplicados++;
                        continue;
                    }

                    // Calcular distancia y unidad
                    $distancia = $resultado['distancia_metros'];
                    $unidad = 'm';
                    
                    if ($distancia >= 1000) {
                        $distancia = round($distancia / 1000, 2);
                        $unidad = 'km';
                    } else {
                        $distancia = round($distancia);
                    }

                    // Obtener el último orden para esta categoría
                    $ultimoOrden = LugarCercano::where('apartamento_id', $apartamentoId)
                        ->where('categoria', $categoria->tipo_categoria)
                        ->max('orden') ?? 0;

                    // Extraer nombre más corto y relevante del resultado
                    $nombreLugar = $resultado['nombre_corto'] ?? $resultado['nombre'];
                    
                    // Si el nombre es muy largo (display_name de OSM puede ser muy largo), truncarlo
                    if (strlen($nombreLugar) > 200) {
                        $nombreLugar = substr($nombreLugar, 0, 197) . '...';
                    }

                    // Crear lugar cercano
                    LugarCercano::create([
                        'apartamento_id' => $apartamentoId,
                        'nombre' => $nombreLugar,
                        'categoria' => $categoria->tipo_categoria,
                        'tipo' => $resultado['tipo'] ?? $resultado['categoria_osm'] ?? null,
                        'distancia' => $distancia,
                        'unidad_distancia' => $unidad,
                        'orden' => $ultimoOrden + 1,
                        'activo' => true,
                    ]);

                    $lugaresEncontrados++;
                    $lugaresGuardadosEnCategoria++;
                }
            }

            $mensaje = "Se encontraron {$lugaresEncontrados} lugares cercanos.";
            if ($lugaresDuplicados > 0) {
                $mensaje .= " Se omitieron {$lugaresDuplicados} duplicados.";
            }
            if ($lugaresFueraRadio > 0) {
                $mensaje .= " Se descartaron {$lugaresFueraRadio} lugares por estar fuera del radio permitido.";
            }

            Alert::success('Éxito', $mensaje);

            \Log::info('Búsqueda de lugares completada', [
                'apartamento_id' => $apartamentoId,
                'lugares_encontrados' => $lugaresEncontrados,
                'lugares_duplicados' => $lugaresDuplicados,
                'lugares_fuera_radio' => $lugaresFueraRadio
            ]);

        } catch (\Exception $e) {
            Alert::error('Error', 'Error al buscar lugares: ' . $e->getMessage());
            \Log::error('Error al generar lugares automáticamente', [
                'apartamento_id' => $apartamentoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
    }
    
    /**
     * Borrar todos los lugares cercanos de un apartamento
     */
    public function borrarTodos($apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        $eliminados = LugarCercano::where('apartamento_id', $apartamentoId)->delete();
        
        Alert::success('Éxito', "Se eliminaron {$eliminados} lugares cercanos.");
        
        return redirect()->route('admin.lugares-cercanos.index', $apartamentoId);
    }

    /**
     * Mostrar vista de generación automática
     */
    public function mostrarGeneracionAutomatica($apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        $categorias = CategoriaLugar::activas()->ordenadas()->get();
        
        // Verificar coordenadas
        $tieneCoordenadas = !empty($apartamento->latitude) && !empty($apartamento->longitude);

        return view('admin.lugares-cercanos.generar-automatico', compact('apartamento', 'categorias', 'tieneCoordenadas'));
    }
}
