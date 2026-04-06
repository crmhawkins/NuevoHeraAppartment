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
        Schema::create('controles_limpieza', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id');
            $table->foreignId('apartamento_limpieza_id')->constrained('apartamento_limpieza')->onDelete('cascade');
            $table->foreignId('item_checklist_id');
            $table->boolean('estado')->default(false);
            $table->timestamps();
            $table->softDeletes();

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
