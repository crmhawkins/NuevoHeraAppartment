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
        if (!Schema::hasTable('reservas')) return; // [2026-04-30] idempotente
        Schema::table('reservas', function (Blueprint $table) {
            $table->text('id_channex')->nullable()->after('room_type_id');
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
