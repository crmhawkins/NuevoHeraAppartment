<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionDescuento extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'porcentaje_descuento',
        'porcentaje_incremento',
        'edificio_id',
        'activo',
        'condiciones'
    ];

    protected $casts = [
        'porcentaje_descuento' => 'decimal:2',
        'porcentaje_incremento' => 'decimal:2',
        'activo' => 'boolean',
        'condiciones' => 'array'
    ];

    /**
     * Relación con el edificio
     */
    public function edificio()
    {
        return $this->belongsTo(Edificio::class);
    }

    /**
     * Relación con historial de descuentos
     */
    public function historialDescuentos()
    {
        return $this->hasMany(HistorialDescuento::class);
    }

    /**
     * Scope para configuraciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener el porcentaje de descuento formateado
     */
    public function getPorcentajeFormateadoAttribute()
    {
        return $this->porcentaje_descuento . '%';
    }

    /**
     * Calcular precio con descuento
     */
    public function calcularPrecioConDescuento($precioOriginal)
    {
        $factorDescuento = (100 - $this->porcentaje_descuento) / 100;
        return $precioOriginal * $factorDescuento;
    }

    /**
     * Calcular ahorro por día
     */
    public function calcularAhorroPorDia($precioOriginal)
    {
        return $precioOriginal - $this->calcularPrecioConDescuento($precioOriginal);
    }

    /**
     * Obtener el porcentaje de incremento formateado
     */
    public function getPorcentajeIncrementoFormateadoAttribute()
    {
        return $this->porcentaje_incremento . '%';
    }

    /**
     * Calcular precio con incremento
     */
    public function calcularPrecioConIncremento($precioOriginal)
    {
        $factorIncremento = (100 + $this->porcentaje_incremento) / 100;
        return $precioOriginal * $factorIncremento;
    }

    /**
     * Calcular ganancia por día (incremento)
     */
    public function calcularGananciaPorDia($precioOriginal)
    {
        return $this->calcularPrecioConIncremento($precioOriginal) - $precioOriginal;
    }

    /**
     * Calcular porcentaje de ocupación del edificio
     */
    public function calcularOcupacionEdificio($fechaInicio, $fechaFin)
    {
        if (!$this->edificio) {
            return 0;
        }

        $apartamentos = $this->edificio->apartamentos;
        $totalDias = 0;
        $diasOcupados = 0;

        foreach ($apartamentos as $apartamento) {
            $diasApartamento = $fechaInicio->diffInDays($fechaFin) + 1;
            $totalDias += $diasApartamento;

            // Contar días ocupados para este apartamento
            for ($fecha = $fechaInicio->copy(); $fecha->lte($fechaFin); $fecha->addDay()) {
                $reserva = \App\Models\Reserva::where('apartamento_id', $apartamento->id)
                    ->where('fecha_entrada', '<=', $fecha)
                    ->where('fecha_salida', '>', $fecha)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($reserva) {
                    $diasOcupados++;
                }
            }
        }

        return $totalDias > 0 ? ($diasOcupados / $totalDias) * 100 : 0;
    }

    /**
     * Determinar si aplicar descuento o incremento basado en ocupación
     */
    public function determinarAccionOcupacion($fechaInicio, $fechaFin)
    {
        $ocupacion = $this->calcularOcupacionEdificio($fechaInicio, $fechaFin);
        $ocupacionMinima = $this->condiciones['ocupacion_minima'] ?? 60;
        $ocupacionMaxima = $this->condiciones['ocupacion_maxima'] ?? 80;

        if ($ocupacion < $ocupacionMinima) {
            return [
                'accion' => 'descuento',
                'porcentaje' => $this->porcentaje_descuento,
                'ocupacion_actual' => $ocupacion,
                'ocupacion_limite' => $ocupacionMinima
            ];
        } elseif ($ocupacion > $ocupacionMaxima) {
            return [
                'accion' => 'incremento',
                'porcentaje' => $this->porcentaje_incremento,
                'ocupacion_actual' => $ocupacion,
                'ocupacion_limite' => $ocupacionMaxima
            ];
        } else {
            return [
                'accion' => 'ninguna',
                'porcentaje' => 0,
                'ocupacion_actual' => $ocupacion,
                'ocupacion_limite' => null
            ];
        }
    }
}
