<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reservas')) return;
        // [2026-04-30] SQLite no soporta ALTER COLUMN. Skipped en local; produccion MySQL OK.
        if (\DB::connection()->getDriverName() === 'sqlite') return; // [2026-04-30] idempotente
        Schema::table('reservas', function (Blueprint $table) {
            $table->text('token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('token', 255)->nullable()->change();
        });
    }
};
