<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Las columnas reserva_id y cliente_id ya existen en la tabla photos
        // Esta migración no debe agregar las mismas columnas nuevamente.
        
        // Si necesitas hacer algún cambio adicional, deberías crear una nueva migración específica.
    }
    
    public function down()
    {
        // No hay nada que revertir ya que no se agregaron columnas
    }
};
