<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('edificios')) return; // [2026-04-30] idempotente
        Schema::table('edificios', function (Blueprint $table) {
            if (!Schema::hasColumn('edificios', 'metodo_entrada')) {
                $table->string('metodo_entrada', 20)->nullable()->after('codigo_establecimiento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('edificios', function (Blueprint $table) {
            if (Schema::hasColumn('edificios', 'metodo_entrada')) {
                $table->dropColumn('metodo_entrada');
            }
        });
    }
};

