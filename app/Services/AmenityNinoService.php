<?php

namespace App\Services;

use App\Models\Amenity;
use App\Models\Reserva;
use Illuminate\Support\Collection;

class AmenityNinoService
{
    /**
     * Obtener amenities automáticos para niños según la reserva
     */
    public function obtenerAmenitiesAutomaticos(Reserva $reserva): Collection
    {
        if (!$reserva->numero_ninos || $reserva->numero_ninos <= 0) {
            return collect();
        }

        $edadesNinos = $reserva->edades_ninos ?? [];
        $amenitiesAutomaticos = collect();

        // Obtener todos los amenities para niños
        $amenitiesNinos = Amenity::paraNinos()->activos()->get();

        foreach ($amenitiesNinos as $amenity) {
            $cantidad = $amenity->calcularCantidadParaNinos($reserva->numero_ninos, $edadesNinos);
            
            if ($cantidad > 0) {
                $amenity->cantidad_calculada = $cantidad;
                $amenity->motivo = $this->generarMotivo($amenity, $edadesNinos);
                $amenitiesAutomaticos->push($amenity);
            }
        }

        return $amenitiesAutomaticos;
    }

    /**
     * Generar motivo para el amenity automático
     */
    private function generarMotivo(Amenity $amenity, array $edadesNinos): string
    {
        $numeroNinos = count($edadesNinos);
        $edadesTexto = $this->formatearEdades($edadesNinos);
        
        if ($amenity->tipo_nino) {
            $tipoDesc = $amenity->descripcion_tipo_nino;
            return "Automático para {$numeroNinos} niño(s) de {$tipoDesc} ({$edadesTexto})";
        } else {
            return "Automático para {$numeroNinos} niño(s) ({$edadesTexto})";
        }
    }

    /**
     * Formatear edades para mostrar
     */
    private function formatearEdades(array $edades): string
    {
        if (empty($edades)) {
            return 'edad no especificada';
        }

        $edadesUnicas = array_unique($edades);
        sort($edadesUnicas);
        
        if (count($edadesUnicas) === 1) {
            return $edadesUnicas[0] . ' años';
        } else {
            $ultima = array_pop($edadesUnicas);
            return implode(', ', $edadesUnicas) . ' y ' . $ultima . ' años';
        }
    }

    /**
     * Obtener recomendaciones de amenities por edad
     */
    public function obtenerRecomendacionesPorEdad(int $edad): Collection
    {
        return Amenity::porEdad($edad)->activos()->get();
    }

    /**
     * Obtener amenities por tipo de niño
     */
    public function obtenerPorTipoNino(string $tipo): Collection
    {
        return Amenity::porTipoNino($tipo)->activos()->get();
    }

    /**
     * Crear amenity automático para niños
     */
    public function crearAmenityAutomatico(string $nombre, string $categoria, string $tipoNino, int $cantidadPorNino = 1): Amenity
    {
        $amenity = new Amenity();
        $amenity->nombre = $nombre;
        $amenity->categoria = $categoria;
        $amenity->es_para_ninos = true;
        $amenity->tipo_nino = $tipoNino;
        $amenity->cantidad_por_nino = $cantidadPorNino;
        $amenity->activo = true;
        
        // Establecer rangos de edad según tipo
        switch ($tipoNino) {
            case 'bebe':
                $amenity->edad_minima = 0;
                $amenity->edad_maxima = 2;
                break;
            case 'nino_pequeno':
                $amenity->edad_minima = 3;
                $amenity->edad_maxima = 6;
                break;
            case 'nino_grande':
                $amenity->edad_minima = 7;
                $amenity->edad_maxima = 12;
                break;
            case 'adolescente':
                $amenity->edad_minima = 13;
                $amenity->edad_maxima = 17;
                break;
        }
        
        return $amenity;
    }

    /**
     * Obtener estadísticas de amenities de niños
     */
    public function obtenerEstadisticas(): array
    {
        $totalAmenities = Amenity::paraNinos()->count();
        $amenitiesActivos = Amenity::paraNinos()->activos()->count();
        
        $porTipo = [
            'bebe' => Amenity::porTipoNino('bebe')->count(),
            'nino_pequeno' => Amenity::porTipoNino('nino_pequeno')->count(),
            'nino_grande' => Amenity::porTipoNino('nino_grande')->count(),
            'adolescente' => Amenity::porTipoNino('adolescente')->count(),
        ];
        
        return [
            'total' => $totalAmenities,
            'activos' => $amenitiesActivos,
            'por_tipo' => $porTipo,
            'porcentaje_activos' => $totalAmenities > 0 ? round(($amenitiesActivos / $totalAmenities) * 100, 2) : 0
        ];
    }
}
