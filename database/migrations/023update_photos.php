<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // La columna reserva_id ya existe en la tabla photos
        // Esta migración no debe agregar la misma columna nuevamente.
        
        // Si necesitas hacer algún cambio adicional, deberías crear una nueva migración específica.
    }

    public function down()
    {
        // No hay nada que revertir ya que no se agregaron columnas
    }
};
