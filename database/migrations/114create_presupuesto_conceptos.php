<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('presupuesto_conceptos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('presupuesto_id')->constrained('presupuestos')->onDelete('cascade');
        $table->string('concepto');
        $table->decimal('precio', 10, 2);
        $table->decimal('iva', 10, 2);
        $table->decimal('subtotal', 10, 2);
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_conceptos');
    }
};
