<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La columna user_id ya fue agregada en la migración 065update_limpieza.php
        // Esta migración es duplicada y no debe agregar la misma columna nuevamente.

        // Si necesitas hacer algún cambio adicional, deberías crear una nueva migración específica.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay nada que revertir ya que no se agregaron columnas
    }
};
