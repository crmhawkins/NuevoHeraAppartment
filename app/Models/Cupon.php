<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Cupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cupones';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo_descuento',
        'valor_descuento',
        'usos_maximos',
        'usos_por_cliente',
        'usos_actuales',
        'importe_minimo',
        'descuento_maximo',
        'fecha_inicio',
        'fecha_fin',
        'reserva_desde',
        'reserva_hasta',
        'noches_minimas',
        'apartamentos_ids',
        'edificios_ids',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'valor_descuento' => 'decimal:2',
        'importe_minimo' => 'decimal:2',
        'descuento_maximo' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'reserva_desde' => 'date',
        'reserva_hasta' => 'date',
        'apartamentos_ids' => 'array',
        'edificios_ids' => 'array',
        'activo' => 'boolean',
    ];

    /**
     * Relaciones
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function usos()
    {
        return $this->hasMany(CuponUso::class);
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeVigentes($query)
    {
        $hoy = Carbon::today();
        return $query->where(function ($q) use ($hoy) {
            $q->whereNull('fecha_inicio')
                ->orWhere('fecha_inicio', '<=', $hoy);
        })->where(function ($q) use ($hoy) {
            $q->whereNull('fecha_fin')
                ->orWhere('fecha_fin', '>=', $hoy);
        });
    }

    public function scopeDisponibles($query)
    {
        return $query->activos()
            ->vigentes()
            ->where(function ($q) {
                $q->whereNull('usos_maximos')
                    ->orWhereRaw('usos_actuales < usos_maximos');
            });
    }

    /**
     * Validar si el cupón es aplicable
     */
    public function esAplicable($importeReserva, $fechaEntrada, $fechaSalida, $apartamentoId = null, $clienteId = null)
    {
        $errores = [];

        // 1. Verificar si está activo
        if (!$this->activo) {
            $errores[] = 'El cupón no está activo.';
        }

        // 2. Verificar fechas de validez del cupón
        $hoy = Carbon::today();
        if ($this->fecha_inicio && $hoy->lt($this->fecha_inicio)) {
            $errores[] = 'El cupón aún no es válido. Válido desde: ' . $this->fecha_inicio->format('d/m/Y');
        }
        if ($this->fecha_fin && $hoy->gt($this->fecha_fin)) {
            $errores[] = 'El cupón ha expirado. Válido hasta: ' . $this->fecha_fin->format('d/m/Y');
        }

        // 3. Verificar usos máximos
        if ($this->usos_maximos && $this->usos_actuales >= $this->usos_maximos) {
            $errores[] = 'El cupón ha alcanzado su límite de usos.';
        }

        // 4. Verificar usos por cliente
        if ($clienteId && $this->usos_por_cliente) {
            $usosCliente = $this->usos()->where('cliente_id', $clienteId)->count();
            if ($usosCliente >= $this->usos_por_cliente) {
                $errores[] = 'Ya has utilizado este cupón el máximo de veces permitido.';
            }
        }

        // 5. Verificar importe mínimo
        if ($this->importe_minimo && $importeReserva < $this->importe_minimo) {
            $errores[] = 'El importe mínimo para este cupón es de ' . number_format($this->importe_minimo, 2) . ' €';
        }

        // 6. Verificar fechas de reserva
        if ($this->reserva_desde && Carbon::parse($fechaEntrada)->lt($this->reserva_desde)) {
            $errores[] = 'El cupón solo es válido para reservas desde ' . $this->reserva_desde->format('d/m/Y');
        }
        if ($this->reserva_hasta && Carbon::parse($fechaEntrada)->gt($this->reserva_hasta)) {
            $errores[] = 'El cupón solo es válido para reservas hasta ' . $this->reserva_hasta->format('d/m/Y');
        }

        // 7. Verificar noches mínimas
        if ($this->noches_minimas) {
            $noches = Carbon::parse($fechaEntrada)->diffInDays(Carbon::parse($fechaSalida));
            if ($noches < $this->noches_minimas) {
                $errores[] = 'El cupón requiere un mínimo de ' . $this->noches_minimas . ' noches.';
            }
        }

        // 8. Verificar apartamento permitido
        if ($apartamentoId && $this->apartamentos_ids && !in_array($apartamentoId, $this->apartamentos_ids)) {
            $errores[] = 'El cupón no es válido para este apartamento.';
        }

        // 9. Verificar edificio permitido
        if ($apartamentoId && $this->edificios_ids) {
            $apartamento = Apartamento::find($apartamentoId);
            if ($apartamento && !in_array($apartamento->edificio_id, $this->edificios_ids)) {
                $errores[] = 'El cupón no es válido para apartamentos de este edificio.';
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Calcular el descuento a aplicar
     */
    public function calcularDescuento($importeOriginal)
    {
        if ($this->tipo_descuento === 'porcentaje') {
            $descuento = ($importeOriginal * $this->valor_descuento) / 100;
            
            // Aplicar descuento máximo si está definido
            if ($this->descuento_maximo && $descuento > $this->descuento_maximo) {
                $descuento = $this->descuento_maximo;
            }
        } else {
            // Descuento fijo
            $descuento = $this->valor_descuento;
            
            // No puede ser mayor que el importe original
            if ($descuento > $importeOriginal) {
                $descuento = $importeOriginal;
            }
        }

        return round($descuento, 2);
    }

    /**
     * Registrar uso del cupón
     */
    public function registrarUso($reservaId, $clienteId, $importeOriginal, $descuentoAplicado, $importeFinal, $ipAddress = null)
    {
        // Incrementar contador
        $this->increment('usos_actuales');

        // Crear registro de uso
        return $this->usos()->create([
            'reserva_id' => $reservaId,
            'cliente_id' => $clienteId,
            'importe_original' => $importeOriginal,
            'descuento_aplicado' => $descuentoAplicado,
            'importe_final' => $importeFinal,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Obtener texto descriptivo del descuento
     */
    public function getDescripcionDescuentoAttribute()
    {
        if ($this->tipo_descuento === 'porcentaje') {
            $texto = $this->valor_descuento . '% de descuento';
            if ($this->descuento_maximo) {
                $texto .= ' (máx. ' . number_format($this->descuento_maximo, 2) . ' €)';
            }
            return $texto;
        }
        
        return number_format($this->valor_descuento, 2) . ' € de descuento';
    }

    /**
     * Verificar si el cupón está vigente
     */
    public function getEsVigenteAttribute()
    {
        $hoy = Carbon::today();
        
        $inicioValido = !$this->fecha_inicio || $hoy->gte($this->fecha_inicio);
        $finValido = !$this->fecha_fin || $hoy->lte($this->fecha_fin);
        
        return $inicioValido && $finValido;
    }

    /**
     * Verificar si tiene usos disponibles
     */
    public function getTieneUsosDisponiblesAttribute()
    {
        return !$this->usos_maximos || $this->usos_actuales < $this->usos_maximos;
    }
}
