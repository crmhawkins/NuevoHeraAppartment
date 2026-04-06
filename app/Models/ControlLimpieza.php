<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlLimpieza extends Model
{
    use HasFactory;
    protected $fillable = ['apartamento_id', 'item_checklist_id', 'estado'];

}
