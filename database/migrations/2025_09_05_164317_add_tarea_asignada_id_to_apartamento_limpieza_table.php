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
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->unsignedBigInteger('tarea_asignada_id')->nullable()->after('empleada_id');
            $table->string('origen')->nullable()->after('tarea_asignada_id');
            $table->index('tarea_asignada_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->dropIndex(['tarea_asignada_id']);
            $table->dropColumn(['tarea_asignada_id', 'origen']);
        });
    }
};
