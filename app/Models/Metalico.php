<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Metalico extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'importe', 'reserva_id', 'fecha_ingreso', 'tipo', 'observaciones'];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }
}
