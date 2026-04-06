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
        Schema::table('items_checklists', function (Blueprint $table) {
            $table->boolean('tiene_stock')->default(false)->after('checklist_id');
            $table->foreignId('articulo_id')->nullable()->constrained('articulos')->onDelete('set null')->after('tiene_stock');
            $table->decimal('cantidad_requerida', 8, 2)->default(1)->after('articulo_id');
            $table->boolean('tiene_averias')->default(false)->after('cantidad_requerida');
            $table->text('observaciones_stock')->nullable()->after('tiene_averias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items_checklists', function (Blueprint $table) {
            $table->dropForeign(['articulo_id']);
            $table->dropColumn([
                'tiene_stock',
                'articulo_id',
                'cantidad_requerida',
                'tiene_averias',
                'observaciones_stock'
            ]);
        });
    }
};
