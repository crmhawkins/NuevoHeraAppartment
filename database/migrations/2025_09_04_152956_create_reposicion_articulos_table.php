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
        Schema::create('reposicion_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_limpieza_id')->constrained('apartamento_limpieza')->onDelete('cascade');
            $table->foreignId('item_checklist_id')->constrained('items_checklists')->onDelete('cascade');
            $table->foreignId('articulo_id')->constrained('articulos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('cantidad_reponer', 8, 2);
            $table->decimal('cantidad_anterior', 8, 2)->default(0);
            $table->decimal('cantidad_nueva', 8, 2);
            $table->enum('tipo_descuento', ['reposicion', 'consumo']);
            $table->boolean('stock_descontado')->default(false);
            $table->decimal('stock_anterior', 8, 2)->nullable();
            $table->decimal('stock_nuevo', 8, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index(['apartamento_limpieza_id', 'item_checklist_id'], 'reposicion_apto_item_idx');
            $table->index(['articulo_id', 'created_at'], 'reposicion_articulo_fecha_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reposicion_articulos');
    }
};