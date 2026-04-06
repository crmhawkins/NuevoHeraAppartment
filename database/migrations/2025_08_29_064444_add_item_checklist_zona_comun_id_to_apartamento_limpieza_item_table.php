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
        Schema::table('apartamento_limpieza_items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_checklist_zona_comun_id')->nullable()->after('item_id');
            $table->unsignedBigInteger('checklist_zona_comun_id')->nullable()->after('checklist_id');
            
            $table->foreign('item_checklist_zona_comun_id')->references('id')->on('item_checklist_zona_comuns')->onDelete('set null');
            $table->foreign('checklist_zona_comun_id')->references('id')->on('checklist_zona_comuns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_limpieza_items', function (Blueprint $table) {
            $table->dropForeign(['item_checklist_zona_comun_id']);
            $table->dropForeign(['checklist_zona_comun_id']);
            $table->dropColumn(['item_checklist_zona_comun_id', 'checklist_zona_comun_id']);
        });
    }
};
