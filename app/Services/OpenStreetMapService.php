<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapService
{
    private const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org/search';
    private const REVERSE_BASE_URL = 'https://nominatim.openstreetmap.org/reverse';
    private const OVERPASS_API_URL = 'https://overpass-api.de/api/interpreter';
    
    /**
     * Buscar lugares cercanos usando Nominatim
     * 
     * @param float $latitude
     * @param float $longitude
     * @param array $params Parámetros de búsqueda
     * @return array
     */
    public function buscarLugaresCercanos(float $latitude, float $longitude, array $params = [])
    {
        // Validar coordenadas
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            Log::error('OpenStreetMap: Coordenadas inválidas', [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            return [];
        }
        
        // Validar rango de coordenadas (latitud: -90 a 90, longitud: -180 a 180)
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            Log::error('OpenStreetMap: Coordenadas fuera de rango válido', [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            return [];
        }
        
        $defaultParams = [
            'q' => '', // Término de búsqueda
            'amenity' => null, // Tipo de amenidad OSM (restaurant, cafe, beach, etc.)
            'shop' => null,
            'tourism' => null,
            'leisure' => null,
            'radius' => 5000, // Radio en metros (default 5km)
            'limit' => 20, // Límite de resultados
            'format' => 'json',
            'addressdetails' => 1,
            'extratags' => 1,
            'namedetails' => 1,
        ];
        
        $params = array_merge($defaultParams, $params);
        $radioMetros = (int) $params['radius'];
        
        // Construir query string para Nominatim
        // Usar formato específico de Nominatim para búsqueda por coordenadas y tags
        $queryParts = [];
        
        // Construir query con formato OSM específico
        $osmQuery = '';
        
        if (!empty($params['amenity'])) {
            $osmQuery .= '[amenity=' . $params['amenity'] . ']';
        }
        
        if (!empty($params['shop'])) {
            $osmQuery .= '[shop=' . $params['shop'] . ']';
        }
        
        if (!empty($params['tourism'])) {
            $osmQuery .= '[tourism=' . $params['tourism'] . ']';
        }
        
        if (!empty($params['leisure'])) {
            $osmQuery .= '[leisure=' . $params['leisure'] . ']';
        }
        
        // Si hay término de búsqueda, combinarlo
        if (!empty($params['q'])) {
            $queryParts[] = $params['q'];
        }
        
        // Construir query params para Nominatim
        // Usar formato "around" para limitar búsqueda por coordenadas
        $queryParams = [
            'format' => 'json',
            'addressdetails' => 1,
            'extratags' => 1,
            'namedetails' => 1,
            'limit' => min($params['limit'] * 2, 50), // Pedir el doble para filtrar después
            'dedupe' => 1, // Eliminar duplicados
        ];
        
        // Construir el query con las coordenadas y el radio
        // Nominatim busca cerca de las coordenadas especificadas
        $baseQuery = '';
        if (!empty($osmQuery)) {
            $baseQuery = $osmQuery;
        } else if (!empty($params['q'])) {
            $baseQuery = $params['q'];
        } else {
            // Si no hay nada específico, buscar cualquier cosa cerca
            $baseQuery = '';
        }
        
        // Usar Overpass API para búsquedas precisas por coordenadas
        // Overpass es mucho más eficiente para búsquedas por tags OSM y coordenadas
        try {
            // Construir query Overpass
            $overpassQuery = $this->construirQueryOverpass($latitude, $longitude, $radioMetros, $params);
            
            Log::info('OpenStreetMap: Ejecutando query Overpass', [
                'coordenadas' => "{$latitude},{$longitude}",
                'radio' => $radioMetros . 'm',
                'params' => $params,
                'query_length' => strlen($overpassQuery),
                'query_preview' => substr($overpassQuery, 0, 300) . '...'
            ]);
            
            $response = Http::withHeaders([
                'User-Agent' => 'ApartamentosAlgeciras/1.0 (contact@apartamentosalgeciras.com)',
            ])->timeout(30)->asForm()->post(self::OVERPASS_API_URL, [
                'data' => $overpassQuery
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['elements']) && is_array($data['elements']) && !empty($data['elements'])) {
                    // Procesar resultados de Overpass
                    $procesados = [];
                    
                    foreach ($data['elements'] as $element) {
                        if (!isset($element['lat']) && !isset($element['center'])) {
                            continue;
                        }
                        
                        $resultado = $this->procesarResultadoOverpass($element, $latitude, $longitude);
                        
                        // Filtrar resultados nulos
                        if ($resultado === null) {
                            continue;
                        }
                        
                        // Filtrar por distancia
                        if ($resultado['distancia_metros'] <= $radioMetros) {
                            $procesados[] = $resultado;
                        }
                    }
                    
                    // Ordenar por distancia
                    usort($procesados, function($a, $b) {
                        return $a['distancia_metros'] <=> $b['distancia_metros'];
                    });
                    
                    // Limitar resultados (máximo 10 para dar margen, luego se limitará a 5 por categoría en el controlador)
                    $procesados = array_slice($procesados, 0, min($params['limit'], 10));
                    
                    Log::info('OpenStreetMap: Búsqueda Overpass completada', [
                        'coordenadas' => "{$latitude},{$longitude}",
                        'radio' => $radioMetros . 'm',
                        'total_encontrados' => count($data['elements']),
                        'dentro_radio' => count($procesados),
                        'devolviendo' => count($procesados)
                    ]);
                    
                    return $procesados;
                }
                
                Log::info('OpenStreetMap: No se encontraron resultados en Overpass', [
                    'coordenadas' => "{$latitude},{$longitude}",
                    'radio' => $radioMetros . 'm',
                    'response' => $response->body()
                ]);
            } else {
                Log::warning('OpenStreetMap: Respuesta Overpass no exitosa', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500)
                ]);
            }
            
            // Fallback a Nominatim si Overpass falla
            return $this->buscarConNominatim($latitude, $longitude, $params, $radioMetros);
            
        } catch (\Exception $e) {
            Log::error('OpenStreetMap: Error al buscar lugares con Overpass', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            
            // Fallback a Nominatim
            return $this->buscarConNominatim($latitude, $longitude, $params, $radioMetros);
        }
    }
    
    /**
     * Buscar lugares por categoría predefinida
     */
    public function buscarPorCategoria(float $latitude, float $longitude, string $categoria, array $config = [])
    {
        $categoriasConfig = [
            'restaurantes' => [
                'amenity' => 'restaurant',
                'radius' => 2000,
                'limit' => 15,
            ],
            'cafes' => [
                'amenity' => 'cafe',
                'radius' => 1500,
                'limit' => 10,
            ],
            'bares' => [
                'amenity' => 'bar',
                'radius' => 2000,
                'limit' => 10,
            ],
            'playas' => [
                'q' => 'beach',
                'radius' => 10000,
                'limit' => 10,
            ],
            'aeropuertos' => [
                'q' => 'airport',
                'radius' => 50000,
                'limit' => 5,
            ],
            'transporte' => [
                'amenity' => 'bus_station',
                'radius' => 3000,
                'limit' => 10,
            ],
            'supermercados' => [
                'shop' => 'supermarket',
                'radius' => 2000,
                'limit' => 10,
            ],
            'farmacias' => [
                'amenity' => 'pharmacy',
                'radius' => 2000,
                'limit' => 10,
            ],
            'hospitales' => [
                'amenity' => 'hospital',
                'radius' => 5000,
                'limit' => 5,
            ],
            'museos' => [
                'tourism' => 'museum',
                'radius' => 5000,
                'limit' => 10,
            ],
            'iglesias' => [
                'amenity' => 'place_of_worship',
                'radius' => 3000,
                'limit' => 10,
            ],
            'parques' => [
                'leisure' => 'park',
                'radius' => 3000,
                'limit' => 10,
            ],
        ];
        
        $configBase = $categoriasConfig[$categoria] ?? [];
        $params = array_merge($configBase, $config);
        
        return $this->buscarLugaresCercanos($latitude, $longitude, $params);
    }
    
    /**
     * Procesar resultado de Nominatim y calcular distancia
     */
    private function procesarResultado(array $item, float $latitude, float $longitude): array
    {
        $lat = (float) $item['lat'];
        $lon = (float) $item['lon'];
        
        return [
            'nombre' => $item['display_name'] ?? $item['name'] ?? 'Sin nombre',
            'nombre_corto' => $item['name'] ?? null,
            'tipo' => $item['type'] ?? null,
            'categoria_osm' => $item['class'] ?? null,
            'amenity' => $item['amenity'] ?? null,
            'latitude' => $lat,
            'longitude' => $lon,
            'distancia_metros' => $this->calcularDistancia($latitude, $longitude, $lat, $lon),
            'direccion' => $this->extraerDireccion($item['address'] ?? []),
            'osm_id' => $item['osm_id'] ?? null,
            'osm_type' => $item['osm_type'] ?? null,
            'extratags' => $item['extratags'] ?? [],
        ];
    }
    
    /**
     * Calcular distancia entre dos puntos (fórmula Haversine)
     */
    private function calcularDistancia(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Radio de la Tierra en metros
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Extraer dirección formateada
     */
    private function extraerDireccion(array $address): string
    {
        $parts = [];
        
        if (isset($address['road'])) {
            $parts[] = $address['road'];
        }
        if (isset($address['house_number'])) {
            $parts[] = $address['house_number'];
        }
        if (isset($address['postcode'])) {
            $parts[] = $address['postcode'];
        }
        if (isset($address['city']) || isset($address['town'])) {
            $parts[] = $address['city'] ?? $address['town'];
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Construir query Overpass para búsqueda por coordenadas y tags
     */
    private function construirQueryOverpass(float $latitude, float $longitude, int $radioMetros, array $params): string
    {
        // Radio en grados aproximados (1 grado ≈ 111km)
        $radioGrados = $radioMetros / 111000;
        
        // Construir filtros según los parámetros
        $filtros = [];
        
        if (!empty($params['amenity'])) {
            $filtros[] = '["amenity"="' . $params['amenity'] . '"]';
        }
        
        if (!empty($params['shop'])) {
            $filtros[] = '["shop"="' . $params['shop'] . '"]';
        }
        
        if (!empty($params['tourism'])) {
            $filtros[] = '["tourism"="' . $params['tourism'] . '"]';
        }
        
        if (!empty($params['leisure'])) {
            $filtros[] = '["leisure"="' . $params['leisure'] . '"]';
        }
        
        // Si no hay filtros específicos y hay término de búsqueda, buscar por nombre
        $filtroTexto = '';
        if (empty($filtros) && !empty($params['q'])) {
            $filtroTexto = '["name"~"' . addslashes($params['q']) . '",i]';
        }
        
        // Construir query Overpass QL
        $query = '[out:json][timeout:25];' . "\n";
        $query .= '(' . "\n";
        
        // Detectar si estamos buscando playas
        $esBusquedaPlaya = false;
        if (!empty($params['leisure']) && $params['leisure'] === 'beach') {
            $esBusquedaPlaya = true;
        } elseif (!empty($params['q']) && (stripos($params['q'], 'beach') !== false || stripos($params['q'], 'playa') !== false)) {
            $esBusquedaPlaya = true;
        }
        
        // Detectar tipos que suelen ser ways/areas en OSM (no solo nodes)
        $tiposComoWay = ['leisure', 'tourism'];
        $esAmenityTransporte = false;
        if (isset($params['amenity'])) {
            $amenitiesComoWay = ['train_station', 'subway_entrance', 'bus_station'];
            $esAmenityTransporte = in_array($params['amenity'], $amenitiesComoWay);
        }
        
        // Buscar nodos y ways (para playas, parques, museos, estaciones que a veces son areas/ways)
        if (!empty($filtros)) {
            foreach ($filtros as $filtro) {
                // Buscar en nodos
                $query .= '  node' . $filtro . '(around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
                
                // También buscar en ways para lugares que suelen ser áreas
                $esLeisure = strpos($filtro, '"leisure"') !== false;
                $esTourism = strpos($filtro, '"tourism"') !== false;
                
                if ($esBusquedaPlaya || $esLeisure || $esTourism || $esAmenityTransporte) {
                    $query .= '  way' . $filtro . '(around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
                    // También buscar en relations para algunos tipos (ej: estaciones de tren grandes)
                    if ($esAmenityTransporte) {
                        $query .= '  relation' . $filtro . '(around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
                    }
                }
            }
        } else if (!empty($filtroTexto)) {
            $query .= '  node' . $filtroTexto . '(around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
            // Para búsquedas de playa, también buscar en ways
            if ($esBusquedaPlaya) {
                $query .= '  way["leisure"="beach"](around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
            }
        } else {
            // Búsqueda genérica de cualquier POI
            $query .= '  node["amenity"](around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
            $query .= '  node["shop"](around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
            $query .= '  node["tourism"](around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
            $query .= '  node["leisure"](around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
            // Incluir ways para playas y parques en búsqueda genérica (estos suelen ser áreas/ways)
            $query .= '  way["leisure"="beach"](around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
            $query .= '  way["leisure"="park"](around:' . $radioMetros . ',' . $latitude . ',' . $longitude . ');' . "\n";
        }
        
        $query .= ');' . "\n";
        // Usar 'out center meta;' para ways/relations obtiene el centro de la geometría
        // 'out meta;' para nodes obtiene las coordenadas directamente
        $query .= 'out center meta;' . "\n";
        
        Log::debug('Query Overpass generado', [
            'query' => $query,
            'params' => $params
        ]);
        
        return $query;
    }
    
    /**
     * Procesar resultado de Overpass API
     */
    private function procesarResultadoOverpass(array $element, float $latitude, float $longitude): ?array
    {
        // Overpass puede devolver coordenadas directamente o en 'center' para ways/relations
        $lat = null;
        $lon = null;
        
        if (isset($element['lat']) && isset($element['lon'])) {
            $lat = (float) $element['lat'];
            $lon = (float) $element['lon'];
        } elseif (isset($element['center']['lat']) && isset($element['center']['lon'])) {
            $lat = (float) $element['center']['lat'];
            $lon = (float) $element['center']['lon'];
        }
        
        if ($lat === null || $lon === null || $lat == 0 || $lon == 0) {
            return null;
        }
        
        $tags = $element['tags'] ?? [];
        
        // Construir nombre - mejorado para playas y lugares sin nombre
        $nombre = null;
        
        // Priorizar nombres en español, luego inglés, luego cualquier idioma
        if (isset($tags['name:es']) && !empty($tags['name:es'])) {
            $nombre = $tags['name:es'];
        } elseif (isset($tags['name:en']) && !empty($tags['name:en'])) {
            $nombre = $tags['name:en'];
        } elseif (isset($tags['name']) && !empty($tags['name'])) {
            $nombre = $tags['name'];
        }
        
        // Si es una playa sin nombre, usar el tipo
        if (empty($nombre) && isset($tags['leisure']) && $tags['leisure'] === 'beach') {
            $nombre = 'Playa';
            // Intentar obtener nombre de la localidad cercana
            if (isset($tags['addr:city']) || isset($tags['addr:town'])) {
                $nombre = 'Playa de ' . ($tags['addr:city'] ?? $tags['addr:town']);
            }
        }
        
        // Si aún no hay nombre, intentar usar el tipo
        if (empty($nombre)) {
            $tipoNombre = $tags['amenity'] ?? $tags['shop'] ?? $tags['tourism'] ?? $tags['leisure'] ?? null;
            if ($tipoNombre) {
                $nombre = ucfirst(str_replace('_', ' ', $tipoNombre));
            } else {
                $nombre = 'Sin nombre';
            }
        }
        
        // Añadir dirección si está disponible
        $direccion = [];
        if (isset($tags['addr:street'])) {
            $direccion[] = $tags['addr:street'];
        }
        if (isset($tags['addr:housenumber'])) {
            $direccion[] = $tags['addr:housenumber'];
        }
        if (isset($tags['addr:city']) || isset($tags['addr:town'])) {
            $direccion[] = $tags['addr:city'] ?? $tags['addr:town'];
        }
        if (isset($tags['addr:postcode'])) {
            $direccion[] = $tags['addr:postcode'];
        }
        
        $displayName = $nombre;
        if (!empty($direccion)) {
            $displayName .= ', ' . implode(', ', $direccion);
        }
        
        return [
            'nombre' => $displayName,
            'nombre_corto' => $nombre,
            'tipo' => $tags['amenity'] ?? $tags['shop'] ?? $tags['tourism'] ?? $tags['leisure'] ?? 'unknown',
            'categoria_osm' => $element['type'] ?? 'node',
            'amenity' => $tags['amenity'] ?? null,
            'latitude' => $lat,
            'longitude' => $lon,
            'distancia_metros' => $this->calcularDistancia($latitude, $longitude, $lat, $lon),
            'direccion' => implode(', ', $direccion),
            'osm_id' => $element['id'] ?? null,
            'osm_type' => $element['type'] ?? 'node',
            'extratags' => $tags,
        ];
    }
    
    /**
     * Fallback: Buscar usando Nominatim
     */
    private function buscarConNominatim(float $latitude, float $longitude, array $params, int $radioMetros): array
    {
        // Construir query para Nominatim de forma más simple
        $queryParams = [
            'format' => 'json',
            'addressdetails' => 1,
            'extratags' => 1,
            'namedetails' => 1,
            'limit' => min($params['limit'] * 3, 50),
            'lat' => $latitude,
            'lon' => $longitude,
            'radius' => $radioMetros * 2, // Aumentar radio para obtener más resultados
        ];
        
        // Construir query simple
        $q = [];
        if (!empty($params['amenity'])) {
            $q[] = $params['amenity'];
        }
        if (!empty($params['shop'])) {
            $q[] = $params['shop'];
        }
        if (!empty($params['q'])) {
            $q[] = $params['q'];
        }
        
        $queryParams['q'] = implode(' ', $q);
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ApartamentosAlgeciras/1.0 (contact@apartamentosalgeciras.com)',
            ])->timeout(15)->get(self::NOMINATIM_BASE_URL, $queryParams);
            
            if ($response->successful()) {
                $results = $response->json();
                
                if (is_array($results) && !empty($results)) {
                    $procesados = [];
                    
                    foreach ($results as $item) {
                        $resultado = $this->procesarResultado($item, $latitude, $longitude);
                        
                        if ($resultado['distancia_metros'] <= $radioMetros) {
                            $procesados[] = $resultado;
                        }
                    }
                    
                    usort($procesados, function($a, $b) {
                        return $a['distancia_metros'] <=> $b['distancia_metros'];
                    });
                    
                    $procesados = array_slice($procesados, 0, $params['limit']);
                    
                    Log::info('OpenStreetMap: Búsqueda Nominatim completada (fallback)', [
                        'coordenadas' => "{$latitude},{$longitude}",
                        'radio' => $radioMetros . 'm',
                        'total_encontrados' => count($results),
                        'dentro_radio' => count($procesados)
                    ]);
                    
                    return $procesados;
                }
            }
        } catch (\Exception $e) {
            Log::error('OpenStreetMap: Error en fallback Nominatim', [
                'error' => $e->getMessage()
            ]);
        }
        
        return [];
    }
    
    /**
     * Geocodificación inversa: obtener información de una coordenada
     */
    public function reverseGeocode(float $latitude, float $longitude)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ApartamentosAlgeciras/1.0 (contact@apartamentosalgeciras.com)',
            ])->timeout(10)->get(self::REVERSE_BASE_URL, [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'addressdetails' => 1,
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('OpenStreetMap: Error en reverse geocode', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            
            return null;
        }
    }
}
