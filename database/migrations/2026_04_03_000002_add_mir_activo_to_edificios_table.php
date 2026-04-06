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
        Schema::table('edificios', function (Blueprint $table) {
            $table->boolean('mir_activo')->default(true)->after('metodo_entrada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edificios', function (Blueprint $table) {
            $table->dropColumn('mir_activo');
        });
    }
};
