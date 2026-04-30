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
        if (!Schema::hasTable('photos')) return; // [2026-04-30] idempotente
        Schema::table('photos', function (Blueprint $table) {
            
            $table->unsignedBigInteger('requirement_id')->nullable();
           
            $table->foreign('requirement_id')->references('id')->on('checklists');
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
