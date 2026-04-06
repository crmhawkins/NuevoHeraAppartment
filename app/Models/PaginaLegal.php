<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Translatable;

class PaginaLegal extends Model
{
    use HasFactory, SoftDeletes, Translatable;

    protected $table = 'paginas_legales';

    protected $fillable = [
        'titulo',
        'slug',
        'contenido',
        'fecha_actualizacion',
        'orden',
        'activo',
        'mostrar_en_sidebar',
    ];

    protected $casts = [
        'fecha_actualizacion' => 'date',
        'activo' => 'boolean',
        'mostrar_en_sidebar' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pagina) {
            if (empty($pagina->slug)) {
                $pagina->slug = Str::slug($pagina->titulo);
            }
        });

        static::updating(function ($pagina) {
            if ($pagina->isDirty('titulo') && empty($pagina->slug)) {
                $pagina->slug = Str::slug($pagina->titulo);
            }
        });
    }

    /**
     * Scope para páginas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para páginas visibles en sidebar
     */
    public function scopeVisiblesEnSidebar($query)
    {
        return $query->where('mostrar_en_sidebar', true);
    }

    /**
     * Scope para ordenar
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('titulo');
    }
}
