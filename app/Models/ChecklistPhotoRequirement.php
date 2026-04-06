<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistPhotoRequirement extends Model
{
    use HasFactory;

    protected $fillable = ['checklist_id', 'nombre', 'descripcion', 'cantidad'];

    /**
     * Relación con Checklist.
     */
    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
}
