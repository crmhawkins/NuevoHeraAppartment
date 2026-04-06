<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServicioTecnico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'servicios_tecnicos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'slug',
        'descripcion',
        'unidad_medida',
        'precio_base',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'precio_base' => 'decimal:2',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($servicio) {
            if (empty($servicio->slug)) {
                $baseSlug = Str::slug($servicio->nombre);
                $slug = $baseSlug;
                $counter = 1;
                
                // Verificar si el slug ya existe (excluyendo soft deleted) y generar uno único
                while (static::withoutTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $servicio->slug = $slug;
            }
        });

        static::updating(function ($servicio) {
            if ($servicio->isDirty('nombre') && empty($servicio->slug)) {
                $baseSlug = Str::slug($servicio->nombre);
                $slug = $baseSlug;
                $counter = 1;
                
                // Verificar si el slug ya existe (excluyendo el registro actual y soft deleted) y generar uno único
                while (static::withoutTrashed()->where('slug', $slug)->where('id', '!=', $servicio->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $servicio->slug = $slug;
            }
        });
    }

    /**
     * Relación con categoría
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaServicioTecnico::class, 'categoria_id');
    }

    /**
     * Relación con técnicos (many-to-many con precios)
     */
    public function tecnicos()
    {
        return $this->belongsToMany(Reparaciones::class, 'tecnico_servicio_precio', 'servicio_id', 'tecnico_id')
                    ->withPivot('precio', 'observaciones', 'activo')
                    ->withTimestamps();
    }

    /**
     * Scope para servicios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }
}
