<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoRequirement extends Model
{
    protected $table = 'photo_requirements';

    protected $fillable = ['nombre', 'cantidad', 'descripcion'];
}

