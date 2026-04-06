<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareaChecklistCompletado extends Model
{
    use HasFactory;

    protected $table = 'tarea_checklist_completados';

    protected $fillable = [
        'tarea_asignada_id',
        'item_checklist_id',
        'checklist_id',
        'completado_por',
        'estado',
        'fecha_completado'
    ];

    protected $casts = [
        'fecha_completado' => 'datetime',
        'estado' => 'boolean'
    ];

    public function tareaAsignada()
    {
        return $this->belongsTo(TareaAsignada::class, 'tarea_asignada_id');
    }

    public function itemChecklist()
    {
        return $this->belongsTo(ItemChecklist::class, 'item_checklist_id');
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    public function completadoPor()
    {
        return $this->belongsTo(User::class, 'completado_por');
    }
}