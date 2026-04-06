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
        Schema::create('tecnico_servicio_precio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tecnico_id')->constrained('reparacion')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios_tecnicos')->onDelete('cascade');
            $table->decimal('precio', 12, 2);
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['tecnico_id', 'servicio_id']);
            $table->index('tecnico_id');
            $table->index('servicio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tecnico_servicio_precio');
    }
};
