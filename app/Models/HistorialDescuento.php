<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialDescuento extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartamento_id',
        'tarifa_id',
        'configuracion_descuento_id',
        'fecha_aplicacion',
        'fecha_inicio_descuento',
        'fecha_fin_descuento',
        'precio_original',
        'precio_con_descuento',
        'porcentaje_descuento',
        'dias_aplicados',
        'ahorro_total',
        'estado',
        'observaciones',
        'datos_channex',
        'datos_momento'
    ];

    protected $casts = [
        'fecha_aplicacion' => 'date',
        'fecha_inicio_descuento' => 'date',
        'fecha_fin_descuento' => 'date',
        'precio_original' => 'decimal:2',
        'precio_con_descuento' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'dias_aplicados' => 'integer',
        'ahorro_total' => 'decimal:2',
        'datos_channex' => 'array',
        'datos_momento' => 'array'
    ];

    /**
     * Relación con apartamento
     */
    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    /**
     * Relación con tarifa
     */
    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class);
    }

    /**
     * Relación con configuración de descuento
     */
    public function configuracionDescuento()
    {
        return $this->belongsTo(ConfiguracionDescuento::class);
    }

    /**
     * Scope para descuentos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para descuentos aplicados
     */
    public function scopeAplicados($query)
    {
        return $query->where('estado', 'aplicado');
    }

    /**
     * Scope para descuentos por fecha
     */
    public function scopePorFecha($query, $fecha)
    {
        return $query->where('fecha_aplicacion', $fecha);
    }

    /**
     * Obtener el estado formateado
     */
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            'pendiente' => 'Pendiente',
            'aplicado' => 'Aplicado',
            'revertido' => 'Revertido',
            'error' => 'Error'
        ];

        return $estados[$this->estado] ?? $this->estado;
    }

    /**
     * Obtener el porcentaje formateado
     */
    public function getPorcentajeFormateadoAttribute()
    {
        return $this->porcentaje_descuento . '%';
    }

    /**
     * Obtener el rango de fechas formateado
     */
    public function getRangoFechasAttribute()
    {
        return $this->fecha_inicio_descuento->format('d/m/Y') . ' - ' . $this->fecha_fin_descuento->format('d/m/Y');
    }

    /**
     * Obtener datos del momento de aplicación
     */
    public function getDatosMomentoAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Verificar si se cumplían los requisitos en el momento
     */
    public function verificarRequisitosCumplidos()
    {
        if (!$this->datos_momento) {
            return [
                'cumplidos' => false,
                'razon' => 'No hay datos del momento disponibles'
            ];
        }

        $datos = $this->datos_momento;
        
        // Verificar ocupación
        $ocupacionActual = $datos['ocupacion_actual'] ?? 0;
        $ocupacionMinima = $datos['configuracion']['condiciones']['ocupacion_minima'] ?? 60;
        $ocupacionMaxima = $datos['configuracion']['condiciones']['ocupacion_maxima'] ?? 80;
        
        $accion = $datos['accion'] ?? 'ninguna';
        
        if ($accion === 'descuento') {
            $cumplido = $ocupacionActual < $ocupacionMinima;
            return [
                'cumplidos' => $cumplido,
                'razon' => $cumplido 
                    ? "Ocupación ({$ocupacionActual}%) < Mínima ({$ocupacionMinima}%)" 
                    : "Ocupación ({$ocupacionActual}%) >= Mínima ({$ocupacionMinima}%)"
            ];
        } elseif ($accion === 'incremento') {
            $cumplido = $ocupacionActual > $ocupacionMaxima;
            return [
                'cumplidos' => $cumplido,
                'razon' => $cumplido 
                    ? "Ocupación ({$ocupacionActual}%) > Máxima ({$ocupacionMaxima}%)" 
                    : "Ocupación ({$ocupacionActual}%) <= Máxima ({$ocupacionMaxima}%)"
            ];
        }
        
        return [
            'cumplidos' => false,
            'razon' => 'No se aplicó ninguna acción'
        ];
    }

    /**
     * Obtener resumen de datos del momento
     */
    public function getResumenDatosMomentoAttribute()
    {
        if (!$this->datos_momento) {
            return 'No hay datos disponibles';
        }

        $datos = $this->datos_momento;
        $verificacion = $this->verificarRequisitosCumplidos();
        
        $resumen = "📊 DATOS DEL MOMENTO:\n";
        $resumen .= "🏢 Edificio: " . ($datos['edificio']['nombre'] ?? 'N/A') . "\n";
        $resumen .= "📅 Fecha análisis: " . ($datos['fecha_analisis'] ?? 'N/A') . "\n";
        $resumen .= "📈 Ocupación: " . ($datos['ocupacion_actual'] ?? 'N/A') . "%\n";
        $resumen .= "🎯 Acción: " . ($datos['accion'] ?? 'N/A') . "\n";
        $resumen .= "💰 Porcentaje: " . ($datos['porcentaje'] ?? 'N/A') . "%\n";
        $resumen .= "✅ Requisitos cumplidos: " . ($verificacion['cumplidos'] ? 'SÍ' : 'NO') . "\n";
        $resumen .= "📝 Razón: " . $verificacion['razon'];
        
        return $resumen;
    }
}
