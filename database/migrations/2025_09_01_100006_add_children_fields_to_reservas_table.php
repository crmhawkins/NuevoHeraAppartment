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
        Schema::table('reservas', function (Blueprint $table) {
            $table->integer('numero_ninos')->default(0)->after('numero_personas');
            $table->json('edades_ninos')->nullable()->after('numero_ninos');
            $table->text('notas_ninos')->nullable()->after('edades_ninos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['numero_ninos', 'edades_ninos', 'notas_ninos']);
        });
    }
};
