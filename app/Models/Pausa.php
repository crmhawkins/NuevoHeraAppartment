<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezone;

class Pausa extends Model
{
    use HasLocalTimezone;
    
    protected $fillable = ['fichaje_id', 'inicio_pausa', 'fin_pausa'];

    public function fichaje()
    {
        return $this->belongsTo(Fichaje::class);
    }
}