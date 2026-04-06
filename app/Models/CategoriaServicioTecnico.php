<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CategoriaServicioTecnico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categorias_servicios_tecnicos';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'icono',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            if (empty($categoria->slug)) {
                $baseSlug = Str::slug($categoria->nombre);
                $slug = $baseSlug;
                $counter = 1;
                
                // Verificar si el slug ya existe (excluyendo soft deleted) y generar uno único
                while (static::withoutTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $categoria->slug = $slug;
            }
        });

        static::updating(function ($categoria) {
            if ($categoria->isDirty('nombre') && empty($categoria->slug)) {
                $baseSlug = Str::slug($categoria->nombre);
                $slug = $baseSlug;
                $counter = 1;
                
                // Verificar si el slug ya existe (excluyendo el registro actual y soft deleted) y generar uno único
                while (static::withoutTrashed()->where('slug', $slug)->where('id', '!=', $categoria->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $categoria->slug = $slug;
            }
        });
    }

    /**
     * Relación con servicios técnicos
     */
    public function servicios()
    {
        return $this->hasMany(ServicioTecnico::class, 'categoria_id');
    }

    /**
     * Scope para categorías activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
