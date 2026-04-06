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
        Schema::create('rate_plan_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rate_plan_id'); // Relación con el plan de tarifas
            $table->decimal('rate', 10, 2)->nullable(); // Tarifa
            $table->integer('occupancy')->nullable(); // Ocupación
            $table->boolean('is_primary')->default(false); // Si es la opción primaria
            $table->boolean('inherit_rate')->default(false); // Si hereda tarifas
            $table->timestamps();

            $table->foreign('rate_plan_id')->references('id')->on('rate_plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_plan_options');
    }
};
