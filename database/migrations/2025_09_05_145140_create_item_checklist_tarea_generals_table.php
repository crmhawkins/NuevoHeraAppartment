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
        Schema::create('item_checklist_tarea_generals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('checklist_tarea_generals')->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria')->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->boolean('tiene_stock')->default(false);
            $table->foreignId('articulo_id')->nullable()->constrained('articulos')->onDelete('set null');
            $table->decimal('cantidad_requerida', 8, 2)->nullable();
            $table->boolean('tiene_averias')->default(false);
            $table->text('observaciones_stock')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_checklist_tarea_generals');
    }
};
