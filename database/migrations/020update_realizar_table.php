<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Las columnas dormitorio_photo, bano_photo, armario_photo, canape_photo, salon_photo, cocina_photo
        // ya existen en la tabla apartamento_limpieza desde la migración 012create_realizar_apartamento.php
        // Por lo tanto, no necesitamos agregarlas nuevamente.

        // Si necesitas cambiar el tipo de dato de timestamp a tinyInteger,
        // deberías crear una nueva migración específica para eso.
    }

    public function down()
    {
        // No hay nada que revertir ya que no se agregaron columnas
    }
};
