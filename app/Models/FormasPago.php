<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormasPago extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'formas_pago';

    protected $fillable = [
        'nombre',
    ];
}
