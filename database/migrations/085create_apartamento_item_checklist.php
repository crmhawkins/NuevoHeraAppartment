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
        Schema::create('apartamento_item_checklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_limpieza_id')->constrained('apartamento_limpieza')->onDelete('cascade');
            $table->foreignId('item_checklist_id')->constrained('items_checklists')->onDelete('cascade');
            $table->boolean('status')->default(false); // Esto almacenará si el ítem fue marcado o no
            $table->timestamps();
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
