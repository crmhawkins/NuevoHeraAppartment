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
        Schema::create('ari_updates', function (Blueprint $table) {
            $table->id();
            $table->string('property_id');
            $table->string('rate_plan_id')->nullable();
            $table->string('room_type_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Para rangos de fechas
            $table->json('details'); // Para almacenar restricciones o tarifas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ari_updates');
    }
};
