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
        if (!Schema::hasTable('items_checklists')) return; // [2026-04-30] idempotente
        Schema::table('items_checklists', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('checklist_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items_checklists', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
