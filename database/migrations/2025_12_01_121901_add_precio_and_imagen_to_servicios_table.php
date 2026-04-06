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
        Schema::table('servicios', function (Blueprint $table) {
            $table->decimal('precio', 12, 2)->nullable()->after('descripcion')->comment('Precio del servicio extra');
            $table->string('imagen')->nullable()->after('precio')->comment('URL o path de la imagen del servicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn(['precio', 'imagen']);
        });
    }
};
