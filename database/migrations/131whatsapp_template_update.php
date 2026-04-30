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
        if (!Schema::hasTable('whatsapp_templates')) return; // [2026-04-30] idempotente
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->string('template_id')->unique()->nullable(); // ← si aún no lo tienes
            $table->string('parameter_format')->nullable();     // ← nuevo campo

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
