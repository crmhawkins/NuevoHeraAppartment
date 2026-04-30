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
        if (!Schema::hasTable('huespedes')) return; // [2026-04-30] idempotente
        Schema::table('huespedes', function (Blueprint $table) {
            $table->tinyInteger('webpol')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropColumn('webpol');
        });
    }
};
