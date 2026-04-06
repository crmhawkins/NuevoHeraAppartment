<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistTareaGeneral extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'checklist_tarea_generals';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'activo',
        'orden'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer'
    ];

    // Relación con items de checklist
    public function items()
    {
        return $this->hasMany(ItemChecklistTareaGeneral::class, 'checklist_id');
    }

    // Scope para checklists activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para ordenar
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden', 'asc')->orderBy('nombre', 'asc');
    }

    // Scope por categoría
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }
}
