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
        Schema::table('apartamentos', function (Blueprint $table) {
            // Eliminar el campo 'edificio' redundante ya que usamos 'edificio_id'
            $table->dropColumn('edificio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            // Restaurar el campo 'edificio' si es necesario hacer rollback
            $table->integer('edificio')->nullable();
        });
    }
};
